<?php
require_once 'config.php';
require_once 'dynamic_breadcrumb.php';

$dynamicBreadcrumb = new DynamicBreadcrumb($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Breadcrumb Test</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Dynamic Breadcrumb Navigation Test</h1>
        
        <h2>Main Pages</h2>
        <h3>MP Master (All MPs)</h3>
        <?php echo $dynamicBreadcrumb->getBreadcrumbForPage('index.php'); ?>
        
        <h3>MLA Master (All MLAs)</h3>
        <?php echo $dynamicBreadcrumb->getBreadcrumbForPage('mla_index.php'); ?>
        
        <h3>Booth Master (All Booths)</h3>
        <?php echo $dynamicBreadcrumb->getBreadcrumbForPage('booth_index.php'); ?>
        
        <h2>Data-Driven Examples</h2>
        <h3>MP Detail (with MP ID)</h3>
        <?php echo $dynamicBreadcrumb->getBreadcrumbForPage('mp_detail.php', ['mp_id' => 'sample-mp-id']); ?>
        
        <h3>MLA Detail (with MP and MLA ID)</h3>
        <?php echo $dynamicBreadcrumb->getBreadcrumbForPage('mla_detail.php', ['mp_id' => 'sample-mp-id', 'mla_id' => 'sample-mla-id']); ?>
        
        <h3>Booth Detail (with MP, MLA and Booth ID)</h3>
        <?php echo $dynamicBreadcrumb->getBreadcrumbForPage('booth_detail.php', ['mp_id' => 'sample-mp-id', 'mla_id' => 'sample-mla-id', 'booth_id' => 'sample-booth-id']); ?>
        
        <h2>Navigation Flow</h2>
        <div class="navigation-flow">
            <div class="flow-step">
                <h4>1. Start at MP Master</h4>
                <p>Click on any MP to see its MLA constituencies</p>
                <code>📊 MP Master → 📊 [MP Name] → 🏛️ MLA Constituencies</code>
            </div>
            
            <div class="flow-step">
                <h4>2. Click on MLA</h4>
                <p>Click on any MLA to see its polling booths</p>
                <code>📊 MP Master → 📊 [MP Name] → 🏛️ [MLA Name] → 🏛️ Polling Booths</code>
            </div>
            
            <div class="flow-step">
                <h4>3. Click on Booth</h4>
                <p>Click on any booth to see detailed information</p>
                <code>📊 MP Master → 📊 [MP Name] → 🏛️ [MLA Name] → 🏛️ [Booth Number]</code>
            </div>
        </div>
        
        <h2>Features</h2>
        <ul>
            <li>✅ <strong>Data-Driven:</strong> Breadcrumbs show actual data names, not generic labels</li>
            <li>✅ <strong>Hierarchical Navigation:</strong> Click through MP → MLA → Booth levels</li>
            <li>✅ <strong>Context Aware:</strong> Shows current position in the data hierarchy</li>
            <li>✅ <strong>Clickable Links:</strong> Each breadcrumb item links to the appropriate level</li>
            <li>✅ <strong>Visual Indicators:</strong> Different styling for different levels</li>
            <li>✅ <strong>Responsive Design:</strong> Works on all screen sizes</li>
        </ul>
    </div>
    
    <style>
        .navigation-flow {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .flow-step {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .flow-step h4 {
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .flow-step code {
            background: #e9ecef;
            padding: 8px 12px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            display: block;
            margin-top: 10px;
        }
        
        ul {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        li {
            margin: 10px 0;
            padding: 5px 0;
        }
    </style>
</body>
</html>
