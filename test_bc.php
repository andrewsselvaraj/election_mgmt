<?php
require_once 'config.php';
require_once 'dynamic_breadcrumb.php';

$dynamicBreadcrumb = new DynamicBreadcrumb($pdo);

echo "Testing Dynamic Breadcrumb System:\n\n";

echo "1. MP Master (All MPs):\n";
echo $dynamicBreadcrumb->getBreadcrumbForPage('index.php');
echo "\n\n";

echo "2. MP Detail (with MP ID):\n";
echo $dynamicBreadcrumb->getBreadcrumbForPage('mp_detail.php', ['mp_id' => 'test-mp-id']);
echo "\n\n";

echo "3. MLA Detail (with MP and MLA ID):\n";
echo $dynamicBreadcrumb->getBreadcrumbForPage('mla_detail.php', ['mp_id' => 'test-mp-id', 'mla_id' => 'test-mla-id']);
echo "\n\n";

echo "4. Booth Detail (with all IDs):\n";
echo $dynamicBreadcrumb->getBreadcrumbForPage('booth_detail.php', ['mp_id' => 'test-mp-id', 'mla_id' => 'test-mla-id', 'booth_id' => 'test-booth-id']);
echo "\n\n";

echo "Test completed successfully!\n";
?>
