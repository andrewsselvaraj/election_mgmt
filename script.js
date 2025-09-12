// JavaScript for MP Master Management System

// Global variables
let isEditMode = false;
let currentRecordId = null;

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeForm();
    initializeModal();
});

// Initialize form functionality
function initializeForm() {
    const form = document.getElementById('mp-form');
    const cancelBtn = document.getElementById('cancel-btn');
    const formTitle = document.getElementById('form-title');
    const submitBtn = document.getElementById('submit-btn');
    
    // Reset form when cancel is clicked
    cancelBtn.addEventListener('click', function() {
        resetForm();
    });
    
    // Handle form submission
    form.addEventListener('submit', function(e) {
        // Basic validation
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.textContent = isEditMode ? 'Updating...' : 'Adding...';
    });
}

// Initialize modal functionality
function initializeModal() {
    const modal = document.getElementById('delete-modal');
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
}

// Edit record function
function editRecord(recordId) {
    // Fetch record data via AJAX
    fetch('get_record.php?id=' + recordId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateForm(data.record);
                switchToEditMode(recordId);
            } else {
                alert('Error loading record: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading record');
        });
}

// Populate form with record data
function populateForm(record) {
    document.getElementById('mp_constituency_code').value = record.mp_constituency_code;
    document.getElementById('mp_constituency_name').value = record.mp_constituency_name;
    document.getElementById('state').value = record.state;
    document.getElementById('created_by').value = record.created_by;
    document.getElementById('updated_by').value = record.updated_by || '';
}

// Switch to edit mode
function switchToEditMode(recordId) {
    isEditMode = true;
    currentRecordId = recordId;
    
    document.getElementById('form-title').textContent = 'Edit MP Record';
    document.getElementById('form-action').value = 'update';
    document.getElementById('mp_id').value = recordId;
    document.getElementById('submit-btn').textContent = 'Update Record';
    document.getElementById('cancel-btn').style.display = 'inline-block';
    document.getElementById('updated_by_group').style.display = 'block';
    
    // Scroll to form
    document.querySelector('.form-container').scrollIntoView({ 
        behavior: 'smooth' 
    });
}

// Reset form to add mode
function resetForm() {
    isEditMode = false;
    currentRecordId = null;
    
    document.getElementById('mp-form').reset();
    document.getElementById('form-title').textContent = 'Add New MP Record';
    document.getElementById('form-action').value = 'create';
    document.getElementById('mp_id').value = '';
    document.getElementById('submit-btn').textContent = 'Add Record';
    document.getElementById('submit-btn').disabled = false;
    document.getElementById('cancel-btn').style.display = 'none';
    document.getElementById('updated_by_group').style.display = 'none';
    document.getElementById('state').value = 'Tamil Nadu'; // Reset to default
}

// Delete record function
function deleteRecord(recordId, recordName) {
    document.getElementById('delete-mp-id').value = recordId;
    document.getElementById('delete-record-name').textContent = recordName;
    document.getElementById('delete-modal').style.display = 'block';
}

// Close modal
function closeModal() {
    document.getElementById('delete-modal').style.display = 'none';
}

// Validate form
function validateForm() {
    const code = document.getElementById('mp_constituency_code').value;
    const name = document.getElementById('mp_constituency_name').value;
    const state = document.getElementById('state').value;
    const createdBy = document.getElementById('created_by').value;
    
    // Check if all required fields are filled
    if (!code || !name || !state || !createdBy) {
        alert('Please fill in all required fields');
        return false;
    }
    
    // Validate constituency code (should be positive number)
    if (isNaN(code) || parseInt(code) <= 0) {
        alert('Constituency code must be a positive number');
        return false;
    }
    
    // Validate name length
    if (name.length < 3) {
        alert('Constituency name must be at least 3 characters long');
        return false;
    }
    
    return true;
}

// Auto-save form data to localStorage
function saveFormData() {
    const formData = {
        code: document.getElementById('mp_constituency_code').value,
        name: document.getElementById('mp_constituency_name').value,
        state: document.getElementById('state').value,
        createdBy: document.getElementById('created_by').value
    };
    
    localStorage.setItem('mpFormData', JSON.stringify(formData));
}

// Load form data from localStorage
function loadFormData() {
    const savedData = localStorage.getItem('mpFormData');
    if (savedData && !isEditMode) {
        const formData = JSON.parse(savedData);
        document.getElementById('mp_constituency_code').value = formData.code || '';
        document.getElementById('mp_constituency_name').value = formData.name || '';
        document.getElementById('state').value = formData.state || 'Tamil Nadu';
        document.getElementById('created_by').value = formData.createdBy || '';
    }
}

// Clear saved form data
function clearFormData() {
    localStorage.removeItem('mpFormData');
}

// Add event listeners for auto-save
document.addEventListener('DOMContentLoaded', function() {
    const formInputs = document.querySelectorAll('#mp-form input');
    formInputs.forEach(input => {
        input.addEventListener('input', saveFormData);
    });
    
    // Load saved data on page load
    loadFormData();
    
    // Clear saved data on successful form submission
    const form = document.getElementById('mp-form');
    form.addEventListener('submit', function() {
        setTimeout(clearFormData, 1000);
    });
});

// Search functionality
function performSearch() {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput.value.trim() === '') {
        return;
    }
    
    // Add loading state
    const searchBtn = document.querySelector('.search-form button');
    const originalText = searchBtn.textContent;
    searchBtn.textContent = 'Searching...';
    searchBtn.disabled = true;
    
    // Reset after a short delay
    setTimeout(() => {
        searchBtn.textContent = originalText;
        searchBtn.disabled = false;
    }, 1000);
}

// Add search event listener
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', performSearch);
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape key to close modal
    if (e.key === 'Escape') {
        closeModal();
    }
    
    // Ctrl+N to add new record
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        resetForm();
        document.querySelector('.form-container').scrollIntoView({ 
            behavior: 'smooth' 
        });
    }
});

// Confirmation for form reset
function confirmReset() {
    if (isEditMode) {
        return confirm('Are you sure you want to cancel editing? Any unsaved changes will be lost.');
    }
    return true;
}

// Enhanced form reset with confirmation
function resetFormWithConfirmation() {
    if (confirmReset()) {
        resetForm();
    }
}

// Update cancel button to use confirmation
document.addEventListener('DOMContentLoaded', function() {
    const cancelBtn = document.getElementById('cancel-btn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', resetFormWithConfirmation);
    }
});
