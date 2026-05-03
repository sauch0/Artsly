<?php
session_start();
include 'database/dbconnect.php';

echo "<h2>POST Data</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h2>Session User</h2>";
echo "<pre>";
print_r($_SESSION['user']);
echo "</pre>";

if ($_POST['action'] === 'update_order_status') {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    $cancellation_message = $_POST['cancellation_message'] ?? '';

    echo "<h2>Debug Info</h2>";
    echo "Order ID: $order_id<br>";
    echo "Status: $status<br>";
    echo "Cancellation Message: '$cancellation_message'<br>";
    echo "Message empty?: " . (empty($cancellation_message) ? 'YES' : 'NO') . "<br>";
    echo "Message length: " . strlen($cancellation_message) . "<br>";
}
?>
