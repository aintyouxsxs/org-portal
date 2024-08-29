<?php
session_start();
include 'functions.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: home.php");
    exit();
}

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// Redirect if not logged in
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header("Location: home.php");
    exit();
}

// Fetch user's name, email, and role for display
$userData = ['firstName' => '', 'lastName' => '', 'email' => '', 'role' => ''];
if (isset($_SESSION['user_id'])) {
    $userData = getUserInfo($_SESSION['user_id']);
}

$errorMessage = '';
$successMessage = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changePassword'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errorMessage = "All fields are required.";
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = "New password and confirm password do not match.";
    } else {
        // Verify current password and update to new password
        $result = updatePassword($_SESSION['user_id'], $currentPassword, $newPassword);
        if ($result['success']) {
            $successMessage = $result['message'];
            $_SESSION['success_message'] = $successMessage;
            header("Location: change-pass.php");
            exit();
        } else {
            $errorMessage = $result['message'];
        }
    }
}


// Check if there’s a success message after password change
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message after showing it
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="css/change-pass.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div id="content">
    <div id="header">
        <div class="cover">
            <div class="nav">
                <div class="logo">
                    <a href="home.php">
                        <img src="image/logo.png" alt="">
                        <p>CYDO</p>
                    </a>
                </div>
                <div class="navigation">
                    <a href="home.php">Home</a>
                    <a href="community.php">Community</a>
                    <a href="event.php">Events</a>
                    <a href="about.php">About</a>
                    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                        <div id="loggedInIcon" class="navigation">
                            <button id="profileButton">
                                <img src="image/avatar.jpg" alt="img">
                                <i class="fa-solid fa-angle-down"></i>
                            </button>
                            <div id="dropdownMenu" class="dropdown-menu">
                                <p><?php echo htmlspecialchars($userData['firstName'] . ' ' . $userData['lastName']); ?></p>
                                <a href="dashboard.php">My Profile</a>
                                <?php if ($userData['role'] === 'president'): ?>
                                    <a href="my-events.php">Events</a>
                                <?php endif; ?>
                                <a href="my-org.php">Organization</a>
                                <a href="dashboard.php?logout=true" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i>Log-out</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <button id="accessButton" type="button" class="access" onclick="openLoginModal()">Access</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="dashboard">
        <div class="profile">
            <div class="container1">
                <div class="user-settings">
                    <div class="settings">
                        <a href="dashboard.php" class="user">
                            <i class="fa-regular fa-user"></i>
                            <p>My profile</p>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>
                        <a href="my-events.php" class="user">
                            <i class="fa-regular fa-calendar"></i>
                            <p>Events</p>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>
                        <a href="my-org.php" class="user">
                            <i class="fa-solid fa-users-rays"></i>
                            <p>Organization</p>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>
                    </div>
                    <a href="dashboard.php?logout=true" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i>Log-out</a>
                </div>
            </div>

            <div class="container2">
                <div class="org-info">
                    <h2>Change Password</h2>
                    <?php if ($errorMessage): ?>
                        <p class="error-message"><?php echo htmlspecialchars($errorMessage); ?></p>
                    <?php elseif ($successMessage): ?>
                        <p class="success-message"><?php echo htmlspecialchars($successMessage); ?></p>
                    <?php endif; ?>
                    <form action="change-pass.php" method="POST">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required readonly>
                        
                        <div class="password-container">
                            <label for="current_password">Current Password:</label>
                            <input type="password" name="current_password" id="current_password" minlength="8" required>
                            <i class="fa-regular fa-eye eye-icon" id="current_password_eye" style="display:none;" onclick="togglePasswordVisibility('current_password', 'current_password_eye', 'current_password_eye_slash')"></i>
                            <i class="fa-regular fa-eye-slash eye-icon" id="current_password_eye_slash" style="display:block;" onclick="togglePasswordVisibility('current_password', 'current_password_eye', 'current_password_eye_slash')" style="display:none;"></i>
                        </div>

                        <div class="password-container">
                            <label for="new_password">New Password:</label>
                            <input type="password" name="new_password" id="new_password" minlength="8" required>
                            <i class="fa-regular fa-eye eye-icon" id="new_password_eye" style="display:none;" onclick="togglePasswordVisibility('new_password', 'new_password_eye', 'new_password_eye_slash')"></i>
                            <i class="fa-regular fa-eye-slash eye-icon" id="new_password_eye_slash" style="display:block;" onclick="togglePasswordVisibility('new_password', 'new_password_eye', 'new_password_eye_slash')" style="display:none;"></i>
                        </div>

                        <div class="password-container">
                            <label for="confirm_password">Confirm New Password:</label>
                            <input type="password" name="confirm_password" id="confirm_password" minlength="8" required>
                            <i class="fa-regular fa-eye eye-icon" id="confirm_password_eye" style="display:none;" onclick="togglePasswordVisibility('confirm_password', 'confirm_password_eye', 'confirm_password_eye_slash')"></i>
                            <i class="fa-regular fa-eye-slash eye-icon" id="confirm_password_eye_slash" style="display:block;" onclick="togglePasswordVisibility('confirm_password', 'confirm_password_eye', 'confirm_password_eye_slash')" style="display:none;"></i>
                        </div>

                        <div class="submits">
                            <button type="submit" name="changePassword">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

    function togglePasswordVisibility(inputId, eyeId, eyeSlashId) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(eyeId);
            const eyeSlashIcon = document.getElementById(eyeSlashId);

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.style.display = 'inline'; // Show the eye icon
                eyeSlashIcon.style.display = 'none'; // Hide the eye-slash icon
            } else {
                passwordInput.type = 'password';
                eyeIcon.style.display = 'none'; // Hide the eye icon
                eyeSlashIcon.style.display = 'inline'; // Show the eye-slash icon
            }
        }

    function openLoginModal() {
        document.getElementById("loginModal").style.display = "block";
        document.getElementById("content").classList.add("blur-background");
    }

    function closeLoginModal() {
        document.getElementById("loginModal").style.display = "none";
        document.getElementById("content").classList.remove("blur-background");
    }

    function openRegisterModal() {
        document.getElementById("loginModal").style.display = "none";
        document.getElementById("registerModal").style.display = "block";
    }

    function closeRegisterModal() {
        document.getElementById("registerModal").style.display = "none";
        document.getElementById("content").classList.remove("blur-background");
    }

    function switchToLoginModal() {
        closeRegisterModal();
        openLoginModal();
    }

    window.onclick = function(event) {
        var loginModal = document.getElementById("loginModal");
        var registerModal = document.getElementById("registerModal");
        if (event.target == loginModal) {
            closeLoginModal();
        } else if (event.target == registerModal) {
            closeRegisterModal();
        }
    }

    document.getElementById('profileButton').addEventListener('click', function () {
        var dropdownMenu = document.getElementById('dropdownMenu');
        dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
    });
</script>

</body>
</html>
