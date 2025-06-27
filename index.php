<?php
session_start(); // Start session to track user login state

// Check if user is logged in, if not, show login and signup options
$isLoggedIn = isset($_SESSION['user_id']);

// If logged in, display gallery
include 'db.php';
// If the download link is clicked, update the download count
if (isset($_GET['download_id'])) {
    $image_id = $_GET['download_id'];

    // Validate the image ID to prevent SQL injection
    if (is_numeric($image_id)) {
        // Increment the download count
        $stmt = $conn->prepare("UPDATE images SET download_count = download_count + 1 WHERE id = :image_id");
        $stmt->execute([':image_id' => $image_id]);
    }

    // Get the image path to serve the download
    $stmt = $conn->prepare("SELECT path FROM images WHERE id = :image_id");
    $stmt->execute([':image_id' => $image_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the image exists, force download
    if ($image) {
        // Path to the image file
        $filePath = $image['path'];

        // Check if the file exists
        if (file_exists($filePath)) {
            // Set headers for download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }
    }
}
$stmt = $conn->prepare("SELECT * FROM images WHERE status = 'approved' ORDER BY upload_date DESC");
$stmt->execute();
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Stock Image Gallery</title>
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
            display: flex;
            flex-direction: column;
            background-color: #f9f9f9;
            color: #333;
            height: 100vh;
            font-family: calibri;
        }

        /* Navigation Bar */
        nav {
            background-color: #3498db;
            padding: 10px 20px;
            display: flex;
            height:60px;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        nav h1 {
            font-size: 30px;
            margin: 10px;
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

        /* Layout Styling */
        .container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        /* Filter Section */
        .filter-section {
            background-color: #ffffff;
            flex: 0 0 15%;
            padding: 20px;
            border-right: 1px solid #ddd;
            overflow-y: auto;
        }

        .filter-section form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .filter-section label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .filter-section select, .filter-section input, .filter-section button {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .filter-section button {
            background-color: #3498db;
            color: white;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        .filter-section button:hover {
            background-color: #2980b9;
        }

        /* Gallery Section */
        .gallery-section {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            overflow-y: auto;
            height: 100%;
        }

        .gallery-section img {
            width: 280px;
            height: 200px;
            border-radius: 8px;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .gallery-section img:hover {
            transform: scale(1.05);
        }

        .gallery-section a {
            display: inline-block;
            margin-top: 5px;
            background-color: #3498db;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
        }

        .gallery-section a:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav>
        <h1>Stock Image Gallery</h1>
        <div class="nav-links">
            <?php if ($isLoggedIn): ?>
                <a href="dashboard.php" style="font-size:17px; padding-bottom:1px;">Go to Dashboard</a>
                <form method="POST" action="logout.php" style="display: inline;">
                    <button type="submit">Logout</button>
                </form>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="sign_up.php">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Filter Section -->
        <aside class="filter-section">
            <h2>Filter Options</h2>
            <br>
            <form method="GET" action="filter.php">
                <label for="category">Category:</label>
                <select name="category">
                    <option value="">All</option>
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
                </select>

                <label for="date">Date:</label>
                <input type="date" name="start_date">
                <h5 style="text-align:center;">to</h5>
                <input type="date" name="end_date" max="<?php echo date('Y-m-d'); ?>">

                <label for="size">Size:</label>
                <select name="size">
                    <option value="">All</option>
                    <option value="600x700">600x700</option>
                    <option value="1200x800">1200x800</option>
                    <option value="1980x1080">1980x1080</option>
                </select>

                <button type="submit">Filter</button>
            </form>
        </aside>
        <div class="gallery-section">

            <?php foreach ($images as $image): ?>
                <div>
                    <img src="<?= $image['path']; ?>" alt="<?= $image['title']; ?>">
                    <p style="font-size:19px; font-weight:bold; padding-top:5px;"><?= $image['title']; ?></p>
                    <p style="font-size:15px; color:rgba(0,0,0,0.6);"><?= $image['category']; ?></p>
                    <p style="font-size:15px; color:rgba(0,0,0,0.6);"><?= $image['size']; ?></p>
                    <a href="index.php?download_id=<?= $image['id']; ?>">Download</a> <!-- Trigger download and count increment -->
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
