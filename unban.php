<?php
session_start();
include 'db.php';

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php"); // Redirect if not logged in as admin
    exit;
}

// Check if the user ID is passed via the URL
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Validate the user ID to make sure it's a valid integer (you can add more validation as needed)
    if (filter_var($user_id, FILTER_VALIDATE_INT)) {
        // Prepare the SQL statement to unban the user by setting 'is_banned' to FALSE
        $stmt = $conn->prepare("UPDATE users SET is_banned = FALSE WHERE id = :user_id");
        $stmt->execute([':user_id' => $user_id]);

        // Check if the user was successfully unbanned
        if ($stmt->rowCount() > 0) {
            echo "User with ID {$user_id} has been successfully unbanned.";
        } else {
            echo "No user found with ID {$user_id}, or the user is already not banned.";
        }
    } else {
        echo "Invalid user ID.";
    }
} else {
    echo "No user ID provided.";
}
header("Location: admin_dashboard.php");
?>
