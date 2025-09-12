// JavaScript for Booth Master Management System

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
    const form = document.getElementById('booth-form');
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
    fetch('get_booth_record.php?id=' + recordId)
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
    document.getElementById('mla_id').value = record.mla_id;
    document.getElementById('Sl_No').value = record.Sl_No;
    document.getElementById('Polling_station_No').value = record.Polling_station_No;
    document.getElementById('Location_name_of_buiding').value = record.Location_name_of_buiding;
    document.getElementById('Polling_Areas').value = record.Polling_Areas || '';
    document.getElementById('Polling_Station_Type').value = record.Polling_Station_Type;
}

// Switch to edit mode
function switchToEditMode(recordId) {
    isEditMode = true;
    currentRecordId = recordId;
    
    document.getElementById('form-title').textContent = 'Edit Booth Record';
    document.getElementById('form-action').value = 'update';
    document.getElementById('booth_id').value = recordId;
    document.getElementById('submit-btn').textContent = 'Update Record';
    document.getElementById('cancel-btn').style.display = 'inline-block';
    
    // Scroll to form
    document.querySelector('.form-container').scrollIntoView({ 
        behavior: 'smooth' 
    });
}

// Reset form to add mode
function resetForm() {
    isEditMode = false;
    currentRecordId = null;
    
    document.getElementById('booth-form').reset();
    document.getElementById('form-title').textContent = 'Add New Booth Record';
    document.getElementById('form-action').value = 'create';
    document.getElementById('booth_id').value = '';
    document.getElementById('submit-btn').textContent = 'Add Record';
    document.getElementById('submit-btn').disabled = false;
    document.getElementById('cancel-btn').style.display = 'none';
}

// Delete record function
function deleteRecord(recordId, recordName) {
    document.getElementById('delete-booth-id').value = recordId;
    document.getElementById('delete-record-name').textContent = recordName;
    document.getElementById('delete-modal').style.display = 'block';
}

// Close modal
function closeModal() {
    document.getElementById('delete-modal').style.display = 'none';
}

// Validate form
function validateForm() {
    const mlaId = document.getElementById('mla_id').value;
    const slNo = document.getElementById('Sl_No').value;
    const stationNo = document.getElementById('Polling_station_No').value;
    const location = document.getElementById('Location_name_of_buiding').value;
    const stationType = document.getElementById('Polling_Station_Type').value;
    
    // Check if all required fields are filled
    if (!mlaId || !slNo || !stationNo || !location || !stationType) {
        alert('Please fill in all required fields');
        return false;
    }
    
    // Validate serial number (should be positive number)
    if (isNaN(slNo) || parseInt(slNo) <= 0) {
        alert('Serial number must be a positive number');
        return false;
    }
    
    // Validate location length
    if (location.length < 3) {
        alert('Location name must be at least 3 characters long');
        return false;
    }
    
    return true;
}

// Auto-save form data to localStorage
function saveFormData() {
    const formData = {
        mlaId: document.getElementById('mla_id').value,
        slNo: document.getElementById('Sl_No').value,
        stationNo: document.getElementById('Polling_station_No').value,
        location: document.getElementById('Location_name_of_buiding').value,
        areas: document.getElementById('Polling_Areas').value,
        stationType: document.getElementById('Polling_Station_Type').value
    };
    
    localStorage.setItem('boothFormData', JSON.stringify(formData));
}

// Load form data from localStorage
function loadFormData() {
    const savedData = localStorage.getItem('boothFormData');
    if (savedData && !isEditMode) {
        const formData = JSON.parse(savedData);
        document.getElementById('mla_id').value = formData.mlaId || '';
        document.getElementById('Sl_No').value = formData.slNo || '';
        document.getElementById('Polling_station_No').value = formData.stationNo || '';
        document.getElementById('Location_name_of_buiding').value = formData.location || '';
        document.getElementById('Polling_Areas').value = formData.areas || '';
        document.getElementById('Polling_Station_Type').value = formData.stationType || 'Regular';
    }
}

// Add event listeners for auto-save
document.addEventListener('DOMContentLoaded', function() {
    const formInputs = document.querySelectorAll('#booth-form input, #booth-form select, #booth-form textarea');
    formInputs.forEach(input => {
        input.addEventListener('input', saveFormData);
        input.addEventListener('change', saveFormData);
    });
    
    // Load saved data on page load
    loadFormData();
    
    // Clear saved data on successful form submission
    const form = document.getElementById('booth-form');
    form.addEventListener('submit', function() {
        setTimeout(clearFormData, 1000);
    });
});

// Clear saved form data
function clearFormData() {
    localStorage.removeItem('boothFormData');
}

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
