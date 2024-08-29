<?php
session_start();
include 'functions.php'; // Include your database connection

// Handle user registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $firstName = $_POST['fname'];
    $lastName = $_POST['lname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];

    $result = registerUser($firstName, $lastName, $email, $password, $confirmPassword);

    if ($result['success']) {
        // Redirect to the same page or another page upon successful registration
        header("Location: community.php");
        exit();
    } else {
        // Set error message and keep the register modal open
        $_SESSION['error'] = $result['message'];
        $_SESSION['show_modal'] = 'register'; // Show register modal
        header("Location: community.php");
        exit();
    }
}

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Use the modified login function with return value
    $loginResult = loginUserWithReturn($email, $password);

    if ($loginResult['success']) {
        // Redirect based on user role or page after successful login
        $redirectPage = isset($_GET['page']) ? $_GET['page'] : 'community.php';
        if ($_SESSION['role'] === 'admin') {
            $redirectPage = 'admin-user-dashboard.php';
        }
        header("Location: $redirectPage");
        exit();
    } else {
        // Handle login failure
        $_SESSION['error'] = $loginResult['message'];
        $_SESSION['show_modal'] = true; // Show the modal on error
        $redirectPage = isset($_GET['page']) ? $_GET['page'] : 'community.php';
        header("Location: $redirectPage");
        exit();
    }
}


// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: community.php"); // Redirect to community.php after logout
    exit();
}

// Fetch all organizations
$organizations = fetchAllOrganizations();

// Fetch user's name, role, and organization for display
$userData = ['firstName' => '', 'lastName' => '', 'role' => '', 'orgName' => '', 'logoUrl' => ''];
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    $userData = getUserInfo($_SESSION['user_id']);
}

$userDatas = [
    'profile_image'=> 'image/avatar.jpg' // Default image
];

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    $userDatas = getUserInfos($_SESSION['user_id']);
    if ($userDatas && is_array($userDatas)) {
        $userDatas['profile_image']= !empty($userDatas['profile_image']) ? $userDatas['profile_image'] : 'image/avatar.jpg'; // Default image
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community</title>
    <link rel="stylesheet" href="css/community.css">
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
                                <img src="<?php echo htmlspecialchars($userDatas['profile_image']); ?>" alt="User Image">
                                <i class="fa-solid fa-angle-down"></i>
                            </button>
                            <div id="dropdownMenu" class="dropdown-menu">
                                <p><?php echo htmlspecialchars($userData['firstName'] . ' ' . $userData['lastName']); ?></p>
                                <a href="dashboard.php">My Profile</a>
                                <?php if ($userData['role'] === 'president'): ?>
                                    <a href="my-events.php">Events</a>
                                <?php endif; ?>
                                <a href="my-org.php">Organization</a>
                                <a href="community.php?logout=true" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i>Log-out</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <button id="accessButton" type="button" class="access" onclick="openLoginModal()">Access</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="org-section">
        <div class="container">
            <div class="title">
                <i class="fa-solid fa-users-line"></i>
                <p>Organizations</p>
            </div>
            <div class="org-grid">
                <?php if (!empty($organizations)): ?>
                    <?php foreach ($organizations as $organization): ?>
                        <div class="org-card">
                            <div class="org-list">
                                <img src="<?php echo htmlspecialchars($organization['logoUrl']); ?>" alt="Organization Logo">
                                <div class="org-info">
                                    <h2><?php echo htmlspecialchars($organization['name']); ?></h2>
                                    <p><?php echo htmlspecialchars($organization['description']); ?></p>
                                    <?php if (isset($organization['page_url']) && !empty($organization['page_url'])): ?>
                                        <a href="organizations/<?php echo htmlspecialchars($organization['page_url']); ?>"><button>View Organization</button></a>
                                    <?php else: ?>
                                        <p>Page URL not available</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No organizations found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="footer">
        <div class="last">
            <div class="container-last">
                <h2>Join our community</h2>
                <div class="socials">
                    <div class="social-image">
                        <img src="social-logo/fb.png" alt="">
                    </div>
                    <div class="social-image">
                        <img src="social-logo/ig.png" alt="">
                    </div>
                </div>
                <div class="web-logo">
                    <img src="image/logo.png" alt="">
                    <p>CYDO</p>
                </div>
            </div>
        </div>
    </div>
    
</div>

<!-- Login Modal -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeLoginModal()">&times;</span>
        <div class="mods">
            <div class="content">
                <p>Welcome to</p>
                <img src="image/logo.png" alt="">
                <h1>Youth</h1>
                <p>City Youth Development Officer Portal</p>
            </div>
            <div class="regs">
                <p>Access with your credentials</p>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message">
                        <p><?php echo htmlspecialchars($_SESSION['error']); ?></p>
                        <?php unset($_SESSION['error']); // Clear the error message after displaying ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="home.php">
                    <label for="login-email">Email:</label>
                    <input type="email" id="login-email" name="email" required>
                    <label for="login-password">Password: </label>
                    <div class="password-field">
                        <input type="password" id="login-password" name="password" required>
                        <i class="fa-regular fa-eye" id="login-password-eye" style="display:none;" onclick="togglePasswordVisibility('login-password', 'login-password-eye')"></i>
                        <i class="fa-regular fa-eye-slash" id="login-password-eye-slash" onclick="togglePasswordVisibility('login-password', 'login-password-eye')"></i>
                    </div>
                    <button type="submit" name="login">Access</button>
                    <p>Create an Account? <a href="#" onclick="openRegisterModal()">Register</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Register Modal -->
<div id="registerModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeRegisterModal()">&times;</span>
        <div class="mods">
            <div class="content">
                <p>Welcome to</p>
                <img src="image/logo.png" alt="">
                <h1>Youth</h1>
                <p>City Youth Development Officer Portal</p>
            </div>
            <div class="regs">
                <p>Create an Account</p>
                <form method="POST" action="home.php">
                    <div class="name-container">
                        <div class="name-field">
                            <label for="fname">First Name:</label>
                            <input type="text" id="fname" name="fname" required>
                        </div>
                        <div class="name-field">
                            <label for="lname">Last Name:</label>
                            <input type="text" id="lname" name="lname" required>
                        </div>
                    </div>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                    <label for="password">Password:</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" minlength="8" required>
                        <i class="fa-regular fa-eye" id="password-eye" style="display:none;" onclick="togglePasswordVisibility('password', 'password-eye')"></i>
                        <i class="fa-regular fa-eye-slash" id="password-eye-slash" onclick="togglePasswordVisibility('password', 'password-eye')"></i>
                    </div>

                    <label for="confirm-password">Confirm Password:</label>
                    <div class="password-field">
                        <input type="password" id="confirm-password" name="confirm-password" minlength="8" required>
                        <i class="fa-regular fa-eye" id="confirm-password-eye" style="display:none;" onclick="togglePasswordVisibility('confirm-password', 'confirm-password-eye')"></i>
                        <i class="fa-regular fa-eye-slash" id="confirm-password-eye-slash" onclick="togglePasswordVisibility('confirm-password', 'confirm-password-eye')"></i>
                    </div>
                    <button type="submit" name="register">Register</button>
                    <p>Already have an Account? <a href="#" onclick="switchToLoginModal()">Login</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<script>

function togglePasswordVisibility(inputId, eyeId) {
    const passwordInput = document.getElementById(inputId);
    const eyeIcon = document.getElementById(eyeId);
    const eyeSlashIcon = document.getElementById(`${inputId}-eye-slash`);

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.style.display = 'inline'; // Hide the eye icon (fa-eye)
        eyeSlashIcon.style.display = 'none'; // Show the eye-slash icon (fa-eye-slash)
    } else {
        passwordInput.type = 'password';
        eyeIcon.style.display = 'none'; // Show the eye icon (fa-eye)
        eyeSlashIcon.style.display = 'inline'; // Hide the eye-slash icon (fa-eye-slash)
    }
}

<?php if (isset($_SESSION['show_modal']) && $_SESSION['show_modal']): ?>
        // Automatically show the modal
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('loginModal');
            if (modal) {
                modal.style.display = 'block';
            }
        });
        <?php unset($_SESSION['show_modal']); // Clear the flag after showing the modal ?>
    <?php endif; ?>

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

    document.getElementById('profileButton').addEventListener('click', function(event) {
        event.stopPropagation(); // Prevent the click event from propagating to the window
        var dropdown = document.getElementById('dropdownMenu');
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });

    window.addEventListener('click', function(event) {
        var dropdown = document.getElementById('dropdownMenu');
        if (dropdown.style.display === 'block') {
            dropdown.style.display = 'none';
        }
    });
</script>

</body>
</html>
