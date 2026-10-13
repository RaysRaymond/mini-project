<?php
session_start();

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true || !isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

try {
    $db = new PDO("mysql:host=localhost;dbname=login_auth", 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$statement = $db->prepare("SELECT is_banned FROM users WHERE id = :id");
$statement->execute([':id' => $_SESSION['user_id']]);
$self = $statement->fetch(PDO::FETCH_OBJ);

if (!$self || $self->is_banned) {
    session_destroy();
    header('Location: ../login.php?banned=1');
    exit;
}

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

function ban_allowed($myRole, $targetRole) {
    if ($myRole === 'admin')     return $targetRole !== 'admin';
    if ($myRole === 'moderator') return in_array($targetRole, ['user', 'editor']);
    return false;
}