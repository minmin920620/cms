<?php

namespace App\Http\Controllers;

use App\Data\BarangayBoundaries;
use App\Models\Barangay;
use App\Models\Crime;
use App\Models\CrimeType;
use App\Models\OffenseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CrimeMapController extends Controller
{
    private const KORONADAL_BOUNDS = [
        'south' => 6.351757,
        'north' => 6.569806,
        'west' => 124.788841,
        'east' => 125.000435,
    ];

    public function index(): View
    {
        $crimeTypes = CrimeType::crimeTypes();
        $offenseTypes = OffenseType::offenseChoices();
        $barangays = Barangay::where('city', 'Koronadal City')->get();
        $statuses = Crime::STATUSES;

        return view('map.index', compact('crimeTypes', 'offenseTypes', 'barangays', 'statuses'));
    }

    public function mapData(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = Crime::with(['crimeType', 'offenseType', 'barangay'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [self::KORONADAL_BOUNDS['south'], self::KORONADAL_BOUNDS['north']])
            ->whereBetween('longitude', [self::KORONADAL_BOUNDS['west'], self::KORONADAL_BOUNDS['east']])
            ->whereHas('barangay', fn ($q) => $q->where('city', 'Koronadal City'));

        if ($request->filled('crime_type_id')) {
            $query->byType($request->crime_type_id);
        }
        if ($request->filled('barangay_id')) {
            $query->byBarangay($request->barangay_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date_occurred', '>=', $request->date_from);
        }
	        if ($request->filled('date_to')) {
	            $query->whereDate('date_occurred', '<=', $request->date_to);
	        }

	        $this->applyRoleVisibility($query);

	        $crimes = $query->orderBy('date_occurred', 'desc')->get();

        $features = $crimes->map(function ($crime) {
            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $crime->longitude, (float) $crime->latitude],
                ],
                'properties' => [
                    'id' => $crime->id,
                    'case_number' => $crime->case_number,
                    'title' => $crime->title,
                    'crime_type' => $crime->crimeType->name ?? 'Unknown',
                    'crime_type_color' => $crime->crimeType->color ?? '#ef4444',
                    'barangay' => $crime->barangay->name ?? 'Unknown',
                    'status' => $crime->status,
                    'date_reported' => $crime->date_reported?->format('Y-m-d'),
                    'date_occurred' => $crime->date_occurred->format('Y-m-d'),
                    'stage_of_felony' => $crime->stage_of_felony,
                    'offense' => $crime->offenseType?->name,
                    'address' => $crime->address,
                    'url' => route('crimes.show', $crime),
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    /**
     * Return GIS barangay boundaries with crime statistics.
     *
     * Priority:
     * 1. GIS GeoJSON file on disk (gis/koronadal-barangays.geojson)
     * 2. Predefined polygon boundaries from BarangayBoundaries data class
     * 3. Convex hull generated from crime locations in that barangay
     * 4. Hexagon fallback (original behavior) as last resort
     */
	    public function barangayBoundaries(Request $request): \Illuminate\Http\JsonResponse
	    {
	        $visibleCrimeIds = $this->visibleCrimeIdsForCurrentUser($request);
	        $barangays = Barangay::withCount([
	            'crimes as total_crimes' => fn ($q) => $this->applyVisibleCrimeIds($q, $visibleCrimeIds),
	            'crimes as pending_crimes' => fn ($q) => $this->applyVisibleCrimeIds($q->whereIn('status', Crime::OPEN_STATUSES), $visibleCrimeIds),
	            'crimes as resolved_crimes' => fn ($q) => $this->applyVisibleCrimeIds($q->whereIn('status', Crime::COMPLETED_STATUSES), $visibleCrimeIds),
	        ])
            ->where('city', 'Koronadal City')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

	        if ($request->filled('barangay_id')) {
	            $barangays->whereKey($request->integer('barangay_id'));
	        }

	        $barangays = $barangays->get();

        // --- PRIORITY 1: GIS GeoJSON file ---
        $gisPath = $this->barangayGeoJsonPath();
        if (File::exists($gisPath)) {
            $geojson = json_decode(File::get($gisPath), true);

            if (($geojson['type'] ?? null) === 'FeatureCollection' && isset($geojson['features'])) {
                $barangayLookup = $barangays->keyBy(fn ($b) => $this->normalizeBarangayName($b->name));
	                $coordinateCrimes = Crime::whereNotNull('latitude')
	                    ->whereNotNull('longitude')
	                    ->whereBetween('latitude', [self::KORONADAL_BOUNDS['south'], self::KORONADAL_BOUNDS['north']])
	                    ->whereBetween('longitude', [self::KORONADAL_BOUNDS['west'], self::KORONADAL_BOUNDS['east']]);
	                $this->applyVisibleCrimeIds($coordinateCrimes, $visibleCrimeIds);
	                $coordinateCrimes = $coordinateCrimes->get(['status', 'latitude', 'longitude']);
                $crimeCountsByBarangay = [];

                foreach ($coordinateCrimes as $crime) {
                    $match = $this->detectBarangayByCoordinates((float) $crime->latitude, (float) $crime->longitude);
                    $barangayId = $match['id'] ?? null;

                    if (! $barangayId) {
                        continue;
                    }

                    $crimeCountsByBarangay[$barangayId] ??= [
                        'total' => 0,
                        'pending' => 0,
                        'resolved' => 0,
                    ];

                    $crimeCountsByBarangay[$barangayId]['total']++;

                    if (in_array($crime->status, Crime::OPEN_STATUSES, true)) {
                        $crimeCountsByBarangay[$barangayId]['pending']++;
                    }

                    if (in_array($crime->status, Crime::COMPLETED_STATUSES, true)) {
                        $crimeCountsByBarangay[$barangayId]['resolved']++;
                    }
                }

                $features = collect($geojson['features'])
                    ->map(function ($feature) use ($barangayLookup, $crimeCountsByBarangay) {
                        $properties = $feature['properties'] ?? [];
                        $featureName = $this->extractBarangayName($properties);
                        $barangay = $featureName ? $barangayLookup->get($this->normalizeBarangayName($featureName)) : null;

                        if (! $barangay) {
                            return null;
                        }

                        $counts = $crimeCountsByBarangay[$barangay->id] ?? [
                            'total' => 0,
                            'pending' => 0,
                            'resolved' => 0,
                        ];

                        $feature['properties'] = array_merge($properties, [
                            'id' => $barangay->id,
                            'name' => $barangay->name,
                            'city' => $barangay->city,
                            'total_crimes' => $counts['total'],
                            'pending_crimes' => $counts['pending'],
                            'resolved_crimes' => $counts['resolved'],
                            'center_lat' => (float) $barangay->latitude,
                            'center_lng' => (float) $barangay->longitude,
                            'boundary_source' => 'GIS GeoJSON',
                        ]);

                        return $feature;
                    })
                    ->filter()
                    ->values();

                $maxCrimes = max($features->max(fn ($feature) => $feature['properties']['total_crimes'] ?? 0), 1);

                $geojson['features'] = $features
                    ->map(function ($feature) use ($maxCrimes) {
                        $totalCrimes = $feature['properties']['total_crimes'] ?? 0;
                        $feature['properties']['crime_density'] = round(($totalCrimes / $maxCrimes) * 100, 1);

                        return $feature;
                    })
                    ->all();

                return response()->json($geojson);
            }
        }

        // --- PRIORITY 2: Predefined polygon boundaries ---
	        $boundaryData = BarangayBoundaries::all();
        $barangayLookup = $barangays->keyBy(fn ($b) => $this->normalizeBarangayName($b->name));
        $maxCrimes = max($barangays->max('total_crimes'), 1);

        $features = [];

        foreach ($boundaryData as $slug => $boundary) {
            $barangayName = 'Brgy. ' . $boundary['name'];
            $normalizedName = $this->normalizeBarangayName($barangayName);
            $barangay = $barangayLookup->get($normalizedName);

            if (! $barangay) {
                continue;
            }

            $crimeDensity = $maxCrimes > 0 ? round(($barangay->total_crimes / $maxCrimes) * 100, 1) : 0;

            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [$boundary['coordinates']],
                ],
                'properties' => [
                    'id' => $barangay->id,
                    'name' => $barangay->name,
                    'city' => $barangay->city,
                    'total_crimes' => (int) $barangay->total_crimes,
                    'pending_crimes' => (int) $barangay->pending_crimes,
                    'resolved_crimes' => (int) $barangay->resolved_crimes,
                    'crime_density' => $crimeDensity,
                    'center_lat' => (float) $barangay->latitude,
                    'center_lng' => (float) $barangay->longitude,
                    'boundary_source' => 'Predefined polygon boundary',
                ],
            ];
        }

        // If we matched any barangays with predefined boundaries, return them
        if (count($features) > 0) {
            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $features,
            ]);
        }

        // --- PRIORITY 3: Convex hull from crime locations ---
        $features = [];
        foreach ($barangays as $barangay) {
	            $crimes = Crime::where('barangay_id', $barangay->id)
	                ->whereNotNull('latitude')
	                ->whereNotNull('longitude');
	            $this->applyVisibleCrimeIds($crimes, $visibleCrimeIds);
	            $crimes = $crimes->get(['latitude', 'longitude']);

            if ($crimes->count() >= 3) {
                $points = $crimes->map(fn ($c) => [(float) $c->longitude, (float) $c->latitude])->toArray();
                $hull = $this->convexHull($points);

                if (count($hull) >= 3) {
                    $hull[] = $hull[0]; // Close the ring
                    $crimeDensity = $maxCrimes > 0 ? round(($barangay->total_crimes / $maxCrimes) * 100, 1) : 0;

                    $features[] = [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'Polygon',
                            'coordinates' => [$hull],
                        ],
                        'properties' => [
                            'id' => $barangay->id,
                            'name' => $barangay->name,
                            'city' => $barangay->city,
                            'total_crimes' => (int) $barangay->total_crimes,
                            'pending_crimes' => (int) $barangay->pending_crimes,
                            'resolved_crimes' => (int) $barangay->resolved_crimes,
                            'crime_density' => $crimeDensity,
                            'center_lat' => (float) $barangay->latitude,
                            'center_lng' => (float) $barangay->longitude,
                            'boundary_source' => 'Generated from crime data',
                        ],
                    ];

                    continue;
                }
            }

            // --- PRIORITY 4: Hexagon fallback ---
            $centerLat = (float) $barangay->latitude;
            $centerLng = (float) $barangay->longitude;
            $radius = 0.002;

            $polygon = [];
            $sides = 6;
            for ($i = 0; $i < $sides; $i++) {
                $angle = deg2rad(60 * $i - 30);
                $dlat = $radius * cos($angle);
                $dlng = $radius * sin($angle) / cos(deg2rad($centerLat));
                $polygon[] = [$centerLng + $dlng, $centerLat + $dlat];
            }
            $polygon[] = $polygon[0];

            $crimeDensity = $maxCrimes > 0 ? round(($barangay->total_crimes / $maxCrimes) * 100, 1) : 0;

            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [$polygon],
                ],
                'properties' => [
                    'id' => $barangay->id,
                    'name' => $barangay->name,
                    'city' => $barangay->city,
                    'total_crimes' => (int) $barangay->total_crimes,
                    'pending_crimes' => (int) $barangay->pending_crimes,
                    'resolved_crimes' => (int) $barangay->resolved_crimes,
                    'crime_density' => $crimeDensity,
                    'center_lat' => $centerLat,
                    'center_lng' => $centerLng,
                    'boundary_source' => 'Approximate generated zone',
                ],
            ];
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    /**
     * Detect which barangay a set of coordinates falls within.
     * Uses point-in-polygon ray casting against predefined boundaries.
     *
     * GET /map/detect-barangay?lat=6.498&lng=124.846
     */
    public function detectBarangay(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|min:' . self::KORONADAL_BOUNDS['south'] . '|max:' . self::KORONADAL_BOUNDS['north'],
            'lng' => 'required|numeric|min:' . self::KORONADAL_BOUNDS['west'] . '|max:' . self::KORONADAL_BOUNDS['east'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $lat = (float) $request->lat;
        $lng = (float) $request->lng;
        $barangay = $this->detectBarangayByCoordinates($lat, $lng);

        if ($barangay) {
            return response()->json([
                'success' => true,
                'barangay' => $barangay['name'],
                'slug' => $barangay['slug'],
                'method' => $barangay['method'],
                'id' => $barangay['id'] ?? null,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No barangay detected for the given coordinates.',
        ], 404);
    }

    /**
     * Point-in-polygon detection using ray casting algorithm.
     * Returns the barangay info if the point falls within any boundary.
     *
     * A barangay is returned only when the plotted coordinate is inside a
     * verified GIS or polygon boundary. Nearest-center guesses are unsafe for
     * incident reporting because they can assign a neighboring barangay.
     */
    public function detectBarangayByCoordinates(float $lat, float $lng): ?array
    {
        $barangays = Barangay::where('city', 'Koronadal City')->get()
            ->keyBy(fn ($b) => $this->normalizeBarangayName($b->name));

        // --- Priority 1: GIS GeoJSON file ---
        $gisMatch = $this->detectBarangayFromGeoJson($lat, $lng, $barangays);
        if ($gisMatch) {
            return $gisMatch;
        }

        // --- Priority 2: Predefined polygon boundaries ---
        $boundaries = BarangayBoundaries::all();

        foreach ($boundaries as $slug => $boundary) {
            $polygon = $boundary['coordinates'];

            if ($this->pointInPolygon($lng, $lat, $polygon)) {
                $normalizedName = $this->normalizeBarangayName('Brgy. ' . $boundary['name']);
                $barangay = $barangays->get($normalizedName);

                return [
                    'name' => 'Brgy. ' . $boundary['name'],
                    'slug' => $slug,
                    'method' => 'predefined_polygon',
                    'id' => $barangay?->id,
                ];
            }
        }

        // --- Priority 3: Try convex hull from crime data ---
        $crimeBarangays = Crime::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '>=', self::KORONADAL_BOUNDS['south'])
            ->where('latitude', '<=', self::KORONADAL_BOUNDS['north'])
            ->where('longitude', '>=', self::KORONADAL_BOUNDS['west'])
            ->where('longitude', '<=', self::KORONADAL_BOUNDS['east'])
            ->get()
            ->groupBy('barangay_id');

        foreach ($crimeBarangays as $barangayId => $crimes) {
            if ($crimes->count() < 3) {
                continue;
            }

            $points = $crimes->map(fn ($c) => [(float) $c->longitude, (float) $c->latitude])->toArray();
            $hull = $this->convexHull($points);

            if (count($hull) >= 3 && $this->pointInPolygon($lng, $lat, $hull)) {
                $barangay = Barangay::find($barangayId);

                return $barangay ? [
                    'name' => $barangay->name,
                    'slug' => $this->normalizeBarangayName($barangay->name),
                    'method' => 'crime_convex_hull',
                    'id' => $barangay->id,
                ] : null;
            }
        }

        // Do not guess a barangay from its center point. The caller will ask
        // the user to select it manually when no boundary contains the pin.
        return null;
    }

    private function detectBarangayFromGeoJson(float $lat, float $lng, $barangays): ?array
    {
        $gisPath = $this->barangayGeoJsonPath();
        if (! File::exists($gisPath)) {
            return null;
        }

        $geojson = json_decode(File::get($gisPath), true);
        if (($geojson['type'] ?? null) !== 'FeatureCollection' || empty($geojson['features'])) {
            return null;
        }

        $matches = [];

        foreach ($geojson['features'] as $feature) {
            $geometry = $feature['geometry'] ?? [];
            $properties = $feature['properties'] ?? [];
            $featureName = $this->extractBarangayName($properties);

            if (! $featureName || ! $this->pointInGeometry($lng, $lat, $geometry)) {
                continue;
            }

            $barangay = $barangays->get($this->normalizeBarangayName($featureName));

            $matches[] = [
                'name' => $barangay?->name ?? 'Brgy. ' . $featureName,
                'slug' => $this->normalizeBarangayName($featureName),
                'method' => 'gis_geojson',
                'id' => $barangay?->id,
                'distance_km' => $barangay
                    ? $this->haversineDistance($lat, $lng, (float) $barangay->latitude, (float) $barangay->longitude)
                    : PHP_FLOAT_MAX,
            ];
        }

        if (empty($matches)) {
            return null;
        }

        usort($matches, fn ($a, $b) => $a['distance_km'] <=> $b['distance_km']);

        return $matches[0];
    }

    /**
     * Resolve the canonical Koronadal boundary dataset.
     *
     * The application source is outside Hostinger's web root, so the GIS file
     * belongs in the repository-level gis directory rather than public_html.
     */
    private function barangayGeoJsonPath(): string
    {
        $sourcePath = base_path('gis/koronadal-barangays.geojson');

        return File::exists($sourcePath)
            ? $sourcePath
            : public_path('gis/koronadal-barangays.geojson');
    }

    private function pointInGeometry(float $lng, float $lat, array $geometry): bool
    {
        $type = $geometry['type'] ?? null;
        $coordinates = $geometry['coordinates'] ?? [];

        if ($type === 'Polygon') {
            return $this->pointInPolygonWithHoles($lng, $lat, $coordinates);
        }

        if ($type === 'MultiPolygon') {
            foreach ($coordinates as $polygon) {
                if ($this->pointInPolygonWithHoles($lng, $lat, $polygon)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function pointInPolygonWithHoles(float $lng, float $lat, array $rings): bool
    {
        if (empty($rings[0]) || ! $this->pointInPolygon($lng, $lat, $rings[0])) {
            return false;
        }

        foreach (array_slice($rings, 1) as $hole) {
            if ($this->pointInPolygon($lng, $lat, $hole)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ray casting algorithm for point-in-polygon detection.
     * Returns true if the point (x, y) is inside the polygon.
     *
     * @param float $x Longitude
     * @param float $y Latitude
     * @param array $polygon Array of [lng, lat] pairs
     */
    private function pointInPolygon(float $x, float $y, array $polygon): bool
    {
        $n = count($polygon);
        $inside = false;

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            $intersect = (($yi > $y) !== ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Find the nearest barangay by center-point distance.
     */
    private function findNearestBarangay(float $lat, float $lng, ?float $maxDistanceKm = null): ?array
    {
        $barangays = Barangay::where('city', 'Koronadal City')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($barangays as $barangay) {
            $distance = $this->haversineDistance(
                $lat, $lng,
                (float) $barangay->latitude,
                (float) $barangay->longitude
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $barangay;
            }
        }

        if ($nearest) {
            if ($maxDistanceKm !== null && $minDistance > $maxDistanceKm) {
                return null;
            }

            return [
                'name' => $nearest->name,
                'slug' => $this->normalizeBarangayName($nearest->name),
                'method' => 'nearest_distance',
                'distance_km' => round($minDistance, 4),
                'id' => $nearest->id,
            ];
        }

        return null;
    }

    /**
     * Haversine distance formula for calculating distance between two
     * geographic coordinates in kilometers.
     */
    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Generate a convex hull polygon from crime location points.
     * Uses the Graham scan algorithm.
     *
     * @param array $points Array of [lng, lat] pairs
     * @return array Array of [lng, lat] pairs forming the convex hull
     */
    private function generateBoundaryFromCrimes(array $points): array
    {
        return $this->convexHull($points);
    }

    /**
     * Convex hull implementation using the Graham scan algorithm.
     *
     * @param array $points Array of [lng, lat] pairs
     * @return array Array of [lng, lat] pairs on the convex hull
     */
    private function convexHull(array $points): array
    {
        $n = count($points);

        if ($n < 3) {
            return $points;
        }

        // Find the point with the lowest y (latitude) — if tie, lowest x (longitude)
        $lowest = 0;
        for ($i = 1; $i < $n; $i++) {
            if ($points[$i][1] < $points[$lowest][1]
                || ($points[$i][1] === $points[$lowest][1]
                    && $points[$i][0] < $points[$lowest][0])) {
                $lowest = $i;
            }
        }

        // Swap lowest to index 0
        [$points[0], $points[$lowest]] = [$points[$lowest], $points[0]];

        // Sort by polar angle with respect to points[0]
        $pivot = $points[0];
        $rest = array_slice($points, 1);

        usort($rest, function ($a, $b) use ($pivot) {
            $angleA = atan2($a[1] - $pivot[1], $a[0] - $pivot[0]);
            $angleB = atan2($b[1] - $pivot[1], $b[0] - $pivot[0]);

            if ($angleA !== $angleB) {
                return $angleA <=> $angleB;
            }

            // Same angle — closer point first
            $distA = ($a[0] - $pivot[0]) ** 2 + ($a[1] - $pivot[1]) ** 2;
            $distB = ($b[0] - $pivot[0]) ** 2 + ($b[1] - $pivot[1]) ** 2;

            return $distA <=> $distB;
        });

        $points = array_merge([$pivot], $rest);

        // Build the hull
        $hull = [];
        foreach ($points as $point) {
            while (count($hull) >= 2 && $this->crossProduct(
                $hull[count($hull) - 2],
                $hull[count($hull) - 1],
                $point
            ) <= 0) {
                array_pop($hull);
            }
            $hull[] = $point;
        }

        return $hull;
    }

    /**
     * Cross product of vectors OA and OB.
     * Returns > 0 if counter-clockwise, < 0 if clockwise, = 0 if collinear.
     */
    private function crossProduct(array $o, array $a, array $b): float
    {
        return ($a[0] - $o[0]) * ($b[1] - $o[1])
            - ($a[1] - $o[1]) * ($b[0] - $o[0]);
    }

    /**
     * Quick plot an incident from the map — AJAX endpoint.
     */
    public function plotIncident(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'crime_type_id' => [
                'required',
                Rule::exists('crime_types', 'id')->where('is_active', true),
            ],
            'barangay_id' => 'required|exists:barangays,id',
            'date_occurred' => 'required|date',
            'time_occurred' => 'nullable|date_format:H:i',
            'stage_of_felony' => 'nullable|in:consummated,frustrated,attempted',
            'offense_type_id' => [
                'required',
                Rule::exists('offense_types', 'id')->where('is_active', true),
            ],
            'latitude' => 'required|numeric|min:' . self::KORONADAL_BOUNDS['south'] . '|max:' . self::KORONADAL_BOUNDS['north'],
            'longitude' => 'required|numeric|min:' . self::KORONADAL_BOUNDS['west'] . '|max:' . self::KORONADAL_BOUNDS['east'],
            'address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['reported_by'] = Auth::id();
        $validated['case_number'] = Crime::generateCaseNumber();
        $validated['status'] = Crime::STATUS_PENDING;
        $validated['date_reported'] = now()->toDateString();
        $validated['time_reported'] = now()->format('H:i');
        $detectedBarangay = $this->detectBarangayByCoordinates(
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );

        if (! empty($detectedBarangay['id'])) {
            $validated['barangay_id'] = $detectedBarangay['id'];
        }

        $crime = Crime::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Incident plotted successfully.',
            'crime' => [
                'id' => $crime->id,
                'case_number' => $crime->case_number,
                'latitude' => (float) $crime->latitude,
                'longitude' => (float) $crime->longitude,
                'title' => $crime->title,
                'crime_type' => $crime->crimeType?->name ?? 'Unknown',
                'crime_type_color' => $crime->crimeType?->color ?? '#ef4444',
                'status' => $crime->status,
                'barangay' => $crime->barangay?->name ?? 'Unknown',
                'url' => route('crimes.show', $crime),
                'date_occurred' => $crime->date_occurred->format('Y-m-d'),
                'offense' => $crime->offenseType?->name,
                'address' => $crime->address,
            ],
        ]);
    }

    private function extractBarangayName(array $properties): ?string
    {
        foreach ([
            'name',
            'NAME',
            'barangay',
            'BARANGAY',
            'brgy',
            'BRGY',
            'brgy_name',
            'BRGY_NAME',
            'ADM4_EN',
            'ADM4_NAME',
        ] as $key) {
            if (! empty($properties[$key])) {
                return (string) $properties[$key];
            }
        }

        return null;
    }

	    private function normalizeBarangayName(string $name): string
	    {
        $normalized = strtolower($name);
        $normalized = preg_replace('/\([^)]*\)/', '', $normalized);
        $normalized = str_replace(["\xc3\xb1", "\xc3\x91", "\xc3\x83\xc2\xb1", "\xc3\x83\xc2\x91"], 'n', $normalized);
        $normalized = str_replace(['ñ', 'Ñ'], 'n', $normalized);
        $normalized = preg_replace('/\bbarangay\b|\bbrgy\.?\b/', '', $normalized);
        $normalized = preg_replace('/\bsto\.?\b/', 'santo', $normalized);
        $normalized = preg_replace('/\bsta\.?\b/', 'santa', $normalized);
        $normalized = preg_replace('/\bzone\s*iv\b/', 'zone4', $normalized);
        $normalized = preg_replace('/\bzone\s*iii\b/', 'zone3', $normalized);
        $normalized = preg_replace('/\bzone\s*ii\b/', 'zone2', $normalized);
        $normalized = preg_replace('/\bzone\s*i\b/', 'zone1', $normalized);
        $normalized = preg_replace('/\bzone\s*one\b/', 'zone1', $normalized);
        $normalized = preg_replace('/\bzone\s*two\b/', 'zone2', $normalized);
        $normalized = preg_replace('/\bzone\s*three\b/', 'zone3', $normalized);
        $normalized = preg_replace('/\bzone\s*four\b/', 'zone4', $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', '', $normalized);

        $aliases = [
            'avancea' => 'avancena',
            'avance' => 'avancena',
            'avancenaa' => 'avancena',
            'sarabia' => 'saravia',
            'stonino' => 'santonino',
        ];

	        return $aliases[$normalized] ?? $normalized ?? '';
	    }

	    private function applyRoleVisibility($query): void
	    {
	        $user = Auth::user();

	        if ($user->isPoliceOfficer()) {
	            $query->where(function ($q) use ($user) {
	                $q->where('reported_by', $user->id)
	                    ->orWhere('assigned_officer', $user->id);
	            });
	        } elseif ($user->isInvestigator()) {
	            $query->where('assigned_officer', $user->id);
	        }
	    }

	    private function visibleCrimeIdsForCurrentUser(?Request $request = null): ?array
	    {
	        $user = Auth::user();

	        $query = Crime::query();
	        $this->applyRoleVisibility($query);
	        $this->applyMapFilters($query, $request);

	        return $query->pluck('id')->all();
	    }

	    private function applyMapFilters($query, ?Request $request): void
	    {
	        if (! $request) {
	            return;
	        }

	        if ($request->filled('crime_type_id')) {
	            $query->byType($request->crime_type_id);
	        }

	        if ($request->filled('barangay_id')) {
	            $query->byBarangay($request->barangay_id);
	        }

	        if ($request->filled('status')) {
	            $query->where('status', $request->status);
	        }

	        if ($request->filled('date_from')) {
	            $query->whereDate('date_occurred', '>=', $request->date_from);
	        }

	        if ($request->filled('date_to')) {
	            $query->whereDate('date_occurred', '<=', $request->date_to);
	        }
	    }

	    private function applyVisibleCrimeIds($query, ?array $visibleCrimeIds): void
	    {
	        if ($visibleCrimeIds !== null) {
	            $query->whereIn('crime_incidents.id', $visibleCrimeIds);
	        }
	    }
	}
