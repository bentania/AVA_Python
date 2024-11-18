<?php
// Database connection settings
$host = '10.0.0.223';
$user = 'pbento';
$password = 'bento2024$%';
$database = 'ava';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the client name is provided
$client_name = isset($_POST['client_name']) ? $_POST['client_name'] : null;

if (!$client_name) {
    die("Client name is required.");
}

// Insert the new client into the database
$insert_query = $conn->prepare("INSERT INTO clients (name) VALUES (?)");
$insert_query->bind_param("s", $client_name);
$insert_query->execute();

// Get the last inserted client ID
$new_client_id = $conn->insert_id;

// Redirect to add languages for the new client
header("Location: add_languages.php?client_id=" . $new_client_id);
exit();
