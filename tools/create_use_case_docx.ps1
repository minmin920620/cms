param(
    [string]$OutputPath = "$env:USERPROFILE\Downloads\crime_mapping_use_case_diagram.docx"
)

Add-Type -AssemblyName System.Drawing
Add-Type -AssemblyName System.IO.Compression.FileSystem

$ErrorActionPreference = "Stop"

$workDir = Join-Path (Get-Location) "storage\app\generated-use-case-docx"
if (Test-Path $workDir) {
    Remove-Item -LiteralPath $workDir -Recurse -Force
}
New-Item -ItemType Directory -Path $workDir | Out-Null

$mediaDir = Join-Path $workDir "word\media"
$relsDir = Join-Path $workDir "_rels"
$wordRelsDir = Join-Path $workDir "word\_rels"
New-Item -ItemType Directory -Path $mediaDir -Force | Out-Null
New-Item -ItemType Directory -Path $relsDir -Force | Out-Null
New-Item -ItemType Directory -Path $wordRelsDir -Force | Out-Null

$diagramPath = Join-Path $mediaDir "use-case-diagram.png"

function Draw-Actor {
    param($Graphics, [int]$X, [int]$Y, [string]$Name, $Pen, $Font, $Brush)

    $Graphics.DrawEllipse($Pen, $X + 20, $Y, 30, 30)
    $Graphics.DrawLine($Pen, $X + 35, $Y + 30, $X + 35, $Y + 78)
    $Graphics.DrawLine($Pen, $X + 5, $Y + 45, $X + 65, $Y + 45)
    $Graphics.DrawLine($Pen, $X + 35, $Y + 78, $X + 8, $Y + 118)
    $Graphics.DrawLine($Pen, $X + 35, $Y + 78, $X + 62, $Y + 118)

    $format = New-Object System.Drawing.StringFormat
    $format.Alignment = [System.Drawing.StringAlignment]::Center
    $nameRect = New-Object System.Drawing.RectangleF -ArgumentList ([single]($X - 35)), ([single]($Y + 124)), ([single]140), ([single]42)
    $Graphics.DrawString($Name, $Font, $Brush, $nameRect, $format)
}

function Draw-UseCase {
    param($Graphics, [int]$X, [int]$Y, [int]$W, [int]$H, [string]$Text, $Pen, $Font, $Brush, $Fill)

    $Graphics.FillEllipse($Fill, $X, $Y, $W, $H)
    $Graphics.DrawEllipse($Pen, $X, $Y, $W, $H)
    $format = New-Object System.Drawing.StringFormat
    $format.Alignment = [System.Drawing.StringAlignment]::Center
    $format.LineAlignment = [System.Drawing.StringAlignment]::Center
    $textRect = New-Object System.Drawing.RectangleF -ArgumentList ([single]($X + 8)), ([single]($Y + 4)), ([single]($W - 16)), ([single]($H - 8))
    $Graphics.DrawString($Text, $Font, $Brush, $textRect, $format)
}

function Draw-Link {
    param($Graphics, [int]$X1, [int]$Y1, [int]$X2, [int]$Y2, $Pen)
    $Graphics.DrawLine($Pen, $X1, $Y1, $X2, $Y2)
}

$bitmap = New-Object System.Drawing.Bitmap 1600, 1050
$graphics = [System.Drawing.Graphics]::FromImage($bitmap)
$graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
$graphics.Clear([System.Drawing.Color]::White)

$blackPen = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(45,45,45)), 2
$linePen = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(95,95,95)), 1.4
$boxPen = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(30,30,30)), 2
$ovalFill = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(248,250,252))
$textBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(32,32,32))
$titleFont = New-Object System.Drawing.Font "Arial", 32, ([System.Drawing.FontStyle]::Bold)
$subtitleFont = New-Object System.Drawing.Font "Arial", 15, ([System.Drawing.FontStyle]::Regular)
$labelFont = New-Object System.Drawing.Font "Arial", 13, ([System.Drawing.FontStyle]::Regular)
$actorFont = New-Object System.Drawing.Font "Arial", 15, ([System.Drawing.FontStyle]::Bold)

$graphics.DrawString("Crime Mapping System Use Case Diagram", $titleFont, $textBrush, 340, 28)
$graphics.DrawString("Actors and major system functions", $subtitleFont, $textBrush, 590, 75)
$graphics.DrawRectangle($boxPen, 300, 125, 980, 830)
$graphics.DrawString("Crime Mapping System", (New-Object System.Drawing.Font "Arial", 18, ([System.Drawing.FontStyle]::Bold)), $textBrush, 690, 138)

Draw-Actor $graphics 70 210 "Admin" $blackPen $actorFont $textBrush
Draw-Actor $graphics 70 610 "Police Officer" $blackPen $actorFont $textBrush
Draw-Actor $graphics 1380 250 "Investigator" $blackPen $actorFont $textBrush
Draw-Actor $graphics 1380 650 "LGU" $blackPen $actorFont $textBrush

$cases = @(
    @{x=365;y=190;w=230;h=58;t="Login / Logout"},
    @{x=665;y=190;w=230;h=58;t="View Dashboard"},
    @{x=965;y=190;w=230;h=58;t="Manage Profile"},
    @{x=365;y=305;w=230;h=58;t="Report Crime Incident"},
    @{x=665;y=305;w=230;h=58;t="Manage Crime Incidents"},
    @{x=965;y=305;w=230;h=58;t="Update Case Status"},
    @{x=365;y=420;w=230;h=58;t="Upload Evidence"},
    @{x=665;y=420;w=230;h=58;t="View / Delete Evidence"},
    @{x=965;y=420;w=230;h=58;t="View Crime Map"},
    @{x=365;y=535;w=230;h=58;t="Plot Incident on Map"},
    @{x=665;y=535;w=230;h=58;t="View Hotspots"},
    @{x=965;y=535;w=230;h=58;t="View Analytics"},
    @{x=365;y=650;w=230;h=58;t="Generate Reports"},
    @{x=665;y=650;w=230;h=58;t="Manage Users"},
    @{x=965;y=650;w=230;h=58;t="Manage Crime Types"},
    @{x=665;y=765;w=230;h=58;t="Manage Offense Types"}
)

foreach ($case in $cases) {
    Draw-UseCase $graphics $case.x $case.y $case.w $case.h $case.t $blackPen $labelFont $textBrush $ovalFill
}

# Shared/basic links
foreach ($target in @(@(480,219), @(780,219), @(1080,219))) {
    Draw-Link $graphics 140 285 $target[0] $target[1] $linePen
    Draw-Link $graphics 1450 325 $target[0] $target[1] $linePen
    Draw-Link $graphics 140 685 $target[0] $target[1] $linePen
    Draw-Link $graphics 1450 725 $target[0] $target[1] $linePen
}

# Admin links
foreach ($target in @(@(480,334),@(780,334),@(1080,334),@(480,449),@(780,449),@(1080,449),@(480,564),@(780,564),@(1080,564),@(480,679),@(780,679),@(1080,679),@(780,794))) {
    Draw-Link $graphics 140 285 $target[0] $target[1] $linePen
}

# Police links
foreach ($target in @(@(480,334),@(780,334),@(1080,334),@(480,449),@(780,449),@(1080,449),@(480,564),@(780,564))) {
    Draw-Link $graphics 140 685 $target[0] $target[1] $linePen
}

# Investigator links
foreach ($target in @(@(780,334),@(1080,334),@(780,449),@(1080,449),@(780,564))) {
    Draw-Link $graphics 1450 325 $target[0] $target[1] $linePen
}

# LGU analytics link
Draw-Link $graphics 1450 725 1080 564 $linePen

$bitmap.Save($diagramPath, [System.Drawing.Imaging.ImageFormat]::Png)
$graphics.Dispose()
$bitmap.Dispose()

function XmlEscape([string]$Value) {
    return [System.Security.SecurityElement]::Escape($Value)
}

function Paragraph([string]$Text, [string]$Style = "") {
    $escaped = XmlEscape $Text
    if ($Style) {
        return "<w:p><w:pPr><w:pStyle w:val=`"$Style`"/></w:pPr><w:r><w:t>$escaped</w:t></w:r></w:p>"
    }
    return "<w:p><w:r><w:t>$escaped</w:t></w:r></w:p>"
}

function TableRow([string[]]$Cells, [bool]$Header = $false) {
    $row = "<w:tr>"
    foreach ($cell in $Cells) {
        $text = XmlEscape $cell
        $shade = if ($Header) { "<w:shd w:fill=`"E5E7EB`"/>" } else { "" }
        $boldStart = if ($Header) { "<w:b/>" } else { "" }
        $row += "<w:tc><w:tcPr><w:tcW w:w=`"3000`" w:type=`"dxa`"/>$shade</w:tcPr><w:p><w:r><w:rPr>$boldStart</w:rPr><w:t>$text</w:t></w:r></w:p></w:tc>"
    }
    $row += "</w:tr>"
    return $row
}

$rows = @(
    @("Use Case", "Description", "User/Actor"),
    @("Login", "Allows users to access the Crime Mapping System using their credentials.", "Admin, Police Officer, Investigator, LGU"),
    @("Logout", "Allows users to securely exit the system.", "Admin, Police Officer, Investigator, LGU"),
    @("View Dashboard", "Displays crime statistics, summaries, and system overview.", "Admin, Police Officer, Investigator, LGU"),
    @("Manage Profile", "Allows users to update their account information.", "Admin, Police Officer, Investigator, LGU"),
    @("Report Crime Incident", "Allows creation of a new crime incident record.", "Admin, Police Officer"),
    @("Manage Crime Incidents", "Allows viewing, editing, updating, or deleting authorized crime records.", "Admin, Police Officer, Investigator"),
    @("Update Case Status", "Allows changing case status such as pending, under investigation, resolved, or closed.", "Admin, Police Officer, Investigator"),
    @("Upload Evidence", "Allows adding evidence files to a crime record.", "Admin, Police Officer"),
    @("View Evidence", "Allows viewing uploaded evidence files.", "Admin, Police Officer, Investigator"),
    @("Delete Evidence", "Allows deletion of authorized evidence files.", "Admin, Police Officer, Investigator"),
    @("View Crime Map", "Displays crime incidents on a map.", "Admin, Police Officer, Investigator"),
    @("Plot Incident on Map", "Allows users to plot a crime incident location on the map.", "Admin, Police Officer"),
    @("View Hotspots", "Shows barangays or areas with high crime incidents.", "Admin, Police Officer, Investigator"),
    @("View Analytics", "Displays crime trends, crime by type, barangay statistics, and status breakdown.", "Admin, LGU"),
    @("Generate Reports", "Allows generation or preview of crime reports.", "Admin"),
    @("Manage Users", "Allows creating, updating, activating, deactivating, or deleting system users.", "Admin"),
    @("Manage Crime Types", "Allows maintaining crime categories used in incident reports.", "Admin"),
    @("Manage Offense Types", "Allows maintaining offense classifications.", "Admin")
)

$tableXml = "<w:tbl><w:tblPr><w:tblStyle w:val=`"TableGrid`"/><w:tblW w:w=`"0`" w:type=`"auto`"/><w:tblBorders><w:top w:val=`"single`" w:sz=`"4`"/><w:left w:val=`"single`" w:sz=`"4`"/><w:bottom w:val=`"single`" w:sz=`"4`"/><w:right w:val=`"single`" w:sz=`"4`"/><w:insideH w:val=`"single`" w:sz=`"4`"/><w:insideV w:val=`"single`" w:sz=`"4`"/></w:tblBorders></w:tblPr>"
for ($i = 0; $i -lt $rows.Count; $i++) {
    $tableXml += TableRow $rows[$i] ($i -eq 0)
}
$tableXml += "</w:tbl>"

$documentXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" mc:Ignorable="w14 wp14">
<w:body>
$(Paragraph "Chapter IV: Methodology, Results, and Discussion" "Title")
$(Paragraph "Use Case Diagram")
<w:p><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="6035040" cy="3962400"/><wp:effectExtent l="0" t="0" r="0" b="0"/><wp:docPr id="1" name="Use Case Diagram"/><wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="0" name="use-case-diagram.png"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="6035040" cy="3962400"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>
$(Paragraph "Table 1: Use Case Description")
$tableXml
<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>
</w:body>
</w:document>
"@

$stylesXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/></w:rPr></w:style>
<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:pPr><w:jc w:val="center"/><w:spacing w:after="240"/></w:pPr><w:rPr><w:b/><w:sz w:val="36"/></w:rPr></w:style>
<w:style w:type="table" w:styleId="TableGrid"><w:name w:val="Table Grid"/><w:tblPr><w:tblBorders><w:top w:val="single" w:sz="4"/><w:left w:val="single" w:sz="4"/><w:bottom w:val="single" w:sz="4"/><w:right w:val="single" w:sz="4"/><w:insideH w:val="single" w:sz="4"/><w:insideV w:val="single" w:sz="4"/></w:tblBorders></w:tblPr></w:style>
</w:styles>
"@

$contentTypesXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Default Extension="png" ContentType="image/png"/>
<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>
"@

$rootRelsXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
"@

$docRelsXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/use-case-diagram.png"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
"@

Set-Content -LiteralPath (Join-Path $workDir "[Content_Types].xml") -Value $contentTypesXml -Encoding UTF8
Set-Content -LiteralPath (Join-Path $relsDir ".rels") -Value $rootRelsXml -Encoding UTF8
Set-Content -LiteralPath (Join-Path $workDir "word\document.xml") -Value $documentXml -Encoding UTF8
Set-Content -LiteralPath (Join-Path $workDir "word\styles.xml") -Value $stylesXml -Encoding UTF8
Set-Content -LiteralPath (Join-Path $wordRelsDir "document.xml.rels") -Value $docRelsXml -Encoding UTF8

if (Test-Path $OutputPath) {
    Remove-Item -LiteralPath $OutputPath -Force
}

[System.IO.Compression.ZipFile]::CreateFromDirectory($workDir, $OutputPath)
Write-Output $OutputPath
