<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_required'] = "You must be logged in to join competitions.";
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_competition'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        $query = "INSERT INTO competitions (name, description, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssi", $name, $description, $start_date, $end_date, $user_id);
        mysqli_stmt_execute($stmt);
        $message = "Competition created successfully!";
    } elseif (isset($_POST['join_competition'])) {
        $competition_id = $_POST['competition_id'];

        // Check if the user is already participating in this competition
        $check_query = "SELECT * FROM competition_participants WHERE competition_id = ? AND user_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ii", $competition_id, $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            $message = "You are already participating in this competition.";
        } else {
            $query = "INSERT INTO competition_participants (competition_id, user_id) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $competition_id, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $message = "You have successfully joined the competition!";
            } else {
                $message = "Error joining the competition. Please try again.";
            }
        }
    } elseif (isset($_POST['finish_competition'])) {
        $competition_id = $_POST['competition_id'];
        
        // Update competition status
        $update_query = "UPDATE competitions SET is_finished = TRUE WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "i", $competition_id);
        mysqli_stmt_execute($update_stmt);

        // Award points to top 3 participants
        $points = [1000, 500, 250]; // Points for 1st, 2nd, and 3rd place
        for ($i = 0; $i < 3; $i++) {
            $position = $i + 1;
            $award_query = "INSERT INTO competition_results (competition_id, user_id, position, points)
                            SELECT ?, user_id, ?, ?
                            FROM competition_participants
                            WHERE competition_id = ?
                            ORDER BY RAND()
                            LIMIT 1";
            $award_stmt = mysqli_prepare($conn, $award_query);
            mysqli_stmt_bind_param($award_stmt, "iiii", $competition_id, $position, $points[$i], $competition_id);
            mysqli_stmt_execute($award_stmt);
        }

        // Update user total points
        $update_points_query = "UPDATE users u
                                JOIN (
                                    SELECT user_id, SUM(points) as total_points
                                    FROM competition_results
                                    GROUP BY user_id
                                ) cr ON u.id = cr.user_id
                                SET u.total_points = cr.total_points";
        mysqli_query($conn, $update_points_query);

        $message = "Competition finished and points awarded!";
    }
}

// Fetch competitions
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM competition_participants WHERE competition_id = c.id) AS participant_count,
          (SELECT COUNT(*) FROM competition_participants WHERE competition_id = c.id AND user_id = ?) AS user_joined
          FROM competitions c
          ORDER BY c.start_date DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$competitions = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Fetch top 10 users by points
$leaderboard_query = "SELECT username, total_points
                      FROM users
                      ORDER BY total_points DESC
                      LIMIT 10";
$leaderboard_result = mysqli_query($conn, $leaderboard_query);
$leaderboard = mysqli_fetch_all($leaderboard_result, MYSQLI_ASSOC);
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competitions - Gaming Community</title>
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
            max-width: 1000px;
            margin: 2rem auto;
            background-color: #fff;
            padding: 2rem;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #35424a;
        }
        h1 {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        form {
            margin-bottom: 2rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #666;
        }
        input[type="text"],
        input[type="date"],
        textarea {
            width: 100%;
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
            padding: 0.7rem 1rem;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        input[type="submit"]:hover {
            background-color: #ff5a2c;
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
        .leaderboard {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 1rem;
            margin-top: 2rem;
        }
        .leaderboard h2 {
            margin-top: 0;
        }
        .leaderboard ol {
            padding-left: 20px;
        }
    </style>
    </style>
</head>
<body>
    <div class="container">
        <h1>Competitions</h1>
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <h2>Create a Competition</h2>
        <form method="POST">
            <input type="hidden" name="create_competition" value="1">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
            
            <label for="description">Description:</label>
            <textarea id="description" name="description" required></textarea>
            
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" required>
            
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" required>
            
            <input type="submit" value="Create Competition">
        </form>

        <h2>Available Competitions</h2>
        <?php foreach ($competitions as $competition): ?>
            <div class="competition">
                <h3><?php echo htmlspecialchars($competition['name']); ?></h3>
                <p><?php echo htmlspecialchars($competition['description']); ?></p>
                <p>Start Date: <?php echo htmlspecialchars($competition['start_date']); ?></p>
                <p>End Date: <?php echo htmlspecialchars($competition['end_date']); ?></p>
                <p>Participants: <?php echo $competition['participant_count']; ?></p>
                <?php if ($competition['is_finished']): ?>
                    <p>This competition has ended.</p>
                <?php elseif ($competition['user_joined'] > 0): ?>
                    <p>You are participating in this competition</p>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="join_competition" value="1">
                        <input type="hidden" name="competition_id" value="<?php echo $competition['id']; ?>">
                        <input type="submit" value="Join Competition">
                    </form>
                <?php endif; ?>
                <?php if ($_SESSION['user_id'] == $competition['created_by'] && !$competition['is_finished']): ?>
                    <form method="POST">
                        <input type="hidden" name="finish_competition" value="1">
                        <input type="hidden" name="competition_id" value="<?php echo $competition['id']; ?>">
                        <input type="submit" value="Finish Competition">
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="leaderboard">
            <h2>Top 10 Players</h2>
            <ol>
                <?php foreach ($leaderboard as $player): ?>
                    <li><?php echo htmlspecialchars($player['username']); ?> - <?php echo $player['total_points']; ?> points</li>
                <?php endforeach; ?>
            </ol>
        </div>

        <a href="index.php" class="back-link">Back to Home</a>
    </div>
</body>
</html>