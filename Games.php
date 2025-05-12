<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$api_key = "e1bd429ceb164d9fae126463588490b6";
$api_url = "https://api.rawg.io/api/games?key={$api_key}&ordering=-rating&page_size=10";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$games_data = json_decode($response, true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Popular Games - Game_verse</title>
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
        }
        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
        }
        .game-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .game-card:hover {
            transform: translateY(-5px);
        }
        .game-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .game-info {
            padding: 1rem;
        }
        .game-title {
            font-size: 1.2rem;
            margin: 0 0 0.5rem 0;
        }
        .game-rating {
            font-weight: bold;
            color: #1877f2;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 2rem;
            color: #1877f2;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Popular Games</h1>
        <div class="games-grid">
            <?php foreach ($games_data['results'] as $game): ?>
                <div class="game-card">
                    <img src="<?php echo $game['background_image']; ?>" alt="<?php echo $game['name']; ?>">
                    <div class="game-info">
                        <h3 class="game-title"><?php echo $game['name']; ?></h3>
                        <p class="game-rating">Rating: <?php echo $game['rating']; ?>/5</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="index.php" class="back-link">Back to Home</a>
    </div>
</body>
</html>