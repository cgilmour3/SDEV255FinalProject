<?php

//checks if user is logged in by verifying session data
//redirects to login if user is not authenticated

if(session_status() == PHP_SESSION_NONE){
    session_start();
}

//check if the user_id session variable is set
if(!isset($_SESSION['user_id'])){
    //user is not logged in
    $_SESSION['message'] = "You must be logged in to access this page.";
    $_SESSION['message_type'] = 'error';

    //redirect to login page
    header("Location: index.php");
    exit();
}

$timeout_duration = 1800;
if(isset($_SESSION['loggedin_time']) && (time()- $_SESSION['loggedin_time'] > $timeout_duration)){
    //expired session
    session_unset();
    session_destroy();

    session_start();
    $_SESSION['message']= "Your session has expired due to inactivity. Please login again.";
    $_SESSION['message_type'] = 'info';

    header("Location: index.php");
    exit();
}

$_SESSION['loggedin_time'] = time();


?>