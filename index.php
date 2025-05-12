<?php
require_once 'config.php';

require_once 'notifications.php';

// Fetch upcoming competitions
$comp_query = "SELECT name, start_date FROM competitions WHERE start_date > CURDATE() ORDER BY start_date ASC LIMIT 5";
$comp_result = mysqli_query($conn, $comp_query);

// Fetch recent forum posts
$forum_query = "SELECT fp.title, u.username 
                FROM forum_posts fp 
                JOIN users u ON fp.user_id = u.id 
                ORDER BY fp.id DESC LIMIT 5";
$forum_result = mysqli_query($conn, $forum_query);

// Fetch top players
$leaderboard_query = "SELECT u.username, SUM(cr.points) as total_score 
                      FROM competition_results cr
                      JOIN users u ON cr.user_id = u.id 
                      GROUP BY cr.user_id 
                      ORDER BY total_score DESC 
                      LIMIT 5";
$leaderboard_result = mysqli_query($conn, $leaderboard_query);

$friend_request_count = isset($_SESSION['user_id']) ? get_friend_request_count($conn, $_SESSION['user_id']) : 0;

// Error checking function
function check_query_error($result, $query) {
    global $conn;
    if (!$result) {
        die("Query failed: " . mysqli_error($conn) . "<br>Query: " . $query);
    }
}

// Check for query errors
check_query_error($comp_result, $comp_query);
check_query_error($forum_result, $forum_query);
check_query_error($leaderboard_result, $leaderboard_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game_verse - Your Gaming Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --background-color: #f0f2f5;
            --text-color: #333;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: #fff;
            padding: 1rem 0;
            box-shadow: var(--box-shadow);
        }

        header h1 {
            margin: 0;
            font-size: 2.5rem;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        nav {
            background-color: #fff;
            padding: 1rem 0;
            box-shadow: var(--box-shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        nav ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
        }

        nav li {
            margin: 0.5rem 1rem;
        }

        nav a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 600;
            transition: color 0.3s ease, transform 0.3s ease;
            display: flex;
            align-items: center;
        }

        nav a:hover {
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .main-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-top: 2rem;
        }

        .welcome-message {
            width: 100%;
            background-color: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
            text-align: center;
            font-size: 1.2rem;
            animation: fadeIn 1s ease-out;
        }

        .feature-box {
            flex-basis: calc(33.333% - 1rem);
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .feature-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .feature-box h2 {
            color: var(--primary-color);
            margin-top: 0;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
        }

        .feature-box ul, .feature-box ol {
            padding-left: 1.5rem;
        }

        .feature-box li {
            margin-bottom: 0.5rem;
        }

        .feature-box a {
            display: inline-block;
            margin-top: 1rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .feature-box a:hover {
            color: var(--secondary-color);
            transform: translateX(5px);
        }

        .highlight {
            color: var(--secondary-color);
            font-weight: 700;
        }

        .icon {
            margin-right: 0.5rem;
            font-size: 1.2em;
        }

        .notification-badge {
            background-color: #e53e3e;
            color: #fff;
            border-radius: 50%;
            padding: 0.2rem 0.5rem;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }

        @media (max-width: 768px) {
            .feature-box {
                flex-basis: 100%;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><span class="highlight">Game_verse</span></h1>
        </div>
    </header>

    <nav>
        <ul>
            <li><a href="index.php"><i class="fas fa-home icon"></i>Home</a></li>
            <li><a href="competitions.php"><i class="fas fa-trophy icon"></i>Competitions</a></li>
            <li><a href="games.php"><i class="fas fa-gamepad icon"></i>Popular Games</a></li>
            <li><a href="leaderboard.php"><i class="fas fa-chart-line icon"></i>Leaderboard</a></li>
            <li>
                <a href="friends.php">
                    <i class="fas fa-users icon"></i>Friends
                    <?php if ($friend_request_count > 0): ?>
                        <span class="notification-badge"><?php echo $friend_request_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="forum.php"><i class="fas fa-comments icon"></i>Community Forum</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="profile.php"><i class="fas fa-user icon"></i>My Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt icon"></i>Logout</a></li>
            <?php else: ?>
                <li><a href="login.php"><i class="fas fa-sign-in-alt icon"></i>Login</a></li>
                <li><a href="register.php"><i class="fas fa-user-plus icon"></i>Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="container">
        <div class="main-content">
            <?php if (isset($_SESSION['user_id'])): ?>
                <p class="welcome-message">Hello, <span class="highlight"><?php echo htmlspecialchars($_SESSION['username']); ?></span>! Welcome to your gaming hub.</p>
            <?php else: ?>
                <p class="welcome-message">Welcome to <span class="highlight">Game_verse</span>! Join us to participate in exciting competitions and connect with fellow gamers.</p>
            <?php endif; ?>
            
            <div class="feature-box">
                <h2><i class="fas fa-calendar-alt icon"></i>Upcoming Competitions</h2>
                <?php if (mysqli_num_rows($comp_result) > 0): ?>
                    <ul>
                    <?php while ($row = mysqli_fetch_assoc($comp_result)): ?>
                        <li><?php echo htmlspecialchars($row['name']); ?> - Starting on <?php echo date('F j, Y', strtotime($row['start_date'])); ?></li>
                    <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>No upcoming competitions at the moment.</p>
                <?php endif; ?>
                <a href="competitions.php">View all competitions <i class="fas fa-arrow-right"></i></a>
            </div>

            <div class="feature-box">
                <h2><i class="fas fa-bullhorn icon"></i>Community Highlights</h2>
                <?php if (mysqli_num_rows($forum_result) > 0): ?>
                    <ul>
                    <?php while ($row = mysqli_fetch_assoc($forum_result)): ?>
                        <li><?php echo htmlspecialchars($row['title']); ?> - by <?php echo htmlspecialchars($row['username']); ?></li>
                    <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>No recent forum activity.</p>
                <?php endif; ?>
                <a href="forum.php">Join the conversation <i class="fas fa-arrow-right"></i></a>
            </div>

            <div class="feature-box">
                <h2><i class="fas fa-medal icon"></i>Leaderboard Updates</h2>
                <?php if (mysqli_num_rows($leaderboard_result) > 0): ?>
                    <ol>
                    <?php while ($row = mysqli_fetch_assoc($leaderboard_result)): ?>
                        <li><?php echo htmlspecialchars($row['username']); ?> - <?php echo number_format($row['total_score']); ?> points</li>
                    <?php endwhile; ?>
                    </ol>
                <?php else: ?>
                    <p>No leaderboard data available.</p>
                <?php endif; ?>
                <a href="leaderboard.php">View full leaderboard <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
</body>
</html>

