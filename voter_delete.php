<?php
require_once 'config.php';
require_once 'VoterMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!$auth->hasPermission('voter', 'delete')) {
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

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    $result = $voterMaster->delete($voterUniqueId, $currentUser['first_name'] . ' ' . $currentUser['last_name']);
    
    if ($result['success']) {
        header('Location: voter_view.php?message=voter_deleted');
        exit;
    } else {
        $error = $result['message'];
    }
}

$pageTitle = 'Delete Voter - ' . htmlspecialchars($voter['voter_name']);
include 'header.php';
?>

<style>
    .delete-container {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .warning-section {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
        padding: 20px;
        border-radius: 5px;
        margin-bottom: 30px;
    }
    
    .warning-section h3 {
        margin: 0 0 10px 0;
        color: #721c24;
    }
    
    .voter-info {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 5px;
        margin-bottom: 30px;
    }
    
    .voter-info h4 {
        margin: 0 0 15px 0;
        color: #333;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #dee2e6;
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: #666;
    }
    
    .info-value {
        color: #333;
    }
    
    .gender-badge {
        padding: 4px 8px;
        border-radius: 12px;
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
    
    .form-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
    }
    
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
        font-weight: 600;
    }
    
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn:hover {
        opacity: 0.9;
    }
    
    .consequences {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        color: #856404;
        padding: 20px;
        border-radius: 5px;
        margin-bottom: 30px;
    }
    
    .consequences h4 {
        margin: 0 0 15px 0;
        color: #856404;
    }
    
    .consequences ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .consequences li {
        margin-bottom: 8px;
    }
</style>

<div class="page-header">
    <h1>🗑️ Delete Voter</h1>
    <p>Confirm deletion of voter record</p>
</div>

<div class="delete-container">
    <?php if (isset($error)): ?>
        <div class="alert alert-error" style="margin-bottom: 20px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="warning-section">
        <h3>⚠️ Warning: This action cannot be undone!</h3>
        <p>You are about to permanently delete this voter record. This action will:</p>
        <ul>
            <li>Remove the voter's access to the system</li>
            <li>Mark the voter record as deleted (soft delete)</li>
            <li>Preserve audit trail and historical data</li>
        </ul>
    </div>
    
    <div class="voter-info">
        <h4>Voter Information</h4>
        <div class="info-row">
            <span class="info-label">Voter ID:</span>
            <span class="info-value"><?php echo htmlspecialchars($voter['voter_id']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Full Name:</span>
            <span class="info-value"><?php echo htmlspecialchars($voter['voter_name']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Father's Name:</span>
            <span class="info-value"><?php echo htmlspecialchars($voter['father_name']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Age:</span>
            <span class="info-value"><?php echo $voter['age']; ?> years</span>
        </div>
        <div class="info-row">
            <span class="info-label">Gender:</span>
            <span class="info-value">
                <span class="gender-badge gender-<?php echo strtolower($voter['gender']); ?>">
                    <?php echo $voter['gender']; ?>
                </span>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">MLA Constituency:</span>
            <span class="info-value"><?php echo htmlspecialchars($voter['mla_name'] ?: 'Not assigned'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Polling Station:</span>
            <span class="info-value"><?php echo htmlspecialchars($voter['polling_station_no'] ?: 'Not assigned'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Ward Number:</span>
            <span class="info-value"><?php echo htmlspecialchars($voter['ward_no'] ?: 'Not assigned'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Part Number:</span>
            <span class="info-value"><?php echo htmlspecialchars($voter['part_no'] ?: 'Not assigned'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Created:</span>
            <span class="info-value"><?php echo date('M j, Y g:i A', strtotime($voter['created_datetime'])); ?></span>
        </div>
    </div>
    
    <div class="consequences">
        <h4>📋 What happens after deletion:</h4>
        <ul>
            <li>The voter will be marked as "DELETED" in the database</li>
            <li>They will no longer appear in voter lists or searches</li>
            <li>Their voter ID will remain reserved for this MLA constituency</li>
            <li>All audit records will be preserved</li>
            <li>Any data they were associated with will remain in the system</li>
            <li>This action will be logged in the audit trail</li>
        </ul>
    </div>
    
    <form method="POST">
        <div class="form-actions">
            <a href="voter_detail.php?id=<?php echo $voter['voter_unique_ID']; ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" name="confirm_delete" class="btn btn-danger" 
                    onclick="return confirm('Are you absolutely sure you want to delete this voter? This action cannot be undone!')">
                🗑️ Delete Voter
            </button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
