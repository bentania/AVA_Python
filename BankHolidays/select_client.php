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

// Get the list of all clients
$clients = $conn->query("SELECT * FROM clients");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Client or Add New</title>
    <!-- Link to the CSS file -->
    <link rel="stylesheet" href="/css/styles.css">
    <script>
        function toggleNewClientForm() {
            var select = document.getElementById('client');
            var newClientForm = document.getElementById('new-client-form');
            var form = document.getElementById('clientForm');

            if (select.value === 'new') {
                newClientForm.style.display = 'block';
                form.action = 'save_new_client.php';  // Action for adding a new client
            } else {
                newClientForm.style.display = 'none';
                form.action = 'add_languages.php';  // Action for selecting an existing client
                // Ensure it submits with GET to pass client_id
                form.method = 'GET';
                form.submit();  // Submit form immediately for existing client
            }
        }
    </script>
</head>
<body>

<!-- Home Button -->
<a href="/bankholidays" class="home-button">Home</a>

<h2>Select Client or Add New Client</h2>

<!-- Form for selecting or adding a client -->
<form id="clientForm" method="POST">
    <label for="client">Choose a client:</label>
    <select name="client_id" id="client" onchange="toggleNewClientForm()">
        <?php
        // List existing clients
        while ($row = $clients->fetch_assoc()): ?>
            <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
        <?php endwhile; ?>
        <!-- Option to add a new client -->
        <option value="new">Add New Client</option>
    </select>

    <!-- New client form (initially hidden) -->
    <div id="new-client-form" style="display: none; margin-top: 20px;">
        <label for="client_name">New Client Name:</label>
        <input type="text" id="client_name" name="client_name">
    </div>

    <br><br>
    <button type="submit">Proceed</button>
</form>

</body>
</html>
