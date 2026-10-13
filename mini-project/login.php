<?php
session_start();

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: index.php');
    exit;
}

$error = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        try {
            $db = new PDO("mysql:host=localhost;dbname=login_auth", 'root', '');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $statement = $db->prepare("SELECT * FROM users WHERE email = :email");
            $statement->execute([':email' => $email]);
            $user = $statement->fetch(PDO::FETCH_OBJ);

            if ($user && password_verify($password, $user->password)) {
                
                if ($user->is_banned) {
                    $error = "Your account has been banned. Please contact a moderator.";
                } else {
                    
                    $_SESSION['authenticated'] = true;
                    $_SESSION['user_id']       = $user->id;       
                    $_SESSION['email']         = $user->email;
                    $_SESSION['role']          = $user->role;     
                    
                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "Database connection error.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <h2>Login to your account</h2><hr class="my-4">

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Email:</label>
        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required><br>

        <label>Password:</label>
        <input type="password" class="form-control" name="password" required><br><br>

        <button type="submit" class="btn btn-primary btn-block">Login</button>
    </form>

    <p class="text-center go-back my-4">
        <a href="index.html" class="text-decoration-none"><i class="bi bi-arrow-left-circle text-primary mr-1"></i>go back</a>
    </p>
</div>
</body>
</html>