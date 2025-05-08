<?php
$pageTitle = "Register";
require 'includes/header.php';

//redirect if already logged in
if (isset($_SESSION['user_id'])){
    header("Location: dashboard.php");
    exit();
}

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';

?>

<h1>Register</h1>

<?php if (!empty($messaage)): ?>
    <div class="messge <?php echo htmlspecialchars($message_type); ?>">
        <?php echo htmlspecialchars($messaage); ?>
    </div>
<?php endif; ?>

<form action="register_process.php" method="post" id="registerForm">
    <label for="name">Full Name:</label>
    <input type="text" id="name" name="name" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required minlength="6">

    <label for="confirm_password">Confirm Password:</label>
    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">

    <label>Register as:</label>
    <div class="radio-group">
        <label>
            <input type="radio" name="role" value="student" checked> Student
        </label>
        <label>
            <input type="radio" name="role" value="teacher"> Teacher
        </label>
    </div>


    <div id="student_fields" style="margin-top 15px;"> 
        <label for="age">Age:</label>
        <input type="number" id="age" name="age" min="1">

        <label for="grade">Grade:</label>
        <input type="text" id="grade" name="grade" maxlength="10">
        <small>Teachers will have 'N/A' automatically.</small>
    </div>

    <button type="submit" class="full-width">Register</button>
    <button type="button" class="secondary-btn full-width" id="clearRegistrationForm">Clear Form</button>
</form>

<div class="link-container">
    <p>Already have an account? <a href="index.php">Login here</a></p>
</div>

<?php require 'includes/footer.php'; ?>
