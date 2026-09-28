<?php

session_start();

if(isset($_SESSION['authenticated']) && $_SESSION['authenticated'] ==true){
unset($_SESSION['authenticated']);
unset($_SESSION['email']);
header('Location: index.html');
exit;
}
