<?php
$pageTitle="Login";
require 'includes/header.php';

if(isset($_SESSION['user_id'])){
    header("Location: dashboard.php");
    exit();
}

$message= $_SESSION['message'] ?? '';
$message_type=$_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

?>
<h1>Login</h1>

<?php if(!empty($message)): ?>
    <div class="message <?php echo htmlspecialchars($message_type); ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif;?>

<form action="login_process.php" method="post" id="loginForm">
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>

    <button type="submit" class="full-width">Login</button>
    <button type="button" class="secondary-btn full-width" id="clearLoginForm">Clear Form</button>

</form>

<div class="link-container">
    <p>Don't have an account? <a href="register.php">Register here</a></p>
</div>

<?php require 'includes/footer.php'; ?>
