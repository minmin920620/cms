<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\Crime;
use Illuminate\View\View;

class HotspotController extends Controller
{
    private const KORONADAL_BOUNDS = [
        'south' => 6.351757,
        'north' => 6.569806,
        'west' => 124.788841,
        'east' => 125.000435,
    ];

    public function index(): View
    {
        $hotspots = Barangay::withCount([
            'crimes as total_incidents',
            'crimes as open_incidents' => fn ($query) => $query->whereIn('status', Crime::OPEN_STATUSES),
            'crimes as resolved_incidents' => fn ($query) => $query->whereIn('status', Crime::COMPLETED_STATUSES),
        ])
            ->with('latestCrime')
            ->where('city', 'Koronadal City')
            ->orderByDesc('total_incidents')
            ->orderBy('name')
            ->get();

        $totalIncidents = $hotspots->sum('total_incidents');
        $barangaysWithIncidents = $hotspots->where('total_incidents', '>', 0)->count();
        $maxIncidents = max((int) $hotspots->max('total_incidents'), 1);

        $mapHotspots = $hotspots
            ->where('total_incidents', '>', 0)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->map(fn ($barangay) => [
                'name' => $barangay->name,
                'lat' => (float) $barangay->latitude,
                'lng' => (float) $barangay->longitude,
                'total' => (int) $barangay->total_incidents,
                'open' => (int) $barangay->open_incidents,
                'resolved' => (int) $barangay->resolved_incidents,
                'density' => round(($barangay->total_incidents / $maxIncidents) * 100, 1),
            ])
            ->values();

        $heatPoints = $mapHotspots
            ->map(fn ($hotspot) => [
                $hotspot['lat'],
                $hotspot['lng'],
                round(0.35 + (($hotspot['density'] / 100) * 0.65), 2),
            ])
            ->values();

        return view('hotspots.index', compact(
            'hotspots',
            'totalIncidents',
            'barangaysWithIncidents',
            'maxIncidents',
            'heatPoints',
            'mapHotspots'
        ));
    }
}
