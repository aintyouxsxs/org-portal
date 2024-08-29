<?php
session_start();
include 'functions.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: community.php"); // Redirect to community.php after logout
    exit();
}

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'] || $_SESSION['role'] !== 'admin') {
    header("Location: home.php"); // Redirect if not an admin
    exit();
}

// Fetch pending organization requests
$requests = fetchOrganizationRequests(); // This function should return pending requests

// Handle request actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['acceptRequest'])) {
        $requestId = $_POST['requestId'];
        acceptOrganizationRequest($requestId);

        // Redirect to avoid re-submission on refresh
        header("Location: admin-organization-requests.php");
        exit();
    } elseif (isset($_POST['declineRequest'])) {
        $requestId = $_POST['requestId'];
        declineOrganizationRequest($requestId);

        // Redirect to avoid re-submission on refresh
        header("Location: admin-organization-requests.php");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Organization Requests</title>
    <link rel="stylesheet" href="css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="content">
    <div class="sidebar">
        <div class="logo">
            <img src="image/avatar.jpg" alt=""/>
        </div>
        <ul class="menu">
            <li>
                <a href="admin-user-dashboard.php">
                    <i class="fa-solid fa-gauge"></i>
                    <span>Users-Info</span>
                </a>
            </li>
            <li>
                <a href="admin-events-requests.php">
                    <i class="fa-solid fa-calendar"></i>
                    <span>Event Requests</span>
                </a>
            </li>
            <li>
                <a href="admin-events-approved.php">
                    <i class="fa-solid fa-calendar"></i>
                    <span>Event Approved</span>
                </a>
            </li>
            <li class="active">
                <a href="admin-organization-requests.php">
                    <i class="fa-solid fa-users"></i>
                    <span>Organizations Requests</span>
                </a>
            </li>
            <li>
                <a href="admin-organizations-approved.php">
                    <i class="fa-solid fa-users"></i>
                    <span>Organizations Approved</span>
                </a>
            </li>
            <form method="POST" class="logout">
                <a href="home.php?logout=true" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i>Log-out</a>
            </form>
        </ul>
    </div>

    <div id="main-content">
        <div class="container">
            <div class="admin-panel">
                <h2>Admin Panel</h2>
            </div>

            <!-- Organizations Section -->
            <div class="organizations-log">
                <h2>Pending Organization Requests</h2>
                <div id="organizations-list">
                    <?php if (empty($requests)): ?>
                        <p>No pending organization requests at the moment.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Logo</th>
                                    <th>President ID</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['org_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['description']); ?></td>
                                    <td><img src="<?php echo htmlspecialchars($request['logo_url']); ?>" alt="<?php echo htmlspecialchars($request['org_name']); ?>" width="100"></td>
                                    <td><?php echo htmlspecialchars($request['president_id']); ?></td>
                                    <td><?php echo htmlspecialchars($request['status']); ?></td>
                                    <td>
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="requestId" value="<?php echo htmlspecialchars($request['request_id']); ?>">
                                            <button type="submit" name="acceptRequest" class="approve-btn" onclick="return confirm('Are you sure you want to approve this organization request?');">Approve</button>
                                            <button type="submit" name="declineRequest" class="remove-btn" onclick="return confirm('Are you sure you want to reject this organization request?');">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
