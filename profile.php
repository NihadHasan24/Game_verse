<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user profile
$profile_query = "SELECT p.*, u.username, u.email, u.total_points 
                  FROM users u
                  LEFT JOIN profiles p ON p.user_id = u.id
                  WHERE u.id = ?";
$profile_stmt = mysqli_prepare($conn, $profile_query);
mysqli_stmt_bind_param($profile_stmt, "i", $user_id);
mysqli_stmt_execute($profile_stmt);
$profile_result = mysqli_stmt_get_result($profile_stmt);
$profile = mysqli_fetch_assoc($profile_result);

// Fetch joined competitions
$competitions_query = "SELECT c.name, c.start_date, c.end_date, c.is_finished
                       FROM competitions c
                       JOIN competition_participants cp ON c.id = cp.competition_id
                       WHERE cp.user_id = ?
                       ORDER BY c.start_date DESC";
$competitions_stmt = mysqli_prepare($conn, $competitions_query);
mysqli_stmt_bind_param($competitions_stmt, "i", $user_id);
mysqli_stmt_execute($competitions_stmt);
$competitions_result = mysqli_stmt_get_result($competitions_stmt);
$joined_competitions = mysqli_fetch_all($competitions_result, MYSQLI_ASSOC);

// Fetch recent competition results
$comp_query = "SELECT c.name, cr.position, cr.points 
               FROM competition_results cr
               JOIN competitions c ON cr.competition_id = c.id
               WHERE cr.user_id = ?
               ORDER BY c.end_date DESC
               LIMIT 5";
$comp_stmt = mysqli_prepare($conn, $comp_query);
mysqli_stmt_bind_param($comp_stmt, "i", $user_id);
mysqli_stmt_execute($comp_stmt);
$comp_result = mysqli_stmt_get_result($comp_stmt);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bio = $_POST['bio'];
    $favorite_game = $_POST['favorite_game'];

    $query = "INSERT INTO profiles (user_id, bio, favorite_game) VALUES (?, ?, ?)
              ON DUPLICATE KEY UPDATE bio = ?, favorite_game = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "issss", $user_id, $bio, $favorite_game, $bio, $favorite_game);
    mysqli_stmt_execute($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Game_verse</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        .container {
            width: 80%;
            max-width: 800px;
            margin: 2rem auto;
            background-color: #fff;
            padding: 2rem;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
        textarea,
        input[type="text"] {
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-family: 'Roboto', sans-serif;
        }
        textarea {
            height: 100px;
            resize: vertical;
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
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #e8491d;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .competitions {
            margin-top: 2rem;
        }
        .competition {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .competition h3 {
            margin-top: 0;
            color: #35424a;
        }
        .profile-info {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
 
       .recent-competitions {
           background-color: #f0f0f0;
           border: 1px solid #ddd;
           border-radius: 5px;
           padding: 1rem;
           margin-top: 1rem;
        }

       .recent-competitions h2 {
           margin-top: 0;
        }

       .recent-competitions ul {
          padding-left: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>My Profile</h1>
        <div class="profile-info">
          <p><strong>Username:</strong> <?php echo htmlspecialchars($profile['username']); ?></p>
          <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>
          <p><strong>Total Points:</strong> <?php echo number_format($profile['total_points']); ?></p>
        </div>
        <form method="POST">
            <label for="bio">Bio:</label>
            <textarea id="bio" name="bio"><?php echo $profile['bio'] ?? ''; ?></textarea><br>
            <label for="favorite_game">Favorite Game:</label>
            <input type="text" id="favorite_game" name="favorite_game" value="<?php echo $profile['favorite_game'] ?? ''; ?>"><br>
            <input type="submit" value="Update Profile">
        </form>
        <div class="friends-section">
           <h2>Friends</h2>
           <?php
           $friends_query = "SELECT u.id, u.username FROM friendships f
                      JOIN users u ON (f.friend_id = u.id OR f.user_id = u.id)
                      WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = 'accepted' AND u.id != ?
                      LIMIT 5";
           $friends_stmt = mysqli_prepare($conn, $friends_query);
           mysqli_stmt_bind_param($friends_stmt, "iii", $user_id, $user_id, $user_id);
           mysqli_stmt_execute($friends_stmt);
           $friends_result = mysqli_stmt_get_result($friends_stmt);
           ?>
           <?php while ($friend = mysqli_fetch_assoc($friends_result)): ?>
                <div class="friend-item">
                    <span><?php echo htmlspecialchars($friend['username']); ?></span>
                </div>
           <?php endwhile; ?>
           <a href="friends.php">View all friends</a>
        </div>

        <div class="competitions">
            <h2>Joined Competitions</h2>
            <?php if (empty($joined_competitions)): ?>
                <p>You haven't joined any competitions yet.</p>
            <?php else: ?>
                <?php foreach ($joined_competitions as $competition): ?>
                    <div class="competition">
                        <h3><?php echo htmlspecialchars($competition['name']); ?></h3>
                        <p>Start Date: <?php echo htmlspecialchars($competition['start_date']); ?></p>
                        <p>End Date: <?php echo htmlspecialchars($competition['end_date']); ?></p>
                        <p>Status: <?php echo $competition['is_finished'] ? 'Finished' : 'Ongoing'; ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="recent-competitions">
           <h2>Recent Competition Results</h2>
           <?php if (mysqli_num_rows($comp_result) > 0): ?>
              <ul>
              <?php while ($row = mysqli_fetch_assoc($comp_result)): ?>
                  <li>
                     <?php echo htmlspecialchars($row['name']); ?> - 
                     Position: <?php echo $row['position']; ?>, 
                     Points: <?php echo $row['points']; ?>
                  </li>
              <?php endwhile; ?>
              </ul>
           <?php else: ?>
               <p>You haven't participated in any competitions yet.</p>
           <?php endif; ?>
        </div>

        <p><a href="index.php">Back to Home</a></p>
    </div>
</body>
</html>