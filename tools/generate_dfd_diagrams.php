<?php

$outDir = __DIR__ . '/../docs/dfd-images';
if (! is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

function canvas(string $title): GdImage
{
    $im = imagecreatetruecolor(1600, 1050);
    $white = imagecolorallocate($im, 255, 255, 255);
    $black = imagecolorallocate($im, 0, 0, 0);
    imagefill($im, 0, 0, $white);
    imagestring($im, 5, 700, 18, $title, $black);
    return $im;
}

function color(GdImage $im): array
{
    return [
        'black' => imagecolorallocate($im, 0, 0, 0),
        'white' => imagecolorallocate($im, 255, 255, 255),
    ];
}

function textCenter(GdImage $im, string $text, int $x, int $y, int $w, int $font = 4): void
{
    $black = imagecolorallocate($im, 0, 0, 0);
    $tw = imagefontwidth($font) * strlen($text);
    imagestring($im, $font, $x + (int)(($w - $tw) / 2), $y, $text, $black);
}

function entity(GdImage $im, int $x, int $y, int $w, int $h, string $label): void
{
    $c = color($im);
    imagerectangle($im, $x, $y, $x + $w, $y + $h, $c['black']);
    textCenter($im, $label, $x, $y + (int)($h / 2) - 7, $w, 5);
}

function processBox(GdImage $im, int $x, int $y, int $w, int $h, string $num, string $label): void
{
    $c = color($im);
    imagerectangle($im, $x, $y, $x + $w, $y + $h, $c['black']);
    imageline($im, $x, $y + 28, $x + $w, $y + 28, $c['black']);
    textCenter($im, $num, $x, $y + 7, $w, 4);
    textCenter($im, $label, $x, $y + 44, $w, 5);
}

function store(GdImage $im, int $x, int $y, int $w, int $h, string $id, string $label): void
{
    $c = color($im);
    imagerectangle($im, $x, $y, $x + $w, $y + $h, $c['black']);
    imageline($im, $x + 55, $y, $x + 55, $y + $h, $c['black']);
    textCenter($im, $id, $x, $y + 18, 55, 4);
    textCenter($im, $label, $x + 55, $y + 18, $w - 55, 4);
}

function arrow(GdImage $im, int $x1, int $y1, int $x2, int $y2, string $label = ''): void
{
    $black = imagecolorallocate($im, 0, 0, 0);
    imageline($im, $x1, $y1, $x2, $y2, $black);
    $angle = atan2($y2 - $y1, $x2 - $x1);
    $len = 13;
    $a1 = $angle + pi() * 0.82;
    $a2 = $angle - pi() * 0.82;
    imageline($im, $x2, $y2, (int)($x2 + $len * cos($a1)), (int)($y2 + $len * sin($a1)), $black);
    imageline($im, $x2, $y2, (int)($x2 + $len * cos($a2)), (int)($y2 + $len * sin($a2)), $black);
    if ($label !== '') {
        imagestring($im, 3, (int)(($x1 + $x2) / 2) - 50, (int)(($y1 + $y2) / 2) - 16, $label, $black);
    }
}

function savePng(GdImage $im, string $path): void
{
    imagepng($im, $path);
    imagedestroy($im);
}

function roleDfd(string $title, string $user, array $processes, array $stores, string $path): void
{
    $im = canvas($title);
    entity($im, 55, 440, 230, 120, $user);

    foreach ($processes as $i => $process) {
        processBox($im, 620, 70 + ($i * 140), 430, 85, $process[0], $process[1]);
        arrow($im, 285, 475 + ($i * 8), 620, 112 + ($i * 140), $process[2]);
    }

    foreach ($stores as $i => $store) {
        store($im, 1235, 80 + ($i * 145), 305, 70, $store[0], $store[1]);
    }

    foreach ($processes as $i => $process) {
        $storeIndex = min($process[3], count($stores) - 1);
        arrow(
            $im,
            1050,
            112 + ($i * 140),
            1235,
            115 + ($storeIndex * 145),
            $process[4]
        );
    }

    savePng($im, $path);
}

$im = canvas('Context Diagram');
entity($im, 50, 70, 220, 120, 'POLICE OFFICER');
entity($im, 50, 850, 220, 120, 'INVESTIGATOR');
entity($im, 1330, 70, 220, 120, 'LGU');
entity($im, 1330, 850, 220, 120, 'ADMIN');
entity($im, 610, 65, 180, 95, 'CSV FILE');
entity($im, 825, 65, 180, 95, 'EVIDENCE FILE');
processBox($im, 600, 435, 420, 170, '0', 'Crime Mapping and Management System');
arrow($im, 270, 115, 600, 455, 'Incident details');
arrow($im, 270, 150, 600, 495, 'Map plot request');
arrow($im, 600, 530, 270, 180, 'Case list/status');
arrow($im, 270, 890, 600, 575, 'Findings/status');
arrow($im, 600, 565, 270, 930, 'Assigned cases');
arrow($im, 1330, 115, 1020, 455, 'Analytics request');
arrow($im, 1020, 500, 1330, 155, 'Summary report');
arrow($im, 1330, 890, 1020, 555, 'Admin requests');
arrow($im, 1020, 575, 1330, 935, 'Reports/backups');
arrow($im, 700, 160, 720, 435, 'Import data');
arrow($im, 915, 160, 875, 435, 'Evidence upload');
savePng($im, "$outDir/context-diagram.png");

$im = canvas('Data Flow Diagram - Level 0');
entity($im, 50, 80, 220, 110, 'Admin');
entity($im, 50, 450, 220, 110, 'Staff');
entity($im, 50, 815, 220, 110, 'LGU');
$processes = [
    ['1.0', 'Manage Users'], ['2.0', 'Manage Crime Records'], ['3.0', 'Manage Map and Hotspots'],
    ['4.0', 'Manage Evidence'], ['5.0', 'Manage Notifications'], ['6.0', 'Generate Reports and Dashboard'], ['7.0', 'System Maintenance'],
];
foreach ($processes as $i => $p) {
    processBox($im, 610, 65 + ($i * 135), 420, 85, $p[0], $p[1]);
}
$stores = [
    ['D1', 'Users table'], ['D2', 'Crime incidents table'], ['D3', 'Barangays/crime types'], ['D4', 'Evidence storage'], ['D5', 'Notifications table'], ['D6', 'Audit/history tables'],
];
foreach ($stores as $i => $s) {
    store($im, 1230, 80 + ($i * 145), 300, 70, $s[0], $s[1]);
}
arrow($im, 270, 120, 610, 108, 'User details');
arrow($im, 270, 485, 610, 242, 'Crime details');
arrow($im, 270, 510, 610, 377, 'Map request');
arrow($im, 270, 535, 610, 512, 'Evidence details');
arrow($im, 270, 850, 610, 782, 'Analytics request');
for ($i = 0; $i < 6; $i++) {
    arrow($im, 1030, 108 + ($i * 135), 1230, 115 + ($i * 145), $stores[$i][1]);
}
savePng($im, "$outDir/dfd-level-0.png");

roleDfd(
    'Data Flow Diagram - Admin',
    'Admin',
    [
        ['1.0', 'Manage Users', 'User details', 0, 'Saved/updated user record'],
        ['2.0', 'Manage Crime Types', 'Reference data details', 1, 'Saved/updated type record'],
        ['3.0', 'Manage Crime Records', 'Crime record details', 2, 'Saved/updated crime record'],
        ['4.0', 'Approve Final Status', 'Approval details', 2, 'Updated approval status'],
        ['5.0', 'Generate Reports', 'Report request', 3, 'Generated PDF report'],
        ['6.0', 'Import and Backup Data', 'Import/backup request', 4, 'Audit/backup record'],
    ],
    [
        ['D1', 'Users table'],
        ['D2', 'Crime/offense types table'],
        ['D3', 'Crime incidents table'],
        ['D4', 'Reports/PDF output'],
        ['D5', 'Audit logs table'],
    ],
    "$outDir/dfd-admin.png"
);

roleDfd(
    'Data Flow Diagram - Police Officer',
    'Police Officer',
    [
        ['1.0', 'Create Crime Incident', 'Incident details', 0, 'Saved crime record'],
        ['2.0', 'Plot Incident on Map', 'Map plot details', 1, 'Saved location data'],
        ['3.0', 'Upload Evidence', 'Evidence file details', 2, 'Saved evidence record'],
        ['4.0', 'View Assigned Records', 'Case view request', 0, 'Case information'],
        ['5.0', 'Update Case Status', 'Status details', 3, 'Saved status history'],
        ['6.0', 'View Notifications', 'Notification request', 4, 'Notification information'],
    ],
    [
        ['D1', 'Crime incidents table'],
        ['D2', 'Barangays/map data'],
        ['D3', 'Evidence table/storage'],
        ['D4', 'Status histories table'],
        ['D5', 'Notifications table'],
    ],
    "$outDir/dfd-police-officer.png"
);

roleDfd(
    'Data Flow Diagram - Investigator',
    'Investigator',
    [
        ['1.0', 'View Assigned Cases', 'Assigned case request', 0, 'Assigned case data'],
        ['2.0', 'Update Findings', 'Investigation findings', 0, 'Updated findings record'],
        ['3.0', 'Upload Evidence', 'Evidence file details', 1, 'Saved evidence record'],
        ['4.0', 'Update Case Status', 'Status update details', 2, 'Saved status history'],
        ['5.0', 'Request Final Approval', 'Approval request details', 3, 'Approval notification'],
        ['6.0', 'View Notifications', 'Notification request', 3, 'Notification information'],
    ],
    [
        ['D1', 'Crime incidents table'],
        ['D2', 'Evidence table/storage'],
        ['D3', 'Status histories table'],
        ['D4', 'Notifications table'],
    ],
    "$outDir/dfd-investigator.png"
);

roleDfd(
    'Data Flow Diagram - LGU',
    'LGU',
    [
        ['1.0', 'View Dashboard Summary', 'Dashboard request', 0, 'Crime summary data'],
        ['2.0', 'View Crime Trends', 'Trend request', 0, 'Trend data'],
        ['3.0', 'View Barangay Statistics', 'Barangay statistics request', 1, 'Barangay summary data'],
        ['4.0', 'View Crime Type Statistics', 'Crime type statistics request', 2, 'Type summary data'],
        ['5.0', 'Generate Summary Report', 'Summary report request', 3, 'Generated summary report'],
    ],
    [
        ['D1', 'Crime incidents table'],
        ['D2', 'Barangays table'],
        ['D3', 'Crime/offense types table'],
        ['D4', 'Summary report output'],
    ],
    "$outDir/dfd-lgu.png"
);

$im = canvas('Child Diagram - 2.0 Manage Crime Records');
entity($im, 60, 455, 220, 110, 'Staff / Admin');
$child = [
    ['2.1', 'Create Crime Incident'], ['2.2', 'View Crime Incident'], ['2.3', 'Update Findings'],
    ['2.4', 'Update Case Status'], ['2.5', 'Approve Final Status'], ['2.6', 'Import Crime Records'],
];
foreach ($child as $i => $p) {
    processBox($im, 620, 75 + ($i * 145), 420, 85, $p[0], $p[1]);
}
store($im, 1240, 95, 300, 70, 'D2', 'Crime incidents table');
store($im, 1240, 255, 300, 70, 'D3', 'Reference tables');
store($im, 1240, 570, 300, 70, 'D6', 'Status histories');
store($im, 1240, 730, 300, 70, 'D5', 'Notifications');
arrow($im, 280, 485, 620, 118, 'New incident details');
arrow($im, 280, 505, 620, 263, 'View request');
arrow($im, 280, 525, 620, 408, 'Findings details');
arrow($im, 280, 545, 620, 553, 'Status details');
arrow($im, 280, 560, 620, 698, 'Approval details');
arrow($im, 1040, 118, 1240, 130, 'Saved crime record');
arrow($im, 1040, 263, 1240, 290, 'Validated reference data');
arrow($im, 1040, 553, 1240, 605, 'Saved status history');
arrow($im, 1040, 698, 1240, 765, 'Approval notification');
savePng($im, "$outDir/child-crime-records.png");

$im = canvas('Child Diagram - 6.0 Generate Reports and Dashboard');
entity($im, 60, 230, 220, 110, 'Admin');
entity($im, 60, 720, 220, 110, 'LGU');
$reports = [
    ['6.1', 'Collect Crime Data'], ['6.2', 'Generate Dashboard Summary'], ['6.3', 'Preview Detailed Report'],
    ['6.4', 'Export PDF Report'], ['6.5', 'Generate Summary Report'],
];
foreach ($reports as $i => $p) {
    processBox($im, 620, 105 + ($i * 165), 430, 85, $p[0], $p[1]);
}
store($im, 1240, 165, 300, 70, 'D2', 'Crime incidents table');
store($im, 1240, 345, 300, 70, 'D3', 'Reference tables');
store($im, 1240, 525, 300, 70, 'D1', 'Users table');
arrow($im, 280, 260, 620, 477, 'Report request');
arrow($im, 280, 290, 620, 642, 'Export request');
arrow($im, 280, 755, 620, 807, 'Summary request');
arrow($im, 1050, 147, 1240, 200, 'Crime data');
arrow($im, 1050, 312, 1240, 380, 'Type/barangay data');
arrow($im, 1050, 477, 1240, 560, 'Officer data');
arrow($im, 1050, 642, 1430, 642, 'PDF report file');
arrow($im, 1050, 807, 1430, 807, 'Summary report');
savePng($im, "$outDir/child-reports-dashboard.png");

echo $outDir . PHP_EOL;
