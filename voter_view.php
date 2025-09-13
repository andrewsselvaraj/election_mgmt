<?php
require_once 'config.php';
require_once 'VoterMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$voterMaster = new VoterMaster($pdo);
$currentUser = $auth->getCurrentUser();

// Handle search and filters
$searchTerm = $_GET['search'] ?? '';
$mlaFilter = $_GET['mla_id'] ?? '';
$wardFilter = $_GET['ward_no'] ?? '';
$partFilter = $_GET['part_no'] ?? '';
$genderFilter = $_GET['gender'] ?? '';

// Get voters based on search and filters
if (!empty($searchTerm)) {
    $voters = $voterMaster->search($searchTerm);
} elseif (!empty($mlaFilter)) {
    $voters = $voterMaster->readByMLA($mlaFilter);
} elseif (!empty($wardFilter)) {
    $voters = $voterMaster->getByWard($wardFilter);
} elseif (!empty($partFilter)) {
    $voters = $voterMaster->getByPart($partFilter);
} else {
    $voters = $voterMaster->readAll();
}

// Apply gender filter if specified
if (!empty($genderFilter) && !empty($voters)) {
    $voters = array_filter($voters, function($voter) use ($genderFilter) {
        return $voter['gender'] === $genderFilter;
    });
}

// Get statistics
$stats = $voterMaster->getStatistics();

// Get MLAs for filter dropdown
$mlas = [];
try {
    $stmt = $pdo->query("SELECT mla_id, mla_name, mla_constituency_name FROM MLA_Master ORDER BY mla_name");
    $mlas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mlas = [];
}

$pageTitle = 'Voter Management - Election Management System';
include 'header.php';
?>

<style>
    .voter-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .stat-card h3 {
        margin: 0 0 10px 0;
        font-size: 2em;
        font-weight: bold;
    }
    
    .stat-card p {
        margin: 0;
        opacity: 0.9;
    }
    
    .search-filters {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    
    .filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
    }
    
    .filter-group label {
        font-weight: 600;
        margin-bottom: 5px;
        color: #333;
    }
    
    .filter-group input,
    .filter-group select {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }
    
    .filter-actions {
        display: flex;
        gap: 10px;
        align-items: end;
    }
    
    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
        font-weight: 500;
    }
    
    .btn-primary {
        background: #007bff;
        color: white;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-success {
        background: #28a745;
        color: white;
    }
    
    .btn:hover {
        opacity: 0.9;
    }
    
    .voter-table {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .voter-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .voter-table th {
        background: #343a40;
        color: white;
        padding: 15px 8px;
        text-align: left;
        font-weight: 600;
        font-size: 12px;
    }
    
    .voter-table td {
        padding: 12px 8px;
        border-bottom: 1px solid #eee;
        font-size: 12px;
    }
    
    .voter-table tr:hover {
        background: #f8f9fa;
    }
    
    .gender-badge {
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 10px;
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
    
    .action-buttons {
        display: flex;
        gap: 3px;
    }
    
    .btn-sm {
        padding: 3px 6px;
        font-size: 10px;
        border-radius: 3px;
        text-decoration: none;
        border: none;
        cursor: pointer;
    }
    
    .btn-warning {
        background: #ffc107;
        color: #212529;
    }
    
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .btn-sm:hover {
        opacity: 0.8;
    }
    
    .no-data {
        text-align: center;
        padding: 40px;
        color: #6c757d;
    }
    
    .add-voter-btn {
        background: #28a745;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        display: inline-block;
        margin-bottom: 20px;
    }
    
    .add-voter-btn:hover {
        background: #218838;
        color: white;
    }
    
    .upload-section {
        background: #e3f2fd;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border: 1px solid #bbdefb;
    }
    
    .upload-section h4 {
        margin: 0 0 10px 0;
        color: #1976d2;
    }
    
    .upload-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }
</style>

<div class="page-header">
    <h1>🗳️ Voter Management</h1>
    <p>Manage voter records and bulk upload functionality</p>
</div>

<!-- Statistics Cards -->
<div class="voter-stats">
    <div class="stat-card">
        <h3><?php echo $stats['total']; ?></h3>
        <p>Total Voters</p>
    </div>
    <div class="stat-card">
        <h3><?php echo count($voters); ?></h3>
        <p>Filtered Results</p>
    </div>
    <div class="stat-card">
        <h3><?php echo count($stats['by_mla']); ?></h3>
        <p>MLA Constituencies</p>
    </div>
    <div class="stat-card">
        <h3><?php echo count($stats['by_ward']); ?></h3>
        <p>Wards</p>
    </div>
</div>

<!-- Upload Section -->
<div class="upload-section">
    <h4>📤 Bulk Upload</h4>
    <div class="upload-actions">
        <a href="voter_upload.php" class="btn btn-primary">📤 Upload Excel File</a>
        <a href="voter_template.csv" class="btn btn-secondary" download>📋 Download Template</a>
        <a href="voter_sample.csv" class="btn btn-secondary" download>📄 Download Sample</a>
    </div>
</div>

<!-- Search and Filters -->
<div class="search-filters">
    <form method="GET" style="display: flex; flex-direction: column; gap: 15px;">
        <div class="filter-row">
            <div class="filter-group">
                <label for="search">Search Voters</label>
                <input type="text" id="search" name="search" placeholder="Search by name, voter ID, father name..." value="<?php echo htmlspecialchars($searchTerm); ?>">
            </div>
            
            <div class="filter-group">
                <label for="mla_id">MLA Constituency</label>
                <select id="mla_id" name="mla_id">
                    <option value="">All MLAs</option>
                    <?php foreach ($mlas as $mla): ?>
                        <option value="<?php echo $mla['mla_id']; ?>" <?php echo $mlaFilter === $mla['mla_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($mla['mla_name'] . ' - ' . $mla['mla_constituency_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="ward_no">Ward Number</label>
                <input type="text" id="ward_no" name="ward_no" placeholder="Enter ward number" value="<?php echo htmlspecialchars($wardFilter); ?>">
            </div>
            
            <div class="filter-group">
                <label for="part_no">Part Number</label>
                <input type="text" id="part_no" name="part_no" placeholder="Enter part number" value="<?php echo htmlspecialchars($partFilter); ?>">
            </div>
            
            <div class="filter-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender">
                    <option value="">All Genders</option>
                    <option value="Male" <?php echo $genderFilter === 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo $genderFilter === 'Female' ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo $genderFilter === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>
        
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">🔍 Search</button>
            <a href="voter_view.php" class="btn btn-secondary">Clear</a>
            <?php if ($auth->hasPermission('voter', 'create')): ?>
                <a href="voter_add.php" class="btn btn-success">➕ Add Voter</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Voters Table -->
<div class="voter-table">
    <?php if (empty($voters)): ?>
        <div class="no-data">
            <h3>No voters found</h3>
            <p>No voters match your search criteria.</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Voter ID</th>
                    <th>Name</th>
                    <th>Father</th>
                    <th>Mother</th>
                    <th>Husband</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>MLA</th>
                    <th>Booth</th>
                    <th>Ward</th>
                    <th>Part</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($voters as $voter): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($voter['voter_id']); ?></strong></td>
                        <td><?php echo htmlspecialchars($voter['voter_name']); ?></td>
                        <td><?php echo htmlspecialchars($voter['father_name']); ?></td>
                        <td><?php echo htmlspecialchars($voter['mother_name']); ?></td>
                        <td><?php echo htmlspecialchars($voter['husband_name'] ?: '-'); ?></td>
                        <td><?php echo $voter['age']; ?></td>
                        <td>
                            <span class="gender-badge gender-<?php echo strtolower($voter['gender']); ?>">
                                <?php echo $voter['gender']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($voter['mla_name'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($voter['polling_station_no'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($voter['ward_no'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($voter['part_no'] ?: '-'); ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($auth->hasPermission('voter', 'read')): ?>
                                    <a href="voter_detail.php?id=<?php echo $voter['voter_unique_ID']; ?>" class="btn-sm btn-primary">View</a>
                                <?php endif; ?>
                                <?php if ($auth->hasPermission('voter', 'update')): ?>
                                    <a href="voter_edit.php?id=<?php echo $voter['voter_unique_ID']; ?>" class="btn-sm btn-warning">Edit</a>
                                <?php endif; ?>
                                <?php if ($auth->hasPermission('voter', 'delete')): ?>
                                    <a href="voter_delete.php?id=<?php echo $voter['voter_unique_ID']; ?>" 
                                       class="btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this voter?')">Delete</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Statistics by Category -->
<?php if (!empty($stats['by_gender']) || !empty($stats['by_mla']) || !empty($stats['by_ward'])): ?>
    <div style="margin-top: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <?php if (!empty($stats['by_gender'])): ?>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <h3>Voters by Gender</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($stats['by_gender'] as $genderStat): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span><?php echo $genderStat['gender']; ?></span>
                            <span style="background: #007bff; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                                <?php echo $genderStat['count']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($stats['by_ward'])): ?>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <h3>Top Wards</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach (array_slice($stats['by_ward'], 0, 5) as $wardStat): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Ward <?php echo $wardStat['ward_no']; ?></span>
                            <span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                                <?php echo $wardStat['count']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($stats['by_mla'])): ?>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <h3>Top MLA Constituencies</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach (array_slice($stats['by_mla'], 0, 5) as $mlaStat): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 12px;"><?php echo htmlspecialchars($mlaStat['mla_name']); ?></span>
                            <span style="background: #ffc107; color: #212529; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                                <?php echo $mlaStat['count']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>
