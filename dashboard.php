<?php
session_start();
include 'db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
// Check if the user is banned
$stmt = $conn->prepare("SELECT is_banned FROM users WHERE id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $user['is_banned']) {
    // If the user is banned, show a blank screen with a message
    echo "<html><head><title>Banned</title></head><body style='background-color: #f0f0f0; text-align: center; padding-top: 50px; font-family:calibri;'>";
    echo "<h1>⛔You have been banned from using the platform due to your controversial uploads</h1>";
    echo "<p>If you believe this is a mistake, please <a href='https://mail.google.com/mail/?view=cm&fs=1&to=admin@example.com&su=Contacting%20Admin&body=Hello%20Admin,%0A%0APlease%20describe%20your%20issue...' target='_blank'>Contact Admin support</a>.</p>";
    echo "</body></html>";
    exit;
}
// Handle image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    $title = $_POST['title'];
    $category = $_POST['category'];
    $size = $_POST['size'];
    $user_id = $_SESSION['user_id'];

    // Check daily upload limit
    $stmt = $conn->prepare("SELECT COUNT(*) as image_count FROM images WHERE user_id = :user_id AND DATE(upload_date) = CURDATE()");
    $stmt->execute([':user_id' => $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['image_count'] >= 7) {
        echo "<script type='text/javascript'>
            alert('Upload limit reached. You can only upload 7 images per day.');
            window.location.href = 'dashboard.php'; // Optionally, redirect the user after the alert
          </script>";
        exit;
    }

    // Get the file extension
    $file_name = $_FILES['image']['name'];
    $file_tmp = $_FILES['image']['tmp_name'];
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Allowed file types
    $allowed_extensions = ['jpg', 'jpeg', 'png'];

    // Check if the file has an allowed extension
    if (!in_array($file_extension, $allowed_extensions)) {
        echo "Invalid file type. Only JPG, JPEG, and PNG files are allowed.";
        exit;
    }

    // Move the file to the upload directory
    $file_path = 'uploads/' . basename($file_name);
    if (move_uploaded_file($file_tmp, $file_path)) {
        // Insert the image details into the database
        $stmt = $conn->prepare("INSERT INTO images (title, category, size, path, user_id, upload_date) VALUES (:title, :category, :size, :path, :user_id, NOW())");
        $stmt->execute([
            ':title' => $title,
            ':category' => $category,
            ':size' => $size,
            ':path' => $file_path,
            ':user_id' => $user_id
        ]);
        echo "<script type='text/javascript'>
        alert('Image Uploaded Successfully');
        window.location.href = 'dashboard.php'; // Optionally, redirect the user after the alert
        </script>";

        header("Location: dashboard.php"); // Redirect after successful upload
        exit;
    } else {
        echo "Error uploading the file.";
        exit;
    }

}


$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM images WHERE user_id = :user_id ORDER BY upload_date DESC");
$stmt->execute([':user_id' => $user_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['delete_id'])) {
    $image_id = $_GET['delete_id'];

    $stmt = $conn->prepare("SELECT * FROM images WHERE id = :image_id AND user_id = :user_id");
    $stmt->execute([':image_id' => $image_id, ':user_id' => $user_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($image) {
        unlink($image['path']);

        $stmt = $conn->prepare("DELETE FROM images WHERE id = :image_id");
        $stmt->execute([':image_id' => $image_id]);
        header('Location: dashboard.php');
        exit;
    }
}

$stmt = $conn->prepare("SELECT username FROM users WHERE id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$username = $stmt->fetch(PDO::FETCH_ASSOC);

// Get the user's messages
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT message, dateofMessage FROM users WHERE id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Query: Number of uploads this month
$stmt = $conn->prepare("SELECT COUNT(*) FROM images WHERE user_id = :user_id AND MONTH(upload_date) = MONTH(CURRENT_DATE) AND YEAR(upload_date) = YEAR(CURRENT_DATE) AND status='approved'");
$stmt->execute([':user_id' => $user_id]);
$uploads_this_month = $stmt->fetchColumn();

// Query: Number of downloads this month
$stmt = $conn->prepare("SELECT SUM(download_count) FROM images WHERE user_id = :user_id AND MONTH(upload_date) = MONTH(CURRENT_DATE) AND YEAR(upload_date) = YEAR(CURRENT_DATE) AND status='approved'");
$stmt->execute([':user_id' => $user_id]);
$downloads_this_month = $stmt->fetchColumn();

// Query: Approved and Rejected image count
$stmt = $conn->prepare("SELECT COUNT(CASE WHEN status = 'approved' THEN 1 END) AS approved, COUNT(CASE WHEN status = 'rejected' THEN 1 END) AS rejected FROM images WHERE user_id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$statuses = $stmt->fetch(PDO::FETCH_ASSOC);
$approved_count = $statuses['approved'];
$rejected_count = $statuses['rejected'];
$total_count = $approved_count + $rejected_count;
$approved_percent = $total_count > 0 ? round(($approved_count / $total_count) * 100, 2) : 0;
$rejected_percent = $total_count > 0 ? round(($rejected_count / $total_count) * 100, 2) : 0;

// Query: Top 5 most downloaded images
$stmt = $conn->prepare("SELECT title, download_count FROM images WHERE user_id = :user_id ORDER BY download_count DESC LIMIT 5");
$stmt->execute([':user_id' => $user_id]);
$top_downloaded_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard</title>
    <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        /* Body Styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
                /* Navigation Bar */
            nav {
            background-color: #3498db;
            padding: 10px 20px;
            text-align: right;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            width: 100%;
            height:60px;
        }

        nav h1 {
            font-size: 24px;
            margin: 0;
        }

        nav .nav-links {
            display: flex;
            gap: 15px;
        }

        nav .nav-links a, nav .nav-links button {
            color: white;
            text-decoration: none;
            font-size: 16px;
            background: none;
            border: none;
            cursor: pointer;
        }

        nav .nav-links button:hover, nav .nav-links a:hover {
            text-decoration: underline;
        }

        /* Header Styling */
        h1 {
            color: #3498db;
            margin-top: 20px;
        }

        /* Dashboard Container */
        .dashboard-container {
            width: 80%;
            max-width: 1200px;
            margin-top: 30px;
            text-align: center;
        }

        .upload-form {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            text-align: left;
        }

        .upload-form input, .upload-form select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
            width: 100%;
            max-width: 400px;
        }

        .upload-form button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .upload-form button:hover {
            background-color: #2980b9;
        }

        /* Image Grid */
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 0px;
        }

        .image-gallery div {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            padding: 10px;
        }

        .image-gallery img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .image-gallery button {
            background-color: #e74c3c;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }


        .image-gallery button:hover {
            background-color: #c0392b;
        }
        .gallery-section a {
            display: inline-block;
            margin-top: 5px;
            background-color: #3498db;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            display: none;
        }

        .gallery-section a:hover {
            background-color: #2980b9;
        }
        /* Status styles */
.status {
    font-weight: bold;
}

.pending
{
    color: rgba(0,0,0,0.5);
}

.approved {
    color: green;
}

.rejected {
    color: red;
}
.dashboard-contain {
    display: flex;
    justify-content: space-between;
    padding: 20px;
}
/* Left Section for Analytics */
.analytics {
    width: 60%; /* Take up 60% of the container width */
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.analytics h2 {
    margin-bottom: 20px;
    color: #3498db;
}

.analytics p {
    margin: 10px 0;
}

/* Right Section for Top 5 Downloaded Images */
.top-images {
    width: 35%; /* Take up 35% of the container width */
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.top-images h3 {
    margin-bottom: 15px;
    color: #3498db;
}

.top-images ul {
    list-style-type: none;
    padding: 0;
}

.top-images li {
    margin-bottom: 10px;
    font-size: 16px;
}

/* Right section for messages */
.right-section {
    width: 300px;
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.right-section h3 {
    color: #e74c3c;
}

.right-section p {
    font-size: 16px;
    color: rgba(0, 0, 0, 0.7);
}

/* Make the layout responsive */
@media screen and (max-width: 768px) {
    .dashboard-container {
        flex-direction: column;
        align-items: center;
    }

    .analytics, .right-section {
        width: 100%;
        margin-right: 0;
        margin-bottom: 20px;
    }
}
        
    </style>
</head>
<body>
        <!-- Navigation Bar -->
        <nav>
        <div class="nav-links">
                <a href="index.php">Home</a>
                <form method="POST" action="logout.php" style="display: inline;">
                    <button type="submit">Logout</button>
                </form>
                <a href="https://mail.google.com/mail/?view=cm&fs=1&to=admin@example.com&su=Contacting%20Admin&body=Hello%20Admin,%0A%0APlease%20describe%20your%20issue..." target="_blank">Contact Admin</a>
        </div>
    </nav>
    <h1>Welcome to Your Dashboard, <?= $username['username']; ?> !!</h1>
    <div class="dashboard-container">
        <form method="POST" enctype="multipart/form-data" class="upload-form" onsubmit="return validateImageSize()">
            <h2>Upload New Image</h2>
            <br>
            <label for="title">Title:</label>
            <input type="text" name="title" required><br>
            <label for="category">Category:</label>
            <select name="category">
            <option value="nature">Nature</option>
                    <option value="city">City</option>
                    <option value="temple">Temple</option>
                    <option value="landscape">Landscape</option>
                    <option value="aesthetic">Aesthetic</option>
                    <option value="tech">Tech</option>
                    <option value="animal">Animal</option>
                    <option value="sky">Sky</option>
                    <option value="anime">Anime</option>
                    <option value="flower">Flower</option>
                    <option value="bird">Bird</option>
            </select><br>

            <label for="size">Size:</label>
            <select name="size" id="size">
            <option value="600x700">600x700</option>
                <option value="1200x800">1200x800</option>
                <option value="1980x1080">1980x1080</option>
            </select><br>

            <label for="image">Choose Image:</label>
            <input type="file" name="image" id="image" accept=".jpg, .jpeg, .png" required><br>

            <button type="submit">Upload Image</button>
            <?php
// Calculate the number of images uploaded today
$stmt = $conn->prepare("SELECT COUNT(*) as image_count FROM images WHERE user_id = :user_id AND DATE(upload_date) = CURDATE()");
$stmt->execute([':user_id' => $user_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$uploaded_today = $result['image_count'];
$remaining_uploads = max(0, 7 - $uploaded_today);
?>
<br>
            <p style="padding-top:15px;">You can upload <strong><?= $remaining_uploads ?></strong> more image(s) today.</p>
        </form>
                <!-- Analytics Section -->
                <div class="dashboard-contain">
    <!-- Left section for Analytics -->
    <div class="analytics">
        <h2>Analytics of Your Uploads</h2>

        <!-- Uploads This Month -->
        <p><strong>Successful Uploads This Month:</strong> <?= $uploads_this_month ?></p>

        <!-- Downloads This Month -->
        <p><strong>Downloads This Month:</strong> <?= $downloads_this_month ?></p>

        <!-- Approved vs Rejected Percentage -->
        <p><strong>Approved Images:</strong> <?= $approved_percent ?>%</p>
        <p><strong>Rejected Images:</strong> <?= $rejected_percent ?>%</p>
    </div>

    <!-- Right section for Top 5 Downloaded Images -->
    <div class="top-images">
        <h3>Top 5 Downloaded Images:</h3>
        <ul>
            <?php foreach ($top_downloaded_images as $image): ?>
                <li><strong><?= htmlspecialchars($image['title']) ?></strong> - <?= $image['download_count'] ?> downloads</li>
            <?php endforeach; ?>
        </ul>
    
    </div>

    <!-- Right section for Messages -->
    <div class="right-section">
        <h3>Messages from Admin</h3>
        <?php if ($user['message']): ?>
            <p><strong>Date:</strong> <?= $user['dateofMessage']; ?></p>
            <br>
            <p><strong>🔔 Message:</strong> <?= $user['message']; ?></p>
        <?php else: ?>
            <p>You have no messages from the admin.</p>
        <?php endif; ?>
    </div>
</div>

        <!-- Display User's Uploaded Images -->
         <h1>Your Uploaded Images</h1>
         <br>
        <div class="image-gallery">
            <?php foreach ($images as $image): ?>
                <div>
                    <img src="<?= $image['path'] ?>" alt="<?= $image['title'] ?>">
                    <p><strong><?= $image['title'] ?></strong></p>
                    <p><?= $image['category'] ?></p>
                    <p><?= $image['upload_date'] ?></p>
                    <p class="status <?= $image['status'] ?>"><?= $image['status'] ?></p>

                    <br>
                    <a href="<?= $image['path'] ?>" download style="color:white; text-decoration:none;"><button>Download</a></button><br>
                    <a href="dashboard.php?delete_id=<?= $image['id'] ?>">
                        <button>Delete Image</button>
                    </a>
                    <p style="padding-top:15px;">Downloaded by <?= $image['download_count'] ?> user(s)</p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
         // Function to validate image size when the file is selected
         document.getElementById('image').addEventListener('change', function(event) {
            var file = event.target.files[0];
            if (file) {
                var selectedSize = document.getElementById('size').value.split('x'); // Extract width and height
                var expectedWidth = parseInt(selectedSize[0]);
                var expectedHeight = parseInt(selectedSize[1]);

                var img = new Image();
                img.onload = function() {
                    // Check if the uploaded image dimensions match the selected size
                    if (img.width !== expectedWidth || img.height !== expectedHeight) {
                        alert('The uploaded image does not match the selected size of ' + expectedWidth + 'x' + expectedHeight + '. Please upload an image of the correct size.');
                        document.getElementById('image').value = ''; // Clear the file input
                    }
                };
                img.src = URL.createObjectURL(file); // Create a URL for the uploaded file
            }
        });
    </script>
    <footer>
        <h1></h1>
            </footer>


</body>
</html>
