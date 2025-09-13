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
    <title>Upload System Demo</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .demo-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .demo-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 5px solid #007bff;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .feature-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .feature-card h3 {
            color: #007bff;
            margin-bottom: 15px;
        }
        
        .step-list {
            list-style: none;
            padding: 0;
        }
        
        .step-list li {
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .step-list li:last-child {
            border-bottom: none;
        }
        
        .step-number {
            background: #007bff;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .comparison-table th,
        .comparison-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .comparison-table th {
            background: #007bff;
            color: white;
            font-weight: 600;
        }
        
        .comparison-table tr:hover {
            background: #f8f9fa;
        }
        
        .pros-cons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        .pros, .cons {
            padding: 20px;
            border-radius: 8px;
        }
        
        .pros {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        
        .cons {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        
        .pros h4, .cons h4 {
            margin-bottom: 15px;
        }
        
        .pros ul, .cons ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .pros li, .cons li {
            margin: 8px 0;
        }
        
        .demo-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        @media (max-width: 768px) {
            .pros-cons {
                grid-template-columns: 1fr;
            }
            
            .demo-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="demo-container">
            <h1>🚀 Advanced File Upload System Demo</h1>
            
            <div class="demo-section">
                <h2>🎯 Two Upload Methods Available</h2>
                <div class="feature-grid">
                    <div class="feature-card">
                        <h3>📤 Quick Upload</h3>
                        <p>Simple upload for files with standard column names</p>
                        <ul class="step-list">
                            <li><span class="step-number">1</span>Upload CSV/Excel file</li>
                            <li><span class="step-number">2</span>System auto-detects columns</li>
                            <li><span class="step-number">3</span>Data is processed immediately</li>
                        </ul>
                        <p><strong>Best for:</strong> Standard format files</p>
                    </div>
                    
                    <div class="feature-card">
                        <h3>📤 Excel Upload & Preview</h3>
                        <p>Direct Excel/CSV upload with validation and preview</p>
                        <ul class="step-list">
                            <li><span class="step-number">1</span>Upload any CSV/Excel file</li>
                            <li><span class="step-number">2</span>Preview data (first 10 rows)</li>
                            <li><span class="step-number">3</span>Map columns to database fields</li>
                            <li><span class="step-number">4</span>Preview mapping before processing</li>
                            <li><span class="step-number">5</span>Process with full control</li>
                        </ul>
                        <p><strong>Best for:</strong> Custom format files</p>
                    </div>
                </div>
            </div>
            
            <div class="demo-section">
                <h2>📊 Feature Comparison</h2>
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Feature</th>
                            <th>Quick Upload</th>
                            <th>Excel Upload & Preview</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>File Format Support</strong></td>
                            <td>CSV, XLS, XLSX</td>
                            <td>CSV, XLS, XLSX</td>
                        </tr>
                        <tr>
                            <td><strong>Column Detection</strong></td>
                            <td>Automatic</td>
                            <td>Manual mapping</td>
                        </tr>
                        <tr>
                            <td><strong>Data Preview</strong></td>
                            <td>No</td>
                            <td>Yes (first 10 rows)</td>
                        </tr>
                        <tr>
                            <td><strong>Column Mapping</strong></td>
                            <td>Fixed format</td>
                            <td>Flexible mapping</td>
                        </tr>
                        <tr>
                            <td><strong>Error Handling</strong></td>
                            <td>Basic</td>
                            <td>Detailed per row</td>
                        </tr>
                        <tr>
                            <td><strong>User Control</strong></td>
                            <td>Minimal</td>
                            <td>Full control</td>
                        </tr>
                        <tr>
                            <td><strong>Processing Speed</strong></td>
                            <td>Fast</td>
                            <td>Moderate</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="demo-section">
                <h2>⚖️ Pros & Cons</h2>
                <div class="pros-cons">
                    <div class="pros">
                        <h4>✅ Quick Upload</h4>
                        <ul>
                            <li>Fast and simple</li>
                            <li>No user configuration needed</li>
                            <li>Works with standard formats</li>
                            <li>One-click processing</li>
                        </ul>
                    </div>
                    <div class="cons">
                        <h4>❌ Quick Upload</h4>
                        <ul>
                            <li>Requires specific column names</li>
                            <li>No data preview</li>
                            <li>Limited error handling</li>
                            <li>Less flexible</li>
                        </ul>
                    </div>
                </div>
                
                <div class="pros-cons">
                    <div class="pros">
                        <h4>✅ Excel Upload & Preview</h4>
                        <ul>
                            <li>Works with any column names</li>
                            <li>Data preview before processing</li>
                            <li>Detailed error reporting</li>
                            <li>Full user control</li>
                            <li>Mapping preview</li>
                        </ul>
                    </div>
                    <div class="cons">
                        <h4>❌ Excel Upload & Preview</h4>
                        <ul>
                            <li>More steps required</li>
                            <li>User needs to map columns</li>
                            <li>Slightly slower process</li>
                            <li>More complex interface</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="demo-section">
                <h2>📋 Sample Data Format</h2>
                <p>Here's an example of how your CSV file should look:</p>
                <div style="background: #2d3748; color: #e2e8f0; padding: 20px; border-radius: 8px; overflow-x: auto; margin: 15px 0;">
                    <pre>Serial_Number,Station_Number,Building_Location,Polling_Areas_Description,Station_Type
1,PS001,Government Primary School,Areas 1-5,Regular
2,PS002,Community Hall,Areas 6-10,Auxiliary
3,PS003,High School Building,Areas 11-15,Special
4,PS004,Panchayat Office,Areas 16-20,Mobile
5,PS005,Private School,Areas 21-25,Regular</pre>
                </div>
                <p><strong>Note:</strong> Column names can be anything - the mapping system will help you connect them to the correct database fields.</p>
            </div>
            
            <div class="demo-section">
                <h2>🎮 Try It Out</h2>
                <div class="demo-buttons">
                    <a href="index.php" class="btn btn-primary">🏠 Go to MP Master</a>
                    <a href="booth_index.php" class="btn btn-secondary">📋 Manage Booths</a>
                    <a href="sample_booth_data.csv" class="btn btn-success" download>📥 Download Sample CSV</a>
                </div>
                <p style="text-align: center; margin-top: 20px; color: #6c757d;">
                    <strong>How to test:</strong> Navigate to MP Master → View MLAs → View Booths → Upload Booth Data
                </p>
            </div>
        </div>
    </div>
</body>
</html>
