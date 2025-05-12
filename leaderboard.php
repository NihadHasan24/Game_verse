<?php
require_once 'config.php';


$query = "SELECT u.username, SUM(cr.points) as total_score 
                      FROM competition_results cr
                      JOIN users u ON cr.user_id = u.id 
                      GROUP BY cr.user_id 
                      ORDER BY total_score DESC 
                      LIMIT 10";
$result = mysqli_query($conn, $query);
$leaderboard = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Gaming Community</title>
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
        .container {h
            width: 80%;
            margin: auto;
            overflow: hidden;
            padding: 20px;
        }
        header {
            background: #35424a;
            color: #ffffff;
            padding-top: 30px;
            min-height: 70px;
            border-bottom: #e8491d 3px solid;
        }
        header h1 {
            margin: 0;
            text-align: center;
            padding-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #35424a;
            color: #ffffff;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #ddd;
        }
        .rank-1 {
            font-weight: bold;
            color: #FFD700;
        }
        .rank-2 {
            font-weight: bold;
            color: #C0C0C0;
        }
        .rank-3 {
            font-weight: bold;
            color: #CD7F32;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #35424a;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-link:hover {
            background-color: #e8491d;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Leaderboard</h1>
        </div>
    </header>
    <div class="container">
        <table>
            <tr>
                <th>Rank</th>
                <th>Username</th>
                <th>Total Score</th>
            </tr>
            <?php foreach ($leaderboard as $index => $entry): ?>
                <tr class="<?php echo 'rank-' . ($index + 1); ?>">
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($entry['username']); ?></td>
                    <td><?php echo number_format($entry['total_score']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <a href="index.php" class="back-link">Back to Home</a>
    </div>
</body>
</html>