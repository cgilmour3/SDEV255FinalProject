<?php
// Start the session to store user data and messages
session_start();

// Include the database connection
require 'db_connect.php';

// --- Check if the form was submitted via POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Retrieve and Sanitize Input ---
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? ''; // Get password as is

    // --- Basic Validation ---
    $errors = [];
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    // --- If basic validation passes, attempt login ---
    if (empty($errors)) {
        // Prepare SQL statement to fetch user by email
        // Select necessary user details including the hashed password
        $sql = "SELECT user_id, user_name, email, password, role FROM Users WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result(); // Get result set

            if ($result->num_rows === 1) {
                // User found, fetch data
                $user = $result->fetch_assoc();

                // Verify the password
                if (password_verify($password, $user['password'])) {
                    // Password is correct!

                    // Regenerate session ID for security (prevents session fixation)
                    session_regenerate_id(true);

                    // Store user information in the session
                    $_SESSION['user_id'] = $user['user_id']; // Use student_id as user_id
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['loggedin_time'] = time(); // Optional: track login time

                    // Redirect to the dashboard
                    $stmt->close();
                    $conn->close();
                    header("Location: dashboard.php");
                    exit();

                } else {
                    // Password incorrect
                    $errors[] = "Invalid email or password.";
                }
            } else {
                // User not found
                $errors[] = "Invalid email or password.";
            }
            $stmt->close(); // Close statement
        } else {
            // Database error preparing statement
            $errors[] = "Login failed due to a server error. Please try again later.";
            error_log("Prepare failed for login select: (" . $conn->errno . ") " . $conn->error);
        }
    }

    // --- Handle Errors: Redirect back to login page ---
    if (!empty($errors)) {
        $_SESSION['message'] = implode("<br>", $errors);
        $_SESSION['message_type'] = 'error';
        header("Location: index.php"); // Redirect back to login page
        exit();
    }

} else {
    // Not a POST request
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = 'error';
    header("Location: index.php");
    exit();
}

// Close connection if it's still open
if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) {
    $conn->close();
}
?>
