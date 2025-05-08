<?php
// 1. Authentication Check: Ensure user is logged in
require_once 'includes/auth_check.php'; // Ensures user is logged in

// 2. Include Database Connection
require_once 'db_connect.php';

// 3. Set Page Title and Include Header
$pageTitle = "All Available Courses";
$useLargeContainer = true; // Use the larger container style
require_once 'includes/header.php';

// --- Variable Initialization ---
$message = $_SESSION['message'] ?? ''; // Get message from session
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']); // Clear message after displaying

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// --- Search/Filter Logic ---
$search_term = trim($_GET['search'] ?? '');
$filter_subject = trim($_GET['subject'] ?? '');

// --- Handle Student Enrollment Action ---
if ($user_role === 'student' && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'enroll_course') {
    $course_id_to_enroll = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);

    if ($course_id_to_enroll) {
        // Check if student is already enrolled in this course
        $sql_check_enrollment = "SELECT enrollment_id FROM Enrollments WHERE user_id = ? AND course_id = ?";
        $stmt_check = $conn->prepare($sql_check_enrollment);
        if ($stmt_check) {
            $stmt_check->bind_param("ii", $user_id, $course_id_to_enroll);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $_SESSION['message'] = "You are already enrolled in this course.";
                $_SESSION['message_type'] = 'info';
            } else {
                // Not enrolled, proceed with enrollment
                $sql_enroll = "INSERT INTO Enrollments (user_id, course_id, enrollment_date) VALUES (?, ?, CURDATE())";
                $stmt_enroll = $conn->prepare($sql_enroll);
                if ($stmt_enroll) {
                    $stmt_enroll->bind_param("ii", $user_id, $course_id_to_enroll);
                    if ($stmt_enroll->execute()) {
                        $_SESSION['message'] = "Successfully enrolled in the course!";
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $_SESSION['message'] = "Error enrolling in course: " . $stmt_enroll->error;
                        $_SESSION['message_type'] = 'error';
                        error_log("Execute failed for enrollment insert: (" . $stmt_enroll->errno . ") " . $stmt_enroll->error);
                    }
                    $stmt_enroll->close();
                } else {
                    $_SESSION['message'] = "Database error preparing enrollment.";
                    $_SESSION['message_type'] = 'error';
                    error_log("Prepare failed for enrollment insert: (" . $conn->errno . ") " . $conn->error);
                }
            }
            $stmt_check->close();
        } else {
            $_SESSION['message'] = "Database error checking enrollment status.";
            $_SESSION['message_type'] = 'error';
            error_log("Prepare failed for enrollment check: (" . $conn->errno . ") " . $conn->error);
        }
    } else {
        $_SESSION['message'] = "Invalid course ID for enrollment.";
        $_SESSION['message_type'] = 'error';
    }
    // Redirect to the same page (with search terms if any) to show message and prevent resubmission
    $redirect_url = "all_courses.php";
    $query_params = [];
    if (!empty($search_term)) $query_params['search'] = $search_term;
    if (!empty($filter_subject)) $query_params['subject'] = $filter_subject;
    if (!empty($query_params)) $redirect_url .= "?" . http_build_query($query_params);
    
    header("Location: " . $redirect_url);
    exit();
}


// --- Fetch All Courses with Search and Filter ---
$courses = [];
$base_sql = "SELECT c.course_id, c.course_name, c.description, c.subject_area, c.credits, u.user_name as instructor_name
             FROM Courses c
             LEFT JOIN Users u ON c.teacher_id = u.user_id"; // LEFT JOIN if instructor can be NULL

$conditions = [];
$params = [];
$types = "";

if (!empty($search_term)) {
    $conditions[] = "(c.course_name LIKE ? OR c.description LIKE ?)";
    $search_like = "%" . $search_term . "%";
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= "ss";
}
if (!empty($filter_subject)) {
    $conditions[] = "c.subject_area = ?";
    $params[] = $filter_subject;
    $types .= "s";
}

if (!empty($conditions)) {
    $base_sql .= " WHERE " . implode(" AND ", $conditions);
}
$base_sql .= " ORDER BY c.subject_area, c.course_name";

$stmt_fetch_courses = $conn->prepare($base_sql);

if ($stmt_fetch_courses) {
    if (!empty($params)) {
        $stmt_fetch_courses->bind_param($types, ...$params);
    }
    $stmt_fetch_courses->execute();
    $result_courses = $stmt_fetch_courses->get_result();
    while ($row = $result_courses->fetch_assoc()) {
        $courses[] = $row;
    }
    $stmt_fetch_courses->close();
} else {
    $message .= " Error fetching courses: " . $conn->error; // Append to existing message
    $message_type = 'error';
    error_log("Prepare failed for fetching all courses: (" . $conn->errno . ") " . $conn->error);
}

// --- For Students: Get IDs of courses they are already enrolled in ---
$student_enrolled_course_ids = [];
if ($user_role === 'student') {
    $sql_student_enrollments = "SELECT course_id FROM Enrollments WHERE user_id = ?";
    $stmt_student_enrollments = $conn->prepare($sql_student_enrollments);
    if ($stmt_student_enrollments) {
        $stmt_student_enrollments->bind_param("i", $user_id);
        $stmt_student_enrollments->execute();
        $result_student_enrollments = $stmt_student_enrollments->get_result();
        while ($row = $result_student_enrollments->fetch_assoc()) {
            $student_enrolled_course_ids[] = $row['course_id'];
        }
        $stmt_student_enrollments->close();
    } else {
        // Handle error if needed, but don't block page load
        error_log("Error fetching student's current enrollments: " . $conn->error);
    }
}

// --- Fetch distinct subject areas for filter dropdown ---
$subject_areas = [];
$sql_subjects = "SELECT DISTINCT subject_area FROM Courses WHERE subject_area IS NOT NULL AND subject_area != '' ORDER BY subject_area ASC";
$result_subjects = $conn->query($sql_subjects);
if ($result_subjects) {
    while($row = $result_subjects->fetch_assoc()) {
        $subject_areas[] = $row['subject_area'];
    }
}

?>

<h1>Browse All Courses</h1>

<?php if (!empty($message)): ?>
    <div class="message <?php echo htmlspecialchars($message_type); ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<section style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border-radius: 8px;">
    <form action="all_courses.php" method="get" class="form-inline">
        <div style="display: flex; gap: 10px; align-items: flex-end;">
            <div style="flex-grow: 1;">
                <label for="search" style="margin-bottom: 5px;">Search by Name/Description:</label>
                <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="e.g., Web Development, Introduction" style="width: 100%;">
            </div>
            <div style="flex-grow: 1;">
                <label for="subject" style="margin-bottom: 5px;">Filter by Subject:</label>
                <select name="subject" id="subject" style="width: 100%;">
                    <option value="">All Subjects</option>
                    <?php foreach ($subject_areas as $subject): ?>
                        <option value="<?php echo htmlspecialchars($subject); ?>" <?php echo ($filter_subject === $subject) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subject); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="button-link" style="padding: 10px 15px;">Filter</button>
                 <a href="all_courses.php" class="button-link secondary-btn" style="padding: 10px 15px;">Clear</a>
            </div>
        </div>
    </form>
</section>

<section>
    <h2>Available Courses</h2>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Course Name</th>
                    <th>Subject</th>
                    <th>Credits</th>
                    <th>Instructor</th>
                    <th>Description</th>
                    <?php if ($user_role === 'student'): ?>
                        <th>Action</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($courses)): ?>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['subject_area']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($course['credits'], 1)); ?></td>
                            <td><?php echo htmlspecialchars($course['instructor_name'] ?? 'N/A'); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($course['description'])); ?></td>
                            <?php if ($user_role === 'student'): ?>
                                <td>
                                    <?php if (in_array($course['course_id'], $student_enrolled_course_ids)): ?>
                                        <span class="button-link secondary-btn" style="cursor: default; background-color: #2ecc71; border-color: #2ecc71;">Enrolled</span>
                                    <?php else: ?>
                                        <form action="all_courses.php<?php
                                            // Preserve search query params in form action
                                            $form_action_params = [];
                                            if (!empty($search_term)) $form_action_params['search'] = $search_term;
                                            if (!empty($filter_subject)) $form_action_params['subject'] = $filter_subject;
                                            if (!empty($form_action_params)) echo '?' . http_build_query($form_action_params);
                                        ?>" method="post">
                                            <input type="hidden" name="action" value="enroll_course">
                                            <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                            <button type="submit">Enroll</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo ($user_role === 'student') ? '6' : '5'; ?>">
                            No courses found matching your criteria. <?php if (empty($search_term) && empty($filter_subject)) echo "Perhaps no courses have been added yet."; ?>
                        </td>
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
