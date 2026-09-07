<?php

$htmlPath = __DIR__ . '/../docs/system-diagrams-print.html';
$outputPath = $argv[1] ?? __DIR__ . '/../docs/crime-management-system-diagrams.docx';

if (! class_exists(ZipArchive::class)) {
    fwrite(STDERR, "The PHP ZipArchive extension is required to create DOCX files.\n");
    exit(1);
}

$html = file_get_contents($htmlPath);

$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cms-diagrams-docx-' . uniqid();
mkdir($tempDir . '/_rels', 0777, true);
mkdir($tempDir . '/word/_rels', 0777, true);

file_put_contents($tempDir . '/[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Default Extension="html" ContentType="text/html"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>
XML);

file_put_contents($tempDir . '/_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML);

file_put_contents($tempDir . '/word/document.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <w:body>
        <w:altChunk r:id="rId1"/>
        <w:sectPr>
            <w:pgSz w:w="15840" w:h="12240" w:orient="landscape"/>
            <w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="360" w:footer="360" w:gutter="0"/>
        </w:sectPr>
    </w:body>
</w:document>
XML);

file_put_contents($tempDir . '/word/_rels/document.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/aFChunk" Target="afchunk.html"/>
</Relationships>
XML);

file_put_contents($tempDir . '/word/afchunk.html', $html);

if (file_exists($outputPath)) {
    unlink($outputPath);
}

$zip = new ZipArchive();
if ($zip->open($outputPath, ZipArchive::CREATE) !== true) {
    fwrite(STDERR, "Could not create {$outputPath}.\n");
    exit(1);
}

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($tempDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $file) {
    $path = $file->getPathname();
    $relativePath = str_replace('\\', '/', substr($path, strlen($tempDir) + 1));
    $zip->addFile($path, $relativePath);
}

$zip->close();

foreach ($files as $file) {
    @unlink($file->getPathname());
}
@rmdir($tempDir . '/word/_rels');
@rmdir($tempDir . '/word');
@rmdir($tempDir . '/_rels');
@rmdir($tempDir);

echo $outputPath . PHP_EOL;
