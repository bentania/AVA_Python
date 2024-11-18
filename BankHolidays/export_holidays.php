<?php
// Set headers to download the CSV file in UTF-8 with BOM
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $_POST['csv_filename'] . '"');

// Output the BOM for UTF-8
echo "\xEF\xBB\xBF";

// Check if translated content is available
if (!isset($_POST['translated_content']) || empty($_POST['translated_content'])) {
    echo "No translated content available.";
    exit;
}

// Get the translated holidays data
$translated_holidays = json_decode($_POST['translated_content'], true);

// Output the tab-separated headers
echo "Date\tHoliday\tCountry\n";

// Output each translated row of CSV data as tab-separated values
foreach ($translated_holidays as $holiday) {
    $date = $holiday['date'];
    $holiday_name = $holiday['holiday'];
    $country_name = $holiday['country'];

    // Use "\t" to separate columns instead of commas
    echo "$date\t$holiday_name\t$country_name\n";
}
?>
