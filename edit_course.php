<?php

require_once 'includes/header.php';

//check if user is teacher
if($_SESSION['role'] !== 'teacher'){
    $_SESSION['message'] = "Access denied. You must be a teacher to view this page.";
    $_SESSION['message_type'] = 'error';
    header("Location: dashboard.php");
    exit();
}

require_once 'db_connect.php';

$pageTitle = "Edit Course";
$useLargeContainer = true;

//variable initialization
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

$teacher_id = $_SESSION['user_id']; //get user id of logged in teacher
$course_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$course = null;

//validate course id
if(!$course_id){
    $_SESSION['message'] = "Invalid course ID entered.";
    $_SESSION['message_type'] = 'error';
    header("Location: teacher_courses.php");
    exit();
}

//fetch course details, also ensuring course belongs to currently logged in teacher
$sql_fetch_course = "SELECT course_id, course_name, description, subject_area, credits, teacher_id
                     FROM Courses
                     WHERE course_id = ?";
$stmt_fetch = $conn->prepare($sql_fetch_course);

if($stmt_fetch){
    $stmt_fetch->bind_param("i", $course_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    if($result->num_rows === 1) {
        $course = $result->fetch_assoc();
        //verify fetched course belongs to teacher
        if($course['teacher_id' !== $teacher_id]){
            $_SESSION['message'] = "Access Denied: You do not have permission to edit this course.";
            $_SESSION['message_type'] = 'error';
            header("Location: teacher_courses.php");
            exit();
        }
        $pageTitle = "Edit Course: " . htmlspecialchars($course['course_name']); //set page title dynamically depending on edited course
    } else{
        $_SESSION['message'] = "Course not found.";
        $_SESSION['message_type'] = 'error';
        header("Location: teacher_courses.php");
        exit();
    }
    $stmt_fetch->close();
} else{
    error_log("Prepare failed for fetching course to edit: (" . $conn->errno . ") " . $conn->error);
    $_SESSION['message'] = "Error retrieving course details, please try again later.";
    $_SESSION['message_type'] = 'error';
    header("Location: teacher_courses.php");
    exit();
}

//updating course form submission
if($_SERVER["REQUEST_METHNOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_course'){
    //ensure course id matches id from GET request
    $submitted_course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    if($submitted_course_id !== $course_id){
        $_SESSION['message'] = "Error: Course ID mismatch. Failed to update.";
        $_SESSION['message_type'] = 'error';
        header("Location: teacher_courses.php");
        exit();
    }

    //retrieve form data
    $course_name = trim($_POST['course_name'] ?? '');
    $description = trim($_POST['description']?? '');
    $subject_area = trim($_POST['subject_area'] ?? '');
    $credits - filter_input(INPUT_POST, 'credits', FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 1, "max_range" => 5]
    ]);

    $errors = [];
    if(empty($course_name)) $errors[] = "Course Name is required.";
    if(empty($description)) $errors[]= "Description is required.";
    if(empty($subject_area)) $errors[] = "Subject Area is required.";
    if($credits === false) $errors[] = "Credits must be an integer value between 1 and 5.";

    if(empty($errors)){
        //prepare UPDATE statement
        $sql_update = "UPDATE Courses SET course_name = ?, description = ?, subject_area = ?, credits = ?
                       WHERE course_id = ? AND teacher_id = ?";
        $stmt_update = $conn->prepare($sql_update);

        if($stmt_update){
            $stmt_update->bind_params("sssdis", $course_name, $description, $subject_area, $credits, $course_id, $teacher_id);

            if($stmt_update->execute()){
                $_SESSION['message'] = "Course updated successfully!";
                $_SESSION['message_type'] = 'success';
                $course['course_name'] = $course_name;
                $course['description'] = $description;
                $course['subject_area'] = $subject_area;
                $course['credits'] = $credits;
            } else{
                $message = "Error updating course: " . $stmt_update->error;
                $message_type = 'error';
                error_log("Prepare failed for course update: (" . $conn->errno . ") " . $conn->error);
            }
            $stmt_update->close();
        } else{
            $message = "Database error preparing course update.";
            $message_type = 'error';
            error_log("Prepare failed for course update: (" . $conn->errno . ") " . $conn->error);
        }
    } else{
        $message = implode("<br>", $errors);
        $message_type = 'error';
    }

}

require_once 'includes/header.php';
?>

<h1><?php echo $pageTitle; ?></h1>

<?php if(!empty($message)): ?>
    <div class="message <?php echo htmlspecialchars($message_type); ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($course): ?>
<section class="form-box" style="background-color: #fdfdfd; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0;">
    <form action="edit_course.php?id=<?php echo $course_id ?>" method="post" id="editCourseForm">
        <input type="hidden" name="action" value="update_course">
        <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">

        <label for="course_name">Course Name:</label>
        <input type="text" id="course_name" name="course_name" value="<?php echo htmlspecialchars($course['course_name'] ?? ''); ?>" required>

        <label for="description">Description</label>
        <textarea id="description" name="description" required><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>

        <label for="subject_area">Subject Area:</label>
        <input type="text" id="subject_area" name="subject_area" value="<?php echo htmlspecialchars($course['subject_area'] ?? ''); ?>" required>

        <label for="credits">Credits:</label>
        <input type="number" id="credits" name="credits" step="1" min="1" max="5" value="<?php echo htmlspecialchars($course['credits'] ?? ''); ?>" required>

        <button type="submit">Update Course</button>
        <a href="teacher_courses.php" class="button-link secondary-btn">Cancel & Back to My Courses</a>
    </form>
</section>
<?php else: ?>
    <p class="message error">Could not load course data for editing.</p>
    <a href="teacher_courses.php" class="button-link secondary-btn">Back to My Courses</a>
<?php endif; ?>


<?php
require_once 'includes/footer.php';
?>
