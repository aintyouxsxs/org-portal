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

// Fetch user's name, role, and organization for display
$userData = ['firstName' => '', 'lastName' => '', 'role' => '', 'orgName' => ''];
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    $userData = getUserInfo($_SESSION['user_id']);
}

// Check if org_id is set in the URL
$orgId = isset($_GET['org_id']) ? $_GET['org_id'] : null;

$events = [];
if ($orgId) {
    $events = getEventsByOrganization($orgId);
}

// Check organization status and store in session
if (!isset($_SESSION['organizationStatus'])) {
    $_SESSION['organizationStatus'] = getOrganizationStatus($_SESSION['user_id']);
}

// Check if form is submitted
$errorMessage = ''; // Variable to store error messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orgName = trim($_POST['orgName']);
    $description = trim($_POST['description']);
    $presidentId = $_SESSION['user_id'];

    // Handle file upload
    $logoUrl = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $logoUrl = handleFileUpload($_FILES['logo']);
    }

    // Validate form data
    if (empty($orgName) || empty($description) || !$logoUrl) {
        $errorMessage = "All fields are required, and logo must be uploaded.";
    } else {
        // Check if the organization name already exists
        if (organizationNameExists($orgName)) {
            $errorMessage = "The organization name is already taken.";
        } else {
            // Create organization request
            if (createOrganizationRequest($orgName, $description, $logoUrl, $presidentId)) {
                // Generate the page URL slug
                $pageUrlSlug = strtolower(str_replace(' ', '-', $orgName)) . '.php';
                
                // Create the organization page
                $filename = createOrganizationPage($orgName, $logoUrl, $description, $pageUrlSlug);
                if ($filename) {
                    // Update session variable
                    $_SESSION['organizationStatus'] = 'pending';
                    header("Location: my-org.php");
                    exit();
                } else {
                    $errorMessage = "Failed to create organization page.";
                }
            } else {
                $errorMessage = "Failed to create organization request.";
            }
        }
    }
}

// Check if the organization already exists
if ($userData['orgName']) {
    $message = "You have already created an organization.";
} elseif ($_SESSION['organizationStatus'] === 'pending') {
    $message = "Your organization request is currently under review. Please wait for admin approval.";
} else {
    $message = "";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Organization</title>
    <link rel="stylesheet" href="css/create-org.css">
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
                <div class="org-info">
                    <h2>Create an Organization</h2>
                    <h2><?php echo htmlspecialchars($message); ?></h2>
                    <?php if ($errorMessage): ?>
                        <p class="error-message"><?php echo htmlspecialchars($errorMessage); ?></p>
                    <?php endif; ?>
                    <?php if (!$userData['orgName'] && $_SESSION['organizationStatus'] !== 'pending'): ?>
                        <form action="create-org.php" method="POST" enctype="multipart/form-data">
                            <label for="orgName">Name</label>
                            <input type="text" id="orgName" name="orgName" required>
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="desc" required></textarea>
                            <label for="logo">Organization Logo</label>
                            <input type="file" id="logo" name="logo" accept="image/jpeg, image/png, image/jpg" required>
                            <div class="submits">
                                <button type="submit">Submit Request</button>
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
        document.getElementById("content").classList.add("blur");
    }

    function closeLoginModal() {
        document.getElementById("loginModal").style.display = "none";
        document.getElementById("content").classList.remove("blur");
    }

    function openSignupModal() {
        document.getElementById("signupModal").style.display = "block";
        document.getElementById("content").classList.add("blur");
    }

    function closeSignupModal() {
        document.getElementById("signupModal").style.display = "none";
        document.getElementById("content").classList.remove("blur");
    }

    document.getElementById("profileButton").addEventListener("click", function () {
        document.getElementById("dropdownMenu").classList.toggle("show");
    });

    window.addEventListener("click", function (event) {
        if (!event.target.matches("#profileButton")) {
            var dropdowns = document.getElementsByClassName("dropdown-menu");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains("show")) {
                    openDropdown.classList.remove("show");
                }
            }
        }
    });
</script>

</body>
</html>
