<?php
include 'database/dbconnect.php';

// Check table structure
echo "<h2>Orders Table Structure</h2>";
$result = $conn->query("DESCRIBE orders");
while ($row = $result->fetch_assoc()) {
    echo "<pre>"; print_r($row); echo "</pre>";
}

// Check all orders with their cancellation messages - try both spellings
echo "<h2>All Orders</h2>";
$result = $conn->query("SELECT * FROM orders");
while ($row = $result->fetch_assoc()) {
    echo "<pre>"; print_r($row); echo "</pre>";
}
?>
