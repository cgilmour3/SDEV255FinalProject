<?php
// require_once 'includes/auth_check.php';

$pageTitle= "Dashboard";
$useLargeContainer = true;
require_once 'includes/header.php';


$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');
$userRole = $_SESSION['role'] ?? 'N/A';

?>

<h1>Welcome, <?php echo $userName; ?>!</h1>
<p>Your role: <strong><?php echo ucfirst(htmlspecialchars($userRole)); ?> </strong> </p>

<hr style="margin: 20px 0;">
<section>
    <h2>Quick Links</h2>
    <?php if ($userRole === 'teacher'): ?>
        <p>Manage your courses and view student enrollments.</p>
        <ul>
            <li><a href="teacher_courses.php" class="button-link">Manage My Courses</a> (Add, Edit, Delete)</li>
            <li><a href="manage_students.php" class="button-link">Manage Students</a></li>
            <li><a href="all_courses.php" class="button-link secondary-btn">View All Courses</a></li>

        </ul>
        <p>From here, you can create new courses, update existing ones, or remove courses you no longer teach.</p>
    
    <?php elseif($userRole === 'student'):?>
        <p>View your schedule and browse available courses</p>
        <ul>
            <li><a href="student_schedule.php" class="button-link">View My Schedule</a> (Add/Drop)</li>
            <li><a href="all_courses.php" class="button-link secondary-btn">Browse and Enroll in Courses</a></li>
        </ul>
        <p>Check your current course schedule or explore new courses to add for the upcoming semster.</p>
    <?php else: ?>
        <p class="message error">Your user role is not recognized. Please contact support.</p>
    <?php endif ?>

</section>

<?php
require_once 'includes/footer.php';
?>


