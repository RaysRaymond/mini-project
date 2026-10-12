<?php

session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email           = $_POST['email'];
    $password        = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if(empty($email) || empty($password) || empty($confirmPassword)){
        echo "All fields are required";
        exit;
    }

    if($password !==$confirmPassword){
        echo"Passwords do not match";
        exit;
    }

$db = new PDO("mysql:host=localhost;dbname=login_auth", 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//check if email exists

$check = $db->prepare("SELECT * FROM users WHERE email = :email");
$check ->execute([':email'=>$email]);

if($check->fetch()){
    echo"The email is already registered";
    exit;
}

$hashedPassword =password_hash($password,PASSWORD_DEFAULT);

$statement = $db->prepare("INSERT INTO users(email,password) VALUES(:email,:password)");
$statement->execute([
    ':email' =>$email,
    ':password'=>$hashedPassword,
]);

echo "Successfully registered";

       // --- MATCHING SESSION LOGIC STARTS HERE ---

    // 1. Match the EXACT keys your login page uses!
    $_SESSION['authenticated'] = true;
    $_SESSION['email']         = $email;

    // 2. Store extra data (like the User ID). 
    // This is crucial for fetching specific user data on the homepage later.
    $_SESSION['user_id']       = $db->lastInsertId();

    // 3. Redirect to homepage
    header("Location: index.php");
    exit; 

    // --- SESSION LOGIC ENDS ---
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5" class="wrapper">

    <h2 class="my-4">Create an account now to help us </h2><hr class="my-4">

    <form method="POST" action="" class="mb-4">

        <label>Email:</label>
        <input type="email" class="form-control" name="email" required>

        <label>Password:</label>
        <input type="password" class="form-control" name="password" required>

        <label>Confirm Password:</label>
        <input type="password" class="form-control" name="confirm_password" required><br>

        <button type="submit" class="btn btn-primary btn-block">Sign Up</button>

    </form>

</div>

    <p class="text-center go-back my-4 fs-">
    
    <a href="index.html" class="text-decoration-none"><i class="bi bi-arrow-left-circle text-primary mr-1"></i>go back</a>

    </p>

</body>
</html>

