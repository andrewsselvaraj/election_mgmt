<?php
require_once 'config.php';
require_once 'MPMaster.php';
require_once 'MLAMaster.php';
require_once 'BoothMaster.php';
require_once 'Auth.php';
require_once 'dynamic_breadcrumb.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$mpId = $_GET['mp_id'] ?? null;
$mlaId = $_GET['mla_id'] ?? null;
$boothId = $_GET['booth_id'] ?? null;

if (!$mpId || !$mlaId || !$boothId) {
    header('Location: index.php');
    exit;
}

$mpMaster = new MPMaster($pdo);
$mlaMaster = new MLAMaster($pdo);
$boothMaster = new BoothMaster($pdo);
$dynamicBreadcrumb = new DynamicBreadcrumb($pdo);

// Get MP details
$mpData = $mpMaster->readById($mpId);
if (!$mpData) {
    header('Location: index.php');
    exit;
}

// Get MLA details
$mlaData = $mlaMaster->readById($mlaId);
if (!$mlaData || $mlaData['mp_id'] != $mpId) {
    header('Location: mp_detail.php?mp_id=' . $mpId);
    exit;
}

// Get Booth details
$boothData = $boothMaster->readById($boothId);
if (!$boothData || $boothData['mla_id'] != $mlaId) {
    header('Location: mla_detail.php?mp_id=' . $mpId . '&mla_id=' . $mlaId);
    exit;
}

$currentUser = $auth->getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booth Details - <?php echo htmlspecialchars($boothData['polling_station_no']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>🏛️ Polling Booth Details</h1>
            <div class="header-actions">
                <a href="mla_detail.php?mp_id=<?php echo $mpId; ?>&mla_id=<?php echo $mlaId; ?>" class="btn btn-secondary">← Back to MLA</a>
                <a href="mp_detail.php?mp_id=<?php echo $mpId; ?>" class="btn btn-secondary">← Back to MP</a>
                <a href="index.php" class="btn btn-secondary">📊 All MPs</a>
                <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
            </div>
        </div>
        
        <div class="user-info">
            <p>Welcome, <strong><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></strong> 
               (<?php echo implode(', ', $currentUser['roles']); ?>)</p>
        </div>
        
        <!-- Dynamic Breadcrumb Navigation -->
        <?php echo $dynamicBreadcrumb->getBreadcrumbForPage('booth_detail.php', ['mp_id' => $mpId, 'mla_id' => $mlaId, 'booth_id' => $boothId]); ?>
        
        <!-- Booth Information -->
        <div class="detail-card">
            <h2>🏛️ Polling Station <?php echo htmlspecialchars($boothData['polling_station_no']); ?></h2>
            <div class="detail-info">
                <p><strong>Serial Number:</strong> <?php echo htmlspecialchars($boothData['sl_no']); ?></p>
                <p><strong>Station Number:</strong> <?php echo htmlspecialchars($boothData['polling_station_no']); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($boothData['location_name_of_building']); ?></p>
                <p><strong>Station Type:</strong> 
                    <span class="station-type <?php echo strtolower($boothData['polling_station_type']); ?>">
                        <?php echo htmlspecialchars($boothData['polling_station_type']); ?>
                    </span>
                </p>
                <p><strong>Polling Areas:</strong> <?php echo htmlspecialchars($boothData['polling_areas'] ?: 'Not specified'); ?></p>
                <p><strong>MLA Constituency:</strong> <?php echo htmlspecialchars($mlaData['mla_constituency_name']); ?></p>
                <p><strong>MP Constituency:</strong> <?php echo htmlspecialchars($mpData['mp_constituency_name']); ?></p>
                <p><strong>State:</strong> <?php echo htmlspecialchars($mpData['state']); ?></p>
                <p><strong>Status:</strong> 
                    <span class="status-badge <?php echo strtolower($boothData['status']); ?>">
                        <?php echo htmlspecialchars($boothData['status']); ?>
                    </span>
                </p>
                <p><strong>Created By:</strong> <?php echo htmlspecialchars($boothData['created_by']); ?></p>
                <p><strong>Created:</strong> <?php echo date('Y-m-d H:i:s', strtotime($boothData['created_datetime'])); ?></p>
                <?php if ($boothData['updated_by']): ?>
                    <p><strong>Last Updated By:</strong> <?php echo htmlspecialchars($boothData['updated_by']); ?></p>
                    <p><strong>Last Updated:</strong> <?php echo date('Y-m-d H:i:s', strtotime($boothData['updated_datetime'])); ?></p>
                <?php endif; ?>
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
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .detail-info p {
            margin: 8px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
        }
        
        .station-type {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .station-type.regular {
            background: #d4edda;
            color: #155724;
        }
        
        .station-type.auxiliary {
            background: #fff3cd;
            color: #856404;
        }
        
        .station-type.special {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .station-type.mobile {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-badge.deleted {
            background: #e2e3e5;
            color: #6c757d;
        }
    </style>
</body>
</html>
