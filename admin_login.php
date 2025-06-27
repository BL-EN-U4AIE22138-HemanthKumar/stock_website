<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id']; // Store admin session
        header("Location: admin_dashboard.php"); // Redirect to the admin dashboard
        exit;
    } else {
        $error = "Invalid credentials!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* General Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background-color: white;
    color: #333;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    flex-direction: column;
}

h1 {
    font-size: 2.5rem;
    color: #3498db;
    margin-bottom: 30px;
    text-align: center;
}

/* Form Styles */
form {
    background-color: #f7f9fc;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 400px;
    box-sizing: border-box;
}

form label {
    font-size: 1rem;
    margin-bottom: 5px;
    display: block;
    color: #555;
}

form input {
    width: 100%;
    padding: 12px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    color: #333;
    background-color: #fff;
}

form input[type="password"] {
    font-family: Arial, sans-serif;
}

form button {
    width: 100%;
    padding: 12px;
    font-size: 1.2rem;
    background-color: #3498db;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

form button:hover {
    background-color: #2980b9;
}

/* Error Message Styles */
.error {
    color: red;
    font-size: 1rem;
    text-align: center;
    margin-top: 20px;
}

/* Responsive Styles */
@media (max-width: 600px) {
    h1 {
        font-size: 2rem;
    }

    form {
        padding: 20px;
    }

    form input,
    form button {
        padding: 10px;
    }
}
    </style>
</head>
<body>
    <h1>Admin Login</h1>
    <form method="POST">
        <label for="username">Username:</label>
        <input type="text" name="username" required>
        <label for="password">Password:</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>
    <?php if (isset($error)): ?>
            <p class="error"><?= $error; ?></p>
        <?php endif; ?>
</body>
</html>