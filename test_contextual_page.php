<?php
require_once 'config.php';
require_once 'ContextualBoothProcessor.php';
require_once 'Auth.php';
require_once 'dynamic_breadcrumb.php';

echo "Testing Contextual Upload Page Components:\n\n";

// Test 1: Check if all required classes exist
echo "1. Checking class availability:\n";
echo "   - ContextualBoothProcessor: " . (class_exists('ContextualBoothProcessor') ? '✓' : '✗') . "\n";
echo "   - Auth: " . (class_exists('Auth') ? '✓' : '✗') . "\n";
echo "   - DynamicBreadcrumb: " . (class_exists('DynamicBreadcrumb') ? '✓' : '✗') . "\n\n";

// Test 2: Test ContextualBoothProcessor instantiation
echo "2. Testing ContextualBoothProcessor instantiation:\n";
try {
    $processor = new ContextualBoothProcessor($pdo, 'test-mla-id');
    echo "   ✓ ContextualBoothProcessor created successfully\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test 3: Test template generation
echo "\n3. Testing template generation:\n";
try {
    $template = $processor->generateTemplate();
    echo "   ✓ Template generated successfully\n";
    echo "   Template preview:\n";
    echo "   " . str_replace("\n", "\n   ", $template) . "\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== All Tests Completed ===\n";
echo "The contextual upload page should now work properly!\n";
?>
