<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT id, username, password FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php");
            exit();
        }
    }

    $error = "Invalid username or password";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Game_verse</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background-color: #fff;
            padding: 2rem;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            text-align: center;
            color: #35424a;
            margin-bottom: 1.5rem;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 0.5rem;
            color: #666;
        }
        input[type="text"],
        input[type="password"] {
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        input[type="submit"] {
            background-color: #e8491d;
            color: #fff;
            padding: 0.7rem;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        input[type="submit"]:hover {
            background-color: #ff5a2c;
        }
        .error {
            color: #e8491d;
            text-align: center;
            margin-bottom: 1rem;
        }
        p {
            text-align: center;
            margin-top: 1rem;
        }
        a {
            color: #e8491d;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Login to Game_verse</h1>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <input type="submit" value="Login">
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>