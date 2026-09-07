<?php

$source = __DIR__ . '/../chapter_iv_module_use_case_activity.md';
$target = __DIR__ . '/../capstone_build/chapter_iv_module_use_case_activity.html';

$markdown = file_get_contents($source);
if ($markdown === false) {
    fwrite(STDERR, "Unable to read source file.\n");
    exit(1);
}

function inline_format(string $text): string
{
    $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

    return $text;
}

function h(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function actor_svg(string $name, int $x, int $y): string
{
    $label = h($name);
    $bodyTop = $y + 9;
    $bodyBottom = $y + 39;
    $armY = $y + 22;
    $leftArmX = $x - 18;
    $rightArmX = $x + 18;
    $leftLegX = $x - 16;
    $rightLegX = $x + 16;
    $legY = $y + 62;
    $labelY = $y + 80;

    return <<<SVG
<circle cx="{$x}" cy="{$y}" r="9" fill="white" stroke="#111827" stroke-width="1.5"/>
<line x1="{$x}" y1="{$bodyTop}" x2="{$x}" y2="{$bodyBottom}" stroke="#111827" stroke-width="1.5"/>
<line x1="{$leftArmX}" y1="{$armY}" x2="{$rightArmX}" y2="{$armY}" stroke="#111827" stroke-width="1.5"/>
<line x1="{$x}" y1="{$bodyBottom}" x2="{$leftLegX}" y2="{$legY}" stroke="#111827" stroke-width="1.5"/>
<line x1="{$x}" y1="{$bodyBottom}" x2="{$rightLegX}" y2="{$legY}" stroke="#111827" stroke-width="1.5"/>
<text x="{$x}" y="{$labelY}" text-anchor="middle" font-size="11" font-weight="700">{$label}</text>
SVG;
}

function use_case_svg(string $name, int $x, int $y): string
{
    $label = h($name);
    $lines = explode('|', wordwrap($label, 24, '|'));
    $text = '';
    $start = $y - ((count($lines) - 1) * 6);
    foreach ($lines as $index => $line) {
        $line = h($line);
        $text .= '<text x="' . $x . '" y="' . ($start + ($index * 13)) . '" text-anchor="middle" font-size="10">' . $line . "</text>\n";
    }

    return <<<SVG
<ellipse cx="{$x}" cy="{$y}" rx="91" ry="28" fill="white" stroke="#111827" stroke-width="1.2"/>
{$text}
SVG;
}

function module_diagrams(): array
{
    return [
        'Login Module' => [
            'actors' => ['Admin', 'LGU', 'Police Officer', 'Investigator'],
            'cases' => ['Login', 'Reset Password', 'Logout'],
        ],
        'Dashboard Module' => [
            'actors' => ['Admin', 'LGU', 'Police Officer', 'Investigator'],
            'cases' => ['View Dashboard', 'View Dashboard Data'],
        ],
        'Crime Incident Management Module' => [
            'actors' => ['Admin', 'Police Officer', 'Investigator'],
            'cases' => ['View Crime Incidents', 'Report Crime Incident', 'Update Crime Incident', 'Update Crime Status', 'Record Investigation Findings', 'Delete Crime Incident'],
        ],
        'Evidence Management Module' => [
            'actors' => ['Admin', 'Police Officer', 'Investigator'],
            'cases' => ['Upload Evidence', 'View Evidence', 'Delete Evidence'],
        ],
        'Crime Map Module' => [
            'actors' => ['Admin', 'Police Officer', 'Investigator'],
            'cases' => ['View Crime Map', 'Filter Map Data', 'Detect Barangay', 'Plot Incident on Map'],
        ],
        'Hotspot Module' => [
            'actors' => ['Admin', 'Police Officer', 'Investigator'],
            'cases' => ['View Hotspots', 'View Hotspot Details'],
        ],
        'Analytics Module' => [
            'actors' => ['Admin', 'LGU'],
            'cases' => ['View Crime Trends', 'View Crime by Type', 'View Crime by Barangay', 'View Status Breakdown', 'View Monthly Report Data'],
        ],
        'Report Module' => [
            'actors' => ['Admin'],
            'cases' => ['Generate Report', 'Preview Report', 'Export or Print Report'],
        ],
        'User Management Module' => [
            'actors' => ['Admin'],
            'cases' => ['View Users', 'Create User', 'Update User', 'Delete User'],
        ],
        'Crime Type Management Module' => [
            'actors' => ['Admin'],
            'cases' => ['View Crime Types', 'Create Crime Type', 'Update Crime Type', 'Delete Crime Type'],
        ],
        'Offense Type Management Module' => [
            'actors' => ['Admin'],
            'cases' => ['View Offense Types', 'Create Offense Type', 'Update Offense Type', 'Delete Offense Type'],
        ],
        'Account Profile Module' => [
            'actors' => ['Admin', 'LGU', 'Police Officer', 'Investigator'],
            'cases' => ['Update Profile Information', 'Change Password', 'Delete Account'],
        ],
    ];
}

function use_case_diagram(string $module): string
{
    $diagrams = module_diagrams();
    if (!isset($diagrams[$module])) {
        return '';
    }

    $actors = $diagrams[$module]['actors'];
    $cases = $diagrams[$module]['cases'];
    $caseCount = count($cases);
    $height = max(260, 92 + ($caseCount * 64));
    $boundaryX = 245;
    $boundaryY = 40;
    $boundaryW = 595;
    $boundaryH = $height - 72;
    $actorX = 105;
    $caseX = 555;
    $caseStartY = 96;
    $caseGap = 64;

    $svg = '<div class="diagram"><svg viewBox="0 0 920 ' . $height . '" role="img" aria-label="' . h($module) . ' Use Case Diagram">';
    $svg .= '<rect x="' . $boundaryX . '" y="' . $boundaryY . '" width="' . $boundaryW . '" height="' . $boundaryH . '" fill="none" stroke="#111827" stroke-width="1.4"/>';
    $svg .= '<text x="' . ($boundaryX + ($boundaryW / 2)) . '" y="' . ($boundaryY + 20) . '" text-anchor="middle" font-size="13" font-weight="700">Crime Mapping System - ' . h($module) . '</text>';

    $caseY = [];
    foreach ($cases as $index => $case) {
        $y = $caseStartY + ($index * $caseGap);
        $caseY[] = $y;
        $svg .= use_case_svg($case, $caseX, $y);
    }

    $actorGap = count($actors) === 1 ? 0 : min(88, max(64, (int) (($height - 175) / max(count($actors) - 1, 1))));
    $actorStartY = count($actors) === 1 ? (int) (($height / 2) - 40) : 58;

    foreach ($actors as $actorIndex => $actor) {
        $ay = $actorStartY + ($actorIndex * $actorGap);
        $svg .= actor_svg($actor, $actorX, $ay);
        $lineStartX = $actorX + 23;
        $lineStartY = $ay + 27;

        foreach ($caseY as $y) {
            $svg .= '<line x1="' . $lineStartX . '" y1="' . $lineStartY . '" x2="' . ($caseX - 91) . '" y2="' . $y . '" stroke="#6b7280" stroke-width="0.8"/>';
        }
    }

    $svg .= '</svg><p class="figure-caption">' . h($module) . ' Use Case Diagram</p></div>';

    return $svg;
}

$lines = preg_split('/\R/', $markdown);
$html = '';
$inTable = false;
$inList = false;

for ($i = 0; $i < count($lines); $i++) {
    $line = rtrim($lines[$i]);

    if ($line === '') {
        if ($inList) {
            $html .= "</ol>\n";
            $inList = false;
        }
        if ($inTable) {
            $html .= "</tbody></table>\n";
            $inTable = false;
        }
        continue;
    }

    if (str_starts_with($line, '|')) {
        if (!$inTable) {
            $html .= "<table><tbody>\n";
            $inTable = true;
        }

        $cells = array_map('trim', explode('|', trim($line, '|')));
        $isSeparator = true;
        foreach ($cells as $cell) {
            if (!preg_match('/^:?-{3,}:?$/', $cell)) {
                $isSeparator = false;
                break;
            }
        }
        if ($isSeparator) {
            continue;
        }

        $tag = str_contains(strtolower(implode(' ', $cells)), 'use case') ? 'th' : 'td';
        $html .= '<tr>';
        foreach ($cells as $cell) {
            $html .= "<{$tag}>" . inline_format($cell) . "</{$tag}>";
        }
        $html .= "</tr>\n";
        continue;
    }

    if ($inTable) {
        $html .= "</tbody></table>\n";
        $inTable = false;
    }

    if (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
        if (!$inList) {
            $html .= "<ol>\n";
            $inList = true;
        }
        $html .= '<li>' . inline_format($matches[1]) . "</li>\n";
        continue;
    }

    if ($inList) {
        $html .= "</ol>\n";
        $inList = false;
    }

    if (str_starts_with($line, '# ')) {
        $html .= '<h1>' . inline_format(substr($line, 2)) . "</h1>\n";
    } elseif (str_starts_with($line, '## ')) {
        $heading = substr($line, 3);
        $html .= '<h2>' . inline_format($heading) . "</h2>\n";
        $html .= use_case_diagram($heading);
    } else {
        $html .= '<p>' . inline_format($line) . "</p>\n";
    }
}

if ($inList) {
    $html .= "</ol>\n";
}
if ($inTable) {
    $html .= "</tbody></table>\n";
}

$document = <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Chapter IV Module Use Case and Activity Diagram</title>
<style>
    @page {
        size: A4;
        margin: 18mm 16mm;
    }
    body {
        color: #111827;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
        line-height: 1.45;
    }
    h1 {
        border-bottom: 1px solid #9ca3af;
        color: #374151;
        font-size: 26px;
        margin: 0 0 18px;
        padding-bottom: 8px;
        text-align: center;
    }
    h2 {
        color: #111827;
        font-size: 15px;
        margin: 22px 0 8px;
        page-break-after: avoid;
    }
    p {
        margin: 6px 0;
    }
    table {
        border-collapse: collapse;
        margin: 8px 0 10px;
        page-break-inside: avoid;
        width: 100%;
    }
    th, td {
        border: 1px solid #4b5563;
        padding: 6px;
        text-align: left;
        vertical-align: top;
    }
    th {
        background: #f3f4f6;
        font-weight: 700;
        text-align: center;
    }
    ol {
        margin: 6px 0 12px 20px;
        padding: 0;
    }
    li {
        margin: 2px 0;
    }
    .diagram {
        margin: 8px auto 12px;
        page-break-inside: avoid;
        text-align: center;
        width: 100%;
    }
    .diagram svg {
        max-height: 520px;
        max-width: 100%;
        width: 100%;
    }
    .figure-caption {
        font-size: 10px;
        font-weight: 700;
        margin-top: 3px;
        text-align: center;
    }
</style>
</head>
<body>
{$html}
</body>
</html>
HTML;

if (!is_dir(dirname($target))) {
    mkdir(dirname($target), 0777, true);
}

file_put_contents($target, $document);
echo $target . PHP_EOL;
