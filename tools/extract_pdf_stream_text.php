<?php

$path = $argv[1] ?? '';
if ($path === '' || ! is_file($path)) {
    fwrite(STDERR, "PDF not found.\n");
    exit(1);
}

$pdf = file_get_contents($path);
preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $matches);

foreach ($matches[1] as $stream) {
    $decoded = @gzuncompress($stream);
    if ($decoded === false) {
        $decoded = @gzdecode($stream);
    }
    if ($decoded === false || ! preg_match('/[A-Za-z]{4}/', $decoded)) {
        continue;
    }

    $clean = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', ' ', $decoded);
    echo $clean, "\n---STREAM---\n";
}
