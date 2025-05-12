<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle new post creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_post'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];

    $query = "INSERT INTO forum_posts (user_id, title, content) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iss", $user_id, $title, $content);
    mysqli_stmt_execute($stmt);
}

// Handle likes
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['like_post'])) {
    $post_id = $_POST['post_id'];
    
    // Check if user already liked the post
    $check_query = "SELECT * FROM post_likes WHERE user_id = ? AND post_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $post_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) == 0) {
        // User hasn't liked the post yet, so add the like
        $like_query = "INSERT INTO post_likes (user_id, post_id) VALUES (?, ?)";
        $like_stmt = mysqli_prepare($conn, $like_query);
        mysqli_stmt_bind_param($like_stmt, "ii", $user_id, $post_id);
        mysqli_stmt_execute($like_stmt);
    }
}

// Handle comments
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
    $post_id = $_POST['post_id'];
    $comment_content = $_POST['comment_content'];
    
    $comment_query = "INSERT INTO post_comments (user_id, post_id, content) VALUES (?, ?, ?)";
    $comment_stmt = mysqli_prepare($conn, $comment_query);
    mysqli_stmt_bind_param($comment_stmt, "iis", $user_id, $post_id, $comment_content);
    mysqli_stmt_execute($comment_stmt);
}

// Fetch forum posts with like counts and user's like status
$query = "SELECT fp.*, u.username, 
          (SELECT COUNT(*) FROM post_likes WHERE post_id = fp.id) as like_count,
          (SELECT COUNT(*) FROM post_likes WHERE post_id = fp.id AND user_id = ?) as user_liked
          FROM forum_posts fp
          JOIN users u ON fp.user_id = u.id
          ORDER BY fp.created_at DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$posts = mysqli_fetch_all($result, MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Forum</title>
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
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        h1 {
            text-align: center;
            color: #1877f2;
        }
        .post-form, .post {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .post-form h2 {
            margin-top: 0;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #1877f2;
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
        }
        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .post-meta {
            font-size: 0.9rem;
            color: #65676b;
        }
        .post-content {
            margin-bottom: 1rem;
        }
        .post-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .like-btn, .comment-btn {
            background: none;
            border: none;
            color: #65676b;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .like-btn.liked {
            color: #1877f2;
        }
        .comments {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #ddd;
        }
        .comment {
            margin-bottom: 0.5rem;
        }
        .comment-form {
            display: flex;
            margin-top: 1rem;
        }
        .comment-form input[type="text"] {
            flex-grow: 1;
            margin-right: 0.5rem;
            margin-bottom: 0;
        }
        .comment-form input[type="submit"] {
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Community Forum</h1>

        <div class="post-form">
            <h2>Create a New Post</h2>
            <form method="POST">
                <input type="text" name="title" placeholder="Title" required>
                <textarea name="content" placeholder="Content" required></textarea>
                <input type="submit" name="create_post" value="Create Post">
            </form>
        </div>

        <?php foreach ($posts as $post): ?>
            <div class="post">
                <div class="post-header">
                    <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                    <span class="post-meta">Posted by <?php echo htmlspecialchars($post['username']); ?> on <?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?></span>
                </div>
                <div class="post-content">
                    <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                </div>
                <div class="post-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <button type="submit" name="like_post" class="like-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>">
                            <i class="fas fa-thumbs-up"></i> Like (<?php echo $post['like_count']; ?>)
                        </button>
                    </form>
                    <button class="comment-btn" onclick="toggleComments(<?php echo $post['id']; ?>)">
                        <i class="fas fa-comment"></i> Comment
                    </button>
                </div>
                <div id="comments-<?php echo $post['id']; ?>" class="comments" style="display: none;">
                    <?php
                    $comment_query = "SELECT pc.*, u.username FROM post_comments pc JOIN users u ON pc.user_id = u.id WHERE pc.post_id = ? ORDER BY pc.created_at DESC";
                    $comment_stmt = mysqli_prepare($conn, $comment_query);
                    mysqli_stmt_bind_param($comment_stmt, "i", $post['id']);
                    mysqli_stmt_execute($comment_stmt);
                    $comment_result = mysqli_stmt_get_result($comment_stmt);
                    $comments = mysqli_fetch_all($comment_result, MYSQLI_ASSOC);
                    ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment">
                            <strong><?php echo htmlspecialchars($comment['username']); ?>:</strong>
                            <?php echo htmlspecialchars($comment['content']); ?>
                        </div>
                    <?php endforeach; ?>
                    <form method="POST" class="comment-form">
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <input type="text" name="comment_content" placeholder="Add a comment" required>
                        <input type="submit" name="add_comment" value="Post">
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

        <p><a href="index.php">Back to Home</a></p>
    </div>

    <script>
        function toggleComments(postId) {
            var commentsDiv = document.getElementById('comments-' + postId);
            if (commentsDiv.style.display === 'none') {
                commentsDiv.style.display = 'block';
            } else {
                commentsDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>