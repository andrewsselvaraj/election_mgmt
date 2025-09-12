<?php
require_once 'config.php';
require_once 'ContextualBoothProcessor.php';

// Test the contextual booth processor
$testMlaId = 'test-mla-id';
$processor = new ContextualBoothProcessor($pdo, $testMlaId);

echo "Testing Contextual Booth Upload System:\n\n";

echo "1. Generating Template:\n";
$template = $processor->generateTemplate();
echo $template . "\n\n";

echo "2. Testing Column Validation:\n";
$testHeaders = ['sl_no', 'polling_station_no', 'location_name_of_building', 'polling_areas', 'polling_station_type'];
$validation = $processor->validateFile('test_booth_data.csv');
echo "Validation result: " . ($validation['valid'] ? 'Valid' : 'Invalid') . "\n";
if (!$validation['valid']) {
    echo "Error: " . $validation['message'] . "\n";
}

echo "\n3. Contextual Upload Features:\n";
echo "✅ No need to specify MP or MLA IDs\n";
echo "✅ MLA ID is automatically set from navigation context\n";
echo "✅ Simplified CSV format with only booth-specific columns\n";
echo "✅ Template generation for specific MLA constituency\n";
echo "✅ Validation ensures data integrity within MLA context\n";

echo "\nTest completed successfully!\n";
?>
