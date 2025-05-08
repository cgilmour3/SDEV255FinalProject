<?php
// Start the session to store messages
session_start();

// Include the database connection
require 'db_connect.php';

// --- Check if the form was submitted via POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Retrieve and Sanitize Input ---
    // Use trim() to remove whitespace from beginning and end
    // Use filter_input for basic sanitization/validation where applicable
    $name = trim($_POST['name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? ''; // Don't trim passwords initially
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = trim($_POST['role'] ?? ''); // 'student' or 'teacher'
    $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    $grade = trim($_POST['grade'] ?? '');

    // --- Input Validation ---
    $errors = []; // Array to hold validation errors

    if (empty($name)) {
        $errors[] = "Full Name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid Email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (empty($role) || !in_array($role, ['student', 'teacher'])) {
        $errors[] = "Invalid role selected.";
    }
    // Optional fields validation (only if provided)
    if ($age === false && !empty($_POST['age'])) { // Check if age was provided but invalid
         $errors[] = "Age must be a valid positive number.";
    }
     if (!empty($grade) && strlen($grade) > 10) {
         $errors[] = "Grade cannot be more than 10 characters.";
     }


    // --- Check if Email Already Exists ---
    if (empty($errors) && !empty($email)) {
        $stmt_check = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
        if ($stmt_check) {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result(); // Store result to check num_rows

            if ($stmt_check->num_rows > 0) {
                $errors[] = "This email address is already registered.";
            }
            $stmt_check->close();
        } else {
             $errors[] = "Database error checking email. Please try again.";
             error_log("Prepare failed for email check: (" . $conn->errno . ") " . $conn->error);
        }
    }

    // --- Process Registration if No Errors ---
    if (empty($errors)) {
        // Hash the password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        if ($hashed_password === false) {
             $errors[] = "Error hashing password. Please try again.";
             error_log("Password hashing failed.");
        } else {
            // Prepare data for insertion
            $final_grade = ($role === 'teacher') ? 'N/A' : (empty($grade) ? NULL : $grade);
            $final_age = ($role === 'teacher' || empty($age)) ? NULL : $age; // Teachers don't need age, students optional

            // Prepare INSERT statement
            // Note: `student_id` is auto-increment, no need to insert it
            $sql = "INSERT INTO Users (user_name, email, password, role, age, grade) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql);

            if ($stmt_insert) {
                // Bind parameters: s=string, i=integer
                $stmt_insert->bind_param("ssssis", $name, $email, $hashed_password, $role, $final_age, $final_grade);

                // Execute the statement
                if ($stmt_insert->execute()) {
                    // Registration successful
                    $_SESSION['message'] = "Registration successful! Please login.";
                    $_SESSION['message_type'] = 'success';
                    $stmt_insert->close();
                    $conn->close();
                    header("Location: index.php"); // Redirect to login page
                    exit();
                } else {
                    // Insertion failed
                    $errors[] = "Registration failed. Please try again later.";
                    error_log("Execute failed for user insert: (" . $stmt_insert->errno . ") " . $stmt_insert->error);
                }
                $stmt_insert->close(); // Close statement even on failure
            } else {
                 $errors[] = "Database error preparing registration. Please try again.";
                 error_log("Prepare failed for user insert: (" . $conn->errno . ") " . $conn->error);
            }
        }
    }

    // --- Handle Errors: Redirect back to registration page ---
    if (!empty($errors)) {
        $_SESSION['message'] = implode("<br>", $errors); // Combine errors into one message
        $_SESSION['message_type'] = 'error';
        // Optional: Store submitted values in session to repopulate form (more complex)
        // $_SESSION['form_data'] = $_POST;
        header("Location: register.php");
        exit();
    }

} else {
    // Not a POST request, redirect to registration page or show error
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = 'error';
    header("Location: register.php");
    exit();
}

// Close connection if it's still open (should be closed on success redirect)
if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) {
    $conn->close();
}
?>
