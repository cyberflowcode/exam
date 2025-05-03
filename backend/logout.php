<?php
session_start();

if (isset($_SESSION["user"])) {
    $_SESSION["last_user"] = $_SESSION["user"];
    unset($_SESSION["last_user"]["private_stuff"]);
}

unset($_SESSION["user"]);

$_SESSION = [];

session_destroy();

header('Location: ../index.php');
exit();
?>