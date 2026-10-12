<?php
session_start();

// 1. The Bouncer: Kick out if not logged in or no user ID
// Notice the "../" which means "go up one folder level"
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true || !isset($_SESSION['user_id'])) {
    header('Location: ../04_login_exercise.php');
    exit;
}

// 2. Shared PDO Connection
try {
    $db = new PDO("mysql:host=localhost;dbname=login_auth", 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// 3. Mid-session ban check (kicks them out if an admin banned them while browsing)
$statement = $db->prepare("SELECT is_banned FROM users WHERE id = :id");
$statement->execute([':id' => $_SESSION['user_id']]);
$self = $statement->fetch(PDO::FETCH_OBJ);

if (!$self || $self->is_banned) {
    session_destroy();
    // Notice the "../" here too
    header('Location: ../04_login_exercise.php?banned=1');
    exit;
}

// 4. Role-Based Access Control
function can($action) {
    $role = $_SESSION['role'] ?? 'user';
    $perms = [
        'user'      => ['create_post', 'edit_own'],
        'editor'    => ['create_post', 'edit_own', 'edit_any'],
        'moderator' => ['create_post', 'edit_own', 'edit_any', 'delete_any', 'lockdown', 'ban_users'],
        'admin'     => ['create_post', 'edit_own', 'edit_any', 'delete_any', 'lockdown', 'ban_users', 'manage_users'],
    ];
    return in_array($action, $perms[$role] ?? []);
}

// 5. Hierarchy check (admins can't ban admins, mods can't ban admins, etc.)
function ban_allowed($myRole, $targetRole) {
    if ($myRole === 'admin')     return $targetRole !== 'admin';
    if ($myRole === 'moderator') return in_array($targetRole, ['user', 'editor']);
    return false;
}