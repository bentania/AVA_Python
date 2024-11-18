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

// Check if the client ID and languages are provided
$client_id = isset($_POST['client_id']) ? $_POST['client_id'] : null;
$language_ids = isset($_POST['language_ids']) ? $_POST['language_ids'] : [];

if (!$client_id) {
    die("Client ID is missing.");
}

// Debugging: Output the client ID and selected language IDs
echo "Client ID: " . $client_id . "<br>";
echo "Selected Language IDs: <br>";
print_r($language_ids);
echo "<br>";

// First, delete any existing languages for the client (removes duplicates)
$delete_query = $conn->prepare("DELETE FROM client_languages WHERE client_id = ?");
$delete_query->bind_param("i", $client_id);

if ($delete_query->execute()) {
    echo "Successfully deleted existing languages for client ID $client_id.<br>";
} else {
    die("Error deleting existing languages: " . $conn->error);
}

// Insert the new selected languages
if (!empty($language_ids)) {
    $insert_query = $conn->prepare("INSERT INTO client_languages (client_id, language_id) VALUES (?, ?)");

    foreach ($language_ids as $language_id) {
        // Attempt insertion
        $insert_query->bind_param("ii", $client_id, $language_id);
        if ($insert_query->execute()) {
            echo "Successfully inserted language ID $language_id for client ID $client_id.<br>";
        } else {
            echo "Error inserting language ID $language_id: " . $conn->error . "<br>";
        }
    }
} else {
    echo "No languages were selected.";
}

// Close the database connection
$conn->close();

// Redirect to the bank holidays page for the client
header("Location: bankholidays.php?client_id=" . $client_id);
exit();
