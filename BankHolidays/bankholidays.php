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

// Initialize variables
$selected_client = isset($_GET['client_id']) ? $_GET['client_id'] : null;
$pre_selected_languages = [];

// If a client is selected, get the pre-set languages for that client
if ($selected_client) {
    $language_query = $conn->prepare("SELECT language_id FROM client_languages WHERE client_id = ?");
    $language_query->bind_param("i", $selected_client);
    $language_query->execute();
    $result = $language_query->get_result();

    // Store pre-selected languages in an array
    while ($row = $result->fetch_assoc()) {
        $pre_selected_languages[] = $row['language_id'];
    }
}

// Get the list of all languages grouped by continent
$languages = $conn->query("SELECT * FROM languages ORDER BY continent, name");

$languages_by_continent = [];
$other_group = [];

// Define the "Other" group countries
$other_countries = [
    'Albania', 'Andorra', 'Austria', 'Belarus', 'Belgium', 'Bosnia and Herzegovina', 'Cyprus', 'Georgia', 'Gibraltar',
    'Greenland', 'Liechtenstein', 'Lithuania', 'Luxembourg', 'Malta', 'Moldova', 'Montenegro', 'Republic of North Macedonia',
    'San Marino', 'Afghanistan', 'Armenia', 'Azerbaijan', 'Bahrain', 'Bangladesh', 'Bhutan', 'Brunei Darussalam', 'Cambodia', 
    'Iran', 'Iraq', 'Jordan', 'Kazakhstan', 'Kuwait', 'Lao', 'Lebanon', 'Malaysia', 'Maldives', 'Mongolia', 'Nepal', 'Oman', 
    'Pakistan', 'Qatar', 'Singapore', 'Sri Lanka', 'Sudan', 'Syria', 'Tajikistan', 'Uzbekistan', 'Bolivia', 'Chile', 'Colombia', 
    'Costa Rica', 'Ecuador', 'El Salvador', 'Guatemala', 'Guyana', 'Paraguay', 'Peru', 'Suriname', 'Uruguay', 'Venezuela', 
    'Bahamas', 'Barbados', 'Belize', 'Bermuda', 'Cuba', 'Dominica', 'Dominican Republic', 'Grenada', 'Haiti', 'Honduras', 
    'Jamaica', 'Nicaragua', 'Panama', 'Puerto Rico', 'Trinidad and Tobago', 'Algeria', 'Angola', 'Benin', 'Botswana', 
    'Burkina Faso', 'Burundi', 'Cabo Verde', 'Cameroon', 'Central African Republic', 'Chad', 'Congo', 'Cote d\'Ivoire', 
    'Djibouti', 'Equatorial Guinea', 'Eritrea', 'Ethiopia', 'Gabon', 'Gambia', 'Ghana', 'Guinea', 'Guinea-Bissau', 'Kenya', 
    'Liberia', 'Libya', 'Madagascar', 'Malawi', 'Mali', 'Mauritania', 'Mauritius', 'Morocco', 'Mozambique', 'Namibia', 
    'Nigeria', 'Rwanda', 'Sao Tome and Principe', 'Senegal', 'Sierra Leone', 'Somalia', 'South Africa', 'Tanzania', 'Tunisia', 
    'Uganda', 'Zambia', 'Zimbabwe', 'Australia', 'Fiji', 'New Zealand', 'Timor-Leste', 'Ukraine'
];

// Sort function to order languages by name
function sort_by_name($a, $b) {
    return strcmp($a['name'], $b['name']);
}

// Group languages by continent or add to "Other" group
while ($row = $languages->fetch_assoc()) {
    if (in_array($row['name'], $other_countries)) {
        // Add to "Other" group
        $other_group[] = $row;
    } else {
        // Group by continent
        $continent = $row['continent'];
        $languages_by_continent[$continent][] = $row;
    }
}

// Sort the "Other" group alphabetically
usort($other_group, 'sort_by_name');

// Sort each continent group alphabetically
foreach ($languages_by_continent as &$continent_languages) {
    usort($continent_languages, 'sort_by_name');
}


// Define custom order for continents
$continent_order = ['Europe', 'Asia', 'South America', 'North America', 'Africa', 'Oceania'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Client and Year for Bank Holidays</title>
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        .continent-group {
            margin-bottom: 20px;
        }
        .continent-header {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .country-container {
            display: flex;
            flex-wrap: wrap;
        }
        .country-container div {
            margin-right: 20px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<!-- Home Button -->
<a href="/bankholidays" class="home-button">Home</a>

<h2>Select Client, Year, and Countries</h2>
<form action="bankholidays.php" method="GET">
    <label for="client">Choose a client:</label>
    <select name="client_id" id="client" onchange="this.form.submit()">
        <option value="">Select Client</option>
        <?php while ($row = $clients->fetch_assoc()): ?>
            <option value="<?php echo $row['id']; ?>" <?php echo ($row['id'] == $selected_client) ? 'selected' : ''; ?>>
                <?php echo $row['name']; ?>
            </option>
        <?php endwhile; ?>
    </select>
</form>

<?php if ($selected_client): ?>
<form action="get_holidays.php" method="POST">
    <input type="hidden" name="client_id" value="<?php echo $selected_client; ?>">

    <br>
    <label for="year">Choose a year:</label>
    <select name="year" id="year">
        <?php
        $current_year = date('Y');
        for ($i = $current_year; $i <= $current_year + 3; $i++) {
            echo "<option value=\"$i\">$i</option>";
        }
        ?>
    </select>
    
    <br><br>
    <button type="submit" style="padding: 40px 80px; font-size: 18px;">Generate Bank Holidays</button>
    
    <br><br>

    <h3>Select Countries</h3>

    <!-- Loop through the continents in custom order and display languages horizontally -->
    <?php foreach ($continent_order as $continent): ?>
        <?php if (isset($languages_by_continent[$continent])): ?>
            <div class="continent-group">
                <div class="continent-header"><?php echo $continent; ?></div>
                <div class="country-container">
                    <?php foreach ($languages_by_continent[$continent] as $language): ?>
                        <div>
                            <input type="checkbox" name="language_ids[]" value="<?php echo $language['id']; ?>"
                                <?php 
                                // Ensure "United Kingdom" is always checked
                                if ($language['name'] === 'United Kingdom' || in_array($language['id'], $pre_selected_languages)) {
                                    echo 'checked';
                                }
                                ?>>
                            <label><?php echo $language['name']; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Display the "Other" group -->
    <?php if (!empty($other_group)): ?>
        <div class="continent-group">
            <div class="continent-header">Other</div>
            <div class="country-container">
                <?php foreach ($other_group as $language): ?>
                    <div>
                        <input type="checkbox" name="language_ids[]" value="<?php echo $language['id']; ?>"
                            <?php 
                            // Ensure "United Kingdom" is always checked
                            if ($language['name'] === 'United Kingdom' || in_array($language['id'], $pre_selected_languages)) {
                                echo 'checked';
                            }
                            ?>>
                        <label><?php echo $language['name']; ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</form>
<?php endif; ?>

</body>
</html>
