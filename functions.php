<?php
include 'db.php';

function registerUser($firstName, $lastName, $email, $password, $confirmPassword) {
    global $conn;
    $errorMessage = '';

    // Check if passwords match
    if ($password !== $confirmPassword) {
        $errorMessage = "Passwords do not match.";
    } else {
        // Validate password strength
        if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
            $errorMessage = "Password must be at least 8 characters long and include at least one uppercase letter, one number, and one special character.";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE Email = :email");
            $stmt->execute([':email' => $email]);
            $emailExists = $stmt->fetchColumn();

            if ($emailExists) {
                $errorMessage = "Email is already registered.";
            } else {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO users (FirstName, LastName, Email, PasswordHash) VALUES (:firstName, :lastName, :email, :passwordHash)");
                $stmt->execute([
                    ':firstName' => $firstName,
                    ':lastName' => $lastName,
                    ':email' => $email,
                    ':passwordHash' => $passwordHash
                ]);
                if ($stmt) {
                    $_SESSION['loggedin'] = true;
                    $_SESSION['user_id'] = $conn->lastInsertId(); // Save the user ID
                    return ['success' => true];
                }
            }
        }
    }

    if ($errorMessage) {
        return ['success' => false, 'message' => $errorMessage];
    }
}


function loginUser($email, $password) {
    global $conn;

    // Prepare and execute the SQL query
    $stmt = $conn->prepare("SELECT UserID, PasswordHash, Role FROM users WHERE Email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify the password and check the user's role
    if ($user) {
        if (password_verify($password, $user['PasswordHash'])) {
            // Set session variables
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['UserID']; // Save the user ID
            $_SESSION['role'] = $user['Role']; // Save the user's role

            // Redirect based on user role
            $redirectPage = isset($_GET['page']) ? $_GET['page'] : 'home.php';
            if ($user['Role'] === 'admin') {
                $redirectPage = 'admin-user-dashboard.php';
            }
            header("Location: $redirectPage");
            exit();
        } else {
            $_SESSION['error'] = "Invalid email or password.";
            $_SESSION['show_modal'] = true; // Show the modal on error
            $redirectPage = isset($_GET['page']) ? $_GET['page'] : 'home.php';
            header("Location: $redirectPage");
            exit();
        }
    } else {
        $_SESSION['error'] = "Invalid email or password.";
        $_SESSION['show_modal'] = true; // Show the modal on error
        $redirectPage = isset($_GET['page']) ? $_GET['page'] : 'home.php';
        header("Location: $redirectPage");
        exit();
    }
}

// Updated version that returns a success array instead of redirecting immediately
function loginUserWithReturn($email, $password) {
    global $conn;

    // Prepare and execute the SQL query
    $stmt = $conn->prepare("SELECT UserID, PasswordHash, Role FROM users WHERE Email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify the password and check the user's role
    if ($user) {
        if (password_verify($password, $user['PasswordHash'])) {
            // Set session variables
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['UserID']; // Save the user ID
            $_SESSION['role'] = $user['Role']; // Save the user's role

            // Return success
            return ['success' => true];
        } else {
            // Return failure with message
            return ['success' => false, 'message' => "Invalid email or password."];
        }
    } else {
        // Return failure with message
        return ['success' => false, 'message' => "Invalid email or password."];
    }
}

function fetchUsers() {
    global $conn;
    $stmt = $conn->prepare("
        SELECT 
            u.UserID, 
            u.FirstName, 
            u.LastName, 
            u.Email, 
            ui.Age, 
            ui.Address, 
            ui.School, 
            o.Org_Name
        FROM 
            Users u
        LEFT JOIN 
            UserInfo ui ON u.UserID = ui.user_id
        LEFT JOIN 
            Membership m ON u.UserID = m.user_id
        LEFT JOIN 
            Organizations o ON m.org_id = o.org_id
        WHERE 
            u.role <> 'admin';  -- Exclude users with the role 'admin'
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function resetPassword($email, $newPassword) {
    global $conn;

    try {
        // Validate new password strength
        if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $newPassword)) {
            return ['success' => false, 'message' => "New password must be at least 8 characters long and include at least one uppercase letter, one number, and one special character."];
        }

        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Prepare the update statement
        $stmt = $conn->prepare("UPDATE Users SET PasswordHash = :password WHERE email = :email");
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // Check if the user was found and password updated
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Password reset successfully.'];
        } else {
            return ['success' => false, 'message' => 'No user found with this email.'];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}




// Function to remove a user
function removeUser($userID) {
    global $conn;
    $conn->beginTransaction();
    
    try {
        // Delete from UserInfo table
        $deleteUserInfo = $conn->prepare("DELETE FROM UserInfo WHERE user_id = :userID");
        $deleteUserInfo->bindParam(':userID', $userID, PDO::PARAM_INT);
        $deleteUserInfo->execute();

        // Delete from Membership table
        $deleteMembership = $conn->prepare("DELETE FROM Membership WHERE user_id = :userID");
        $deleteMembership->bindParam(':userID', $userID, PDO::PARAM_INT);
        $deleteMembership->execute();

        // Delete from Users table
        $deleteUser = $conn->prepare("DELETE FROM Users WHERE UserID = :userID");
        $deleteUser->bindParam(':userID', $userID, PDO::PARAM_INT);
        $deleteUser->execute();

        // Commit the transaction
        $conn->commit();
        return true;

    } catch (Exception $e) {
        // Rollback transaction if anything goes wrong
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo "Failed to delete user: " . $e->getMessage();
        return false;
    }
}


// functions.php

function getUserInfo($userId) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT u.FirstName, u.LastName, u.Email, m.role, o.org_name, o.logo_url 
        FROM users u 
        LEFT JOIN Membership m ON u.UserID = m.user_id 
        LEFT JOIN Organizations o ON m.org_id = o.org_id 
        WHERE u.UserID = ?
    ");
    $stmt->execute([$userId]);
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $result = $stmt->fetch();

    return [
        'firstName' => $result['FirstName'] ?? '', 
        'lastName' => $result['LastName'] ?? '', 
        'email' => $result['Email'] ?? '', 
        'role' => !empty($result['role']) ? $result['role'] : 'N/A', 
        'orgName' => $result['org_name'] ?? '',
        'logoUrl' => $result['logo_url'] ?? ''
    ];
}



function getUserInfos($userId) {
    global $conn;

    // Prepare the SQL statement
    $stmt = $conn->prepare("SELECT * FROM UserInfo WHERE user_id = :user_id");

    // Execute the statement with the provided user ID
    $stmt->execute(['user_id' => $userId]);

    // Fetch the result
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Provide default values if result is null or missing fields
    if ($result === false) {
        return [
            'age' => 'N/A',
            'address' => 'N/A',
            'school' => 'N/A',
            'profile_image' => 'image/default-avatar.jpg'
        ];
    }

    return [
        'age' => $result['age'] ?? 'N/A',
        'address' => $result['address'] ?? 'N/A',
        'school' => $result['school'] ?? 'N/A',
        'profile_image' => $result['profile_image'] ?? 'image/avatar.jpg'
    ];
}


function updatePassword($userId, $currentPassword, $newPassword) {
    global $conn;

    // Fetch the hashed password from the database
    $stmt = $conn->prepare("SELECT PasswordHash FROM users WHERE UserID = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if the user was found
    if (!$user) {
        return ['success' => false, 'message' => "User not found."];
    }

    // Verify the current password
    if (!password_verify($currentPassword, $user['PasswordHash'])) {
        return ['success' => false, 'message' => "Current password is incorrect."];
    }

    // Validate new password strength
    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $newPassword)) {
        return ['success' => false, 'message' => "New password must be at least 8 characters long and include at least one uppercase letter, one number, and one special character."];
    }

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update the password in the database
    $stmt = $conn->prepare("UPDATE users SET PasswordHash = ? WHERE UserID = ?");
    $updateResult = $stmt->execute([$hashedPassword, $userId]);

    if ($updateResult) {
        return ['success' => true, 'message' => "Password updated successfully."];
    } else {
        return ['success' => false, 'message' => "Failed to update password. Please try again."];
    }
}




function updateUserProfile($userId, $age, $address, $school, $profileImage) {
    global $conn;
    try {
        // Check if the user info exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM UserInfo WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $infoExists = $stmt->fetchColumn() > 0;

        if ($infoExists) {
            // Update existing user info
            $query = "UPDATE UserInfo SET age = :age, address = :address, school = :school, profile_image = :profile_image WHERE user_id = :user_id";
        } else {
            // Insert new user info
            $query = "INSERT INTO UserInfo (user_id, age, address, school, profile_image) VALUES (:user_id, :age, :address, :school, :profile_image)";
        }

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':age', $age);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':school', $school);
        $stmt->bindParam(':profile_image', $profileImage);
        $stmt->execute();

        return true;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}



// Function to approve event requests
function approveEventRequest($requestId) {
    global $conn;

    try {
        // Fetch the event request data
        $stmt = $conn->prepare("SELECT * FROM event_requests WHERE request_id = :request_id");
        $stmt->bindParam(':request_id', $requestId, PDO::PARAM_INT);
        $stmt->execute();
        $eventRequest = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($eventRequest) {
            // Insert into the events table with the user_id and org_id
            $stmt = $conn->prepare("
                INSERT INTO events (org_id, user_id, title, venue, date, start_time, end_time, description, image_url) 
                VALUES (:org_id, :user_id, :title, :venue, :date, :start_time, :end_time, :description, :image_url)
            ");
            $stmt->bindParam(':org_id', $eventRequest['org_id'], PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $eventRequest['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':title', $eventRequest['title'], PDO::PARAM_STR);
            $stmt->bindParam(':venue', $eventRequest['venue'], PDO::PARAM_STR);
            $stmt->bindParam(':date', $eventRequest['date'], PDO::PARAM_STR);
            $stmt->bindParam(':start_time', $eventRequest['start_time'], PDO::PARAM_STR);
            $stmt->bindParam(':end_time', $eventRequest['end_time'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $eventRequest['description'], PDO::PARAM_STR);
            $stmt->bindParam(':image_url', $eventRequest['image_url'], PDO::PARAM_STR);
            $stmt->execute();

            // Update the event request status
            $stmt = $conn->prepare("UPDATE event_requests SET status = 'approved' WHERE request_id = :request_id");
            $stmt->bindParam(':request_id', $requestId, PDO::PARAM_INT);
            $stmt->execute();
        }
    } catch (PDOException $e) {
        error_log("Error approving event request: " . $e->getMessage());
    }
}

function getOrganizationIdByPresident($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT org_id FROM organizations WHERE president_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['org_id'] : null;
}





// Function to reject event requests
function rejectEventRequest($requestId) {
    global $conn;

    $stmt = $conn->prepare("UPDATE event_requests SET status = 'rejected' WHERE request_id = ?");
    $stmt->execute([$requestId]);
}

// Function to add an event request to the database
function addEventRequest($user_id, $org_id, $title, $venue, $date, $start_time, $end_time, $description, $image_url) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO event_requests (user_id, org_id, title, venue, date, start_time, end_time, description, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    return $stmt->execute([$user_id, $org_id, $title, $venue, $date, $start_time, $end_time, $description, $image_url]);
}


// Function to fetch pending event requests
function fetchPendingEventRequests() {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM event_requests WHERE status = 'pending'");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchEvents() {
    global $conn;
    $stmt = $conn->query("SELECT * FROM events");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function fetchApprovedEvents() {
    global $conn; // Assuming you have a global PDO connection variable

    try {
        $stmt = $conn->prepare("SELECT * FROM events WHERE status = 'active'");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}


// remove events functions.php

function removeEvent($eventId) {
    global $conn; // Assuming you have a global PDO connection variable

    try {
        $stmt = $conn->prepare("DELETE FROM events WHERE event_id = :eventId");
        $stmt->bindParam(':eventId', $eventId, PDO::PARAM_INT);
        $stmt->execute();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Function to handle file upload
function handleFileUpload($file) {
    $uploadDir = 'uploads/';
    $fileName = basename($file['name']);
    $targetFilePath = $uploadDir . $fileName;

    // Ensure the file is an image
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png'];

    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            return $targetFilePath;
        }
    }
    return null;
}

function createOrganizationRequest($orgName, $description, $logoUrl, $presidentId) {
    global $conn;

    try {
        // Start the transaction
        $conn->beginTransaction();

        // Insert the new organization request
        $stmt = $conn->prepare("
            INSERT INTO OrganizationRequests (org_name, description, logo_url, president_id, status)
            VALUES (:orgName, :description, :logoUrl, :presidentId, 'pending')
        ");
        $stmt->execute([
            ':orgName' => $orgName,
            ':description' => $description,
            ':logoUrl' => $logoUrl,
            ':presidentId' => $presidentId
        ]);

        // Commit the transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback the transaction
        $conn->rollBack();
        
        // Display the error message for debugging
        echo "Error occurred: " . $e->getMessage();
        return false;
    }
}


function organizationNameExists($orgName) {
    global $conn;
    
    // Check in OrganizationRequests table
    $stmt = $conn->prepare("SELECT COUNT(*) FROM OrganizationRequests WHERE org_name = :orgName");
    $stmt->execute(['orgName' => $orgName]);
    if ($stmt->fetchColumn() > 0) {
        return true;
    }
    
    // Check in Organizations table
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Organizations WHERE page_url LIKE :orgNameSlug");
    $orgNameSlug = strtolower(str_replace(' ', '-', $orgName)) . '.php';
    $stmt->execute(['orgNameSlug' => $orgNameSlug]);
    if ($stmt->fetchColumn() > 0) {
        return true;
    }

    return false;
}


// Fetch requests function
function fetchOrganizationRequests() {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM OrganizationRequests");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo "Error fetching organization requests: " . $e->getMessage();
        return [];
    }
}

function removeOrganization($orgId) {
    global $conn;

    try {
        // Begin a transaction
        $conn->beginTransaction();

        // Fetch the page URL slug for the organization
        $stmt = $conn->prepare("SELECT page_url FROM organizations WHERE org_id = :org_id");
        $stmt->bindParam(':org_id', $orgId, PDO::PARAM_INT);
        $stmt->execute();
        $org = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($org) {
            // Define the path to the organization page file
            $pageFile = $org['page_url'] . ".php";

            // Delete the page file if it exists
            if (file_exists($pageFile)) {
                unlink($pageFile);
            }
        }

        // Delete events created by the organization
        $stmt = $conn->prepare("DELETE FROM events WHERE org_id = :org_id");
        $stmt->bindParam(':org_id', $orgId, PDO::PARAM_INT);
        $stmt->execute();

        // Delete records from the membership table
        $stmt = $conn->prepare("DELETE FROM membership WHERE org_id = :org_id");
        $stmt->bindParam(':org_id', $orgId, PDO::PARAM_INT);
        $stmt->execute();

        // Delete records from the event_requests table
        $stmt = $conn->prepare("DELETE FROM event_requests WHERE org_id = :org_id");
        $stmt->bindParam(':org_id', $orgId, PDO::PARAM_INT);
        $stmt->execute();

        // Delete the organization
        $stmt = $conn->prepare("DELETE FROM organizations WHERE org_id = :org_id");
        $stmt->bindParam(':org_id', $orgId, PDO::PARAM_INT);
        $stmt->execute();

        // Commit the transaction
        $conn->commit();
        echo "Organization and associated events removed successfully.";
    } catch (PDOException $e) {
        // Roll back the transaction on error
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}


function getEventsByOrganization($orgId) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM events WHERE org_id = :org_id");
        $stmt->bindParam(':org_id', $orgId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error fetching events: " . $e->getMessage();
        return [];
    }
}


// Accept request function
function acceptOrganizationRequest($requestId) {
    global $conn;

    try {
        // Start transaction
        $conn->beginTransaction();

        // Fetch the request details
        $query = "SELECT * FROM organizationrequests WHERE request_id = :requestId";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':requestId', $requestId, PDO::PARAM_INT);
        $stmt->execute();
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($request && $request['status'] === 'pending') {

            // Generate page URL slug
            $pageUrlSlug = strtolower(str_replace(' ', '-', $request['org_name']));

            // Create the full page URL
            $pageUrl = $pageUrlSlug . ".php";

            // Insert the new organization into the organizations table
            $insertOrgQuery = "INSERT INTO organizations (org_name, description, logo_url, president_id, status, page_url) 
            VALUES (:orgName, :description, :logoUrl, :presidentId, 'approved', :pageUrl)";
                $insertOrgStmt = $conn->prepare($insertOrgQuery);
                $insertOrgStmt->bindParam(':orgName', $request['org_name']);
                $insertOrgStmt->bindParam(':description', $request['description']);
                $insertOrgStmt->bindParam(':logoUrl', $request['logo_url']);
                $insertOrgStmt->bindParam(':presidentId', $request['president_id']);
                $insertOrgStmt->bindParam(':pageUrl', $pageUrl); // Use $pageUrl, not $pageUrlSlug
                $insertOrgStmt->execute();

            // Get the new org_id
            $orgId = $conn->lastInsertId();

            // Insert into the membership table
            $membershipQuery = "INSERT INTO membership (user_id, org_id, role) 
                                VALUES (:presidentId, :orgId, 'president')";
            $membershipStmt = $conn->prepare($membershipQuery);
            $membershipStmt->bindParam(':presidentId', $request['president_id']);
            $membershipStmt->bindParam(':orgId', $orgId, PDO::PARAM_INT);
            $membershipStmt->execute();

            // Create the organization page
            $createPageSuccess = createOrganizationPage($request['org_name'], $request['logo_url'], $request['description'], $pageUrlSlug);

            if (!$createPageSuccess) {
                throw new Exception("Failed to create organization page.");
            }

            // Remove the approved request from the org_requests table
            $deleteRequestQuery = "DELETE FROM organizationrequests WHERE request_id = :requestId";
            $deleteRequestStmt = $conn->prepare($deleteRequestQuery);
            $deleteRequestStmt->bindParam(':requestId', $requestId, PDO::PARAM_INT);
            $deleteRequestStmt->execute();

            // Commit transaction
            $conn->commit();
            echo "Organization request accepted and organization page created successfully.";
        } else {
            echo "The request is either not pending or does not exist.";
        }
    } catch (PDOException $e) {
        // Rollback transaction if something goes wrong
        $conn->rollBack();
        echo "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        // Rollback transaction if something goes wrong
        $conn->rollBack();
        echo "General error: " . $e->getMessage();
    }
}




// Decline request function
function declineOrganizationRequest($requestId) {
    global $conn;

    try {
        // Delete the request
        $stmt = $conn->prepare("DELETE FROM OrganizationRequests WHERE request_id = :requestId");
        $stmt->execute([':requestId' => $requestId]);

        // Optionally, update user's organization status to 'rejected' in a dedicated table or field
        $stmt = $conn->prepare("UPDATE Users SET organization_status = 'rejected' WHERE user_id = (SELECT president_id FROM OrganizationRequests WHERE request_id = :requestId)");
        $stmt->execute([':requestId' => $requestId]);

        echo "Request successfully removed.";
    } catch (Exception $e) {
        echo "Error occurred while removing the request: " . $e->getMessage();
    }
}


function getEventRequestStatus($user_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT status FROM event_requests WHERE user_id = :user_id ORDER BY request_id DESC LIMIT 1");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        return $result['status']; // This could return 'pending', 'approved', or 'rejected'
    } else {
        return null; // No request found
    }
}


function getOrganizationStatus($presidentId) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT status FROM  OrganizationRequests WHERE president_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$presidentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['status'] : '';
    } catch (Exception $e) {
        error_log($e->getMessage());
        return '';
    }
}

function fetchApprovedOrganizations() {
    global $conn; // Assuming you have a global PDO connection variable

    try {
        // Prepare and execute the query to fetch approved organizations
        $stmt = $conn->prepare("SELECT * FROM organizations WHERE status = 'approved'");
        $stmt->execute();

        // Fetch all approved organizations
        $approvedOrgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $approvedOrgs;
    } catch (PDOException $e) {
        // Handle the error (e.g., log it or display a message)
        echo "Error: " . $e->getMessage();
        return [];
    }
}


function fetchAllOrganizations() {
    global $conn; // Use $conn as defined in your connection setup

    try {
        // Updated query to include the page_url column
        $stmt = $conn->prepare("SELECT org_name AS name, description, logo_url AS logoUrl, page_url FROM organizations");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}


function createOrganizationPage($orgName, $orgLogo, $orgDescription, $pageUrlSlug) {
    // Define the filename using the page URL slug
    $filename = "organizations/{$pageUrlSlug}.php"; // Ensure it’s a PHP file

    // Ensure the directory exists
    if (!is_dir('organizations')) {
        mkdir('organizations', 0755, true); // Create the directory if it does not exist
    }

    // Content of the organization page
    $content = "
    <?php
    session_start();
    include '../functions.php';

    // Handle user registration
    if (\$_SERVER['REQUEST_METHOD'] == 'POST' && isset(\$_POST['register'])) {
        \$firstName = \$_POST['fname'];
        \$lastName = \$_POST['lname'];
        \$email = \$_POST['email'];
        \$password = \$_POST['password'];
        \$confirmPassword = \$_POST['confirm-password'];
        registerUser(\$firstName, \$lastName, \$email, \$password, \$confirmPassword);
    }

    // Handle user login
    if (\$_SERVER['REQUEST_METHOD'] == 'POST' && isset(\$_POST['login'])) {
        \$email = \$_POST['email'];
        \$password = \$_POST['password'];
        loginUser(\$email, \$password);
    }

    // Handle logout
    if (isset(\$_GET['logout'])) {
        session_unset();
        session_destroy();
        header('Location: ../community.php'); // Redirect to community.php after logout
        exit();
    }

    // Fetch user information
    \$userData = ['firstName' => '', 'lastName' => '', 'role' => '', 'orgName' => '', 'logoUrl' => ''];
    if (isset(\$_SESSION['loggedin']) && \$_SESSION['loggedin']) {
        \$userData = getUserInfo(\$_SESSION['user_id']);
    }

    // Fetch all events for the organization
    \$events = [];
    \$stmt = \$conn->prepare(\"SELECT title, venue, date, start_time, end_time, description, image_url FROM events\");
    \$stmt->execute();
    \$events = \$stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch additional user information
    \$userDatas = [
        'profile_image' => '../image/avatar.jpg' // Default image path adjusted
    ];

    if (isset(\$_SESSION['loggedin']) && \$_SESSION['loggedin']) {
        \$userDatas = getUserInfos(\$_SESSION['user_id']);
        if (\$userDatas && is_array(\$userDatas)) {
            \$userDatas['profile_image'] = !empty(\$userDatas['profile_image']) ? '../' . \$userDatas['profile_image'] : '../image/avatar.jpg'; // Default image path adjusted
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang=\"en\">
    <head>
        <meta charset=\"UTF-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        <title><?php echo htmlspecialchars(\$orgName); ?></title>
        <link rel=\"stylesheet\" href=\"../css/org-design.css\">
        <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\">
    </head>
    <body>

    <div id=\"content\">
        <div id=\"header\">
            <div class=\"cover\">
                <div class=\"nav\">
                    <div class=\"logo\">
                        <a href=\"../home.php\">
                            <img src=\"../image/logo.png\" alt=\"\">
                            <p>CYDO</p>
                        </a>
                    </div>
                    <div class=\"navigation\">
                        <a href=\"../home.php\">Home</a>
                        <a href=\"../community.php\">Community</a>
                        <a href=\"../event.php\">News</a>
                        <a href=\"../about.php\">About</a>
                        <?php if (isset(\$_SESSION['loggedin']) && \$_SESSION['loggedin']): ?>
                            <div id=\"loggedInIcon\" class=\"navigation\">
                                <button id=\"profileButton\">
                                    <img src=\"<?php echo htmlspecialchars(\$userDatas['profile_image']); ?>\" alt=\"User Image\">
                                    <i class=\"fa-solid fa-angle-down\"></i>
                                </button>
                                <div id=\"dropdownMenu\" class=\"dropdown-menu\">
                                    <p><?php echo htmlspecialchars(\$userData['firstName'] . ' ' . \$userData['lastName']); ?></p>
                                    <a href=\"../dashboard.php\">My Profile</a>
                                    <a href=\"../my-events.php\">Events</a>
                                    <a href=\"../my-org.php\">Organization</a>
                                    <a href=\"../community.php?logout=true\" class=\"logout\"><i class=\"fa-solid fa-arrow-right-from-bracket\"></i>Log-out</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <button id=\"accessButton\" type=\"button\" class=\"access\" onclick=\"openLoginModal()\">Access</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class=\"org-dashboard\">
            <div class=\"organization\">
                <div class=\"container\">
                    <div class=\"org-container\">
                        <img src=\"../{$orgLogo}\" alt=\"{$orgName} Logo\">
                        <div class=\"org-info\">
                            <h1>{$orgName}</h1>
                            <h3>About Us</h3>
                            <p>{$orgDescription}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id=\"events-section\">
                <div class=\"container\">
                    <div class=\"title\">
                        <i class=\"fa-regular fa-calendar\"></i>
                        <p>Events</p>
                    </div>
                    <div class=\"events-grid\">
                        <?php foreach (\$events as \$event): ?>
                            <div class=\"event-card\" data-title=\"<?php echo htmlspecialchars(\$event['title']); ?>\"
                                data-venue=\"<?php echo htmlspecialchars(\$event['venue']); ?>\"
                                data-date=\"<?php echo htmlspecialchars(\$event['date']); ?>\"
                                data-time=\"<?php echo htmlspecialchars(\$event['start_time']) . ' - ' . htmlspecialchars(\$event['end_time']); ?>\"
                                data-description=\"<?php echo htmlspecialchars(\$event['description']); ?>\"
                                data-image=\"../<?php echo htmlspecialchars(\$event['image_url']); ?>\"
                                onclick=\"openEventModal(this)\">
                                <div class=\"events\">
                                    <img src=\"../<?php echo htmlspecialchars(\$event['image_url']); ?>\" alt=\"Event Image\" onerror=\"this.src='../image/default-event.jpg';\">
                                        <div class=\"info\">
                                            <h3><?php echo htmlspecialchars(\$event['title']); ?></h3>
                                            <p><?php echo htmlspecialchars(\$event['venue']); ?></p>
                                            <div class=\"date\">
                                                <p><?php echo htmlspecialchars(\$event['date']); ?></p>
                                                <p><?php echo htmlspecialchars(\$event['start_time']) . ' - ' . htmlspecialchars(\$event['end_time']); ?></p>
                                            </div>
                                            <p>Description: <?php echo htmlspecialchars(\$event['description']); ?></p>
                                            <button>More information</button>
                                        </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id=\"footer\">
            <div class=\"last\">
                <div class=\"container-last\">
                    <h2>Join our community</h2>
                    <div class=\"socials\">
                        <div class=\"social-image\">
                            <img src=\"../social-logo/fb.png\" alt=\"\">
                        </div>
                        <div class=\"social-image\">
                            <img src=\"../social-logo/ig.png\" alt=\"\">
                        </div>
                    </div>
                    <div class=\"web-logo\">
                        <img src=\"../image/logo.png\" alt=\"\">
                        <p>CYDO</p>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <div id=\"loginModal\" class=\"modal\">
    <div class=\"modal-contents\">
        <span class=\"close\" onclick=\"closeLoginModal()\">&times;</span>
        <div class=\"mods\">
            <div class=\"contents\">
                <p>Welcome to</p>
                <h1>Youth</h1>
                <p>The most active sus keme nyo.</p>
            </div>
            <div class=\"regs\">
                <p>Access with your credentials</p>
                <form method=\"POST\" action=\"event.php\">
                    <label for=\"login-email\">Email:</label>
                    <input type=\"email\" id=\"login-email\" name=\"email\" required>
                    <label for=\"login-password\">Password:</label>
                    <input type=\"password\" id=\"login-password\" name=\"password\" required>
                    <button type=\"submit\" name=\"login\">Access</button>
                    <p>Create an Account? <a href=\"#\" onclick=\"openRegisterModal()\">Register</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<div id=\"registerModal\" class=\"modal\">
    <div class=\"modal-content\">
        <span class=\"close\" onclick=\"closeRegisterModal()\">&times;</span>
        <div class=\"mods\">
            <div class=\"content\">
                <p>Welcome to</p>
                <h1>Youth</h1>
                <p>Sasali kapa wala karin naman gagawin.</p>
            </div>
            <div class=\"regs\">
                <p>Create an Account</p>
                <form method=\"POST\" action=\"event.php\">
                    <div class=\"name-container\">
                        <div class=\"name-field\">
                            <label for=\"fname\">First Name:</label>
                            <input type=\"text\" id=\"fname\" name=\"fname\" required>
                        </div>
                        <div class=\"name-field\">
                            <label for=\"lname\">Last Name:</label>
                            <input type=\"text\" id=\"lname\" name=\"lname\" required>
                        </div>
                    </div>
                    <label for=\"login-email\">Email:</label>
                    <input type=\"email\" id=\"email\" name=\"email\" required>
                    <label for=\"login-password\">Password:</label>
                    <input type=\"password\" id=\"password\" name=\"password\" required>
                    <label for=\"confirm-password\">Confirm Password:</label>
                    <input type=\"password\" id=\"confirm-password\" name=\"confirm-password\" required>
                    <button type=\"submit\" name=\"register\">Register</button>
                    <p>Already have an Account? <a href=\"#\" onclick=\"switchToLoginModal()\">Login</a></p>
                </form>
            </div>
        </div>
    </div>
</div>


    <div id=\"eventModal\" class=\"modal\">
        <div class=\"modal-contents\">
            <span class=\"close\" onclick=\"closeEventModal()\">&times;</span>
                <img id=\"eventImage\" src=\"\" alt=\"Event Image\">
                <h2 id=\"eventTitle\"></h2>
                <p><strong>Venue:</strong> <span id=\"eventVenue\"></span></p>
                    <div class=\"dates\">
                        <p><i class=\"fa-regular fa-calendar\"></i> <span id=\"eventDate\"></span> </p>
                        <p><i class=\"fa-regular fa-clock\"></i> <span id=\"eventTime\"></span></p>
                    </div>
                <p><strong>About:</strong> <span id=\"eventDescription\"></span></p>
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
    ";

    // Write the content to the file
    file_put_contents($filename, $content);

    // Return the filename for reference
    return $filename;
}



?>