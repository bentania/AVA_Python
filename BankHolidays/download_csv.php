<?php
if (isset($_POST['holidays'])) {
    // Decode the grouped holidays data from the POST request
    $grouped_holidays = json_decode($_POST['holidays'], true);

    // Sort the grouped holidays by date
    ksort($grouped_holidays);

    // Set CSV headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="grouped_holidays.csv"');

    // Open a file pointer to output the CSV
    $output = fopen('php://output', 'w');
    
    // Add the CSV header row
    fputcsv($output, ['Date', 'Holiday', 'Countries']);

    // Loop through grouped holidays and add rows to the CSV
    foreach ($grouped_holidays as $date => $holidays_on_date) {
        // Convert date from YYYY-MM-DD to DD/MM/YYYY
        $formatted_date = DateTime::createFromFormat('Y-m-d', $date)->format('d/m/Y');

        foreach ($holidays_on_date as $holiday_name => $countries) {
            // Join the countries into a single string
            $countries_list = implode(', ', $countries);
            fputcsv($output, [$formatted_date, $holiday_name, $countries_list]);
        }
    }

    // Close the file pointer
    fclose($output);
    exit();
}
?>
