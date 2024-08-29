
    <?php
    session_start();
    include '../functions.php';

    // Handle user registration
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
        $firstName = $_POST['fname'];
        $lastName = $_POST['lname'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm-password'];
        registerUser($firstName, $lastName, $email, $password, $confirmPassword);
    }

    // Handle user login
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        loginUser($email, $password);
    }

    // Handle logout
    if (isset($_GET['logout'])) {
        session_unset();
        session_destroy();
        header('Location: ../community.php'); // Redirect to community.php after logout
        exit();
    }

    // Fetch user information
    $userData = ['firstName' => '', 'lastName' => '', 'role' => '', 'orgName' => '', 'logoUrl' => ''];
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
        $userData = getUserInfo($_SESSION['user_id']);
    }

    // Fetch all events for the organization
    $events = [];
    $stmt = $conn->prepare("SELECT title, venue, date, start_time, end_time, description, image_url FROM events");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch additional user information
    $userDatas = [
        'profile_image' => '../image/avatar.jpg' // Default image path adjusted
    ];

    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
        $userDatas = getUserInfos($_SESSION['user_id']);
        if ($userDatas && is_array($userDatas)) {
            $userDatas['profile_image'] = !empty($userDatas['profile_image']) ? '../' . $userDatas['profile_image'] : '../image/avatar.jpg'; // Default image path adjusted
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($orgName); ?></title>
        <link rel="stylesheet" href="../css/org-design.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body>

    <div id="content">
        <div id="header">
            <div class="cover">
                <div class="nav">
                    <div class="logo">
                        <a href="../home.php">
                            <img src="../image/logo.png" alt="">
                            <p>CYDO</p>
                        </a>
                    </div>
                    <div class="navigation">
                        <a href="../home.php">Home</a>
                        <a href="../community.php">Community</a>
                        <a href="../event.php">News</a>
                        <a href="../about.php">About</a>
                        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                            <div id="loggedInIcon" class="navigation">
                                <button id="profileButton">
                                    <img src="<?php echo htmlspecialchars($userDatas['profile_image']); ?>" alt="User Image">
                                    <i class="fa-solid fa-angle-down"></i>
                                </button>
                                <div id="dropdownMenu" class="dropdown-menu">
                                    <p><?php echo htmlspecialchars($userData['firstName'] . ' ' . $userData['lastName']); ?></p>
                                    <a href="../dashboard.php">My Profile</a>
                                    <a href="../security.php">Security</a>
                                    <a href="../my-events.php">Events</a>
                                    <a href="../my-org.php">Organization</a>
                                    <a href="../community.php?logout=true" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i>Log-out</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <button id="accessButton" type="button" class="access" onclick="openLoginModal()">Access</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="org-dashboard">
            <div class="organization">
                <div class="container">
                    <div class="org-container">
                        <img src="../uploads/work5.png" alt="Luzille Cutie Logo">
                        <div class="org-info">
                            <h1>Luzille Cutie</h1>
                            <h3>About Us</h3>
                            <p>hehe</p>
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
                        <?php foreach ($events as $event): ?>
                            <div class="event-card" data-title="<?php echo htmlspecialchars($event['title']); ?>"
                                data-venue="<?php echo htmlspecialchars($event['venue']); ?>"
                                data-date="<?php echo htmlspecialchars($event['date']); ?>"
                                data-time="<?php echo htmlspecialchars($event['start_time']) . ' - ' . htmlspecialchars($event['end_time']); ?>"
                                data-description="<?php echo htmlspecialchars($event['description']); ?>"
                                data-image="../<?php echo htmlspecialchars($event['image_url']); ?>"
                                onclick="openEventModal(this)">
                                <div class="events">
                                    <img src="../<?php echo htmlspecialchars($event['image_url']); ?>" alt="Event Image" onerror="this.src='../image/default-event.jpg';">
                                        <div class="info">
                                            <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                            <p><?php echo htmlspecialchars($event['venue']); ?></p>
                                            <div class="date">
                                                <p><?php echo htmlspecialchars($event['date']); ?></p>
                                                <p><?php echo htmlspecialchars($event['start_time']) . ' - ' . htmlspecialchars($event['end_time']); ?></p>
                                            </div>
                                            <p>Description: <?php echo htmlspecialchars($event['description']); ?></p>
                                            <button>More information</button>
                                        </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="footer">
            <div class="last">
                <div class="container-last">
                    <h2>Join our community</h2>
                    <div class="socials">
                        <div class="social-image">
                            <img src="../social-logo/fb.png" alt="">
                        </div>
                        <div class="social-image">
                            <img src="../social-logo/ig.png" alt="">
                        </div>
                    </div>
                    <div class="web-logo">
                        <img src="../image/logo.png" alt="">
                        <p>CYDO</p>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <div id="loginModal" class="modal">
    <div class="modal-contents">
        <span class="close" onclick="closeLoginModal()">&times;</span>
        <div class="mods">
            <div class="contents">
                <p>Welcome to</p>
                <h1>Youth</h1>
                <p>The most active sus keme nyo.</p>
            </div>
            <div class="regs">
                <p>Access with your credentials</p>
                <form method="POST" action="event.php">
                    <label for="login-email">Email:</label>
                    <input type="email" id="login-email" name="email" required>
                    <label for="login-password">Password:</label>
                    <input type="password" id="login-password" name="password" required>
                    <button type="submit" name="login">Access</button>
                    <p>Create an Account? <a href="#" onclick="openRegisterModal()">Register</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="registerModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeRegisterModal()">&times;</span>
        <div class="mods">
            <div class="content">
                <p>Welcome to</p>
                <h1>Youth</h1>
                <p>Sasali kapa wala karin naman gagawin.</p>
            </div>
            <div class="regs">
                <p>Create an Account</p>
                <form method="POST" action="event.php">
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
                    <label for="login-email">Email:</label>
                    <input type="email" id="email" name="email" required>
                    <label for="login-password">Password:</label>
                    <input type="password" id="password" name="password" required>
                    <label for="confirm-password">Confirm Password:</label>
                    <input type="password" id="confirm-password" name="confirm-password" required>
                    <button type="submit" name="register">Register</button>
                    <p>Already have an Account? <a href="#" onclick="switchToLoginModal()">Login</a></p>
                </form>
            </div>
        </div>
    </div>
</div>


    <div id="eventModal" class="modal">
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
        function openLoginModal() {
            document.getElementById('loginModal').style.display = 'block';
            document.getElementById('content').classList.add('blur-background');
        }

        function closeLoginModal() {
            document.getElementById('loginModal').style.display = 'none';
            document.getElementById('content').classList.remove('blur-background');
        }

        function openRegisterModal() {
            document.getElementById('loginModal').style.display = 'none';
            document.getElementById('registerModal').style.display = 'block';
        }

        function closeRegisterModal() {
            document.getElementById('registerModal').style.display = 'none';
            document.getElementById('content').classList.remove('blur-background');
        }

        function switchToLoginModal() {
            closeRegisterModal();
            openLoginModal();
        }

        window.onclick = function(event) {
            var loginModal = document.getElementById('loginModal');
            var registerModal = document.getElementById('registerModal');
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

        function openEventModal(eventElement) {
            document.getElementById('eventImage').src = eventElement.dataset.image;
            document.getElementById('eventTitle').textContent = eventElement.dataset.title;
            document.getElementById('eventVenue').textContent = eventElement.dataset.venue;
            document.getElementById('eventDate').textContent = eventElement.dataset.date;
            document.getElementById('eventTime').textContent = eventElement.dataset.time;
            document.getElementById('eventDescription').textContent = eventElement.dataset.description;
            document.getElementById('eventModal').style.display = 'block';
        }

        function closeEventModal() {
            document.getElementById('eventModal').style.display = 'none';
        }

        // Prevent the modal from closing if the content is clicked
        document.querySelectorAll('.modal-content').forEach(function(modalContent) {
            modalContent.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    </script>
    </body>
    </html>
    