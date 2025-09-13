<?php
require_once 'config.php';
require_once 'VoterMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!$auth->hasPermission('voter', 'read')) {
    header('Location: voter_view.php?error=no_permission');
    exit;
}

$voterMaster = new VoterMaster($pdo);
$voterUniqueId = $_GET['id'] ?? '';

if (empty($voterUniqueId)) {
    header('Location: voter_view.php?error=invalid_voter');
    exit;
}

$voter = $voterMaster->readById($voterUniqueId);

if (!$voter) {
    header('Location: voter_view.php?error=voter_not_found');
    exit;
}

$currentUser = $auth->getCurrentUser();

$pageTitle = 'Voter Details - ' . htmlspecialchars($voter['voter_name']);
include 'header.php';
?>

<style>
    .voter-detail-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .voter-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .voter-info h1 {
        margin: 0 0 10px 0;
        font-size: 2em;
    }
    
    .voter-info p {
        margin: 0;
        opacity: 0.9;
    }
    
    .voter-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
    }
    
    .btn-primary {
        background: rgba(255,255,255,0.2);
        color: white;
        border: 1px solid rgba(255,255,255,0.3);
    }
    
    .btn-warning {
        background: #ffc107;
        color: #212529;
    }
    
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .btn:hover {
        opacity: 0.9;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }
    
    .detail-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .detail-card h3 {
        margin: 0 0 20px 0;
        color: #333;
        border-bottom: 2px solid #f8f9fa;
        padding-bottom: 10px;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-weight: 600;
        color: #666;
        min-width: 120px;
    }
    
    .detail-value {
        color: #333;
        text-align: right;
        flex: 1;
    }
    
    .gender-badge {
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
    }
    
    .gender-male {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .gender-female {
        background: #f8d7da;
        color: #721c24;
    }
    
    .gender-other {
        background: #d4edda;
        color: #155724;
    }
    
    .admin-section {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .activity-section {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .activity-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-info {
        flex: 1;
    }
    
    .activity-title {
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }
    
    .activity-description {
        color: #666;
        font-size: 14px;
    }
    
    .activity-time {
        color: #999;
        font-size: 12px;
        white-space: nowrap;
    }
    
    .no-activity {
        text-align: center;
        color: #6c757d;
        padding: 40px;
    }
    
    .full-width {
        grid-column: 1 / -1;
    }
</style>

<div class="voter-detail-container">
    <!-- Voter Header -->
    <div class="voter-header">
        <div class="voter-info">
            <h1><?php echo htmlspecialchars($voter['voter_name']); ?></h1>
            <p>Voter ID: <?php echo htmlspecialchars($voter['voter_id']); ?> • <?php echo htmlspecialchars($voter['mla_name'] ?: 'Unknown MLA'); ?></p>
        </div>
        <div class="voter-actions">
            <?php if ($auth->hasPermission('voter', 'update')): ?>
                <a href="voter_edit.php?id=<?php echo $voter['voter_unique_ID']; ?>" class="btn btn-warning">Edit Voter</a>
            <?php endif; ?>
            <a href="voter_view.php" class="btn btn-primary">Back to Voters</a>
        </div>
    </div>
    
    <!-- Voter Details Grid -->
    <div class="detail-grid">
        <!-- Personal Information -->
        <div class="detail-card">
            <h3>👤 Personal Information</h3>
            <div class="detail-row">
                <span class="detail-label">Voter ID:</span>
                <span class="detail-value"><?php echo htmlspecialchars($voter['voter_id']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Full Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($voter['voter_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Father's Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($voter['father_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Mother's Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($voter['mother_name'] ?: 'Not provided'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Husband's Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($voter['husband_name'] ?: 'Not provided'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Age:</span>
                <span class="detail-value"><?php echo $voter['age']; ?> years</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Gender:</span>
                <span class="detail-value">
                    <span class="gender-badge gender-<?php echo strtolower($voter['gender']); ?>">
                        <?php echo $voter['gender']; ?>
                    </span>
                </span>
            </div>
        </div>
        
        <!-- Contact Information -->
        <div class="detail-card">
            <h3>📞 Contact Information</h3>
            <div class="detail-row">
                <span class="detail-label">Address:</span>
                <span class="detail-value"><?php echo htmlspecialchars($voter['address'] ?: 'Not provided'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone:</span>
                <span class="detail-value"><?php echo htmlspecialchars($voter['phone'] ?: 'Not provided'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value"><?php echo htmlspecialchars($voter['email'] ?: 'Not provided'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Administrative Information -->
    <div class="detail-grid">
        <!-- MLA & Booth Information -->
        <div class="detail-card">
            <h3>🏛️ Constituency & Booth</h3>
            <div class="detail-row">
                <span class="detail-label">MLA Constituency:</span>
                <span class="detail-value"><?php echo htmlspecialchars($voter['mla_name'] ?: 'Not assigned'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Constituency Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($voter['mla_constituency_name'] ?: 'Not assigned'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Polling Station:</span>
                <span class="detail-value"><?php echo htmlspecialchars($voter['polling_station_no'] ?: 'Not assigned'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Booth Location:</span>
                <span class="detail-value"><?php echo htmlspecialchars($voter['location_name_of_building'] ?: 'Not assigned'); ?></span>
            </div>
        </div>
        
        <!-- Administrative Details -->
        <div class="detail-card">
            <h3>📋 Administrative Details</h3>
            <div class="detail-row">
                <span class="detail-label">Ward Number:</span>
                <span class="detail-value"><?php echo htmlspecialchars($voter['ward_no'] ?: 'Not assigned'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Part Number:</span>
                <span class="detail-value"><?php echo htmlspecialchars($voter['part_no'] ?: 'Not assigned'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value">
                    <span style="color: #28a745; font-weight: bold;">Active</span>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Activity Timeline -->
    <div class="activity-section full-width">
        <h3>📊 Record Activity</h3>
        <div class="activity-item">
            <div class="activity-info">
                <div class="activity-title">Voter Record Created</div>
                <div class="activity-description">Voter record was created by <?php echo htmlspecialchars($voter['created_by']); ?></div>
            </div>
            <div class="activity-time">
                <?php echo date('M j, Y g:i A', strtotime($voter['created_datetime'])); ?>
            </div>
        </div>
        
        <?php if ($voter['updated_datetime']): ?>
            <div class="activity-item">
                <div class="activity-info">
                    <div class="activity-title">Last Updated</div>
                    <div class="activity-description">Record was last modified by <?php echo htmlspecialchars($voter['updated_by'] ?: 'System'); ?></div>
                </div>
                <div class="activity-time">
                    <?php echo date('M j, Y g:i A', strtotime($voter['updated_datetime'])); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!$voter['updated_datetime']): ?>
            <div class="no-activity">
                <p>No recent updates recorded for this voter.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
