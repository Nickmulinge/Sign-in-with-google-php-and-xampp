<?php
require __DIR__ . "/vendor/autoload.php";

// Set up Google Client
$client = new Google\Client;
$client->setClientId("");
$client->setClientSecret("");
$client->setRedirectUri("http://localhost/googlephp/redirect.php");

$client->addScope("email");
$client->addScope("profile");

// Database connection details
$host = 'localhost';
$db = 'googlephp';
$user = 'root';
$pass = '';

// Check if Google authentication code is present
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // Get user information from Google
    $google_service = new Google\Service\Oauth2($client);
    $google_user = $google_service->userinfo->get();

    $name = $google_user->name;
    $email = $google_user->email;

    // Database connection
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if the email already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Insert the new Google user into the database
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        
        // Google login usually doesn't provide a password, so store NULL or a default
        $password = null; // Or set a default password here if desired
        $stmt->bind_param("sss", $name, $email, $password);
        
        if ($stmt->execute()) {
            echo "Google Signup successful! You can now log in.";
        } else {
            echo "Signup failed. Please try again.";
        }
    } else {
        echo "Welcome back, " . $name . "!";
    }

    $stmt->close();
    $conn->close();
}
?>
