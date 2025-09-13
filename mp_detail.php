<?php
require_once 'config.php';
require_once 'MPMaster.php';
require_once 'MLAMaster.php';
require_once 'Auth.php';
require_once 'dynamic_breadcrumb.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$mpId = $_GET['mp_id'] ?? null;
if (!$mpId) {
    header('Location: index.php');
    exit;
}

$mpMaster = new MPMaster($pdo);
$mlaMaster = new MLAMaster($pdo);
$dynamicBreadcrumb = new DynamicBreadcrumb($pdo);

// Get MP details
$mpData = $mpMaster->readById($mpId);
if (!$mpData) {
    header('Location: index.php');
    exit;
}

// Get MLAs for this MP
$mlaRecords = $mlaMaster->getByMPId($mpId);
$currentUser = $auth->getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MP Details - <?php echo htmlspecialchars($mpData['mp_constituency_name']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>📊 MP Constituency Details</h1>
            <div class="header-actions">
                <a href="mp_view.php" class="btn btn-primary current-page">📊 MP Master</a>
                <a href="mla_view.php" class="btn btn-secondary">🏛️ MLA Master</a>
                <a href="booth_view.php" class="btn btn-secondary">🏛️ Booth Master</a>
                <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
            </div>
        </div>
        
        <div class="user-info">
            <p>Welcome, <strong><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></strong> 
               (<?php echo implode(', ', $currentUser['roles']); ?>)</p>
        </div>
        
        <!-- Dynamic Breadcrumb Navigation -->
        <?php echo $dynamicBreadcrumb->getBreadcrumbForPage('mp_detail.php', ['mp_id' => $mpId]); ?>
        
        <!-- MP Information -->
        <div class="detail-card">
            <h2>📊 <?php echo htmlspecialchars($mpData['mp_constituency_name']); ?></h2>
            <div class="detail-info">
                <p><strong>Constituency Code:</strong> <?php echo htmlspecialchars($mpData['mp_constituency_code']); ?></p>
                <p><strong>State:</strong> <?php echo htmlspecialchars($mpData['state']); ?></p>
                <p><strong>Created By:</strong> <?php echo htmlspecialchars($mpData['created_by']); ?></p>
                <p><strong>Created:</strong> <?php echo date('Y-m-d H:i:s', strtotime($mpData['created_at'])); ?></p>
            </div>
        </div>
        
        <!-- MLA Constituencies -->
        <div class="records-container">
            <h3>🏛️ MLA Constituencies in this MP</h3>
            <div class="records-table">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>MLA Constituency Name</th>
                            <th>Created By</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mlaRecords)): ?>
                            <tr>
                                <td colspan="5" class="no-data">No MLA constituencies found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mlaRecords as $mla): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($mla['mla_constituency_code']); ?></td>
                                    <td><?php echo htmlspecialchars($mla['mla_constituency_name']); ?></td>
                                    <td><?php echo htmlspecialchars($mla['created_by']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($mla['created_at'])); ?></td>
                                    <td class="actions">
                                        <a href="mla_detail.php?mp_id=<?php echo $mpId; ?>&mla_id=<?php echo $mla['mla_id']; ?>" 
                                           class="btn btn-primary">View Booths</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <style>
        .detail-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        
        .detail-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .detail-info p {
            margin: 8px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        
        .dynamic-breadcrumb .breadcrumb-item {
            position: relative;
        }
        
        .dynamic-breadcrumb .breadcrumb-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
    </style>
</body>
</html>
