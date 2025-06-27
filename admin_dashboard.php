<?php
session_start();
include 'db.php';

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php"); // Redirect if not logged in as admin
    exit;
}

// Handle approval or rejection of images
if (isset($_GET['action']) && isset($_GET['image_id'])) {
    $image_id = $_GET['image_id'];
    $action = $_GET['action'];  // Action can be 'approve' or 'reject'

    if ($action === 'approve') {
        // Update image status to 'approved'
        $stmt = $conn->prepare("UPDATE images SET status = 'approved' WHERE id = :image_id");
        $stmt->execute([':image_id' => $image_id]);

        // Redirect back to the admin dashboard after approval
        header("Location: admin_dashboard.php");
        exit;
    } elseif ($action === 'reject' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        // Get rejection reason from the form submission
        $rejection_reason = $_POST['rejection_reason'] ?? 'No reason provided';

        // Update image status to 'rejected'
        $stmt = $conn->prepare("UPDATE images SET status = 'rejected' WHERE id = :image_id");
        $stmt->execute([':image_id' => $image_id]);

        // Get the user_id who uploaded the image
        $stmt = $conn->prepare("SELECT user_id, title FROM images WHERE id = :image_id");
        $stmt->execute([':image_id' => $image_id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($image) {
            // Store the rejection message in the user's message column
            $stmt = $conn->prepare("UPDATE users SET message = :message, dateofMessage = NOW() WHERE id = :user_id");
            $stmt->execute([
                ':message' => "Your image titled '{$image['title']}' has been rejected for the following reason: {$rejection_reason}",
                ':user_id' => $image['user_id']
            ]);

            if ($action === 'reject' && $rejection_reason === 'controversial') {
                // Fetch the user ID of the image uploader
                $stmt = $conn->prepare("SELECT user_id FROM images WHERE id = :image_id");
                $stmt->execute([':image_id' => $image_id]);
                $image = $stmt->fetch(PDO::FETCH_ASSOC);
        
                if ($image) {
                    $user_id = $image['user_id'];
        
                    // Increment the controversial rejection count for the user
                    $stmt = $conn->prepare("UPDATE users SET controversial_rejection_count = COALESCE(controversial_rejection_count, 0) + 1 WHERE id = :user_id");
                    $stmt->execute([':user_id' => $user_id]);
        
                    // Check the current controversial rejection count
                    $stmt = $conn->prepare("SELECT controversial_rejection_count FROM users WHERE id = :user_id");
                    $stmt->execute([':user_id' => $user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
                    if ($user && $user['controversial_rejection_count'] % 4 == 1) {
                        // Ban the user by setting the 'is_banned' flag to true
                        $stmt = $conn->prepare("UPDATE users SET is_banned = TRUE WHERE id = :user_id");
                        $stmt->execute([':user_id' => $user_id]);
        
                        // Notify the admin that the user has been banned
                        echo "User has been banned due to excessive controversial rejections.";
                    }
                }
            }
        }

        // Redirect back to the admin dashboard after rejection
        header("Location: admin_dashboard.php");
        exit;
    }
}

// Fetch all images with 'pending' status for admin to approve/reject
$stmt = $conn->prepare("SELECT * FROM images WHERE status = 'pending' ORDER BY upload_date ASC");
$stmt->execute();
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if the user is trying to unban a user
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Validate the user ID to ensure it's a valid integer
    if (!is_numeric($user_id)) {
        echo "<script>alert('Invalid user ID.'); window.location.href = 'admin_dashboard.php';</script>";
        exit;
    }

    // Unban the user by setting the 'is_banned' column to false
    $stmt = $conn->prepare("UPDATE users SET is_banned = 0 and controversial_rejection_count = 0 WHERE id = :user_id");
    $stmt->execute([':user_id' => $user_id]);

    // Check if the user was successfully unbanned
    if ($stmt->rowCount() > 0) {
        echo "<script>alert('User successfully unbanned.'); window.location.href = 'admin_dashboard.php';</script>";
    } else {
        echo "<script>alert('User not found or already unbanned.'); window.location.href = 'admin_dashboard.php';</script>";
    }
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Image Approval</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* General Reset */
* {
    margin: 1;
    padding: 0;
    box-sizing: border-box;
}

/* Body Styling */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
    color: #333;
    height: 100vh;
    display: flex;
    flex-direction: column;
}

/* Header Styling */
h1 {
    text-align: center;
    color: #3498db;
    margin-top: 20px;
    font-size: 2rem;
}
.unban button
{
    color: white;
    font-size: 16px;
    font-weight: bold;
    background: #3498db;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s ease;
    border-radius: 5px;
}

.unban button:hover
{
    opacity: 0.8;
}
/* Navigation Bar */
nav {
    background-color: #3498db;
    padding: 15px 30px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
}

nav h1 {
    font-size: 24px;
    margin: 0;
}

nav .nav-links {
    display: flex;
    gap: 20px;
}

nav .nav-links a,
nav .nav-links button {
    color: white;
    font-size: 16px;
    background: none;
    border: none;
    cursor: pointer;
    text-decoration: none;
}

nav .nav-links button:hover,
nav .nav-links a:hover {
    text-decoration: underline;
}

/* Main Content Area */
.main-content {
    margin-top: 8px; /* To make space for the fixed nav bar */
    padding: 30px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Image Gallery Section */
.image-gallery {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.image-item {
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 300px;
    height: auto;
    position: relative;
}

/* Style for image thumbnail */
.image-item img {
    max-width: 200px;
    max-height: 150px;
    object-fit: cover;
    border-radius: 5px;
    margin-bottom: 10px;
    cursor: pointer;
}

/* Modal Styles */
.modal {
    display: none; /* Hidden by default */
    position: fixed;
    z-index: 1; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.7); /* Black with transparency */
    padding-top: 60px;
}

.modal-content {
    margin: auto;
    display: block;
    width: 700px;
    max-width: 700px;
    object-fit: contain; /* To preserve aspect ratio */
}

.close {
    position: absolute;
    top: 15px;
    right: 35px;
    color: #fff;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: #bbb;
    text-decoration: none;
    cursor: pointer;
}

#modalCaption {
    text-align: center;
    color: white;
    font-size: 20px;
}

.image-item p {
    font-size: 14px;
    margin-bottom: 10px;
    font-weight: bold;
    color: #333;
}

.image-item a {
    display: inline-block;
    padding: 10px 15px;
    background-color: #3498db;
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
    font-size: 14px;
}

.image-item a:hover {
    background-color: #2980b9;
}

/* Buttons for Approve/Reject */
button.action-btn {
    padding: 10px 20px;
    margin-top: 10px;
    font-size: 14px;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
}

button.approve-btn {
    background-color: #2ecc71; /* Green */
}

button.reject-btn {
    background-color: #e74c3c; /* Red */
}

button.action-btn:hover {
    opacity: 0.8;
}

/* Rejection Reason Section */
.rejection-reason {
    margin-top: 20px;
    padding: 15px;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    width: 100%;
    box-sizing: border-box;
}

.rejection-reason textarea {
    width: 100%;
    padding: 10px;
    border-radius: 5px;
    border: 1px solid #ccc;
    font-size: 14px;
    min-height: 100px;
    box-sizing: border-box;
}

.rejection-reason label {
    font-weight: bold;
    margin-bottom: 5px;
    display: block;
    color: #333;
}

.rejection-reason button {
    background-color: #e74c3c;
    color: white;
    font-size: 16px;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 10px;
}

.rejection-reason button:hover {
    background-color: #c0392b;
}/* Button Styling */
.reject-button {
    background-color: #e74c3c;
    color: white;
    font-size: 16px;
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    width: 100%;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

/* Hover effect on reject button */
.reject-button:hover {
    background-color: #c0392b;
}

/* Make the reject button active when pressed */
.reject-button:active {
    background-color: #a93226;
}

/* Responsive Layout for Small Screens */
@media (max-width: 768px) {
    .image-gallery {
        flex-direction: column;
        align-items: center;
    }

    .image-item {
        width: 90%;
        margin-bottom: 20px;
    }

    .main-content {
        padding: 15px;
    }
}

</style>
</head>
<body>
<div class="unban">
    <a href="javascript:void(0)" onclick="unbanUser()">
        <button>Unban Users</button>
    </a>
</div>
    <h1>Admin Dashboard</h1>

    <div class="image-gallery">
        <?php foreach ($images as $image): ?>
            <div class="image-item">
            <a href="#" class="image-link" onclick="openModal('<?= $image['path']; ?>')">
            <img src="<?= $image['path']; ?>" alt="<?= $image['title']; ?>" class="thumbnail">
        </a>
        <h3 style="padding-top:20px; padding-bottom:20px"><?= $image['title']; ?></h3>
        <p style="font-size:15px; color:rgba(0,0,0,0.6);"><?= $image['category'] ?></p>
        <p style="font-size:15px; color:rgba(0,0,0,0.6);"><?= $image['size'] ?></p>
        <p style="font-size:15px; color:rgba(0,0,0,0.6);"><?= $image['upload_date'] ?></p>
                <!-- Modal for full image -->
<div id="imageModal" class="modal">
    <span class="close" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="modalImage">
    <div id="modalCaption"></div>
</div>
<a href="?action=approve&image_id=<?= $image['id']; ?>" class="approve-button">Approve</a><br>

<!-- Form to submit rejection reason -->
<form action="?action=reject&image_id=<?= $image['id']; ?>" method="POST">
    <label for="rejection_reason">Reason for Rejection:</label>
    <select name="rejection_reason" required>
        <option value="inappropriate image">Inappropriate Image</option>
        <option value="low quality">Low Quality</option>
        <option value="controversial">Controversial</option>
        <option value="invalid category_name">Invalid Category</option>
        <option value="invalid name">Invalid Name</option>
    </select><br>
    <button type="submit" class="reject-button">Reject</button>
</form>

            </div>
        <?php endforeach; ?>
    </div>
    <script>
        function openModal(imagePath) {
    var modal = document.getElementById("imageModal");
    var modalImg = document.getElementById("modalImage");
    var captionText = document.getElementById("modalCaption");

    modal.style.display = "block";
    modalImg.src = imagePath; // Set the source of the image to the clicked image
    captionText.innerHTML = "Full image"; // Optional: Add a caption text
}

function closeModal() {
    var modal = document.getElementById("imageModal");
    modal.style.display = "none";
}

    </script>
    <script>
    function unbanUser() {
        var userId = prompt("Please enter the user ID to unban:");

        if (userId) {
            // Redirect to unban.php with the entered user ID as a query parameter
            window.location.href = "admin_dashboard.php?user_id=" + userId;
        }
    }
</script>
</body>
</html>
