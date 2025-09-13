    </div>
    
    <footer style="margin-top: 50px; padding: 20px; background: #f8f9fa; border-radius: 10px; text-align: center; color: #666;">
        <p>&copy; <?php echo date('Y'); ?> Election Management System. All rights reserved.</p>
        <p>Version 1.0 | Last updated: <?php echo date('M j, Y'); ?></p>
    </footer>
    
    <script>
        // Common JavaScript functions
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }
        
        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.style.cssText = 'padding: 15px; margin: 20px 0; border-radius: 5px;';
            alertDiv.innerHTML = message;
            
            if (type === 'success') {
                alertDiv.style.background = '#d4edda';
                alertDiv.style.color = '#155724';
                alertDiv.style.border = '1px solid #c3e6cb';
            } else if (type === 'error') {
                alertDiv.style.background = '#f8d7da';
                alertDiv.style.color = '#721c24';
                alertDiv.style.border = '1px solid #f5c6cb';
            } else if (type === 'warning') {
                alertDiv.style.background = '#fff3cd';
                alertDiv.style.color = '#856404';
                alertDiv.style.border = '1px solid #ffeaa7';
            } else {
                alertDiv.style.background = '#d1ecf1';
                alertDiv.style.color = '#0c5460';
                alertDiv.style.border = '1px solid #bee5eb';
            }
            
            document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.container').firstChild);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }
        
        // Search functionality
        function initSearch(inputId, tableId) {
            const searchInput = document.getElementById(inputId);
            const table = document.getElementById(tableId);
            
            if (searchInput && table) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = table.querySelectorAll('tbody tr');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        }
        
        // Form validation
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (form) {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            isValid = false;
                            field.style.borderColor = '#dc3545';
                        } else {
                            field.style.borderColor = '#ddd';
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        showAlert('Please fill in all required fields.', 'error');
                    }
                });
            }
        }
        
        // Initialize common functionality when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize search for any search inputs
            const searchInputs = document.querySelectorAll('input[type="text"][placeholder*="Search"]');
            searchInputs.forEach(input => {
                const tableId = input.closest('.search-container')?.nextElementSibling?.querySelector('table')?.id;
                if (tableId) {
                    initSearch(input.id, tableId);
                }
            });
            
            // Initialize form validation
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                validateForm(form.id);
            });
        });
    </script>
</body>
</html>
