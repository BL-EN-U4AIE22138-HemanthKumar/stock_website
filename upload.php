<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $category = $_POST['category'];
    $size = $_POST['size'];
    $upload_dir = "uploads/";
    $file_path = $upload_dir . basename($_FILES['image']['name']);

    if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
        $conn = new PDO("mysql:host=localhost;dbname=stock_images", "root", "");
        $stmt = $conn->prepare("INSERT INTO images (title, category, size, path, upload_date) VALUES (:title, :category, :size, :path, NOW())");
        $stmt->execute([':title' => $title, ':category' => $category, ':size' => $size, ':path' => $file_path]);
        echo "Image uploaded successfully!";\
    } else {
        echo "Failed to upload image.";
    }
}
?>
