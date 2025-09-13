<?php
require_once 'config.php';
require_once 'dynamic_breadcrumb.php';

// Demo with sample data
$demoMpId = 'demo-mp-001';
$demoMlaId = 'demo-mla-001';
$dynamicBreadcrumb = new DynamicBreadcrumb($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contextual Upload Demo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>🎯 Contextual Booth Upload Demo</h1>
        
        <div class="demo-section">
            <h2>📋 How It Works</h2>
            <div class="demo-steps">
                <div class="step">
                    <h3>Step 1: Navigate to MP</h3>
                    <p>Go to MP Master → Click "View MLAs" on any MP</p>
                    <code>📊 MP Master → 📊 [MP Name] → 🏛️ MLA Constituencies</code>
                </div>
                
                <div class="step">
                    <h3>Step 2: Navigate to MLA</h3>
                    <p>Click "View Booths" on any MLA</p>
                    <code>📊 MP Master → 📊 [MP Name] → 🏛️ [MLA Name] → 🏛️ Polling Booths</code>
                </div>
                
                <div class="step">
                    <h3>Step 3: Add Booths</h3>
                    <p>Click "➕ Add Booth" button to add individual booth records</p>
                    <code>📊 MP Master → 📊 [MP Name] → 🏛️ [MLA Name] → ➕ Add Booth</code>
                </div>
            </div>
        </div>
        
        <div class="demo-section">
            <h2>🧭 Dynamic Breadcrumb Navigation</h2>
            <p>Here's how the breadcrumb looks at each step:</p>
            
            <h3>At MP Level:</h3>
            <?php echo $dynamicBreadcrumb->getBreadcrumbForPage('mp_detail.php', ['mp_id' => $demoMpId]); ?>
            
            <h3>At MLA Level:</h3>
            <?php echo $dynamicBreadcrumb->getBreadcrumbForPage('mla_detail.php', ['mp_id' => $demoMpId, 'mla_id' => $demoMlaId]); ?>
            
            <h3>At Upload Level:</h3>
            <?php echo $dynamicBreadcrumb->getBreadcrumbForPage('booth_add.php', ['mp_id' => $demoMpId, 'mla_id' => $demoMlaId]); ?>
        </div>
        
        <div class="demo-section">
            <h2>📤 Simplified Upload Process</h2>
            <div class="comparison">
                <div class="old-way">
                    <h3>❌ Old Way (Complex)</h3>
                    <ul>
                        <li>Select MP from dropdown</li>
                        <li>Select MLA from dropdown</li>
                        <li>Include MP/MLA info in CSV</li>
                        <li>Risk of foreign key mismatches</li>
                        <li>Confusing for users</li>
                    </ul>
                </div>
                
                <div class="new-way">
                    <h3>✅ New Way (Contextual)</h3>
                    <ul>
                        <li>Navigate to desired MLA</li>
                        <li>Click "Add Booth" button to add individual records</li>
                        <li>Only booth data in CSV</li>
                        <li>Automatic context setting</li>
                        <li>User-friendly process</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="demo-section">
            <h2>📋 CSV Template Example</h2>
            <p>Here's the simplified CSV format for booth uploads:</p>
            <div class="code-block">
                <pre>sl_no,polling_station_no,location_name_of_building,polling_areas,polling_station_type
1,001,Government School Building,Area 1-5,Regular
2,002,Community Hall,Area 6-10,Regular
3,003,Primary School,Area 11-15,Auxiliary
4,004,High School,Area 16-20,Special
5,005,Panchayat Office,Area 21-25,Mobile</pre>
            </div>
        </div>
        
        <div class="demo-section">
            <h2>🎯 Key Benefits</h2>
            <div class="benefits-grid">
                <div class="benefit">
                    <h4>🚀 Simplified Process</h4>
                    <p>No need to select MP/MLA from dropdowns</p>
                </div>
                <div class="benefit">
                    <h4>🎯 Context Aware</h4>
                    <p>Automatically knows which MLA to upload to</p>
                </div>
                <div class="benefit">
                    <h4>🛡️ Error Prevention</h4>
                    <p>Eliminates foreign key mismatches</p>
                </div>
                <div class="benefit">
                    <h4>👥 User Friendly</h4>
                    <p>Clear navigation and context display</p>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .demo-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 5px solid #007bff;
        }
        
        .demo-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .step {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .step h3 {
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .step code {
            background: #e9ecef;
            padding: 8px 12px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            display: block;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        .old-way, .new-way {
            padding: 20px;
            border-radius: 8px;
        }
        
        .old-way {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        
        .new-way {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        
        .old-way h3, .new-way h3 {
            margin-bottom: 15px;
        }
        
        .old-way ul, .new-way ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .old-way li, .new-way li {
            margin: 8px 0;
        }
        
        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 15px 0;
        }
        
        .code-block pre {
            margin: 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .benefit {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .benefit h4 {
            color: #007bff;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .comparison {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
