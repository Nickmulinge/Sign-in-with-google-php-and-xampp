<?php
require_once 'vendor/autoload.php';  // Assuming you installed Google Client Library via Composer

use Google\Client;

// Database connection details
$host = 'localhost';
$db = 'googlephp';
$user = '';
$pass = '';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id_token = $data['id_token'];

// Verify the ID token with Google
$client = new Google_Client(['client_id' => '634696284816-e7it3uq408uuenqdho91f6v8do86qot3.apps.googleusercontent.com']);
$payload = $client->verifyIdToken($id_token);

if ($payload) {
    $google_id = $payload['sub'];
    $email = $payload['email'];
    $name = $payload['name'];

    // Create a connection
    $conn = new mysqli($host, $user, $pass, $db);

    // Check connection
    if ($conn->connect_error) {
        die(json_encode(['success' => false, 'error' => 'Database connection failed.']));
    }

    // Check if the user exists, otherwise insert
    $stmt = $conn->prepare("SELECT * FROM users WHERE google_id = ?");
    $stmt->bind_param("s", $google_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (google_id, email, name) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $google_id, $email, $name);
        $stmt->execute();
    }

    // Close connection
    $stmt->close();
    $conn->close();

    echo json_encode(['success' => true, 'message' => 'User logged in successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid ID token']);
}
?>
