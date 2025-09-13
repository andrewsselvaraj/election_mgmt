<?php
require_once 'config.php';
require_once 'MLAMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$mlaMaster = new MLAMaster($pdo);
$currentUser = $auth->getCurrentUser();

// Get all MLA records with MP details
$mlas = $mlaMaster->readAllWithMP();

// Get statistics
$stats = $mlaMaster->getStatistics();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MLA Master - View Records</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>🏛️ MLA Master - View Records</h1>
            <div class="header-actions">
                <a href="mp_view.php" class="btn btn-secondary">📊 MP Master</a>
                <a href="mla_view.php" class="btn btn-primary current-page">🏛️ MLA Master</a>
                <a href="booth_index.php" class="btn btn-secondary">🏛️ Booth Master</a>
                <a href="voter_view.php" class="btn btn-secondary">🗳️ Voter Information</a>
                <?php if ($auth->hasPermission('users', 'read')): ?>
                    <a href="user_management.php" class="btn btn-warning">👥 Users</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
            </div>
        </div>
        
        <div class="user-info">
            <p>Welcome, <strong><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></strong> 
               (<?php echo implode(', ', $currentUser['roles']); ?>)</p>
        </div>

        <!-- Statistics -->
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total MLA Constituencies</h3>
                <p class="stat-number"><?php echo $stats['total_mla_constituencies']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Active Records</h3>
                <p class="stat-number"><?php echo $stats['active_records']; ?></p>
            </div>
            <div class="stat-card">
                <h3>MP Constituencies</h3>
                <p class="stat-number"><?php echo $stats['total_mp_constituencies']; ?></p>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="upload-section" style="background: #e3f2fd; padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #bbdefb;">
            <h3 style="margin: 0 0 15px 0; color: #1976d2;">📤 Bulk Upload</h3>
            <p style="margin: 0 0 15px 0; color: #666;">Upload MLA data from Excel or CSV files</p>
            <div style="display: flex; gap: 15px; align-items: center;">
                <a href="mla_upload.php" class="btn btn-primary">📤 Upload Excel</a>
                <a href="mla_template.csv" class="btn btn-secondary" download>📄 Download Template</a>
                <a href="mla_sample.csv" class="btn btn-secondary" download>📊 Download Sample</a>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-container">
            <div class="search-box">
                <input type="text" id="search-input" placeholder="Search by MLA name, code, or MP constituency...">
                <button type="button" id="search-btn" class="btn btn-primary">🔍 Search</button>
                <button type="button" id="clear-search" class="btn btn-secondary">Clear</button>
            </div>
        </div>

        <!-- Records Table -->
        <div class="table-container">
            <table class="data-table" id="mla-table">
                <thead>
                    <tr>
                        <th>MLA Code</th>
                        <th>MLA Constituency</th>
                        <th>MP Constituency</th>
                        <th>State</th>
                        <th>Created By</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mlas)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No MLA records found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($mlas as $mla): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($mla['mla_constituency_code']); ?></td>
                                <td><?php echo htmlspecialchars($mla['mla_constituency_name']); ?></td>
                                <td><?php echo htmlspecialchars($mla['mp_constituency_name']); ?></td>
                                <td><?php echo htmlspecialchars($mla['state']); ?></td>
                                <td><?php echo htmlspecialchars($mla['created_by']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($mla['created_at'])); ?></td>
                                <td>
                                    <a href="mla_detail.php?mp_id=<?php echo $mla['mp_id']; ?>&mla_id=<?php echo $mla['mla_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    <?php if ($auth->hasPermission('mla', 'update')): ?>
                                        <button onclick="editMLA('<?php echo $mla['mla_id']; ?>')" class="btn btn-sm btn-warning">Edit</button>
                                    <?php endif; ?>
                                    <?php if ($auth->hasPermission('mla', 'delete')): ?>
                                        <button onclick="deleteMLA('<?php echo $mla['mla_id']; ?>')" class="btn btn-sm btn-danger">Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <button id="prev-page" class="btn btn-secondary">Previous</button>
            <span id="page-info">Page 1 of 1</span>
            <button id="next-page" class="btn btn-secondary">Next</button>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('search-btn').addEventListener('click', function() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const rows = document.querySelectorAll('#mla-table tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Clear search
        document.getElementById('clear-search').addEventListener('click', function() {
            document.getElementById('search-input').value = '';
            const rows = document.querySelectorAll('#mla-table tbody tr');
            rows.forEach(row => {
                row.style.display = '';
            });
        });

        // Edit function
        function editMLA(mlaId) {
            window.location.href = 'mla_edit.php?mla_id=' + mlaId;
        }

        // Delete function
        function deleteMLA(mlaId) {
            if (confirm('Are you sure you want to delete this MLA record?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'mla_delete.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'mla_id';
                input.value = mlaId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
