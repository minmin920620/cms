<?php
declare(strict_types=1);

$source = $argv[1] ?? (__DIR__ . '/../LGU_REQUIREMENTS_DOCUMENTATION.md');
$target = $argv[2] ?? (__DIR__ . '/../capstone_build/lgu_requirements_documentation.docx');

$markdown = file_get_contents($source);
if ($markdown === false) {
    fwrite(STDERR, "Unable to read requirements documentation.\n");
    exit(1);
}

function xml_text(string $text): string
{
    return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function paragraph(string $text, string $style = 'Normal'): string
{
    return '<w:p><w:pPr><w:pStyle w:val="' . $style . '"/></w:pPr><w:r><w:t xml:space="preserve">' . xml_text($text) . '</w:t></w:r></w:p>';
}

$body = [];
$lines = preg_split('/\R/', $markdown);

foreach ($lines as $line) {
    $trimmed = trim($line);

    if ($trimmed === '') {
        continue;
    }

    if (str_starts_with($trimmed, '# ')) {
        $body[] = paragraph(substr($trimmed, 2), 'Title');
        continue;
    }

    if (str_starts_with($trimmed, '## ')) {
        $body[] = paragraph(substr($trimmed, 3), 'Heading1');
        continue;
    }

    if (str_starts_with($trimmed, '### ')) {
        $body[] = paragraph(substr($trimmed, 4), 'Heading2');
        continue;
    }

    if (preg_match('/^REQ\.\s+[A-Z]+\.\d+\.\d+\s+/', $trimmed)) {
        $body[] = paragraph($trimmed, 'Requirement');
        continue;
    }

    $body[] = paragraph($trimmed);
}

$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>
    ' . implode("\n", $body) . '
    <w:sectPr>
      <w:pgSz w:w="12240" w:h="15840"/>
      <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/>
    </w:sectPr>
  </w:body>
</w:document>';

$stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault>
      <w:rPr>
        <w:rFonts w:ascii="Arial" w:hAnsi="Arial"/>
        <w:sz w:val="23"/>
      </w:rPr>
    </w:rPrDefault>
  </w:docDefaults>
  <w:style w:type="paragraph" w:default="1" w:styleId="Normal">
    <w:name w:val="Normal"/>
    <w:pPr>
      <w:spacing w:after="160" w:line="276" w:lineRule="auto"/>
    </w:pPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Title">
    <w:name w:val="Title"/>
    <w:pPr>
      <w:jc w:val="center"/>
      <w:spacing w:after="260"/>
    </w:pPr>
    <w:rPr>
      <w:b/>
      <w:sz w:val="40"/>
    </w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading1">
    <w:name w:val="heading 1"/>
    <w:pPr>
      <w:spacing w:before="300" w:after="160"/>
    </w:pPr>
    <w:rPr>
      <w:b/>
      <w:sz w:val="30"/>
    </w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading2">
    <w:name w:val="heading 2"/>
    <w:pPr>
      <w:spacing w:before="220" w:after="120"/>
    </w:pPr>
    <w:rPr>
      <w:b/>
      <w:sz w:val="25"/>
    </w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Requirement">
    <w:name w:val="Requirement"/>
    <w:pPr>
      <w:ind w:left="360" w:hanging="360"/>
      <w:spacing w:after="140" w:line="276" w:lineRule="auto"/>
    </w:pPr>
  </w:style>
</w:styles>';

if (! is_dir(dirname($target))) {
    mkdir(dirname($target), 0777, true);
}

$zip = new ZipArchive();
if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Unable to create Word document.\n");
    exit(1);
}

$zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>');
$zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>');
$zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');
$zip->addFromString('word/document.xml', $documentXml);
$zip->addFromString('word/styles.xml', $stylesXml);
$zip->close();

echo $target . PHP_EOL;
