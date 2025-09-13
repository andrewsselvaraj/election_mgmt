<?php
require_once 'config.php';
require_once 'Auth.php';
require_once 'MPMaster.php';
require_once 'MLAMaster.php';
require_once 'BoothMaster.php';
require_once 'VoterMaster.php';
require_once 'UserMaster.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();

// Get statistics from all modules
$mpMaster = new MPMaster($pdo);
$mlaMaster = new MLAMaster($pdo);
$boothMaster = new BoothMaster($pdo);
$voterMaster = new VoterMaster($pdo);
$userMaster = new UserMaster($pdo);

$mpStats = $mpMaster->getStatistics();
$mlaStats = $mlaMaster->getStatistics();
$boothStats = $boothMaster->getStatistics();
$voterStats = $voterMaster->getStatistics();
$userStats = $userMaster->getStatistics();

$pageTitle = 'Election Management System - Dashboard';
include 'header.php';
?>

<style>
    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .welcome-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 15px;
        margin-bottom: 30px;
        text-align: center;
    }
    
    .welcome-section h1 {
        margin: 0 0 10px 0;
        font-size: 2.5em;
        font-weight: bold;
    }
    
    .welcome-section p {
        margin: 0;
        font-size: 1.2em;
        opacity: 0.9;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-card h3 {
        margin: 0 0 15px 0;
        color: #333;
        font-size: 1.1em;
    }
    
    .stat-number {
        font-size: 2.5em;
        font-weight: bold;
        margin: 0 0 10px 0;
    }
    
    .stat-card.mp { border-top: 4px solid #007bff; }
    .stat-card.mla { border-top: 4px solid #28a745; }
    .stat-card.booth { border-top: 4px solid #ffc107; }
    .stat-card.voter { border-top: 4px solid #dc3545; }
    .stat-card.user { border-top: 4px solid #6f42c1; }
    
    .stat-card.mp .stat-number { color: #007bff; }
    .stat-card.mla .stat-number { color: #28a745; }
    .stat-card.booth .stat-number { color: #ffc107; }
    .stat-card.voter .stat-number { color: #dc3545; }
    .stat-card.user .stat-number { color: #6f42c1; }
    
    .modules-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }
    
    .module-card {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .module-card:hover {
        transform: translateY(-5px);
    }
    
    .module-card h3 {
        margin: 0 0 15px 0;
        color: #333;
        font-size: 1.3em;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .module-card p {
        color: #666;
        margin: 0 0 20px 0;
        line-height: 1.6;
    }
    
    .module-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-primary {
        background: #007bff;
        color: white;
    }
    
    .btn-success {
        background: #28a745;
        color: white;
    }
    
    .btn-warning {
        background: #ffc107;
        color: #212529;
    }
    
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .btn-info {
        background: #17a2b8;
        color: white;
    }
    
    .btn:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }
</style>

<div class="dashboard-container">
    <!-- Statistics Overview -->
    <div class="stats-grid">
        <div class="stat-card mp">
            <h3>📊 MP Constituencies</h3>
            <div class="stat-number"><?php echo $mpStats['total_mp_constituencies'] ?? 0; ?></div>
            <p>Total MP Records</p>
        </div>
        
        <div class="stat-card mla">
            <h3>🏛️ MLA Constituencies</h3>
            <div class="stat-number"><?php echo $mlaStats['total_mla_constituencies'] ?? 0; ?></div>
            <p>Total MLA Records</p>
        </div>
        
        <div class="stat-card booth">
            <h3>🏛️ Polling Booths</h3>
            <div class="stat-number"><?php echo $boothStats['total_booths'] ?? 0; ?></div>
            <p>Total Booth Records</p>
        </div>
        
        <div class="stat-card voter">
            <h3>🗳️ Voters</h3>
            <div class="stat-number"><?php echo $voterStats['total'] ?? 0; ?></div>
            <p>Total Voter Records</p>
        </div>
        
        <div class="stat-card user">
            <h3>👥 Users</h3>
            <div class="stat-number"><?php echo $userStats['total'] ?? 0; ?></div>
            <p>System Users</p>
        </div>
    </div>
    
    <!-- Module Cards -->
    <div class="modules-grid">
        <!-- MP Master Module -->
        <div class="module-card">
            <h3>📊 MP Master</h3>
            <p>Manage Member of Parliament constituencies, their details, and associated data.</p>
            <div class="module-actions">
                <a href="mp_view.php" class="btn btn-primary">View MPs</a>
                <a href="mp_add.php" class="btn btn-success">Add MP</a>
                <a href="upload.php" class="btn btn-info">Upload Data</a>
            </div>
        </div>
        
        <!-- MLA Master Module -->
        <div class="module-card">
            <h3>🏛️ MLA Master</h3>
            <p>Manage Member of Legislative Assembly constituencies and their information.</p>
            <div class="module-actions">
                <a href="mla_index.php" class="btn btn-primary">View MLAs</a>
                <a href="mla_add.php" class="btn btn-success">Add MLA</a>
                <a href="mla_upload.php" class="btn btn-info">Upload Data</a>
            </div>
        </div>
        
        <!-- Booth Master Module -->
        <div class="module-card">
            <h3>🏛️ Booth Master</h3>
            <p>Manage polling booths, their locations, and associated details.</p>
            <div class="module-actions">
                <a href="booth_index.php" class="btn btn-primary">View Booths</a>
                <a href="booth_add.php" class="btn btn-success">Add Booth</a>
                <a href="booth_upload.php" class="btn btn-info">Upload Data</a>
            </div>
        </div>
        
        <!-- Voter Information Module -->
        <div class="module-card">
            <h3>🗳️ Voter Information</h3>
            <p>Manage voter records, demographics, and voting information.</p>
            <div class="module-actions">
                <a href="voter_view.php" class="btn btn-primary">View Voters</a>
                <a href="voter_add.php" class="btn btn-success">Add Voter</a>
                <a href="voter_upload.php" class="btn btn-info">Upload Data</a>
            </div>
        </div>
        
        <!-- User Management Module -->
        <?php if ($auth->hasPermission('user', 'read')): ?>
        <div class="module-card">
            <h3>👥 User Management</h3>
            <p>Manage system users, roles, and permissions.</p>
            <div class="module-actions">
                <a href="user_view.php" class="btn btn-primary">View Users</a>
                <a href="user_add.php" class="btn btn-success">Add User</a>
                <a href="user_management.php" class="btn btn-info">Manage</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>