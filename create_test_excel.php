<?php
// Create a test Excel file using basic XML structure
$data = [
    ['mla_constituency_code', 'sl_no', 'polling_station_no', 'location_name_of_building', 'polling_areas', 'polling_station_type'],
    ['1', '1', '001', 'Government Higher Secondary School', 'Areas 1-5', 'Regular'],
    ['1', '2', '002', 'Panchayat Union Primary School', 'Areas 6-10', 'Regular'],
    ['1', '3', '003', 'Community Hall', 'Areas 11-15', 'Auxiliary'],
    ['2', '1', '001', 'Government High School', 'Areas 1-8', 'Regular'],
    ['2', '2', '002', 'Private School Building', 'Areas 9-12', 'Special']
];

// Create a simple XLSX file structure
$zip = new ZipArchive();
$result = $zip->open('test_booth_upload_simple.xlsx', ZipArchive::CREATE | ZipArchive::OVERWRITE);
if ($result === TRUE) {
    
    // Create shared strings XML
    $sharedStrings = [];
    foreach ($data as $row) {
        foreach ($row as $cell) {
            if (!in_array($cell, $sharedStrings)) {
                $sharedStrings[] = $cell;
            }
        }
    }
    
    $sharedStringsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($sharedStrings) . '" uniqueCount="' . count($sharedStrings) . '">';
    foreach ($sharedStrings as $string) {
        $sharedStringsXml .= '<si><t>' . htmlspecialchars($string) . '</t></si>';
    }
    $sharedStringsXml .= '</sst>';
    
    $zip->addFromString('xl/sharedStrings.xml', $sharedStringsXml);
    
    // Create worksheet XML
    $worksheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
    
    foreach ($data as $rowIndex => $row) {
        $rowNum = $rowIndex + 1;
        $worksheetXml .= '<row r="' . $rowNum . '">';
        
        foreach ($row as $colIndex => $cell) {
            $colLetter = chr(65 + $colIndex); // A, B, C, etc.
            $cellRef = $colLetter . $rowNum;
            $sharedStringIndex = array_search($cell, $sharedStrings);
            
            $worksheetXml .= '<c r="' . $cellRef . '" t="s">';
            $worksheetXml .= '<v>' . $sharedStringIndex . '</v>';
            $worksheetXml .= '</c>';
        }
        
        $worksheetXml .= '</row>';
    }
    
    $worksheetXml .= '</sheetData></worksheet>';
    
    $zip->addFromString('xl/worksheets/sheet1.xml', $worksheetXml);
    
    // Create workbook XML
    $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>';
    $zip->addFromString('xl/workbook.xml', $workbookXml);
    
    // Create relationships
    $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>';
    $zip->addFromString('xl/_rels/workbook.xml.rels', $relsXml);
    
    // Create main relationships
    $mainRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    $zip->addFromString('_rels/.rels', $mainRelsXml);
    
    // Create content types
    $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/></Types>';
    $zip->addFromString('[Content_Types].xml', $contentTypesXml);
    
    $zip->close();
    
    echo "Excel file created successfully: test_booth_upload_simple.xlsx\n";
    echo "File contains " . count($data) . " rows of data.\n";
} else {
    echo "Failed to create Excel file. Error code: " . $result . "\n";
    echo "ZipArchive error: " . $zip->getStatusString() . "\n";
}
?>
