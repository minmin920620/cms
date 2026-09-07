<?php

$source = $argv[1] ?? (__DIR__ . '/../REQUIREMENTS_DOCUMENTATION.md');
$target = $argv[2] ?? (__DIR__ . '/../capstone_build/requirements_documentation.html');

$markdown = file_get_contents($source);
if ($markdown === false) {
    fwrite(STDERR, "Unable to read requirements documentation.\n");
    exit(1);
}

function text_html(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$lines = preg_split('/\R/', $markdown);
$body = [];

foreach ($lines as $line) {
    $trimmed = trim($line);

    if ($trimmed === '') {
        continue;
    }

    if (str_starts_with($trimmed, '# ')) {
        $body[] = '<h1>' . text_html(substr($trimmed, 2)) . '</h1>';
        continue;
    }

    if (str_starts_with($trimmed, '## ')) {
        $body[] = '<h2>' . text_html(substr($trimmed, 3)) . '</h2>';
        continue;
    }

    if (str_starts_with($trimmed, '### ')) {
        $body[] = '<h3>' . text_html(substr($trimmed, 4)) . '</h3>';
        continue;
    }

    if (preg_match('/^(REQ\.\s+\d+\.\d+)\s+(.+)$/', $trimmed, $matches)) {
        $body[] = '<p class="requirement"><strong>' . text_html($matches[1]) . '</strong> ' . text_html($matches[2]) . '</p>';
        continue;
    }

    $body[] = '<p>' . text_html($trimmed) . '</p>';
}

$html = '<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Requirements Documentation</title>
    <style>
        @page {
            size: A4;
            margin: 18mm 17mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11.5pt;
            line-height: 1.6;
            margin: 0;
        }

        h1 {
            font-size: 20pt;
            margin: 0 0 18px;
            text-align: center;
        }

        h2 {
            border-bottom: 1px solid #d1d5db;
            font-size: 15pt;
            margin: 24px 0 12px;
            padding-bottom: 5px;
        }

        h3 {
            font-size: 12.5pt;
            margin: 18px 0 8px;
        }

        p {
            margin: 0 0 10px;
            text-align: justify;
        }

        .requirement {
            margin-left: 18px;
            text-indent: -18px;
        }

        h2, h3 {
            break-after: avoid;
        }
    </style>
</head>
<body>
' . implode("\n", $body) . '
</body>
</html>';

if (! is_dir(dirname($target))) {
    mkdir(dirname($target), 0777, true);
}

file_put_contents($target, $html);
echo $target . PHP_EOL;
