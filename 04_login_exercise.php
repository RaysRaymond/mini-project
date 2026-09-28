<?php

session_start();

if(isset($_SESSION['authenticated']) && $_SESSION['authenticated'] ==true){
    header('Location:index.php');
    exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $email =$_POST['email'];
    $password =$_POST['password'];

    //validate inputs
    if(empty($email) || empty($password)){
        echo"All fields are required.";
        exit;
    }
    //PDO object to connect to DB
 $db = new PDO("mysql:host=localhost;dbname=login_auth", 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //check the user by email
    $statement =$db->prepare("SELECT * FROM users WHERE email = :email");
    $statement ->execute([':email'=>$email]);
    $user =$statement->fetch(PDO::FETCH_OBJ);

    //verify the password
    if($user &&password_verify($password, $user->password)){
        $_SESSION['authenticated']=true;
        $_SESSION['email'] =$user ->email;
        header('Location:index.php');
        exit;
    }else{
        echo"Invaild email or password";
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

    <form method="POST" action="">

        <label>Email:</label>

        <input type="email" class="form-control" name="email" required><br><br>

        <label>Password:</label>

        <input type="password" class="form-control" name="password" required><br><br>

        <button type="submit" class="btn btn-primary btn-block">Login</button>

    </form>

    <p class="text-center go-back my-4 fs-">
    
    <a href="index.html" class="text-decoration-none"><i class="bi bi-arrow-left-circle text-primary mr-1"></i>go back</a>

    </p>

</div>
</body>
</html>