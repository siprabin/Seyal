<?php
/**
 * Report exports without Composer:
 *  - CSV   (UTF-8 with BOM so Excel shows Indian/Arabic scripts correctly)
 *  - Excel (a real .xlsx built with PHP's ZipArchive)
 *  - PDF   (a print-ready page; the browser's "Save as PDF" renders every language and right-to-left text correctly)
 */
declare(strict_types=1);

function export_filename(string $base, string $ext): string
{
    $base = preg_replace('/[^A-Za-z0-9_-]+/', '-', $base) ?: 'report';
    return $base . '-' . date('Y-m-d') . '.' . $ext;
}

/** @param array $headers list of column titles; @param array $rows list of lists (scalars) */
function export_csv(string $base, array $headers, array $rows): never
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . export_filename($base, 'csv') . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $r) {
        // Prevent spreadsheet formula injection from user-entered text.
        fputcsv($out, array_map(fn($v) => is_string($v) && $v !== '' && strpbrk($v[0], '=+-@') !== false && !is_numeric($v) ? "'" . $v : $v, $r));
    }
    fclose($out);
    exit;
}

function xlsx_col(int $i): string
{
    $s = '';
    for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
        $s = chr(65 + ($i - 1) % 26) . $s;
    }
    return $s;
}

function xlsx_text(string $s): string
{
    $s = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $s) ?? '';
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function export_xlsx(string $base, string $sheet, array $headers, array $rows): never
{
    if (!class_exists('ZipArchive')) {
        export_csv($base, $headers, $rows);
    }
    $rtl = is_rtl() ? ' rightToLeft="1"' : '';
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetViews><sheetView workbookViewId="0"' . $rtl . '><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
        . '<cols>';
    foreach ($headers as $i => $h) {
        $w = max(10, min(60, mb_strlen((string)$h) + 4));
        foreach (array_slice($rows, 0, 200) as $r) {
            $w = max($w, min(60, mb_strlen((string)($r[$i] ?? '')) + 2));
        }
        $xml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
    }
    $xml .= '</cols><sheetData>';
    $all = array_merge([$headers], $rows);
    foreach ($all as $ri => $r) {
        $xml .= '<row r="' . ($ri + 1) . '">';
        foreach (array_values($r) as $ci => $v) {
            $ref = xlsx_col($ci) . ($ri + 1);
            $style = $ri === 0 ? ' s="1"' : '';
            if ($ri > 0 && (is_int($v) || is_float($v))) {
                $xml .= '<c r="' . $ref . '"' . $style . '><v>' . $v . '</v></c>';
            } elseif ($v !== null && $v !== '') {
                $xml .= '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t xml:space="preserve">' . xlsx_text((string)$v) . '</t></is></c>';
            }
        }
        $xml .= '</row>';
    }
    // Printed Excel pages get a footer: company on the left, credit on the right ("&" is a code character, so it is doubled).
    $hf = fn(string $s) => xlsx_text(str_replace('&', '&&', $s));
    $xml .= '</sheetData>'
        . '<headerFooter><oddFooter>&amp;L' . $hf(setting('company_name', APP_NAME)) . '&amp;C&amp;P / &amp;N&amp;R' . $hf(powered_by()) . '</oddFooter></headerFooter>'
        . '</worksheet>';
    // File details (File → Info in Excel): author, application and company.
    $esc = fn(string $s) => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $stamp = gmdate('Y-m-d\TH:i:s\Z');
    $coreXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
        . '<dc:title>' . $esc($sheet) . '</dc:title>'
        . '<dc:creator>' . $esc(APP_NAME . ' - ' . APP_AUTHOR) . '</dc:creator>'
        . '<cp:lastModifiedBy>' . $esc((string)(user()['name'] ?? APP_NAME)) . '</cp:lastModifiedBy>'
        . '<dc:description>' . $esc(powered_by() . ' (' . APP_AUTHOR_URL . ')') . '</dc:description>'
        . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $stamp . '</dcterms:created>'
        . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $stamp . '</dcterms:modified>'
        . '</cp:coreProperties>';
    $appXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">'
        . '<Application>' . $esc(APP_NAME) . '</Application>'
        . '<Company>' . $esc(setting('company_name', APP_NAME)) . '</Company>'
        . '</Properties>';

    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="' . xlsx_text(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $sheet), 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
    $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE9EEF5"/></patternFill></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="2"><xf/><xf fontId="1" fillId="2" applyFont="1" applyFill="1"/></cellXfs></styleSheet>');
    $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
    $zip->addFromString('docProps/core.xml', $coreXml);
    $zip->addFromString('docProps/app.xml', $appXml);
    $zip->close();

    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . export_filename($base, 'xlsx') . '"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
    exit;
}

/** Print-ready page (use the browser's "Save as PDF"). */
function export_print(string $title, string $subtitle, array $headers, array $rows, array $numericCols = []): never
{
    render('reports/print', compact('title', 'subtitle', 'headers', 'rows', 'numericCols'), false);
    exit;
}

/** Dispatch on ?export=csv|xlsx|print. */
function maybe_export(string $base, string $title, string $subtitle, array $headers, array $rows, array $numericCols = []): void
{
    switch (input('export')) {
        case 'csv':
            export_csv($base, $headers, $rows);
        case 'xlsx':
            export_xlsx($base, $title, $headers, $rows);
        case 'print':
            export_print($title, $subtitle, $headers, $rows, $numericCols);
    }
}
