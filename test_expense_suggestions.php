<?php
// Test file for expense control suggestions
session_start();

// Simulate a logged-in user
$_SESSION['id'] = 3; // Using user ID 2 from the database

// Include the expense control suggestions file
include_once 'FUNCTION/expenseControlSuggestions.php';

echo "Test completed successfully!";
?> 
