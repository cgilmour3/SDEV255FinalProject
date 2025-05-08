<?php
// 1. Authentication Check & Role Verification
require_once 'includes/auth_check.php'; // Ensures user is logged in

// Check if the user is a student
if ($_SESSION['role'] !== 'student') {
    // Not a student, redirect to dashboard or show an error
    $_SESSION['message'] = "Access denied. You must be a student to view this page.";
    $_SESSION['message_type'] = 'error';
    header("Location: dashboard.php");
    exit();
}

// 2. Include Database Connection
require_once 'db_connect.php';

// 3. Set Page Title and Include Header
$pageTitle = "My Course Schedule";
$useLargeContainer = true; // Use the larger container style
require_once 'includes/header.php';

// --- Variable Initialization ---
$message = $_SESSION['message'] ?? ''; // Get message from session
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']); // Clear message after displaying

$student_id = $_SESSION['user_id']; // Get logged-in student's ID

// --- Handle Drop Course Action ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'drop_course') {
    $enrollment_id_to_drop = filter_input(INPUT_POST, 'enrollment_id', FILTER_VALIDATE_INT);

    if ($enrollment_id_to_drop) {
        // Verify that the enrollment belongs to this student before dropping
        // This is an extra check; the enrollment_id itself should be unique enough,
        // but it's good practice to ensure the student owns what they're trying to modify.
        $sql_check = "SELECT enrollment_id FROM Enrollments WHERE enrollment_id = ? AND student_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        if ($stmt_check) {
            $stmt_check->bind_param("ii", $enrollment_id_to_drop, $student_id);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows === 1) {
                // Enrollment belongs to the student, proceed with deletion
                $sql_delete = "DELETE FROM Enrollments WHERE enrollment_id = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                if ($stmt_delete) {
                    $stmt_delete->bind_param("i", $enrollment_id_to_drop);
                    if ($stmt_delete->execute()) {
                        $_SESSION['message'] = "Course dropped successfully from your schedule.";
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $_SESSION['message'] = "Error dropping course: " . $stmt_delete->error;
                        $_SESSION['message_type'] = 'error';
                        error_log("Execute failed for enrollment delete: (" . $stmt_delete->errno . ") " . $stmt_delete->error);
                    }
                    $stmt_delete->close();
                } else {
                    $_SESSION['message'] = "Database error preparing to drop course.";
                    $_SESSION['message_type'] = 'error';
                    error_log("Prepare failed for enrollment delete: (" . $conn->errno . ") " . $conn->error);
                }
            } else {
                // Enrollment not found or doesn't belong to this student
                $_SESSION['message'] = "Error: Course enrollment not found or it does not belong to you.";
                $_SESSION['message_type'] = 'error';
            }
            $stmt_check->close();
        } else {
            $_SESSION['message'] = "Database error checking enrollment.";
            $_SESSION['message_type'] = 'error';
            error_log("Prepare failed for enrollment check: (" . $conn->errno . ") " . $conn->error);
        }
    } else {
        $_SESSION['message'] = "Invalid enrollment ID for dropping.";
        $_SESSION['message_type'] = 'error';
    }

    // Redirect back to the same page to prevent form resubmission and show message
    header("Location: student_schedule.php");
    exit();
}


// --- Fetch Courses Student is Enrolled In ---
$enrolled_courses = [];
$sql_fetch = "SELECT e.enrollment_id, c.course_id, c.course_name, c.description, c.subject_area, c.credits, u.user_name as instructor_name, e.enrollment_date
              FROM Enrollments e
              JOIN Courses c ON e.course_id = c.course_id
              LEFT JOIN Users u ON c.teacher_id = u.user_id -- LEFT JOIN in case teacher_id is NULL
              WHERE e.user_id = ?
              ORDER BY c.subject_area, c.course_name";
$stmt_fetch = $conn->prepare($sql_fetch);

if ($stmt_fetch) {
    $stmt_fetch->bind_param("i", $student_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    while ($row = $result->fetch_assoc()) {
        $enrolled_courses[] = $row;
    }
    $stmt_fetch->close();
} else {
    $message = "Error fetching your enrolled courses: " . $conn->error;
    $message_type = 'error'; // Append to existing messages if any
    error_log("Prepare failed for fetching student schedule: (" . $conn->errno . ") " . $conn->error);
}

?>

<h1>My Course Schedule</h1>

<?php if (!empty($message)): ?>
    <div class="message <?php echo htmlspecialchars($message_type); ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<p>Here are the courses you are currently enrolled in. You can also <a href="all_courses.php">browse and enroll in new courses</a>.</p>

<section>
    <h2>Current Enrollments</h2>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Course Name</th>
                    <th>Subject</th>
                    <th>Credits</th>
                    <th>Instructor</th>
                    <th>Description</th>
                    <th>Enrolled On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($enrolled_courses)): ?>
                    <?php foreach ($enrolled_courses as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['subject_area']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($course['credits'], 1)); ?></td>
                            <td><?php echo htmlspecialchars($course['instructor_name'] ?? 'N/A'); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($course['description'])); ?></td>
                            <td><?php echo htmlspecialchars(date("M j, Y", strtotime($course['enrollment_date']))); ?></td>
                            <td>
                                <form action="student_schedule.php" method="post" class="confirm-delete" data-confirm-message="Are you sure you want to drop '<?php echo htmlspecialchars($course['course_name']); ?>' from your schedule?">
                                    <input type="hidden" name="action" value="drop_course">
                                    <input type="hidden" name="enrollment_id" value="<?php echo $course['enrollment_id']; ?>">
                                    <button type="submit" class="clear-btn">Drop Course</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">You are not currently enrolled in any courses. <a href="all_courses.php">Find courses to enroll in!</a></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
// Include Footer
require_once 'includes/footer.php';
?>
