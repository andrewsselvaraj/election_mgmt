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

if (!$mpId || !$mlaId) {
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

// Get Booths for this MLA
$boothRecords = $boothMaster->getByMLAId($mlaId);
$currentUser = $auth->getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MLA Details - <?php echo htmlspecialchars($mlaData['mla_constituency_name']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>🏛️ MLA Constituency Details</h1>
            <div class="header-actions">
                <a href="mp_detail.php?mp_id=<?php echo $mpId; ?>" class="btn btn-secondary">← Back to MP</a>
                <a href="index.php" class="btn btn-secondary">📊 All MPs</a>
                <a href="mla_index.php" class="btn btn-secondary">🏛️ All MLAs</a>
                <a href="booth_index.php" class="btn btn-secondary">🏛️ All Booths</a>
                <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
            </div>
        </div>
        
        <div class="user-info">
            <p>Welcome, <strong><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></strong> 
               (<?php echo implode(', ', $currentUser['roles']); ?>)</p>
        </div>
        
        <!-- Dynamic Breadcrumb Navigation -->
        <?php echo $dynamicBreadcrumb->getBreadcrumbForPage('mla_detail.php', ['mp_id' => $mpId, 'mla_id' => $mlaId]); ?>
        
        <!-- MLA Information -->
        <div class="detail-card">
            <h2>🏛️ <?php echo htmlspecialchars($mlaData['mla_constituency_name']); ?></h2>
            <div class="detail-info">
                <p><strong>MLA Code:</strong> <?php echo htmlspecialchars($mlaData['mla_constituency_code']); ?></p>
                <p><strong>MP Constituency:</strong> <?php echo htmlspecialchars($mpData['mp_constituency_name']); ?></p>
                <p><strong>State:</strong> <?php echo htmlspecialchars($mpData['state']); ?></p>
                <p><strong>Created By:</strong> <?php echo htmlspecialchars($mlaData['created_by']); ?></p>
                <p><strong>Created:</strong> <?php echo date('Y-m-d H:i:s', strtotime($mlaData['created_at'])); ?></p>
            </div>
        </div>
        
        <!-- Polling Booths -->
        <div class="records-container">
            <div class="section-header">
                <h3>🏛️ Polling Booths in this MLA Constituency</h3>
                <div class="section-actions">
                    <a href="contextual_booth_upload.php?mp_id=<?php echo $mpId; ?>&mla_id=<?php echo $mlaId; ?>" class="btn btn-primary">📤 Quick Upload</a>
                    <a href="file_upload_preview.php?mp_id=<?php echo $mpId; ?>&mla_id=<?php echo $mlaId; ?>" class="btn btn-secondary">🗺️ Upload with Mapping</a>
                </div>
            </div>
            <div class="records-table">
                <table>
                    <thead>
                        <tr>
                            <th>Sl No</th>
                            <th>Station No</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Areas</th>
                            <th>Created By</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($boothRecords)): ?>
                            <tr>
                                <td colspan="8" class="no-data">No polling booths found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($boothRecords as $booth): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booth['sl_no']); ?></td>
                                    <td><?php echo htmlspecialchars($booth['polling_station_no']); ?></td>
                                    <td><?php echo htmlspecialchars($booth['location_name_of_building']); ?></td>
                                    <td>
                                        <span class="station-type <?php echo strtolower($booth['polling_station_type']); ?>">
                                            <?php echo htmlspecialchars($booth['polling_station_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($booth['polling_areas'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($booth['created_by']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($booth['created_datetime'])); ?></td>
                                    <td class="actions">
                                        <a href="booth_detail.php?mp_id=<?php echo $mpId; ?>&mla_id=<?php echo $mlaId; ?>&booth_id=<?php echo $booth['booth_id']; ?>" 
                                           class="btn btn-primary">View Details</a>
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
            border-left: 4px solid #28a745;
        }
        
        .station-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
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
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .section-actions {
            display: flex;
            gap: 10px;
        }
    </style>
</body>
</html>
