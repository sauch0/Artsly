<?php
// Run this file once to add the cancellation_message column to the orders table
include 'dbconnect.php';

$sql = "ALTER TABLE orders ADD COLUMN cancellation_message TEXT AFTER status";

if ($conn->query($sql) === TRUE) {
    echo "Column 'cancellation_message' added successfully!";
} else {
    if ($conn->errno === 1060) {
        echo "Column 'cancellation_message' already exists.";
    } else {
        echo "Error: " . $conn->error;
    }
}
$conn->close();
?>
