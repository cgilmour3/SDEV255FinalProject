<?php
session_start();

$logout_message="You have been logged out successfully.";

$_SESSION = array();

if(ini_get("session.use_cookies")){
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() -42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );

}

session_destroy();

session_start();
$_SESSION['mesage']= $logout_message;
$_SESSION['message_type'] = 'info';

header("Location: index.php");
exit();
?>