<?php
session_start();

// 1. Bouncer
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: 04_login_exercise.php');
    exit;
}

// 2. Database Connection
$db = new PDO("mysql:host=localhost;dbname=login_auth", 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$current_user_id = $_SESSION['user_id'];
$current_role = $_SESSION['role'] ?? 'user';

// 3. Permission Helper (The Brain)
function can($role, $action) {
    $perms = [
        'admin'     => ['post', 'reply', 'edit_own', 'delete_own', 'delete_any', 'pin', 'announce', 'ban', 'kick', 'timeout', 'lockdown', 'manage_roles'],
        'moderator' => ['post', 'reply', 'edit_own', 'delete_own', 'delete_any', 'pin', 'announce', 'ban', 'kick', 'timeout', 'lockdown'],
        'staff'     => ['post', 'reply', 'edit_own', 'delete_own', 'pin', 'announce', 'kick', 'timeout'],
        'user'      => ['post', 'reply', 'edit_own', 'delete_own']
    ];
    return in_array($action, $perms[$role] ?? []);
}

// 4. Handle Actions (CRUD + Moderation)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CREATE POST
    if ($action === 'create_post' && can($current_role, 'post')) {
        $content = trim($_POST['content']);
        $is_ann = can($current_role, 'announce') && isset($_POST['is_announcement']) ? 1 : 0;
        if (!empty($content)) {
            $stmt = $db->prepare("INSERT INTO posts (user_id, content, is_announcement) VALUES (?, ?, ?)");
            $stmt->execute([$current_user_id, $content, $is_ann]);
        }
        header('Location: forum.php'); exit;
    }

    // CREATE REPLY
    if ($action === 'create_reply' && can($current_role, 'reply')) {
        $post_id = (int)$_POST['post_id'];
        $content = trim($_POST['content']);
        if (!empty($content)) {
            $stmt = $db->prepare("INSERT INTO replies (post_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$post_id, $current_user_id, $content]);
        }
        header('Location: forum.php#' . $post_id); exit;
    }

    // UPDATE: Edit Post
    if ($action === 'edit_post') {
        $post_id = (int)$_POST['post_id'];
        $content = trim($_POST['content']);
        // Check if owns post OR has delete_any (staff+)
        $stmt = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($post && ($post['user_id'] == $current_user_id || can($current_role, 'delete_any'))) {
            $stmt = $db->prepare("UPDATE posts SET content = ? WHERE id = ?");
            $stmt->execute([$content, $post_id]);
        }
        header('Location: forum.php'); exit;
    }

    // DELETE POST
    if ($action === 'delete_post') {
        $post_id = (int)$_POST['post_id'];
        $stmt = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($post && ($post['user_id'] == $current_user_id || can($current_role, 'delete_any'))) {
            $db->prepare("DELETE FROM posts WHERE id = ?")->execute([$post_id]);
        }
        header('Location: forum.php'); exit;
    }

    // UPDATE: Pin Post
    if ($action === 'toggle_pin' && can($current_role, 'pin')) {
        $post_id = (int)$_POST['post_id'];
        $db->prepare("UPDATE posts SET is_pinned = NOT is_pinned WHERE id = ?")->execute([$post_id]);
        header('Location: forum.php'); exit;
    }

    // UPDATE: Ban User
    if ($action === 'ban_user' && can($current_role, 'ban')) {
        $target_id = (int)$_POST['user_id'];
        $db->prepare("UPDATE users SET is_banned = NOT is_banned WHERE id = ?")->execute([$target_id]);
        header('Location: forum.php'); exit;
    }

    // UPDATE: Timeout User (1 hour)
    if ($action === 'timeout_user' && can($current_role, 'timeout')) {
        $target_id = (int)$_POST['user_id'];
        $db->prepare("UPDATE users SET timeout_until = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?")->execute([$target_id]);
        header('Location: forum.php'); exit;
    }

    // UPDATE: Lockdown Channel
    if ($action === 'toggle_lockdown' && can($current_role, 'lockdown')) {
        $db->prepare("UPDATE channel_settings SET is_locked = NOT is_locked WHERE id = 1")->execute();
        header('Location: forum.php'); exit;
    }
}

// 5. Fetch Data for Display
$settings = $db->query("SELECT * FROM channel_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$is_locked = $settings['is_locked'];

// Get all posts, pinned first
$posts_stmt = $db->query("SELECT p.*, u.email, u.role FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.is_pinned DESC, p.created_at DESC");
$posts = $posts_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all replies grouped by post_id
$replies_stmt = $db->query("SELECT r.*, u.email FROM replies r JOIN users u ON r.user_id = u.id ORDER BY r.created_at ASC");
$replies = $replies_stmt->fetchAll(PDO::FETCH_ASSOC);
$replies_by_post = [];
foreach ($replies as $reply) {
    $replies_by_post[$reply['post_id']][] = $reply;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HealthGauge Forum</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
    <style>
        .role-badge { font-size: 0.7em; padding: 3px 8px; border-radius: 10px; color: white; margin-left: 5px; }
        .role-admin { background: #e74c3c; } .role-moderator { background: #f39c12; }
        .role-staff { background: #3498db; } .role-user { background: #95a5a6; }
        .post-announcement { border-left: 5px solid #e74c3c; background: #fdf2f2; }
        .post-pinned { border-left: 5px solid #f39c12; background: #fef9e7; }
        .action-btn { font-size: 0.8em; margin-right: 5px; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📢 Community Forum</h2>
        <div>
            <span class="badge badge-info p-2">You: <?php echo htmlspecialchars($_SESSION['email']); ?> 
                <span class="role-badge role-<?php echo $current_role; ?>"><?php echo strtoupper($current_role); ?></span>
            </span>
            <a href="05_logout_exercise.php" class="btn btn-sm btn-outline-danger ml-2">Logout</a>
        </div>
    </div>

    <?php if ($is_locked): ?>
        <div class="alert alert-warning">🔒 <strong>Channel is locked.</strong> Only staff can post.</div>
    <?php endif; ?>

    <!-- CREATE POST FORM -->
    <?php if (!$is_locked || can($current_role, 'post')): ?>
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="create_post">
                <textarea name="content" class="form-control mb-2" rows="2" placeholder="Share something..." required></textarea>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <?php if (can($current_role, 'announce')): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="is_announcement" id="annCheck">
                                <label class="form-check-label" for="annCheck">📢 Make Announcement</label>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary">Post</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- MODERATION CONTROLS -->
    <div class="mb-3">
        <?php if (can($current_role, 'lockdown')): ?>
            <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="toggle_lockdown">
                <button class="btn btn-sm <?php echo $is_locked ? 'btn-success' : 'btn-warning'; ?>">
                    <?php echo $is_locked ? ' Unlock Channel' : '🔒 Lockdown Channel'; ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- POSTS LIST -->
    <?php foreach ($posts as $post): 
        $post_classes = [];
        if ($post['is_announcement']) $post_classes[] = 'post-announcement';
        if ($post['is_pinned']) $post_classes[] = 'post-pinned';
    ?>
        <div class="card mb-3 <?php echo implode(' ', $post_classes); ?>" id="post-<?php echo $post['id']; ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <h6 class="mb-1">
                        <?php echo htmlspecialchars($post['email']); ?>
                        <span class="role-badge role-<?php echo $post['role']; ?>"><?php echo strtoupper($post['role']); ?></span>
                        <?php if ($post['is_announcement']): ?><span class="badge badge-danger">ANNOUNCEMENT</span><?php endif; ?>
                        <?php if ($post['is_pinned']): ?><span class="badge badge-warning">PINNED</span><?php endif; ?>
                    </h6>
                    <small class="text-muted"><?php echo date('M j, g:i A', strtotime($post['created_at'])); ?></small>
                </div>
                <p class="mb-2"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                
                <!-- POST ACTIONS -->
                <div class="mt-2">
                    <?php if ($post['user_id'] == $current_user_id || can($current_role, 'edit_own')): ?>
                        <!-- Simple edit toggle could go here, keeping it simple with delete for now -->
                    <?php endif; ?>
                    
                    <?php if ($post['user_id'] == $current_user_id || can($current_role, 'delete_any')): ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this post?');">
                            <input type="hidden" name="action" value="delete_post">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <button class="btn btn-sm btn-outline-danger action-btn">Delete</button>
                        </form>
                    <?php endif; ?>

                    <?php if (can($current_role, 'pin')): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle_pin">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <button class="btn btn-sm btn-outline-secondary action-btn"><?php echo $post['is_pinned'] ? 'Unpin' : 'Pin'; ?></button>
                        </form>
                    <?php endif; ?>

                    <!-- USER MODERATION (Ban/Timeout) -->
                    <?php if ($post['user_id'] != $current_user_id): ?>
                        <?php if (can($current_role, 'timeout')): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="timeout_user">
                                <input type="hidden" name="user_id" value="<?php echo $post['user_id']; ?>">
                                <button class="btn btn-sm btn-outline-warning action-btn">Timeout (1h)</button>
                            </form>
                        <?php endif; ?>
                        <?php if (can($current_role, 'ban')): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="ban_user">
                                <input type="hidden" name="user_id" value="<?php echo $post['user_id']; ?>">
                                <button class="btn btn-sm btn-outline-danger action-btn">Ban User</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- REPLIES SECTION -->
                <hr>
                <h6 class="text-muted">Replies</h6>
                <?php if (isset($replies_by_post[$post['id']])): ?>
                    <?php foreach ($replies_by_post[$post['id']] as $reply): ?>
                        <div class="ml-3 mb-2 p-2 bg-white rounded border">
                            <small><strong><?php echo htmlspecialchars($reply['email']); ?></strong>: <?php echo htmlspecialchars($reply['content']); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small ml-3">No replies yet.</p>
                <?php endif; ?>

                <!-- REPLY FORM -->
                <?php if (!$is_locked || can($current_role, 'reply')): ?>
                <form method="POST" class="mt-2 ml-3">
                    <input type="hidden" name="action" value="create_reply">
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                    <div class="input-group input-group-sm">
                        <input type="text" name="content" class="form-control" placeholder="Write a reply..." required>
                        <div class="input-group-append">
                            <button class="btn btn-outline-primary" type="submit">Reply</button>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

</div>
</body>
</html>