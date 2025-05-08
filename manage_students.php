<?php
// 1. Authentication Check & Role Verification
require_once 'includes/auth_check.php'; // Ensures user is logged in

// Check if the user is a teacher
if ($_SESSION['role'] !== 'teacher') {
    $_SESSION['message'] = "Access denied. You must be a teacher to view this page.";
    $_SESSION['message_type'] = 'error';
    header("Location: dashboard.php");
    exit();
}

// 2. Include Database Connection
require_once 'db_connect.php';


$pageTitle = "Manage Students";
$useLargeContainer = true; 
require_once 'includes/header.php'; // Header will be included after potential POST processing

// --- Variable Initialization ---
$message = $_SESSION['message'] ?? ''; 
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']); 

$teacher_id = $_SESSION['user_id']; // Logged-in teacher's ID 

// --- Handle Remove Student Action ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'remove_student') {
    $student_id_to_remove = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if ($student_id_to_remove) {
        // Ensure we are not trying to delete the teacher themselves or another teacher (safety check)
        $sql_check_role = "SELECT role FROM Users WHERE user_id = ?";
        $stmt_check_role = $conn->prepare($sql_check_role);
        if ($stmt_check_role) {
            $stmt_check_role->bind_param("i", $student_id_to_remove);
            $stmt_check_role->execute();
            $result_role = $stmt_check_role->get_result();
            if ($user_to_remove = $result_role->fetch_assoc()) {
                if ($user_to_remove['role'] === 'student') {
                    // Role is 'student', proceed with deletion.
                    // Enrollments will be deleted automatically due to ON DELETE CASCADE.
                    // Also, courses taught by this user (if they were a teacher being deleted by an admin role in future)
                    // would have their teacher_id set to NULL due to ON DELETE SET NULL.
                    $sql_delete_student = "DELETE FROM Users WHERE user_id = ?";
                    $stmt_delete = $conn->prepare($sql_delete_student);
                    if ($stmt_delete) {
                        $stmt_delete->bind_param("i", $student_id_to_remove);
                        if ($stmt_delete->execute()) {
                            if ($stmt_delete->affected_rows > 0) {
                                $_SESSION['message'] = "Student and their enrollments removed successfully.";
                                $_SESSION['message_type'] = 'success';
                            } else {
                                $_SESSION['message'] = "Student not found or already removed.";
                                $_SESSION['message_type'] = 'info';
                            }
                        } else {
                            $_SESSION['message'] = "Error removing student: " . $stmt_delete->error;
                            $_SESSION['message_type'] = 'error';
                            error_log("Execute failed for student delete: (" . $stmt_delete->errno . ") " . $stmt_delete->error);
                        }
                        $stmt_delete->close();
                    } else {
                        $_SESSION['message'] = "Database error preparing student removal.";
                        $_SESSION['message_type'] = 'error';
                        error_log("Prepare failed for student delete: (" . $conn->errno . ") " . $conn->error);
                    }
                } else {
                    $_SESSION['message'] = "Cannot remove this user. Only students can be removed via this page.";
                    $_SESSION['message_type'] = 'error';
                }
            } else {
                 $_SESSION['message'] = "User not found.";
                 $_SESSION['message_type'] = 'error';
            }
            $stmt_check_role->close();
        } else {
            $_SESSION['message'] = "Database error checking user role.";
            $_SESSION['message_type'] = 'error';
            error_log("Prepare failed for student role check: (" . $conn->errno . ") " . $conn->error);
        }
    } else {
        $_SESSION['message'] = "Invalid student ID for removal.";
        $_SESSION['message_type'] = 'error';
    }

    // Redirect back to the same page to prevent form resubmission and show message
    header("Location: manage_students.php");
    exit();
}

// --- Fetch All Students ---
$students_list = [];
$sql_fetch_students = "SELECT user_id, user_name, email, age, grade FROM Users WHERE role = 'student' ORDER BY user_name ASC";
$stmt_fetch_students = $conn->prepare($sql_fetch_students);

if ($stmt_fetch_students) {
    $stmt_fetch_students->execute();
    $result_students = $stmt_fetch_students->get_result();
    while ($row = $result_students->fetch_assoc()) {
        $students_list[] = $row;
    }
    $stmt_fetch_students->close();
} else {
    $message .= " Error fetching students list: " . $conn->error; // Append to existing message
    $message_type = 'error';
    error_log("Prepare failed for fetching students list: (" . $conn->errno . ") " . $conn->error);
}

?>

<h1>Manage Students</h1>
<p>This page allows you to view and remove student accounts from the system. Removing a student will also remove all their course enrollments.</p>

<?php if (!empty($message)): ?>
    <div class="message <?php echo htmlspecialchars($message_type); ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<section>
    <h2>All Students</h2>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Age</th>
                    <th>Grade</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($students_list)): ?>
                    <?php foreach ($students_list as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($student['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['age'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($student['grade'] ?? 'N/A'); ?></td>
                            <td>
                                <form action="manage_students.php" method="post" class="confirm-delete" data-confirm-message="Are you sure you want to remove student '<?php echo htmlspecialchars($student['user_name']); ?> (ID: <?php echo $student['user_id']; ?>)'? This action cannot be undone and will remove all their enrollments.">
                                    <input type="hidden" name="action" value="remove_student">
                                    <input type="hidden" name="student_id" value="<?php echo $student['user_id']; ?>">
                                    <button type="submit" class="clear-btn">Remove Student</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No students found in the system.</td>
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
