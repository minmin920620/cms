<?php

namespace App\Data;

use Illuminate\Support\Facades\File;

/**
 * Representative points guaranteed to fall inside Koronadal barangay GIS polygons.
 * The GeoJSON uses PSGC codes; those codes keep the GIS names independent from
 * spelling and encoding differences in the barangays table.
 */
class BarangayMapPoints
{
    private const PSGC_NAMES = [
        '126306001' => 'Brgy. Assumption',
        '126306002' => "Brgy. Avance\u{00C3}\u{00B1}a",
        '126306003' => 'Brgy. Cacub',
        '126306004' => 'Brgy. Caloocan',
        '126306005' => 'Brgy. Carpenter Hill',
        '126306006' => 'Brgy. Concepcion',
        '126306007' => 'Brgy. Esperanza',
        '126306008' => 'Brgy. General Paulino Santos',
        '126306009' => 'Brgy. Mabini',
        '126306010' => 'Brgy. Magsaysay',
        '126306011' => 'Brgy. Mambucal',
        '126306012' => 'Brgy. Morales',
        '126306013' => 'Brgy. Namnama',
        '126306014' => 'Brgy. New Pangasinan',
        '126306015' => 'Brgy. Paraiso',
        '126306016' => 'Brgy. Zone 1',
        '126306017' => 'Brgy. Zone 2',
        '126306018' => 'Brgy. Zone 3',
        '126306019' => 'Brgy. Zone 4',
        '126306020' => 'Brgy. Rotonda',
        '126306021' => 'Brgy. San Isidro',
        '126306022' => 'Brgy. San Jose',
        '126306023' => 'Brgy. San Roque',
        '126306024' => 'Brgy. Sta. Cruz',
        '126306025' => "Brgy. Sto. Ni\u{00C3}\u{00B1}o",
        '126306026' => 'Brgy. Saravia',
        '126306027' => 'Brgy. Topland',
    ];

    /**
     * @return array<string, array{lat: float, lng: float}>
     */
    public static function all(): array
    {
        $path = base_path('gis/koronadal-barangays.geojson');

        if (! File::exists($path)) {
            $path = public_path('gis/koronadal-barangays.geojson');
        }

        if (! File::exists($path)) {
            return [];
        }

        $geojson = json_decode(File::get($path), true);
        $points = [];

        foreach ($geojson['features'] ?? [] as $feature) {
            $code = (string) ($feature['properties']['brgy_code'] ?? '');
            $name = self::PSGC_NAMES[$code] ?? null;
            $geometry = $feature['geometry'] ?? [];

            if (! $name || ! in_array($geometry['type'] ?? null, ['Polygon', 'MultiPolygon'], true)) {
                continue;
            }

            $polygons = $geometry['type'] === 'Polygon'
                ? [$geometry['coordinates']]
                : $geometry['coordinates'];

            foreach ($polygons as $polygon) {
                $ring = $polygon[0] ?? [];
                $point = self::interiorPoint($ring);

                if ($point) {
                    $points[$name] = ['lat' => $point[1], 'lng' => $point[0]];
                    break;
                }
            }
        }

        return $points;
    }

    /** @return array{0: float, 1: float}|null */
    private static function interiorPoint(array $ring): ?array
    {
        if (count($ring) < 4) {
            return null;
        }

        $twiceArea = 0.0;
        $centroidX = 0.0;
        $centroidY = 0.0;
        $minX = $maxX = (float) $ring[0][0];
        $minY = $maxY = (float) $ring[0][1];
        $count = count($ring);

        for ($i = 0; $i < $count - 1; $i++) {
            $x1 = (float) $ring[$i][0];
            $y1 = (float) $ring[$i][1];
            $x2 = (float) $ring[$i + 1][0];
            $y2 = (float) $ring[$i + 1][1];
            $cross = ($x1 * $y2) - ($x2 * $y1);

            $twiceArea += $cross;
            $centroidX += ($x1 + $x2) * $cross;
            $centroidY += ($y1 + $y2) * $cross;
            $minX = min($minX, $x1);
            $maxX = max($maxX, $x1);
            $minY = min($minY, $y1);
            $maxY = max($maxY, $y1);
        }

        if (abs($twiceArea) > 1.0e-12) {
            $centroid = [$centroidX / (3 * $twiceArea), $centroidY / (3 * $twiceArea)];

            if (self::contains($centroid, $ring)) {
                return $centroid;
            }
        }

        // Some irregular polygons have an area centroid outside the boundary.
        // Find an interior grid point nearest the polygon's bounding-box center.
        $target = [($minX + $maxX) / 2, ($minY + $maxY) / 2];
        $best = null;
        $bestDistance = INF;

        for ($steps = 8; $steps <= 64; $steps += 8) {
            for ($xStep = 1; $xStep < $steps; $xStep++) {
                for ($yStep = 1; $yStep < $steps; $yStep++) {
                    $candidate = [
                        $minX + ($xStep / $steps) * ($maxX - $minX),
                        $minY + ($yStep / $steps) * ($maxY - $minY),
                    ];

                    if (! self::contains($candidate, $ring)) {
                        continue;
                    }

                    $distance = (($candidate[0] - $target[0]) ** 2) + (($candidate[1] - $target[1]) ** 2);
                    if ($distance < $bestDistance) {
                        $best = $candidate;
                        $bestDistance = $distance;
                    }
                }
            }

            if ($best) {
                return $best;
            }
        }

        return null;
    }

    private static function contains(array $point, array $ring): bool
    {
        [$x, $y] = $point;
        $inside = false;
        $j = count($ring) - 1;

        for ($i = 0; $i < count($ring); $i++) {
            $xi = (float) $ring[$i][0];
            $yi = (float) $ring[$i][1];
            $xj = (float) $ring[$j][0];
            $yj = (float) $ring[$j][1];

            if ((($yi > $y) !== ($yj > $y))
                && ($x < (($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1.0e-20)) + $xi)) {
                $inside = ! $inside;
            }

            $j = $i;
        }

        return $inside;
    }
}
