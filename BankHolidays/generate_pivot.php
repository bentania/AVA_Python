<?php
$year = $_POST['year'];
$language_ids = $_POST['language_ids'];

// Run the Python script to fetch holidays and generate the pivot table
$command = escapeshellcmd("python3 fetch_holidays.py $language_ids $year");
$output = shell_exec($command);

// Decode the JSON response
$pivot_table = json_decode($output, true);

// Display the pivot table (assuming you want to render it as an HTML table)
if (!empty($pivot_table)) {
    echo "<table border='1'>";
    echo "<tr><th>Date</th><th>Country</th><th>Holidays</th></tr>";
    foreach ($pivot_table as $row) {
        foreach ($row as $date => $countries) {
            foreach ($countries as $country => $holidays) {
                echo "<tr><td>$date</td><td>$country</td><td>$holidays</td></tr>";
            }
        }
    }
    echo "</table>";
} else {
    echo "No holiday data available for the selected year and languages.";
}
?>
