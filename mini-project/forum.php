<?php
session_start();

// 1. Simple Bouncer: Must be logged in
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: 04_login_exercise.php');
    exit;
}

// 2. Database Connection (PDO)
$db = new PDO("mysql:host=localhost;dbname=login_auth", 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 3. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $email = $_SESSION['email'];

    if (!empty($message)) {
        $stmt = $db->prepare("INSERT INTO posts (user_email, title, message) VALUES (:email, :title, :message)");
        $stmt->execute([
            ':email' => $email,
            ':title' => $title,
            ':message' => $message
        ]);
        // Redirect to prevent double-posting on refresh
        header('Location: forum.php');
        exit;
    }
}

// 4. Fetch all posts (newest first)
$stmt = $db->query("SELECT * FROM posts ORDER BY created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_OBJ);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Simple Forum</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
</head>
<body>

<div class="container my-5">
    <h2>Simple Forum</h2>
    <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['email']); ?></strong>! 
       <a href="05_logout_exercise.php">Log out</a></p>
    <hr>

    <!-- Post Form -->
    <div class="card p-4 mb-4">
        <h4>Create a new post</h4>
        <form method="POST">
            <div class="form-group">
                <label>Title (optional)</label>
                <input type="text" name="title" class="form-control" maxlength="100">
            </div>
            <div class="form-group">
                <label>Message</label>
                <textarea name="message" class="form-control" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Post</button>
        </form>
    </div>

    <!-- Posts List -->
    <h4>Recent Posts</h4>
    <?php if (empty($posts)): ?>
        <p class="text-muted">No posts yet. Be the first to post!</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">
                        <?php echo htmlspecialchars($post->title ?: 'No Title'); ?>
                    </h5>
                    <h6 class="card-subtitle mb-2 text-muted">
                        Posted by <?php echo htmlspecialchars($post->user_email); ?> 
                        on <?php echo date('M j, Y g:i A', strtotime($post->created_at)); ?>
                    </h6>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($post->message)); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>