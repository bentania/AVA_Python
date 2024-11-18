<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Client or Add New</title>
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
            }
        }
    </script>
</head>
<body>

<h2>Select Client or Add New Client</h2>

<!-- Form for selecting or adding a client -->
<form id="clientForm" method="POST">
    <label for="client">Choose a client:</label>
    <select name="client_id" id="client" onchange="toggleNewClientForm()">
        <?php
        // Connect to database
        $conn = new mysqli('10.0.0.223', 'pbento', 'bento2024$%', 'ava');
        $clients = $conn->query("SELECT * FROM clients");

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

<!-- Button to go to the home page -->
<a href="/bankholidays" class="home-button">Home</a>

</body>
</html>
