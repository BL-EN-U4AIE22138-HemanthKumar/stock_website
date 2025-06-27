<?php
$password = 'adminpassword';  // Admin password (plaintext)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);  // Hash the password
$conn = new PDO("mysql:host=localhost;dbname=stock_images", "root", "");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// Now, insert the username, email, and hashed password into the admin table
// Example SQL query to insert the admin credentials
$sql = "INSERT INTO admin (username, email, password) VALUES ('admin', 'admin@example.com', :password)";
$stmt = $conn->prepare($sql);
$stmt->execute([':password' => $hashed_password]);

echo "Admin inserted successfully!";
?>