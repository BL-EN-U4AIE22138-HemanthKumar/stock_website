<?php
$conn = new PDO("mysql:host=localhost;dbname=stock_images", "root", "");

// Retrieve filter values from the GET request
$category = $_GET['category'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$size = $_GET['size'] ?? '';

// Build the query with filters
$query = "SELECT * FROM images WHERE 1=1 and status = 'approved'";
if ($category) $query .= " AND category = :category";
if ($start_date) $query .= " AND upload_date >= :start_date";
if ($end_date) $query .= " AND upload_date <= :end_date";
if ($size) $query .= " AND size = :size";

$stmt = $conn->prepare($query);

// Bind parameters
if ($category) $stmt->bindParam(':category', $category);
if ($start_date) $stmt->bindParam(':start_date', $start_date);
if ($end_date) $stmt->bindParam(':end_date', $end_date);
if ($size) $stmt->bindParam(':size', $size);

$stmt->execute();
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filtered Images</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            color: #333;
        }

        h1 {
            text-align: center;
            margin-top: 30px;
            color: #3498db;
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .image-container {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .image-container img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .image-container p {
            padding-bottom: 1px;
            margin: 10px;
        }

        .image-container .title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .image-container .category,
        .image-container .size {
            font-size: 14px;
            color: rgba(0,0,0,0.6);
        }

        .image-container .download-btn {
            display: block;
            margin: 10px;
            padding: 8px 15px;
            text-align: center;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .image-container .download-btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>

<h1>Filtered Images</h1>

<div class="gallery">
    <?php foreach ($images as $image): ?>
        <div class="image-container">
            <img src="<?= htmlspecialchars($image['path']); ?>" alt="<?= htmlspecialchars($image['title']); ?>">
            <p class="title"><?= htmlspecialchars($image['title']); ?></p>
            <p class="category">Category: <?= htmlspecialchars($image['category']); ?></p>
            <p class="size">Size: <?= htmlspecialchars($image['size']); ?></p>
            <a href="index.php?download_id=<?= $image['id']; ?>" class="download-btn">Download</a>
        </div>
    <?php endforeach; ?>
</div>

<?php
// Handle the download increment
if (isset($_GET['download_id'])) {
    $image_id = $_GET['download_id'];
    
    // Validate the image ID
    if (is_numeric($image_id)) {
        // Increment the download count
        $stmt = $conn->prepare("UPDATE images SET download_count = download_count + 1 WHERE id = :image_id");
        $stmt->execute([':image_id' => $image_id]);

        // Fetch the image path for the download
        $stmt = $conn->prepare("SELECT path FROM images WHERE id = :image_id");
        $stmt->execute([':image_id' => $image_id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        // Serve the image for download
        if ($image) {
            // Set headers to trigger a download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($image['path']) . '"');
            header('Content-Length: ' . filesize($image['path']));
            readfile($image['path']);
            exit;
        }
    }
}
?>

</body>
</html>
