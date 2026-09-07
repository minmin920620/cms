<?php

require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$htmlPath = __DIR__ . '/../docs/system-diagrams-print.html';
$outputPath = $argv[1] ?? __DIR__ . '/../docs/crime-management-system-diagrams.pdf';

$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', false);

$dompdf = new Dompdf($options);
$dompdf->loadHtml(file_get_contents($htmlPath));
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

file_put_contents($outputPath, $dompdf->output());

echo $outputPath . PHP_EOL;
