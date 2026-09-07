<?php

namespace App\Http\Controllers;

use App\Models\Crime;
use App\Models\CrimeType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private function monthExpression(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', date_occurred)"
            : "DATE_FORMAT(date_occurred, '%Y-%m')";
    }

    public function index(): View
    {
        $totalCrimes = Crime::count();
        $crimesToday = Crime::today()->count();
        $crimesThisMonth = Crime::thisMonth()->count();
        $pendingCrimes = Crime::pending()->count();
        $taskIndicators = [
            'unassigned' => Crime::whereNull('assigned_officer')->whereIn('status', Crime::OPEN_STATUSES)->count(),
            'pending_review' => Crime::where('status', Crime::STATUS_FOR_REVIEW)->count(),
            'for_correction' => Crime::where('status', Crime::STATUS_FOR_CORRECTION)->count(),
            'without_coordinates' => Crime::where(function ($query) {
                $query->whereNull('latitude')->orWhereNull('longitude');
            })->whereIn('status', Crime::OPEN_STATUSES)->count(),
        ];
        $assignedInvestigations = collect();
        $assignedInvestigationCount = 0;
        $user = Auth::user();

        if ($user?->isInvestigator()) {
            $assignedInvestigations = Crime::with(['crimeType', 'barangay'])
                ->where('assigned_officer', $user->id)
                ->whereIn('status', Crime::OPEN_STATUSES)
                ->latest()
                ->take(5)
                ->get();

            $assignedInvestigationCount = Crime::where('assigned_officer', $user->id)
                ->whereIn('status', Crime::OPEN_STATUSES)
                ->count();
        }

        // Crime by type for chart
        $crimeByType = CrimeType::query()
            ->where('is_active', true)
            ->withCount('crimes')
            ->orderBy('crimes_count', 'desc')
            ->get()
            ->map(function ($type) {
                return [
                    'name' => $type->name,
                    'count' => $type->crimes_count,
                    'color' => $type->color,
                ];
            });

        // Crime by barangay for hotspot summary
        $hotspots = Crime::select('barangay_id', DB::raw('count(*) as total'))
            ->with('barangay')
            ->groupBy('barangay_id')
            ->orderBy('total', 'desc')
            ->take(5)
            ->get()
            ->map(function ($crime) {
                return [
                    'barangay' => $crime->barangay->name ?? 'Unknown',
                    'count' => $crime->total,
                ];
            });

        // Monthly crime trend (last 6 months)
        $monthlyCrimes = Crime::select(
            DB::raw($this->monthExpression() . ' as month'),
            DB::raw('count(*) as total')
        )
            ->where('date_occurred', '>=', Carbon::now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Status breakdown
        $statusBreakdown = Crime::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => Crime::STATUS_LABELS[$item->status] ?? ucfirst(str_replace('_', ' ', $item->status)),
                    'count' => $item->total,
                ];
            });
        $statusColors = collect(Crime::STATUS_COLORS)
            ->mapWithKeys(fn ($color, $status) => [Crime::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status)) => $color]);
        $statusChartColors = $statusBreakdown
            ->pluck('status')
            ->map(fn ($status) => $statusColors[$status] ?? '#6b7280');

        return view('dashboard.index', compact(
            'totalCrimes',
            'crimesToday',
            'crimesThisMonth',
            'pendingCrimes',
            'crimeByType',
            'hotspots',
            'monthlyCrimes',
            'statusBreakdown',
            'statusChartColors',
            'assignedInvestigations',
            'assignedInvestigationCount',
            'taskIndicators'
        ));
    }

    public function apiData(): \Illuminate\Http\JsonResponse
    {
        $crimeByType = CrimeType::query()
            ->where('is_active', true)
            ->withCount('crimes')
            ->get();
        $monthlyCrimes = Crime::select(
            DB::raw($this->monthExpression() . ' as month'),
            DB::raw('count(*) as total')
        )
            ->where('date_occurred', '>=', Carbon::now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'crimeByType' => $crimeByType,
            'monthlyCrimes' => $monthlyCrimes,
        ]);
    }
}

