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

// Fetch user's name and role for display
$userData = ['firstName' => '', 'lastName' => '', 'role' => ''];
if (isset($_SESSION['user_id'])) {
    $userData = getUserInfo($_SESSION['user_id']);
}

// Fetch the organization ID for the logged-in user
$orgId = getOrganizationIdByPresident($_SESSION['user_id']);
if (!$orgId) {
    die('User does not belong to any organization.');
    // Or you can redirect to an error page or display a user-friendly message
    // header("Location: error.php?message=User does not belong to any organization.");
    // exit();
}

// Check event request status
$user_id = $_SESSION['user_id'];
$eventStatus = getEventRequestStatus($user_id); // Assume this function returns 'pending', 'approved', or 'rejected'

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

// Check if form is submitted
$errorMessage = ''; // Variable to store error messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitEvent'])) {
    $title = trim($_POST['title']);
    $venue = trim($_POST['venue']);
    $date = trim($_POST['date']);
    $startTime = trim($_POST['start_time']);
    $endTime = trim($_POST['end_time']);
    $description = trim($_POST['description']);

    // Handle file upload
    $imageUrl = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageUrl = handleFileUpload($_FILES['image']);
    }

    // Validate form data
    if (empty($title) || empty($venue) || empty($date) || empty($startTime) || empty($endTime) || empty($description) || !$imageUrl) {
        $errorMessage = "All fields are required, and an image must be uploaded.";
    } else {
        // Insert event request into the event_requests table with org_id
        if (addEventRequest($user_id, $orgId, $title, $venue, $date, $startTime, $endTime, $description, $imageUrl)) {
            echo "Event request submitted successfully.";
            // Redirect to avoid re-submission on refresh
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $errorMessage = "Failed to submit event request.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event</title>
    <link rel="stylesheet" href="css/create-events.css">
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
                                <p><?php echo htmlspecialchars($userData['role']); ?></p>
                                <a href="dashboard.php">My Profile</a>
                                <a href="security.php">Security</a>
                                <?php if ($userData['role'] === 'president'): ?>
                                    <a href="event-proposal.php">Events</a>
                                <?php endif; ?>
                                <a href="organization.php">Organization</a>
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
                    <h2>Create Event</h2>
                    <?php if ($errorMessage): ?>
                        <p class="error-message"><?php echo htmlspecialchars($errorMessage); ?></p>
                    <?php endif; ?>

                    <?php if ($eventStatus === 'pending'): ?>
                        <p class="info-message">Your event request is currently under review. You will be notified once it is approved or rejected.</p>
                    <?php elseif ($eventStatus === 'rejected' && !isset($_GET['retry'])): ?>
                        <a href="create-events.php?retry=true" class="back"><button>Back to Form</button></a>
                        <p class="error-message">Your previous event request was rejected.</p>
                    <?php else: ?>
                        <form action="create-events.php" method="POST" enctype="multipart/form-data">
                            <label for="title">Event Title:</label>
                            <input type="text" id="title" name="title" maxlength="25" required>

                            <label for="venue">Venue:</label>
                            <input type="text" id="venue" name="venue" maxlength="30" required>

                            <div class="time">
                                <label for="date">Event Date:</label>
                                <input type="date" id="date" name="date" required>

                                <label for="start_time">Start Time:</label>
                                <input type="time" id="start_time" name="start_time" required>

                                <label for="end_time">End Time:</label>
                                <input type="time" id="end_time" name="end_time" required>
                            </div>

                            <label for="description">Event Description:</label>
                            <textarea id="description" name="description" rows="4" maxlength="1000" required></textarea>

                            <label for="image">Event Image:</label>
                            <input type="file" id="image" name="image" accept="image/*" required>

                            <div class="submits">
                                <button type="submit" name="submitEvent">Submit Event</button>
                            </div>
                        </form>
                    <?php endif; ?>
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

    document.addEventListener('DOMContentLoaded', function () {
        const profileButton = document.getElementById("profileButton");
        const dropdownMenu = document.getElementById("dropdownMenu");

        if (profileButton) {
            profileButton.addEventListener("click", function () {
                dropdownMenu.classList.toggle("show");
            });

            window.addEventListener("click", function (event) {
                if (!event.target.matches("#profileButton") && !event.target.matches("#profileButton img") && !event.target.matches("#profileButton i")) {
                    dropdownMenu.classList.remove("show");
                }
            });
        }
    });
</script>

</body>
</html>
