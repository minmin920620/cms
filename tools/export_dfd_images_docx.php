<?php

$outputPath = $argv[1] ?? __DIR__ . '/../docs/crime-management-system-diagrams.docx';
$imageDir = __DIR__ . '/../docs/dfd-images';
$images = [
    ['Context Diagram', 'context-diagram.png'],
    ['Data Flow Diagram - Level 0', 'dfd-level-0.png'],
    ['Data Flow Diagram - Admin', 'dfd-admin.png'],
    ['Data Flow Diagram - Police Officer', 'dfd-police-officer.png'],
    ['Data Flow Diagram - Investigator', 'dfd-investigator.png'],
    ['Data Flow Diagram - LGU', 'dfd-lgu.png'],
    ['Child Diagram - 2.0 Manage Crime Records', 'child-crime-records.png'],
    ['Child Diagram - 6.0 Generate Reports and Dashboard', 'child-reports-dashboard.png'],
];

$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cms-dfd-docx-' . uniqid();
mkdir($tempDir . '/_rels', 0777, true);
mkdir($tempDir . '/word/_rels', 0777, true);
mkdir($tempDir . '/word/media', 0777, true);

file_put_contents($tempDir . '/[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Default Extension="png" ContentType="image/png"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>
XML);

file_put_contents($tempDir . '/_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML);

$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
$rels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";

$body = '';
foreach ($images as $index => [$title, $file]) {
    $rid = 'rId' . ($index + 1);
    $mediaName = 'diagram' . ($index + 1) . '.png';
    copy($imageDir . '/' . $file, $tempDir . '/word/media/' . $mediaName);
    $rels .= '  <Relationship Id="' . $rid . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/' . $mediaName . '"/>' . "\n";
    $body .= titleParagraph($title);
    $body .= imageParagraph($rid, $index + 1, $title);
    if ($index < count($images) - 1) {
        $body .= '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
    }
}
$rels .= '</Relationships>';
file_put_contents($tempDir . '/word/_rels/document.xml.rels', $rels);

$document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
    . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
    . 'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" '
    . 'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
    . 'xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
    . '<w:body>'
    . $body
    . '<w:sectPr><w:pgSz w:w="15840" w:h="12240" w:orient="landscape"/><w:pgMar w:top="360" w:right="360" w:bottom="360" w:left="360" w:header="360" w:footer="360" w:gutter="0"/></w:sectPr>'
    . '</w:body></w:document>';
file_put_contents($tempDir . '/word/document.xml', $document);

if (file_exists($outputPath)) {
    unlink($outputPath);
}

$zip = new ZipArchive();
$zip->open($outputPath, ZipArchive::CREATE);
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir, FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    $path = $file->getPathname();
    $relative = str_replace('\\', '/', substr($path, strlen($tempDir) + 1));
    $zip->addFile($path, $relative);
}
$zip->close();

echo $outputPath . PHP_EOL;

function titleParagraph(string $title): string
{
    return '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="32"/></w:rPr><w:t>' . xml($title) . '</w:t></w:r></w:p>';
}

function imageParagraph(string $rid, int $id, string $name): string
{
    return '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">'
        . '<wp:extent cx="9144000" cy="6000000"/><wp:docPr id="' . $id . '" name="' . xml($name) . '"/>'
        . '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
        . '<pic:pic><pic:nvPicPr><pic:cNvPr id="' . $id . '" name="' . xml($name) . '"/><pic:cNvPicPr/></pic:nvPicPr>'
        . '<pic:blipFill><a:blip r:embed="' . $rid . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
        . '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="9144000" cy="6000000"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
        . '</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>';
}

function xml(string $text): string
{
    return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
