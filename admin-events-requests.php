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

// Handle event request actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approveEvent'])) {
        $requestId = $_POST['requestId'];
        approveEventRequest($requestId);

        // Redirect to avoid re-submission on refresh
        header("Location: admin-events-requests.php"); // Adjust to your actual admin dashboard URL
        exit();
    } elseif (isset($_POST['rejectEvent'])) {
        $requestId = $_POST['requestId'];
        rejectEventRequest($requestId);

        // Redirect to avoid re-submission on refresh
        header("Location: admin-events-requests.php"); // Adjust to your actual admin dashboard URL
        exit();
    }
}

// Fetch pending event requests
$pendingEventRequests = fetchPendingEventRequests();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            <li class="active">
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
            <li>
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

            <!-- Events Section -->
            <div class="events-log">
                <h2>Pending Event Requests</h2>
                <div id="event-requests-list">
                    <?php if (empty($pendingEventRequests)): ?>
                        <p>No pending event requests at the moment.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Venue</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Image</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($pendingEventRequests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['title']); ?></td>
                                    <td><?php echo htmlspecialchars($request['venue']); ?></td>
                                    <td><?php echo htmlspecialchars($request['date']); ?></td>
                                    <td><?php echo htmlspecialchars($request['start_time'] . ' - ' . $request['end_time']); ?></td>
                                    <td><img src="<?php echo htmlspecialchars($request['image_url']); ?>" alt="<?php echo htmlspecialchars($request['title']); ?>" width="100"></td>
                                    <td><?php echo htmlspecialchars($request['description']); ?></td>
                                    <td>
                                        <form method="POST" action="">
                                            <input type="hidden" name="requestId" value="<?php echo $request['request_id']; ?>">
                                            <button type="submit" name="approveEvent" class="approve-btn">Approve</button>
                                            <button type="submit" name="removeEvent" class="remove-btn">Reject</button>
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
