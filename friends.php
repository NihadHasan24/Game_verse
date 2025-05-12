<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle friend request
if (isset($_POST['send_request'])) {
    $friend_id = $_POST['friend_id'];
    $query = "INSERT INTO friendships (user_id, friend_id) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $friend_id);
    mysqli_stmt_execute($stmt);
}

// Handle accept/reject request
if (isset($_POST['action']) && isset($_POST['request_id'])) {
    $action = $_POST['action'];
    $request_id = $_POST['request_id'];
    $status = ($action === 'accept') ? 'accepted' : 'rejected';
    $query = "UPDATE friendships SET status = ? WHERE id = ? AND friend_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sii", $status, $request_id, $user_id);
    mysqli_stmt_execute($stmt);
}

// Fetch friend requests
$request_query = "SELECT f.id, u.username FROM friendships f
                  JOIN users u ON f.user_id = u.id
                  WHERE f.friend_id = ? AND f.status = 'pending'";
$request_stmt = mysqli_prepare($conn, $request_query);
mysqli_stmt_bind_param($request_stmt, "i", $user_id);
mysqli_stmt_execute($request_stmt);
$request_result = mysqli_stmt_get_result($request_stmt);

// Fetch friends list
$friends_query = "SELECT u.id, u.username FROM friendships f
                  JOIN users u ON (f.friend_id = u.id OR f.user_id = u.id)
                  WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = 'accepted' AND u.id != ?";
$friends_stmt = mysqli_prepare($conn, $friends_query);
mysqli_stmt_bind_param($friends_stmt, "iii", $user_id, $user_id, $user_id);
mysqli_stmt_execute($friends_stmt);
$friends_result = mysqli_stmt_get_result($friends_stmt);

// Fetch other users (potential friends)
$others_query = "SELECT id, username FROM users WHERE id != ? AND id NOT IN 
                 (SELECT IF(user_id = ?, friend_id, user_id) FROM friendships WHERE user_id = ? OR friend_id = ?)";
$others_stmt = mysqli_prepare($conn, $others_query);
mysqli_stmt_bind_param($others_stmt, "iiii", $user_id, $user_id, $user_id, $user_id);
mysqli_stmt_execute($others_stmt);
$others_result = mysqli_stmt_get_result($others_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friends - Gaming Community</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        h1 {
            text-align: center;
            color: #1877f2;
            margin-bottom: 2rem;
        }
        .friends-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .friends-section {
            flex-basis: calc(33.333% - 1rem);
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }
        .friends-section:hover {
            transform: translateY(-5px);
        }
        .friends-section h2 {
            color: #1877f2;
            margin-top: 0;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        .friend-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background-color: #f7f7f7;
            border-radius: 4px;
        }
        .friend-action {
            background-color: #1877f2;
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 0.9rem;
        }
        .friend-action:hover {
            background-color: #166fe5;
        }
        .friend-action.reject {
            background-color: #e4e6eb;
            color: #050505;
        }
        .friend-action.reject:hover {
            background-color: #d8dadf;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 2rem;
            color: #1877f2;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .friends-section {
                flex-basis: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Friends</h1>
        <div class="friends-container">
            <div class="friends-section">
                <h2><i class="fas fa-user-plus"></i> Friend Requests</h2>
                <?php if (mysqli_num_rows($request_result) > 0): ?>
                    <?php while ($request = mysqli_fetch_assoc($request_result)): ?>
                        <div class="friend-item">
                            <span><?php echo htmlspecialchars($request['username']); ?></span>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                <button type="submit" name="action" value="accept" class="friend-action">Accept</button>
                                <button type="submit" name="action" value="reject" class="friend-action reject">Reject</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No pending friend requests.</p>
                <?php endif; ?>
            </div>
            <div class="friends-section">
                <h2><i class="fas fa-users"></i> Your Friends</h2>
                <?php if (mysqli_num_rows($friends_result) > 0): ?>
                    <?php while ($friend = mysqli_fetch_assoc($friends_result)): ?>
                        <div class="friend-item">
                            <span><?php echo htmlspecialchars($friend['username']); ?></span>
                            <button class="friend-action" onclick="alert('View profile feature coming soon!')">View Profile</button>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>You haven't added any friends yet.</p>
                <?php endif; ?>
            </div>
            <div class="friends-section">
                <h2><i class="fas fa-user-plus"></i> Add Friends</h2>
                <?php if (mysqli_num_rows($others_result) > 0): ?>
                    <?php while ($other = mysqli_fetch_assoc($others_result)): ?>
                        <div class="friend-item">
                            <span><?php echo htmlspecialchars($other['username']); ?></span>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="friend_id" value="<?php echo $other['id']; ?>">
                                <button type="submit" name="send_request" class="friend-action">Add Friend</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No new users to add as friends.</p>
                <?php endif; ?>
            </div>
        </div>
        <a href="index.php" class="back-link">Back to Home</a>
    </div>
</body>
</html>