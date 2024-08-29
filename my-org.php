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
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect if not logged in
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header("Location: home.php");
    exit();
}

// Fetch user's name, role, and organization for display
$userData = ['firstName' => '', 'lastName' => '', 'role' => '', 'orgName' => ''];
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    $userData = getUserInfo($_SESSION['user_id']);
}

// Refresh organization status
$_SESSION['organizationStatus'] = getOrganizationStatus($_SESSION['user_id']);

// Debugging statement to verify status
error_log('Organization Status: ' . $_SESSION['organizationStatus']);

// Fetch user's additional information
$userDatas = [
    'profile_image'=> 'image/avatar.jpg'
];

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    $userDatas = getUserInfos($_SESSION['user_id']);
    if ($userDatas && is_array($userDatas)) {
        $userDatas['profile_image'] = !empty($userDatas['profile_image']) ? $userDatas['profile_image'] : 'image/avatar.jpg';
    }
}

// Determine if there is a rejection message
$rejectionMessage = '';
if ($_SESSION['organizationStatus'] === 'rejected') {
    $rejectionMessage = "Your organization request has been declined.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events</title>
    <link rel="stylesheet" href="css/my-org.css">
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
                                <a href="security.php">Security</a>
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
                        <?php if ($userData['role'] === 'president'): ?>
                            <a href="my-events.php" class="user">
                                <i class="fa-regular fa-calendar"></i>
                                <p>Events</p>
                                <i class="fa-solid fa-angle-right"></i>
                            </a>
                        <?php endif; ?>
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
                <div class="user-info">
                    <div class="user-img">
                        <img src="<?php echo htmlspecialchars($userDatas['profile_image']); ?>" alt="User Image">
                    </div>
                    <div class="name">
                        <div class="user1">
                            <p><?php echo htmlspecialchars($userData['firstName'] . ' ' . $userData['lastName']); ?></p>
                        </div>
                        <div class="role">
                            <p>Role: <?php echo htmlspecialchars($userData['role']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="create">
                    <div class="org">
                        <?php if ($rejectionMessage): ?>
                            <div class="message rejection">
                                <p><?php echo htmlspecialchars($rejectionMessage); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($userData['orgName']): ?>
                            <div class="org-list">
                                <div class="org-info">
                                    <img src="<?php echo htmlspecialchars($userData['logoUrl']); ?>" alt="Organization Logo">
                                    <h2><?php echo htmlspecialchars($userData['orgName']); ?></h2>
                                </div>
                                <p>Members:</p>
                            </div>
                        <?php elseif ($_SESSION['organizationStatus'] === 'pending'): ?>
                            <div class="org-info">
                                <h2>Reviewing</h2>
                                <p>Your organization request is currently under review. Please wait for admin approval.</p>
                            </div>
                        <?php else: ?>
                            <a href="create-org.php"><button>Create organization</button></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
</script>

</body>
</html>
