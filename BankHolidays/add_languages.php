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

// Get the client ID from the query string
$client_id = isset($_GET['client_id']) ? $_GET['client_id'] : null;

if (!$client_id) {
    die("Client ID is missing.");
}

// Fetch client information
$client_query = "SELECT * FROM clients WHERE id = ?";
$client_stmt = $conn->prepare($client_query);
$client_stmt->bind_param("i", $client_id);
$client_stmt->execute();
$client_result = $client_stmt->get_result();

if ($client_result->num_rows === 0) {
    die("Client not found.");
}

$client = $client_result->fetch_assoc();

// Fetch all languages grouped by continent
$languages_query = "SELECT * FROM languages ORDER BY continent, name";
$languages_result = $conn->query($languages_query);

if (!$languages_result || $languages_result->num_rows == 0) {
    die("No languages found.");
}

// Fetch the languages already assigned to the client
$client_languages_query = "SELECT language_id FROM client_languages WHERE client_id = ?";
$client_languages_stmt = $conn->prepare($client_languages_query);
$client_languages_stmt->bind_param("i", $client_id);
$client_languages_stmt->execute();
$client_languages_result = $client_languages_stmt->get_result();

$client_languages = [];
while ($row = $client_languages_result->fetch_assoc()) {
    $client_languages[] = $row['language_id'];
}

// Create an array to store languages by continent
$languages_by_continent = [];

// Group languages by continent
while ($row = $languages_result->fetch_assoc()) {
    $continent = $row['continent'];
    $languages_by_continent[$continent][] = $row; // Group by continent
}

// Define custom order for the continents
$continent_order = ['Europe', 'Asia', 'South America', 'North America', 'Africa', 'Oceania'];

// Define the 'Other' group countries
$other_group = [
    'Albania', 'Andorra', 'Austria', 'Belarus', 'Belgium', 'Bosnia and Herzegovina', 'Cyprus',
    'Georgia', 'Gibraltar', 'Greenland', 'Liechtenstein', 'Lithuania', 'Luxembourg', 'Malta',
    'Moldova', 'Montenegro', 'Republic of North Macedonia', 'San Marino', 'Afghanistan', 'Armenia',
    'Azerbaijan', 'Bahrain', 'Bangladesh', 'Bhutan', 'Brunei Darussalam', 'Cambodia', 'Iran',
    'Iraq', 'Jordan', 'Kazakhstan', 'Kuwait', 'Lao', 'Lebanon', 'Malaysia', 'Maldives', 'Mongolia',
    'Nepal', 'Oman', 'Pakistan', 'Qatar', 'Singapore', 'Sri Lanka', 'Sudan', 'Syria', 'Tajikistan',
    'Uzbekistan', 'Bolivia', 'Chile', 'Colombia', 'Costa Rica', 'Ecuador', 'El Salvador', 'Guatemala',
    'Guyana', 'Paraguay', 'Peru', 'Suriname', 'Uruguay', 'Venezuela', 'Bahamas', 'Barbados', 'Belize',
    'Bermuda', 'Cuba', 'Dominica', 'Dominican Republic', 'Grenada', 'Haiti', 'Honduras', 'Jamaica',
    'Nicaragua', 'Panama', 'Puerto Rico', 'Trinidad and Tobago', 'Algeria', 'Angola', 'Benin',
    'Botswana', 'Burkina Faso', 'Burundi', 'Cabo Verde', 'Cameroon', 'Central African Republic', 'Chad',
    'Congo', 'Cote d\'Ivoire', 'Djibouti', 'Equatorial Guinea', 'Eritrea', 'Ethiopia', 'Gabon', 'Gambia',
    'Ghana', 'Guinea', 'Guinea-Bissau', 'Kenya', 'Liberia', 'Libya', 'Madagascar', 'Malawi', 'Mali',
    'Mauritania', 'Mauritius', 'Morocco', 'Mozambique', 'Namibia', 'Nigeria', 'Rwanda', 'Sao Tome and Principe',
    'Senegal', 'Sierra Leone', 'Somalia', 'South Africa', 'Tanzania', 'Tunisia', 'Uganda', 'Zambia', 'Zimbabwe',
    'Australia', 'Fiji', 'New Zealand', 'Timor-Leste', 'Ukraine'
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Languages to Client</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h2 {
            color: #333;
        }
        .continent-group {
            margin-bottom: 30px;
        }
        .continent-header {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .language-container {
            display: flex;
            flex-wrap: wrap;
        }
        .language-container div {
            margin-right: 20px;
            margin-bottom: 10px;
        }
        .button-container {
            margin-top: 20px;
        }
    </style>
</head>

    <!-- Button to go to the home page -->
    <a href="/bankholidays" class="home-button">Home</a>

<body>

<h2>Select Languages for <?php echo $client['name']; ?></h2>

<form action="save_client_languages.php" method="POST">
    <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">

    <!-- Loop through the continents in the custom order and display the languages -->
    <?php foreach ($continent_order as $continent): ?>
        <?php if (isset($languages_by_continent[$continent])): ?>
            <div class="continent-group">
                <div class="continent-header"><?php echo $continent; ?></div>
                <div class="language-container">
                    <?php foreach ($languages_by_continent[$continent] as $language): ?>
                        <?php if (!in_array($language['name'], $other_group)): // Exclude countries in the 'Other' group ?>
                            <div>
                                <input type="checkbox" name="language_ids[]" value="<?php echo $language['id']; ?>" 
                                    <?php
                                    // Ensure "United Kingdom" is always checked
                                    if ($language['name'] === 'United Kingdom' || in_array($language['id'], $client_languages)) {
                                        echo 'checked';
                                    }
                                    ?>>
                                <label><?php echo $language['name']; ?></label>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Display the 'Other' group -->
    <div class="continent-group">
        <div class="continent-header">Other</div>
        <div class="language-container">
            <?php foreach ($languages_by_continent as $continent => $languages): ?>
                <?php foreach ($languages as $language): ?>
                    <?php if (in_array($language['name'], $other_group)): ?>
                        <div>
                            <input type="checkbox" name="language_ids[]" value="<?php echo $language['id']; ?>" 
                                <?php echo in_array($language['id'], $client_languages) ? 'checked' : ''; ?>>
                            <label><?php echo $language['name']; ?></label>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="button-container">
        <button type="submit">Save Languages</button>
    </div>
</form>


</body>
</html>
