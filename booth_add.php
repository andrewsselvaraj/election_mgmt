<?php
require_once 'config.php';
require_once 'BoothMaster.php';
require_once 'MLAMaster.php';
require_once 'MPMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!$auth->hasPermission('booth', 'create')) {
    header('Location: booth_view.php?error=no_permission');
    exit;
}

$boothMaster = new BoothMaster($pdo);
$mlaMaster = new MLAMaster($pdo);
$mpMaster = new MPMaster($pdo);
$message = '';
$messageType = '';
$currentUser = $auth->getCurrentUser();

// Get all MLAs with MP details for dropdown
$mlas = $mlaMaster->readAllWithMP();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $data = [
            'mla_id' => $_POST['mla_id'],
            'sl_no' => $_POST['sl_no'],
            'polling_station_no' => $_POST['polling_station_no'],
            'location_name_of_building' => $_POST['location_name_of_building'],
            'polling_areas' => $_POST['polling_areas'],
            'polling_station_type' => $_POST['polling_station_type'],
            'notes' => $_POST['notes'] ?? null,
            'created_by' => $currentUser['first_name'] . ' ' . $currentUser['last_name']
        ];
        
        if ($boothMaster->create($data)) {
            $message = 'Booth record created successfully!';
            $messageType = 'success';
            // Clear form data after successful creation
            $_POST = [];
        } else {
            $message = 'Failed to create record!';
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booth Master - Add Record</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>🏛️ Booth Master - Add New Record</h1>
            <div class="header-actions">
                <a href="mp_view.php" class="btn btn-secondary">📊 MP Master</a>
                <a href="mla_view.php" class="btn btn-secondary">🏛️ MLA Master</a>
                <a href="booth_view.php" class="btn btn-secondary">🏛️ View Booths</a>
                <a href="booth_add.php" class="btn btn-primary current-page">➕ Add Booth</a>
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

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add Form -->
        <div class="form-container">
            <h2>Add New Booth Record</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="mla_id">MLA Constituency: <span class="required">*</span></label>
                    <select id="mla_id" name="mla_id" required>
                        <option value="">Select MLA Constituency</option>
                        <?php foreach ($mlas as $mla): ?>
                            <option value="<?php echo $mla['mla_id']; ?>" 
                                    <?php echo (($_POST['mla_id'] ?? '') == $mla['mla_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($mla['mla_constituency_name'] . ' (' . $mla['mla_constituency_code'] . ') - ' . $mla['mp_constituency_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-help">Select the MLA constituency this booth belongs to</small>
                </div>
                
                <div class="form-group">
                    <label for="sl_no">Serial Number: <span class="required">*</span></label>
                    <input type="number" id="sl_no" name="sl_no" 
                           value="<?php echo htmlspecialchars($_POST['sl_no'] ?? ''); ?>" 
                           required>
                    <small class="form-help">Enter the serial number for this booth</small>
                </div>
                
                <div class="form-group">
                    <label for="polling_station_no">Polling Station Number: <span class="required">*</span></label>
                    <input type="text" id="polling_station_no" name="polling_station_no" 
                           value="<?php echo htmlspecialchars($_POST['polling_station_no'] ?? ''); ?>" 
                           required>
                    <small class="form-help">Enter the polling station number</small>
                </div>
                
                <div class="form-group">
                    <label for="location_name_of_building">Location/Building Name: <span class="required">*</span></label>
                    <input type="text" id="location_name_of_building" name="location_name_of_building" 
                           value="<?php echo htmlspecialchars($_POST['location_name_of_building'] ?? ''); ?>" 
                           required>
                    <small class="form-help">Enter the name of the building or location</small>
                </div>
                
                <div class="form-group">
                    <label for="polling_areas">Polling Areas: <span class="required">*</span></label>
                    <input type="text" id="polling_areas" name="polling_areas" 
                           value="<?php echo htmlspecialchars($_POST['polling_areas'] ?? ''); ?>" 
                           required>
                    <small class="form-help">Enter the polling areas covered by this booth</small>
                </div>
                
                <div class="form-group">
                    <label for="polling_station_type">Polling Station Type: <span class="required">*</span></label>
                    <input type="text" id="polling_station_type" name="polling_station_type" 
                           value="<?php echo htmlspecialchars($_POST['polling_station_type'] ?? 'Regular'); ?>" 
                           list="station_types_list" required>
                    <datalist id="station_types_list">
                        <option value="Regular">
                        <option value="Auxiliary">
                        <option value="Special">
                        <option value="Mobile">
                        <option value="Sensitive">
                        <option value="Critical">
                    </datalist>
                    <small class="form-help">Enter the type of polling station (Regular, Auxiliary, Special, Mobile, etc.)</small>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea id="notes" name="notes" rows="3" 
                              placeholder="Enter any additional notes or comments about this booth..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    <small class="form-help">Optional: Add any additional notes or comments about this booth</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">➕ Create Booth Record</button>
                    <a href="booth_view.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Quick Stats -->
        <div class="stats-container">
            <div class="stat-card">
                <h3>Quick Info</h3>
                <p>Fill in the required fields to create a new booth record. Make sure to select the correct MLA constituency.</p>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const mlaId = document.getElementById('mla_id').value;
            const slNo = document.getElementById('sl_no').value;
            const stationNo = document.getElementById('polling_station_no').value;
            const location = document.getElementById('location_name_of_building').value;
            const areas = document.getElementById('polling_areas').value;
            const type = document.getElementById('polling_station_type').value;
            
            if (!mlaId || !slNo || !stationNo || !location || !areas || !type) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return;
            }
            
            if (slNo <= 0) {
                e.preventDefault();
                alert('Serial number must be a positive number');
                return;
            }
        });

        // Auto-focus on first field
        document.getElementById('mla_id').focus();
    </script>
</body>
</html>
