                <!-- Page Content Ends Here -->
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Admin JS -->
    <script>
    // Initialize DataTables
    $(document).ready(function() {
        $('.data-table').DataTable({
            pageLength: 25,
            responsive: true,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search..."
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
    
    // Confirm before delete
    function confirmDelete(message = 'Are you sure you want to delete this item?') {
        return confirm(message);
    }
    
    // Toggle sidebar on mobile
    function toggleSidebar() {
        document.getElementById('sidebarMenu').classList.toggle('collapse');
    }
    
    // Update admin status badge
    function updateStatus(element, newStatus) {
        const badgeClass = {
            'pending': 'warning',
            'processing': 'info',
            'shipped': 'primary',
            'delivered': 'success',
            'cancelled': 'danger'
        }[newStatus] || 'secondary';
        
        element.innerHTML = `<span class="badge bg-${badgeClass}">${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}</span>`;
    }
    
    // Copy to clipboard
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Copied to clipboard!');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }
    
    // Format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Load more data with AJAX
    function loadMoreData(url, containerId) {
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                $(`#${containerId}`).append(response);
            },
            error: function() {
                alert('Error loading more data');
            }
        });
    }
    
    // Update stock quantity
    function updateStock(productId, newQuantity) {
        if (confirm('Update stock quantity?')) {
            $.ajax({
                url: 'update_stock.php',
                type: 'POST',
                data: {
                    product_id: productId,
                    quantity: newQuantity
                },
                success: function(response) {
                    alert('Stock updated successfully!');
                    location.reload();
                },
                error: function() {
                    alert('Error updating stock');
                }
            });
        }
    }
    
    // Export data
    function exportData(format) {
        const params = new URLSearchParams(window.location.search);
        params.set('export', format);
        window.location.href = window.location.pathname + '?' + params.toString();
    }
    
    // Print page
    function printPage() {
        window.print();
    }
    
    // Download file
    function downloadFile(filename, content) {
        const element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(content));
        element.setAttribute('download', filename);
        element.style.display = 'none';
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    }
    
    // Show loading spinner
    function showLoading() {
        $('#loadingSpinner').show();
    }
    
    // Hide loading spinner
    function hideLoading() {
        $('#loadingSpinner').hide();
    }
    
    // Initialize tooltips
    $(function () {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
    
    // Initialize popovers
    $(function () {
        $('[data-bs-toggle="popover"]').popover();
    });
    </script>
    
    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="d-none" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999;">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    
    <!-- Global AJAX Error Handler -->
    <script>
    $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
        console.error('AJAX Error:', thrownError);
        alert('An error occurred while processing your request. Please try again.');
        hideLoading();
    });
    </script>
    
    <!-- Session Timeout Warning -->
    <script>
    let idleTime = 0;
    
    // Increment idle time every minute
    setInterval(timerIncrement, 60000);
    
    function timerIncrement() {
        idleTime++;
        if (idleTime > 29) { // 30 minutes
            alert('Your session will expire soon due to inactivity.');
            idleTime = 0;
        }
    }
    
    // Reset idle time on user activity
    $(document).on('mousemove keypress scroll click', function() {
        idleTime = 0;
    });
    </script>
</body>
</html>