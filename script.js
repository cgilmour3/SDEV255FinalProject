/**
 * script.js
 * Handles basic client-side interactions for the Student Management App.
 */

document.addEventListener('DOMContentLoaded', function() {

    console.log('DOM fully loaded and parsed.');

    // --- Form Clearing ---
    // Generic function to clear any form by ID
    function clearFormById(formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.reset();
            console.log(`Form with ID '${formId}' cleared.`);
        } else {
            console.warn(`Form with ID '${formId}' not found for clearing.`);
        }
    }

    // Attach clear function to specific buttons
    const clearStudentFormBtn = document.getElementById('clearStudentForm');
    if (clearStudentFormBtn) {
        clearStudentFormBtn.addEventListener('click', () => clearFormById('studentForm'));
    }

    const clearCourseFormBtn = document.getElementById('clearCourseForm');
    if (clearCourseFormBtn) {
        clearCourseFormBtn.addEventListener('click', () => clearFormById('courseForm'));
    }

    const clearRegisterFormBtn = document.getElementById('clearRegisterForm');
     if (clearRegisterFormBtn) {
        clearRegisterFormBtn.addEventListener('click', () => clearFormById('registerForm'));
    }

     const clearLoginFormBtn = document.getElementById('clearLoginForm');
     if (clearLoginFormBtn) {
        clearLoginFormBtn.addEventListener('click', () => clearFormById('loginForm'));
    }


    // --- Table View Toggling ---
    // Generic function to toggle visibility of an element
    function toggleElementVisibility(buttonId, elementId, showText = 'Hide', hideText = 'View') {
        const button = document.getElementById(buttonId);
        const element = document.getElementById(elementId);

        if (button && element) {
            button.addEventListener('click', () => {
                if (element.style.display === 'none' || element.style.display === '') {
                    element.style.display = 'block'; // Or 'table', 'flex', etc., depending on element
                    button.textContent = button.textContent.replace(hideText, showText);
                } else {
                    element.style.display = 'none';
                    button.textContent = button.textContent.replace(showText, hideText);
                }
                console.log(`Toggled visibility for element '${elementId}'`);
            });
            // Optional: Initially hide the element
            // element.style.display = 'none';
            // button.textContent = button.textContent.replace(showText, hideText); // Adjust initial text if hiding
        } else {
             console.warn(`Button '${buttonId}' or Element '${elementId}' not found for toggling.`);
        }
    }

    // Apply toggle functionality to table view buttons (if they exist)
    toggleElementVisibility('viewStudentsBtn', 'studentTableContainer', 'Hide Students', 'View Students');
    toggleElementVisibility('viewCoursesBtn', 'courseTableContainer', 'Hide Courses', 'View Courses');
    toggleElementVisibility('viewEnrollmentsBtn', 'enrollmentTableContainer', 'Hide Enrollments', 'View Enrollments');
    toggleElementVisibility('viewMyCoursesBtn', 'myCoursesTableContainer', 'Hide My Courses', 'View My Courses'); // For student schedule
    toggleElementVisibility('viewManagedCoursesBtn', 'managedCoursesTableContainer', 'Hide My Courses', 'View My Courses'); // For teacher courses


    // --- Confirmation Dialogs ---
    // Add confirmation to all forms with the 'confirm-delete' class
    const deleteForms = document.querySelectorAll('form.confirm-delete');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            const message = this.dataset.confirmMessage || 'Are you sure you want to perform this action?';
            if (!confirm(message)) {
                event.preventDefault(); // Stop form submission if user cancels
                console.log('Form submission cancelled by user.');
            }
        });
    });

    console.log('JavaScript initial setup complete.');
});
