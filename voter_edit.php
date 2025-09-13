<?php
require_once 'config.php';
require_once 'VoterMaster.php';
require_once 'MLAMaster.php';
require_once 'BoothMaster.php';
require_once 'Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!$auth->hasPermission('voter', 'update')) {
    header('Location: voter_view.php?error=no_permission');
    exit;
}

$voterMaster = new VoterMaster($pdo);
$mlaMaster = new MLAMaster($pdo);
$boothMaster = new BoothMaster($pdo);
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

$message = '';
$messageType = '';
$currentUser = $auth->getCurrentUser();

// Get MLAs and Booths for dropdowns
$mlas = $mlaMaster->readAll();
$booths = $boothMaster->readAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate required fields
        $requiredFields = ['voter_id', 'mla_id', 'voter_name', 'father_name', 'age', 'gender'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            throw new Exception('Missing required fields: ' . implode(', ', $missingFields));
        }
        
        // Check if voter ID already exists for this MLA (excluding current voter)
        if ($voterMaster->voterIdExists($_POST['voter_id'], $_POST['mla_id'], $voterUniqueId)) {
            throw new Exception('Voter ID already exists for this MLA constituency');
        }
        
        // Validate age
        if (!is_numeric($_POST['age']) || $_POST['age'] < 18 || $_POST['age'] > 120) {
            throw new Exception('Age must be between 18 and 120');
        }
        
        // Prepare data
        $data = [
            'voter_id' => trim($_POST['voter_id']),
            'mla_id' => $_POST['mla_id'],
            'voter_name' => trim($_POST['voter_name']),
            'father_name' => trim($_POST['father_name']),
            'mother_name' => trim($_POST['mother_name'] ?? ''),
            'husband_name' => trim($_POST['husband_name'] ?? ''),
            'age' => (int)$_POST['age'],
            'gender' => $_POST['gender'],
            'address' => trim($_POST['address'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'booth_id' => $_POST['booth_id'] ?: null,
            'ward_no' => trim($_POST['ward_no'] ?? ''),
            'part_no' => trim($_POST['part_no'] ?? ''),
            'updated_by' => $currentUser['first_name'] . ' ' . $currentUser['last_name']
        ];
        
        $result = $voterMaster->update($voterUniqueId, $data);
        
        if ($result['success']) {
            $message = 'Voter record updated successfully!';
            $messageType = 'success';
            // Refresh voter data
            $voter = $voterMaster->readById($voterUniqueId);
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Available genders
$genders = ['Male', 'Female', 'Other'];

$pageTitle = 'Edit Voter - ' . htmlspecialchars($voter['voter_name']);
include 'header.php';
?>

<style>
    .form-container {
        max-width: 1000px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
    }
    
    .required {
        color: #dc3545;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    .form-row-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
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
        background: #007bff;
        color: white;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn:hover {
        opacity: 0.9;
    }
    
    .form-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 30px;
    }
    
    .help-text {
        font-size: 12px;
        color: #6c757d;
        margin-top: 5px;
    }
    
    .section-title {
        background: #f8f9fa;
        padding: 10px 15px;
        margin: 20px 0 15px 0;
        border-radius: 5px;
        font-weight: 600;
        color: #333;
        border-left: 4px solid #007bff;
    }
    
    .booth-info {
        background: #e3f2fd;
        padding: 10px;
        border-radius: 5px;
        margin-top: 5px;
        font-size: 12px;
        color: #1976d2;
    }
</style>

<div class="page-header">
    <h1>✏️ Edit Voter</h1>
    <p>Update voter information and details</p>
</div>

<div class="form-container">
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 20px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="section-title">📋 Basic Information</div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="voter_id">Voter ID <span class="required">*</span></label>
                <input type="text" id="voter_id" name="voter_id" 
                       value="<?php echo htmlspecialchars($voter['voter_id']); ?>" 
                       required>
                <div class="help-text">Unique voter ID within the MLA constituency</div>
            </div>
            
            <div class="form-group">
                <label for="mla_id">MLA Constituency <span class="required">*</span></label>
                <select id="mla_id" name="mla_id" required>
                    <option value="">Select MLA Constituency</option>
                    <?php foreach ($mlas as $mla): ?>
                        <option value="<?php echo $mla['mla_id']; ?>" 
                                <?php echo $voter['mla_id'] === $mla['mla_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($mla['mla_name'] . ' - ' . $mla['mla_constituency_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="voter_name">Voter Name <span class="required">*</span></label>
                <input type="text" id="voter_name" name="voter_name" 
                       value="<?php echo htmlspecialchars($voter['voter_name']); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="father_name">Father's Name <span class="required">*</span></label>
                <input type="text" id="father_name" name="father_name" 
                       value="<?php echo htmlspecialchars($voter['father_name']); ?>" 
                       required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="mother_name">Mother's Name</label>
                <input type="text" id="mother_name" name="mother_name" 
                       value="<?php echo htmlspecialchars($voter['mother_name']); ?>">
            </div>
            
            <div class="form-group">
                <label for="husband_name">Husband's Name</label>
                <input type="text" id="husband_name" name="husband_name" 
                       value="<?php echo htmlspecialchars($voter['husband_name']); ?>">
                <div class="help-text">For married women</div>
            </div>
        </div>
        
        <div class="form-row-3">
            <div class="form-group">
                <label for="age">Age <span class="required">*</span></label>
                <input type="number" id="age" name="age" min="18" max="120"
                       value="<?php echo $voter['age']; ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="gender">Gender <span class="required">*</span></label>
                <select id="gender" name="gender" required>
                    <option value="">Select Gender</option>
                    <?php foreach ($genders as $gender): ?>
                        <option value="<?php echo $gender; ?>" 
                                <?php echo $voter['gender'] === $gender ? 'selected' : ''; ?>>
                            <?php echo $gender; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="booth_id">Polling Booth</label>
                <select id="booth_id" name="booth_id">
                    <option value="">Select Booth (Optional)</option>
                    <?php foreach ($booths as $booth): ?>
                        <option value="<?php echo $booth['booth_id']; ?>" 
                                <?php echo $voter['booth_id'] === $booth['booth_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($booth['polling_station_no'] . ' - ' . $booth['location_name_of_building']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="booth-info" id="booth-info" style="display: none;">
                    <!-- Booth details will be shown here -->
                </div>
            </div>
        </div>
        
        <div class="section-title">📍 Address & Contact</div>
        
        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($voter['address']); ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($voter['phone']); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($voter['email']); ?>">
            </div>
        </div>
        
        <div class="section-title">🏛️ Administrative Details</div>
        
        <div class="form-row-3">
            <div class="form-group">
                <label for="ward_no">Ward Number</label>
                <input type="text" id="ward_no" name="ward_no" 
                       value="<?php echo htmlspecialchars($voter['ward_no']); ?>">
            </div>
            
            <div class="form-group">
                <label for="part_no">Part Number</label>
                <input type="text" id="part_no" name="part_no" 
                       value="<?php echo htmlspecialchars($voter['part_no']); ?>">
            </div>
            
            <div class="form-group">
                <!-- Empty column for alignment -->
            </div>
        </div>
        
        <div class="form-actions">
            <a href="voter_detail.php?id=<?php echo $voter['voter_unique_ID']; ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Voter</button>
        </div>
    </form>
</div>

<script>
    // Show booth information when selected
    document.getElementById('booth_id').addEventListener('change', function() {
        const boothId = this.value;
        const boothInfo = document.getElementById('booth-info');
        
        if (boothId) {
            boothInfo.style.display = 'block';
            boothInfo.innerHTML = 'Booth selected: ' + this.options[this.selectedIndex].text;
        } else {
            boothInfo.style.display = 'none';
        }
    });
    
    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const voterId = document.getElementById('voter_id').value;
        const mlaId = document.getElementById('mla_id').value;
        const voterName = document.getElementById('voter_name').value;
        const fatherName = document.getElementById('father_name').value;
        const age = document.getElementById('age').value;
        const gender = document.getElementById('gender').value;
        
        if (!voterId || !mlaId || !voterName || !fatherName || !age || !gender) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return;
        }
        
        if (age < 18 || age > 120) {
            e.preventDefault();
            alert('Age must be between 18 and 120.');
            return;
        }
    });
</script>

<?php include 'footer.php'; ?>
