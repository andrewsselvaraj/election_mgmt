<?php
require_once 'config.php';
require_once 'MPMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$mpMaster = new MPMaster($pdo);
$currentUser = $auth->getCurrentUser();

// Get all MP records
$mps = $mpMaster->readAll();

// Get statistics
$stats = $mpMaster->getStatistics();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MP Master - View Records</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>📊 MP Master - View Records</h1>
            <div class="header-actions">
                <a href="mp_view.php" class="btn btn-primary current-page">📊 MP Master</a>
                <a href="mla_index.php" class="btn btn-secondary">🏛️ MLA Master</a>
                <a href="booth_index.php" class="btn btn-secondary">🏛️ Booth Master</a>
                <a href="voter_view.php" class="btn btn-secondary">🗳️ Voter Information</a>
                <?php if ($auth->hasPermission('user', 'read')): ?>
                    <a href="user_management.php" class="btn btn-warning">👥 Users</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
            </div>
        </div>
        
        <div class="user-info">
            <p>Welcome, <strong><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></strong> 
               (<?php echo implode(', ', $currentUser['roles']); ?>)</p>
        </div>

        <!-- Action Buttons Section -->
        <div class="action-buttons-section" style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #dee2e6;">
            <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                <a href="mp_add.php" class="btn btn-primary">➕ Add MP</a>
                <a href="upload.php" class="btn btn-success">📤 Upload Excel</a>
                <a href="mp_template.csv" class="btn btn-secondary" download>📄 Download Template</a>
                <a href="mp_sample.csv" class="btn btn-secondary" download>📊 Download Sample</a>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-container">
            <div class="search-box">
                <input type="text" id="search-input" placeholder="Search by constituency name or code...">
                <button type="button" id="search-btn" class="btn btn-primary">🔍 Search</button>
                <button type="button" id="clear-search" class="btn btn-secondary">Clear</button>
            </div>
        </div>

        <!-- Records Table -->
        <div class="table-container">
            <table class="data-table" id="mp-table">
                <thead>
                    <tr>
                        <th>Constituency Code</th>
                        <th>Constituency Name</th>
                        <th>State</th>
                        <th>Created By</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mps)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No MP records found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($mps as $mp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($mp['mp_constituency_code']); ?></td>
                                <td><?php echo htmlspecialchars($mp['mp_constituency_name']); ?></td>
                                <td><?php echo htmlspecialchars($mp['state']); ?></td>
                                <td><?php echo htmlspecialchars($mp['created_by']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($mp['created_at'])); ?></td>
                                <td>
                                    <a href="mp_detail.php?mp_id=<?php echo $mp['mp_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    <?php if ($auth->hasPermission('mp', 'update')): ?>
                                        <button onclick="editMP('<?php echo $mp['mp_id']; ?>')" class="btn btn-sm btn-warning">Edit</button>
                                    <?php endif; ?>
                                    <?php if ($auth->hasPermission('mp', 'delete')): ?>
                                        <button onclick="deleteMP('<?php echo $mp['mp_id']; ?>')" class="btn btn-sm btn-danger">Delete</button>
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
            const rows = document.querySelectorAll('#mp-table tbody tr');
            
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
            const rows = document.querySelectorAll('#mp-table tbody tr');
            rows.forEach(row => {
                row.style.display = '';
            });
        });

        // Edit function
        function editMP(mpId) {
            window.location.href = 'mp_edit.php?mp_id=' + mpId;
        }

        // Delete function
        function deleteMP(mpId) {
            if (confirm('Are you sure you want to delete this MP record?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'mp_delete.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'mp_id';
                input.value = mpId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
