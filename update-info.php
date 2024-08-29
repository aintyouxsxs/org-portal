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

// Fetch user's information
$userDatas = ['firstName' => '', 'lastName' => '', 'age' => '', 'address' => '', 'school' => '', 'profile_image' => ''];
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    $userDatas = getUserInfos($_SESSION['user_id']);
    // Ensure $userData is an array
    if (!is_array($userDatas)) {
        $userDatas = ['firstName' => '', 'lastName' => '', 'age' => '', 'address' => '', 'school' => '', 'profile_image' => ''];
        $errorMessage = "Failed to retrieve user information.";
    }
}

// Check if form is submitted
$errorMessage = ''; // Variable to store error messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $age = trim($_POST['age']);
    $address = trim($_POST['address']);
    $school = trim($_POST['school']);
    $userId = $_SESSION['user_id'];

    // Handle profile image upload
    $profileImage = $userDatas['profile_image']; // Default to current image
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_image']['tmp_name'];
        $fileName = $_FILES['profile_image']['name'];
        $fileSize = $_FILES['profile_image']['size'];
        $fileType = $_FILES['profile_image']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        // Define allowed file extensions
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            // Create a unique file name
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = 'uploads/profile_images/';
            $dest_path = $uploadFileDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $profileImage = $dest_path;
            } else {
                $errorMessage = "Failed to move uploaded file.";
            }
        } else {
            $errorMessage = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
        }
    }

    // Validate form data
    if (empty($age) || empty($address) || empty($school)) {
        $errorMessage = "All fields are required.";
    } else {
        // Update user info
        if (updateUserProfile($userId, $age, $address, $school, $profileImage)) {
            header("Location: dashboard.php");
            exit();
        } else {
            $errorMessage = "Failed to update information.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Information</title>
    <link rel="stylesheet" href="css/update-info.css">
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
                    <a href="community">Community</a>
                    <a href="event.php">Events</a>
                    <a href="about.php">About</a>
                    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                        <div id="loggedInIcon" class="navigation">
                            <button id="profileButton">
                                <img src="<?php echo htmlspecialchars($userDatas['profile_image'] ?: 'image/avatar.jpg'); ?>" alt="Profile Image">
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
                <div class="user-info">
                    <h2>Update Information</h2>
                    <?php if ($errorMessage): ?>
                        <p class="error-message"><?php echo htmlspecialchars($errorMessage); ?></p>
                    <?php endif; ?>
                    <form action="update-info.php" method="POST" enctype="multipart/form-data">
                        <label for="age">Age</label>
                        <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($userDatas['age']); ?>" required>
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($userDatas['address']); ?>" required>
                        <label for="school">School</label>
                        <input type="text" id="school" name="school" value="<?php echo htmlspecialchars($userDatas['school']); ?>" required>
                        <label for="profile_image">Profile Image</label>
                        <input type="file" id="profile_image" name="profile_image">
                        <div class="submits">
                            <button type="submit">Update</button>
                        </div>
                    </form>
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

    document.getElementById('profileButton').addEventListener('click', function () {
        var dropdownMenu = document.getElementById('dropdownMenu');
        dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
    });
</script>

</body>
</html>