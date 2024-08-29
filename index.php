<?php

session_start();

include 'functions.php';

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
        header("Location: home.php");
        exit();
    } else {
        // Set error message and keep the register modal open
        $_SESSION['error'] = $result['message'];
        $_SESSION['show_modal'] = 'register'; // Show register modal
        header("Location: home.php");
        exit();
    }
}

// Handle password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset-password'])) {
    $email = $_POST['email'];
    $newPassword = $_POST['new-password'];
    $confirmPassword = $_POST['confirm-new-password'];

    if ($newPassword === $confirmPassword) {
        $resetResult = resetPassword($email, $newPassword);

        if ($resetResult['success']) {
            $_SESSION['success'] = $resetResult['message'];
            header("Location: home.php");
            exit();
        } else {
            $_SESSION['error'] = $resetResult['message'];
            $_SESSION['show_modal'] = 'reset-password'; // Show reset password modal
            header("Location: home.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Passwords do not match.";
        $_SESSION['show_modal'] = 'reset-password'; // Show reset password modal
        header("Location: home.php");
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
        $redirectPage = isset($_GET['page']) ? $_GET['page'] : 'home.php';
        if ($_SESSION['role'] === 'admin') {
            $redirectPage = 'admin-user-dashboard.php';
        }
        header("Location: $redirectPage");
        exit();
    } else {
        // Handle login failure
        $_SESSION['error'] = $loginResult['message'];
        $_SESSION['show_modal'] = true; // Show the modal on error
        $redirectPage = isset($_GET['page']) ? $_GET['page'] : 'home.php';
        header("Location: $redirectPage");
        exit();
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: home.php");
    exit();
}

// Fetch user's name, role, and organization for display
$userData = [
    'firstName' => '',
    'lastName'  => '',
    'role'      => '',
    'orgName'   => '',
];

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    $userInfo = getUserInfo($_SESSION['user_id']);
    if ($userInfo && is_array($userInfo)) {
        $userData['firstName'] = $userInfo['firstName'] ?? '';
        $userData['lastName']  = $userInfo['lastName'] ?? '';
        $userData['role']      = $userInfo['role'] ?? '';
        $userData['orgName']   = $userInfo['orgName'] ?? '';
    }
}

$userDatas = [
    'profile_image' => 'image/avatar.jpg' // Default image
];

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    $userDatas = getUserInfos($_SESSION['user_id']);
    if ($userDatas && is_array($userDatas)) {
        $userDatas['profile_image'] = !empty($userDatas['profile_image']) ? $userDatas['profile_image'] : 'image/avatar.jpg'; // Default image
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/home.css">
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
                        <a href="index.php">Home</a>
                        <a href="community.php">Community</a>
                        <a href="event.php">Events</a>
                        <a href="about.php">About</a>
                        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                            <div id="loggedInIcon" class="navigation">
                                <button id="profileButton">
                                    <img src="<?php echo htmlspecialchars($userDatas['profile_image']); ?>" alt="User Image" class="profile-image">
                                    <i class="fa-solid fa-angle-down"></i>
                                </button>
                                <div id="dropdownMenu" class="dropdown-menu">
                                    <p><?php echo htmlspecialchars($userData['firstName'] . ' ' . $userData['lastName']); ?></p>
                                    <a href="dashboard.php">My Profile</a>
                                    <?php if ($userData['role'] === 'president'): ?>
                                    <a href="my-events.php">Events</a>
                                    <?php endif; ?>
                                    <a href="my-org.php">Organization</a>
                                    <a href="home.php?logout=true" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i>Log-out</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <button id="accessButton" type="button" class="access" onclick="openLoginModal()">Access</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="header1">
            <video autoplay muted loop id="backgroundVideo">
                <source src="/PORTAL_SYSTEM/image/FINAL.mp4" type="video/mp4">
            </video>
            <div class="cover1">
                <div class="container">
                    <h1>City Youth Development Office - Trece Martires</h1>
                    <p>Join, discover, and build your future in the leadership industry.</p>
                    <div class="access1">
                        <button class="join"><a href="https://web.facebook.com/CYDOTrece">Visit our Facebook Page</a></button>
                    </div>
                </div>
            </div>
        </div>

        <div id="header2">
            <div class="cover2">
                <div class="container2">
                    <div class="container2-list">
                        <img src="image/home1.png" alt="IMG">
                    </div>
                    <div class="container2-list">
                        <h2 class="container2-info">Maximizes Youth Opportunities</h2>
                        <p class="container2-p">Unlock a lot of youth opportunities at the Office of City Youth Development Officer-Trece Martires City</p>
                        <button class="button2"><a href="https://web.facebook.com/CYDOTrece">Visit our Facebook</a></button>
                    </div>
                </div>
            </div>
        </div>

        <div id="header3">
            <div class="cover3">
                <div class="container3">
                    <div class="container3-list">
                        <h2 class="container3-info">Explore CYDO</h2>
                        <p class="container3-p">Explore the City Youth Development Office website and be the champion for youth empowerment.</p>
                        <button class="button3"><a href="https://web.facebook.com/CYDOTrece">Discover Now</a></button>
                    </div>
                    <div class="container3-list">
                        <img src="image/home2.png" alt="IMG">
                    </div>
                </div>
            </div>
        </div>

        <div id="header4">
            <div class="cover4">
                <div class="container4">
                    <div class="container4-list">
                        <img src="image/home3.png" alt="IMG">
                    </div>
                    <div class="container4-list">
                        <h2 class="container4-info">Crafting Digital Experiences</h2>
                        <p class="container4-p">Our Website Development Team tranforms ideas into engaging online platform for Youth of Trece Martires City</p>
                        <button class="button4"><a href="community.php">Join Now</a></button>
                    </div>
                </div>
            </div>
        </div>

        <div id="header5">
            <div class="cover5">
                <div class="container5">
                    <h2>Opportunities awaits to your organizations!</h2>
                    <div class="oppu">
                        <div class="oppu-list">
                            <strong>Promotion and protection of Youth!</strong>
                                <ul>
                                    <li>Promotion and protection of the physical, moral, spiritual, intellectual and social well-being of the youth of Trece Martires City 
                                        to the end that the youth realize their potential for improving the quality of lie.</li>
                                </ul>
                        </div>
                        <div class="oppu-list">
                            <strong>Be part of City Youth Development Council!</strong>
                                <ul>
                                    <li>By registration your organizations have the opportunity to be part of City Youth Development Council, a yout-led city wide Council
                                        at Trece Martires City, headed by SK Federation President of Trece Martires City
                                    </li>
                                </ul>
                        </div>
                        <div class="oppu-list">
                            <strong>Fund your organization's initiatives!</strong>
                                <ul>
                                    <li>The Office City Youth Development Officer-Trece Martires provides opportunity in funding a choosen your organization advocacy espacially projects 
                                        centered in Youth Developments</li>
                                </ul>
                        </div>
                        <div class="oppu-list">
                            <strong>Expand youth opportunities!</strong>
                                <ul>
                                    <li>Increase opportunities for its members to participate in capacity-building programs, local, and international exchange programs and conferences</li>
                                </ul>
                        </div>
                    </div>
                    <p>We prepare a lot of opportunities to all registered organizations at YORP-Trece so what are you waiting for, register now and be part of our growing Family!</p>
                </div>
            </div>
        </div>

        <div id="footer">
            <div class="last">
                <div class="container-last">
                    <h2>Join our community</h2>
                    <div class="socials">
                        <div class="social-image">
                            <a href="https://web.facebook.com/CYDOTrece"><img src="social-logo/fb.png" alt=""></a>
                        </div>
                    </div>
                    <div class="web-logo">
                        <a href="home.php"><img src="image/logo.png" alt=""></a>
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
                    <p>Create an Account. <a href="#" onclick="openRegisterModal()">Register</a></p>
                    <p><a href="#" onclick="openResetPasswordModal()">Forgot Password?</a></p>
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

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeResetPasswordModal()">&times;</span>
        <div class="mods">
            <div class="content">
                <p>Reset Your Password</p>
                <img src="image/logo.png" alt="">
                <h1>Youth</h1>
                <p>City Youth Development Officer Portal</p>
            </div>
            <div class="regs">
                <p>Enter your email and new password</p>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message">
                        <p><?php echo htmlspecialchars($_SESSION['error']); ?></p>
                        <?php unset($_SESSION['error']); // Clear the error message after displaying ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="home.php">
                    <label for="reset-email">Email:</label>
                    <input type="email" id="reset-email" name="email" required>
                    <label for="new-password">New Password: </label>
                    <div class="password-field">
                        <input type="password" id="new-password" name="new-password" required>
                        <i class="fa-regular fa-eye" id="new-password-eye" style="display:none;" onclick="togglePasswordVisibility('new-password', 'new-password-eye')"></i>
                        <i class="fa-regular fa-eye-slash" id="new-password-eye-slash" onclick="togglePasswordVisibility('new-password', 'new-password-eye')"></i>
                    </div>
                    <label for="confirm-new-password">Confirm New Password: </label>
                    <div class="password-field">
                        <input type="password" id="confirm-new-password" name="confirm-new-password" required>
                        <i class="fa-regular fa-eye" id="confirm-new-password-eye" style="display:none;" onclick="togglePasswordVisibility('confirm-new-password', 'confirm-new-password-eye')"></i>
                        <i class="fa-regular fa-eye-slash" id="confirm-new-password-eye-slash" onclick="togglePasswordVisibility('confirm-new-password', 'confirm-new-password-eye')"></i>
                    </div>
                    <button type="submit" name="reset-password">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</div>



    <script>

function openResetPasswordModal() {
    document.getElementById('resetPasswordModal').style.display = 'block';
}

function closeResetPasswordModal() {
    document.getElementById('resetPasswordModal').style.display = 'none';
}

function togglePasswordVisibility(passwordFieldId, eyeIconId) {
    var passwordField = document.getElementById(passwordFieldId);
    var eyeIcon = document.getElementById(eyeIconId);
    var eyeSlashIcon = document.getElementById(eyeIconId + '-slash');

    if (passwordField.type === "password") {
        passwordField.type = "text";
        eyeIcon.style.display = "none";
        eyeSlashIcon.style.display = "inline";
    } else {
        passwordField.type = "password";
        eyeIcon.style.display = "inline";
        eyeSlashIcon.style.display = "none";
    }
}


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
