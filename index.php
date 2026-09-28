<?php
session_start();

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: 04_login_exercise.php');
    exit;
}

$conn = mysqli_connect("localhost", "root", "", "login_auth");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        mysqli_query($conn, "INSERT INTO tasks (title, done) VALUES ('$title', 0)");
    }
    if (isset($_POST['toggle'])) {
        $id = (int) $_POST['id'];
        mysqli_query($conn, "UPDATE tasks SET done = NOT done WHERE id = $id");
    }
    if (isset($_POST['delete'])) {
        $id = (int) $_POST['id'];
        mysqli_query($conn, "DELETE FROM tasks WHERE id = $id");
    }
    header('Location: index.php');
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM tasks ORDER BY id ASC");
$tasks = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 py-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="font-weight-bold mb-4">My Todo List</h3>
                    <ul class="list-group mb-4">
                        <?php if (empty($tasks)): ?>
                            <li class="list-group-item text-muted">No tasks yet — add one below.</li>
                        <?php endif; ?>
                        <?php foreach ($tasks as $task): ?>
                        <li class="list-group-item d-flex align-items-center py-3">
                            <form method="post" class="m-0 mr-3">
                                <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                <button type="submit" name="toggle" class="btn <?= $task['done'] ? 'btn-success' : 'btn-light' ?>">
                                    <i class="bi <?= $task['done'] ? 'bi-check-square' : 'bi-square' ?>"></i>
                                </button>
                            </form>
                            <span class="flex-grow-1">
                                <?= $task['done'] ? '<s>' . htmlspecialchars($task['title']) . '</s>' : htmlspecialchars($task['title']) ?>
                            </span>
                            <form method="post" class="m-0 ml-3">
                                <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                <button type="submit" name="delete" class="btn btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <form method="post">
                        <div class="form-row">
                            <div class="col">
                                <input type="text" name="title" class="form-control" placeholder="Add new item..." required>
                            </div>
                            <div class="col-auto">
                                <button type="submit" name="add" class="btn btn-primary px-4">Add</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <p class="text-center mt-4 mb-0">
                <a href="05_logout_exercise.php">Logout</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>