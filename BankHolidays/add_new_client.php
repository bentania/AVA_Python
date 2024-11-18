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

// Check if the new client name is provided
if (isset($_GET['new_client_name']) && !empty($_GET['new_client_name'])) {
    $new_client_name = $conn->real_escape_string($_GET['new_client_name']);

    // Insert the new client into the clients table
    $sql = "INSERT INTO clients (name) VALUES ('$new_client_name')";

    if ($conn->query($sql) === TRUE) {
        $new_client_id = $conn->insert_id; // Get the newly inserted client ID
        header("Location: add_languages.php?client_id=$new_client_id&year=" . $_GET['year']);
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
} else {
    echo "No client name provided.";
}

$conn->close();
?>
