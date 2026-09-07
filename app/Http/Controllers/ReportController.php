<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\Crime;
use App\Models\CrimeType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $crimeTypes = CrimeType::crimeTypes();
        $barangays = Barangay::all();
        $statuses = Crime::STATUSES;

        return view('reports.index', compact('crimeTypes', 'barangays', 'statuses'));
    }

    public function generate(Request $request): View
    {
        $crimes = $this->filteredCrimes($request);
        $summary = $this->buildSummary($crimes);

        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;

        return view('reports.preview', compact('crimes', 'summary', 'dateFrom', 'dateTo'));
    }

    public function exportPdf(Request $request)
    {
        $crimes = $this->filteredCrimes($request);
        $summary = $this->buildSummary($crimes);
        $chartImages = $this->buildChartImages($summary);
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        $filename = 'crime-report-' . now()->format('Ymd-His') . '.pdf';

        return Pdf::loadView('reports.pdf', compact('crimes', 'summary', 'chartImages', 'dateFrom', 'dateTo'))
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    private function filteredCrimes(Request $request)
    {
        $query = Crime::with(['crimeType', 'barangay', 'reporter', 'assignedOfficer']);

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

        return $query->orderBy('date_occurred', 'desc')->get();
    }

    private function buildSummary($crimes): array
    {
        $monthly = $crimes
            ->sortBy('date_occurred')
            ->groupBy(fn ($crime) => $crime->date_occurred->format('Y-m'))
            ->map(fn ($items) => [
                'label' => $items->first()->date_occurred->format('M Y'),
                'count' => $items->count(),
            ])
            ->values();

        return [
            'total' => $crimes->count(),
            'by_status' => $crimes->groupBy('status')->map->count()->sortDesc(),
            'by_type' => $crimes->groupBy(fn ($crime) => $crime->crimeType->name ?? 'Unknown')->map->count()->sortDesc(),
            'by_barangay' => $crimes->groupBy(fn ($crime) => $crime->barangay->name ?? 'Unknown')->map->count()->sortDesc(),
            'monthly' => $monthly,
        ];
    }

    private function buildChartImages(array $summary): array
    {
        $colors = ['#dc2626', '#2563eb', '#16a34a', '#f59e0b', '#7c3aed', '#0891b2', '#db2777', '#65a30d', '#ea580c', '#475569', '#0d9488', '#9333ea'];
        $barangayData = $summary['by_barangay']->map(fn ($count) => (int) $count)->all();
        $typeData = $summary['by_type']->map(fn ($count) => (int) $count)->all();
        $monthlyLabels = $summary['monthly']->pluck('label')->map(fn ($label) => explode(' ', $label)[0])->all();
        $monthlyValues = $summary['monthly']->pluck('count')->map(fn ($count) => (int) $count)->all();

        return [
            'barangay' => $this->pieChartImage($barangayData, $colors, false),
            'type' => $this->pieChartImage($typeData, $colors, true),
            'monthlyBar' => $this->barChartImage($monthlyLabels, $monthlyValues, '#dc2626'),
            'monthlyLine' => $this->lineChartImage($monthlyLabels, $monthlyValues, '#2563eb'),
        ];
    }

    private function pieChartImage(array $data, array $colors, bool $doughnut = false): string
    {
        $image = imagecreatetruecolor(300, 190);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        $total = max(array_sum($data), 1);
        $start = 0;

        foreach (array_values($data) as $index => $value) {
            $end = $start + (($value / $total) * 360);
            [$r, $g, $b] = $this->hexToRgb($colors[$index % count($colors)]);
            imagefilledarc($image, 150, 92, 150, 150, (int) round($start), (int) round($end), imagecolorallocate($image, $r, $g, $b), IMG_ARC_PIE);
            $start = $end;
        }

        if ($doughnut) {
            imagefilledellipse($image, 150, 92, 70, 70, $white);
        }

        return $this->imageToDataUri($image);
    }

    private function barChartImage(array $labels, array $values, string $color): string
    {
        $image = imagecreatetruecolor(430, 190);
        $white = imagecolorallocate($image, 255, 255, 255);
        $grid = imagecolorallocate($image, 229, 231, 235);
        $text = imagecolorallocate($image, 100, 116, 139);
        [$r, $g, $b] = $this->hexToRgb($color);
        $barColor = imagecolorallocate($image, $r, $g, $b);
        imagefill($image, 0, 0, $white);

        $max = max(max($values ?: [0]), 1);
        for ($i = 0; $i <= 4; $i++) {
            $y = 25 + ($i * 30);
            imageline($image, 34, $y, 410, $y, $grid);
        }

        $count = max(count($values), 1);
        $slot = 376 / $count;
        $barWidth = min(34, max(14, $slot * 0.55));

        foreach ($values as $index => $value) {
            $height = ($value / $max) * 120;
            $x1 = 40 + ($index * $slot) + (($slot - $barWidth) / 2);
            $y1 = 145 - $height;
            imagefilledrectangle($image, (int) $x1, (int) $y1, (int) ($x1 + $barWidth), 145, $barColor);
            imagestring($image, 2, (int) ($x1 - 1), 155, substr($labels[$index] ?? '', 0, 3), $text);
        }

        return $this->imageToDataUri($image);
    }

    private function lineChartImage(array $labels, array $values, string $color): string
    {
        $image = imagecreatetruecolor(430, 190);
        $white = imagecolorallocate($image, 255, 255, 255);
        $grid = imagecolorallocate($image, 229, 231, 235);
        $fill = imagecolorallocatealpha($image, 219, 234, 254, 35);
        $text = imagecolorallocate($image, 100, 116, 139);
        [$r, $g, $b] = $this->hexToRgb($color);
        $line = imagecolorallocate($image, $r, $g, $b);
        imagefill($image, 0, 0, $white);

        $max = max(max($values ?: [0]), 1);
        for ($i = 0; $i <= 4; $i++) {
            $y = 25 + ($i * 30);
            imageline($image, 34, $y, 410, $y, $grid);
        }

        $points = [];
        $lastIndex = max(count($values) - 1, 1);
        foreach ($values as $index => $value) {
            $x = 40 + (($index / $lastIndex) * 360);
            $y = 145 - (($value / $max) * 120);
            $points[] = [(int) round($x), (int) round($y)];
            imagestring($image, 2, (int) $x - 8, 155, substr($labels[$index] ?? '', 0, 3), $text);
        }

        if (count($points) > 1) {
            $polygon = [40, 145];
            foreach ($points as $point) {
                array_push($polygon, $point[0], $point[1]);
            }
            array_push($polygon, 400, 145);
            imagefilledpolygon($image, $polygon, count($polygon) / 2, $fill);

            foreach ($points as $index => $point) {
                if (! isset($points[$index + 1])) {
                    continue;
                }
                imageline($image, $point[0], $point[1], $points[$index + 1][0], $points[$index + 1][1], $line);
            }
        }

        foreach ($points as $point) {
            imagefilledellipse($image, $point[0], $point[1], 8, 8, $white);
            imageellipse($image, $point[0], $point[1], 8, 8, $line);
        }

        return $this->imageToDataUri($image);
    }

    private function imageToDataUri($image): string
    {
        ob_start();
        imagepng($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,' . base64_encode($contents);
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}

