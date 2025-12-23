<?php
// ... (existing code)

// Add this after displaying order details:
$order_id = $_GET['order_id'] ?? 0;
if ($order_id) {
    $conn = getConnection();
    $sql = "SELECT * FROM esewa_transactions WHERE order_id = '$order_id'";
    $result = mysqli_query($conn, $sql);
    if ($txn = mysqli_fetch_assoc($result)) {
        echo '<div class="card mt-4">';
        echo '<div class="card-header bg-info text-white">';
        echo '<h5>📄 Demo Payment Receipt</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        echo '<div class="alert alert-warning">';
        echo '<strong>🧪 SANDBOX TRANSACTION</strong> - For academic demonstration only';
        echo '</div>';
        echo '<table class="table">';
        echo '<tr><td>Transaction ID:</td><td>' . $txn['transaction_uuid'] . '</td></tr>';
        echo '<tr><td>Reference ID:</td><td>' . ($txn['ref_id'] ?: 'N/A') . '</td></tr>';
        echo '<tr><td>Amount:</td><td>NPR ' . $txn['total_amount'] . '</td></tr>';
        echo '<tr><td>Status:</td><td>' . $txn['status'] . '</td></tr>';
        echo '<tr><td>Date:</td><td>' . $txn['transaction_date'] . '</td></tr>';
        echo '</table>';
        echo '</div></div>';
    }
    closeConnection($conn);
}
?>