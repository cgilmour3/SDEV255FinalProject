<?php
// Start session on all pages that include this header
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Student Management'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php
// Basic Navigation - This will be expanded later based on login status and role
// Check if user is logged in by checking session variable
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null; // Get role if logged in

// Determine if the container should be large (for dashboards etc.) or standard (for login/register)
$containerClass = 'container';
if (isset($useLargeContainer) && $useLargeContainer) {
    $containerClass .= ' container-large';
}

?>

<?php if ($isLoggedIn): ?>
<nav>
    <ul>
        <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
        <?php if ($userRole === 'teacher'): ?>
            <li><a href="teacher_courses.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'teacher_courses.php' || basename($_SERVER['PHP_SELF']) == 'edit_course.php' ? 'active' : ''; ?>">My Courses</a></li>
            <li><a href="manage_students.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_students.php' ? 'active' : ''; ?>">Manage Students</a></li>
            <li><a href="all_courses.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'all_courses.php' ? 'active' : ''; ?>">All Courses</a></li>
        <?php elseif ($userRole === 'student'): ?>
            <li><a href="student_schedule.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'student_schedule.php' ? 'active' : ''; ?>">My Schedule</a></li>
            <li><a href="all_courses.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'all_courses.php' ? 'active' : ''; ?>">Available Courses</a></li>
        <?php endif; ?>
        <li><a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>)</a></li>
    </ul>
</nav>
<?php endif; ?>

<div class="<?php echo $containerClass; ?>">

    