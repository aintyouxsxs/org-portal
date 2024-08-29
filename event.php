<?php
session_start();

include 'functions.php'; // Include your database connection

// Handle registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $firstName = $_POST['fname'];
    $lastName = $_POST['lname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];

    $result = registerUser($firstName, $lastName, $email, $password, $confirmPassword);

    if ($result['success']) {
        // Redirect to the same page or another page upon successful registration
        header("Location: event.php");
        exit();
    } else {
        // Set error message and keep the register modal open
        $_SESSION['error'] = $result['message'];
        $_SESSION['show_modal'] = 'register'; // Show register modal
        header("Location: event.php");
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
        $redirectPage = isset($_GET['page']) ? $_GET['page'] : 'event.php';
        if ($_SESSION['role'] === 'admin') {
            $redirectPage = 'admin-user-dashboard.php';
        }
        header("Location: $redirectPage");
        exit();
    } else {
        // Handle login failure
        $_SESSION['error'] = $loginResult['message'];
        $_SESSION['show_modal'] = true; // Show the modal on error
        $redirectPage = isset($_GET['page']) ? $_GET['page'] : 'event.php';
        header("Location: $redirectPage");
        exit();
    }
}


// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: event.php");
    exit();
}

// Fetch events
$events = fetchEvents();

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
    'profile_image'=> 'image/avatar.jpg' // Default image
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events</title>
    <link rel="stylesheet" href="css/event.css">
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

    <div id="events-section">
        <div class="container">
            <div class="title">
                <i class="fa-regular fa-calendar"></i>
                <p>Events</p>
            </div>
            <div class="events-grid">
                <?php if (empty($events)): ?>
                    <p>No events yet.</p>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <div class="event-card" data-title="<?php echo htmlspecialchars($event['title']); ?>"
                            data-venue="<?php echo htmlspecialchars($event['venue']); ?>"
                            data-date="<?php echo htmlspecialchars($event['date']); ?>"
                            data-time="<?php echo htmlspecialchars($event['start_time']) . ' - ' . htmlspecialchars($event['end_time']); ?>"
                            data-description="<?php echo htmlspecialchars($event['description']); ?>"
                            data-image="<?php echo htmlspecialchars($event['image_url']); ?>"
                            onclick="openEventModal(this)">
                                <div class="events">
                                    <img src="<?php echo htmlspecialchars($event['image_url']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                    <div class="info">
                                        <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                        <p><strong>Venue:</strong> <?php echo htmlspecialchars($event['venue']); ?></p>
                                        <div class="date">
                                            <p><i class="fa-regular fa-calendar"></i> <?php echo htmlspecialchars($event['date']); ?> </p>
                                            <p><i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($event['start_time']) . ' - ' . htmlspecialchars($event['end_time']); ?></p>
                                        </div>
                                        <p>Description: <?php echo htmlspecialchars($event['description']); ?></p>
                                        <button>More information</button>
                                    </div>
                                </div>
                        </div>
                    <?php endforeach; ?>
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

    <div id="eventModal" class="modals">
        <div class="modal-contents">
            <span class="close" onclick="closeEventModal()">&times;</span>
                <img id="eventImage" src="" alt="Event Image">
                <h2 id="eventTitle"></h2>
                <p><strong>Venue:</strong> <span id="eventVenue"></span></p>
                    <div class="dates">
                        <p><i class="fa-regular fa-calendar"></i> <span id="eventDate"></span> </p>
                        <p><i class="fa-regular fa-clock"></i> <span id="eventTime"></span></p>
                    </div>
                <p><strong>About:</strong> <span id="eventDescription"></span></p>
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

        document.getElementById('profileButton').addEventListener('click', function(event) {
        event.stopPropagation(); // Prevent the click event from propagating to the window
        var dropdown = document.getElementById('dropdownMenu');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });

        window.addEventListener('click', function(event) {
            var dropdown = document.getElementById('dropdownMenu');
            if (dropdown.style.display === 'block' && !event.target.closest('#profileButton')) {
                dropdown.style.display = 'none';
            }
        });

        // Disable caching for the page
        window.history.replaceState(null, null, window.location.href);
        window.onpageshow = function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        };

        function openEventModal(element) {
            // Retrieve the event data attributes from the clicked element
            const title = element.getAttribute('data-title');
            const venue = element.getAttribute('data-venue');
            const date = element.getAttribute('data-date');
            const time = element.getAttribute('data-time');
            const description = element.getAttribute('data-description');
            const image = element.getAttribute('data-image');

            // Set the modal content
            document.getElementById('eventTitle').innerText = title;
            document.getElementById('eventVenue').innerText = venue;
            document.getElementById('eventDate').innerText = date;
            document.getElementById('eventTime').innerText = time;
            document.getElementById('eventDescription').innerText = description;
            document.getElementById('eventImage').src = image;

            // Display the modal
            document.getElementById('eventModal').style.display = 'block';
        }

        function closeEventModal() {
            // Hide the modal
            document.getElementById('eventModal').style.display = 'none';
        }

        window.onclick = function(event) {
            // Close the modals when clicking outside of them
            if (event.target === document.getElementById('loginModal')) {
                closeLoginModal();
            } else if (event.target === document.getElementById('registerModal')) {
                closeRegisterModal();
            } else if (event.target === document.getElementById('eventModal')) {
                closeEventModal();
            }
        };

    </script>
</body>
</html>
