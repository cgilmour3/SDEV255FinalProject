<?php
// 1. Authentication Check & Role Verification
require_once 'includes/auth_check.php'; // Ensures user is logged in

// Check if the user is a teacher
if ($_SESSION['role'] !== 'teacher') {
    // Not a teacher, redirect to dashboard or show an error
    $_SESSION['message'] = "Access denied. You must be a teacher to view this page.";
    $_SESSION['message_type'] = 'error';
    header("Location: dashboard.php");
    exit();
}

// 2. Include Database Connection
require_once 'db_connect.php';

// 3. Set Page Title and Include Header
$pageTitle = "Manage My Courses";
$useLargeContainer = true; // Use the larger container style
require_once 'includes/header.php';

// --- Variable Initialization ---
$message = $_SESSION['message'] ?? ''; // Get message from session (e.g., after form submission)
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']); // Clear message after displaying

$teacher_id = $_SESSION['user_id']; // Get logged-in teacher's ID

// --- Handle Add Course Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_course') {

    // Retrieve and sanitize form data
    $course_name = trim($_POST['course_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $subject_area = trim($_POST['subject_area'] ?? '');
    // Validate credits (decimal between 0.5 and maybe 9.0?)
    $credits = filter_input(INPUT_POST, 'credits', FILTER_VALIDATE_FLOAT, [
        "options" => ["min_range" => 0.5, "max_range" => 9.0, "decimal" => "."]
    ]);

    // Basic Validation
    $errors = [];
    if (empty($course_name)) $errors[] = "Course Name is required.";
    if (empty($description)) $errors[] = "Description is required.";
    if (empty($subject_area)) $errors[] = "Subject Area is required.";
    if ($credits === false) $errors[] = "Credits must be a number (e.g., 3.0 or 3.5) between 0.5 and 9.0.";

    if (empty($errors)) {
        // Prepare INSERT statement
        $sql = "INSERT INTO Courses (course_name, description, subject_area, credits, teacher_id, instructor)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Use teacher's name from session for the instructor column
            $instructor_name = $_SESSION['user_name'];
            // Bind parameters: s=string, d=double/decimal, i=integer
            $stmt->bind_param("sssdis", $course_name, $description, $subject_area, $credits, $teacher_id, $instructor_name);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Course added successfully!";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Error adding course: " . $stmt->error;
                $_SESSION['message_type'] = 'error';
                error_log("Execute failed for course insert: (" . $stmt->errno . ") " . $stmt->error);
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = "Database error preparing course insertion.";
            $_SESSION['message_type'] = 'error';
            error_log("Prepare failed for course insert: (" . $conn->errno . ") " . $conn->error);
        }
    } else {
        // Validation errors occurred
        $_SESSION['message'] = implode("<br>", $errors);
        $_SESSION['message_type'] = 'error';
    }

    // Redirect back to the same page to prevent form resubmission on refresh
    header("Location: teacher_courses.php");
    exit();
}


// --- Handle Delete Course Action ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_course') {
    $course_id_to_delete = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);

    if ($course_id_to_delete) {
        // Verify that the course actually belongs to this teacher before deleting
        $sql_check = "SELECT course_id FROM Courses WHERE course_id = ? AND teacher_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $course_id_to_delete, $teacher_id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows === 1) {
            // Course belongs to the teacher, proceed with deletion
            // Enrollments related to this course will be deleted due to ON DELETE CASCADE in DB schema
            $sql_delete = "DELETE FROM Courses WHERE course_id = ? AND teacher_id = ?"; // Double check teacher_id
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("ii", $course_id_to_delete, $teacher_id);

            if ($stmt_delete->execute()) {
                $_SESSION['message'] = "Course deleted successfully!";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Error deleting course: " . $stmt_delete->error;
                $_SESSION['message_type'] = 'error';
                error_log("Execute failed for course delete: (" . $stmt_delete->errno . ") " . $stmt_delete->error);
            }
            $stmt_delete->close();
        } else {
            // Course not found or doesn't belong to this teacher
            $_SESSION['message'] = "Error: Course not found or you do not have permission to delete it.";
            $_SESSION['message_type'] = 'error';
        }
        $stmt_check->close();
    } else {
        $_SESSION['message'] = "Invalid course ID for deletion.";
        $_SESSION['message_type'] = 'error';
    }

    // Redirect back to the same page
    header("Location: teacher_courses.php");
    exit();
}


// --- Fetch Courses Taught by This Teacher ---
$courses = [];
$sql_fetch = "SELECT course_id, course_name, description, subject_area, credits
              FROM Courses
              WHERE teacher_id = ?
              ORDER BY subject_area, course_name";
$stmt_fetch = $conn->prepare($sql_fetch);

if ($stmt_fetch) {
    $stmt_fetch->bind_param("i", $teacher_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    $stmt_fetch->close();
} else {
    $message = "Error fetching courses: " . $conn->error;
    $message_type = 'error';
    error_log("Prepare failed for course fetch: (" . $conn->errno . ") " . $conn->error);
}

?>

<h1>Manage My Courses</h1>

<?php if (!empty($message)): ?>
    <div class="message <?php echo htmlspecialchars($message_type); ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<section class="form-box" style="margin-bottom: 30px; background-color: #fdfdfd; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0;">
    <h3>Add New Course</h3>
    <form action="teacher_courses.php" method="post" id="courseForm">
        <input type="hidden" name="action" value="add_course">

        <label for="course_name">Course Name:</label>
        <input type="text" id="course_name" name="course_name" required>

        <label for="description">Description:</label>
        <textarea id="description" name="description" required></textarea>

        <label for="subject_area">Subject Area:</label>
        <input type="text" id="subject_area" name="subject_area" required>

        <label for="credits">Credits (e.g., 3.0):</label>
        <input type="number" id="credits" name="credits" step="0.1" min="0.5" max="9.0" required>

        <button type="submit">Add Course</button>
        <button type="button" class="clear-btn" id="clearCourseForm">Clear Form</button>
    </form>
</section>

<section>
    <h2>My Current Courses</h2>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Course Name</th>
                    <th>Subject</th>
                    <th>Credits</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($courses)): ?>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['subject_area']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($course['credits'], 1)); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($course['description'])); // Use nl2br to respect newlines ?></td>
                            <td>
                                <a href="edit_course.php?id=<?php echo $course['course_id']; ?>" class="button-link secondary-btn">Edit</a>

                                <form action="teacher_courses.php" method="post" class="confirm-delete" data-confirm-message="Are you sure you want to delete this course? This will also remove all student enrollments for it.">
                                    <input type="hidden" name="action" value="delete_course">
                                    <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                    <button type="submit" class="clear-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">You are not currently assigned to teach any courses. Use the form above to add one.</td>
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
