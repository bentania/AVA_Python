<?php
// Specify the path to the input CSV file
$inputFile = 'path_to_your_file.csv'; // Change this to the actual path of your input CSV file
$outputFile = 'transformed_file.csv'; // Path for the output CSV file

// Open the input CSV file
if (($handle = fopen($inputFile, 'r')) === FALSE) {
    die('Error: Unable to open the input CSV file.');
}

// Prepare to write to the output CSV file
$outputHandle = fopen($outputFile, 'w');

// Write header to the output file
fputcsv($outputHandle, ['Language', 'File', 'X-translated', '101%', 'Repetitions', '100%', '95% - 99%', '85% - 94%', '75% - 84%', '50% - 74%', 'No match', 'Total']);

// Read and process the input CSV file
while (($data = fgetcsv($handle)) !== FALSE) {
    // Skip the header row
    if (strpos($data[0], 'File') !== FALSE) {
        continue;
    }

    // Extract relevant data from the row
    $file = $data[0];
    $language = substr($file, 1, 3); // Extract language code from the file name
    
    $x_translated = $data[1];
    $repetitions = $data[2];
    $segments = $data[17]; // Assuming columns are zero-indexed
    $words = $data[18];
    $characters = $data[19];
    $asian_characters = $data[20];
    $tags = $data[21];

    // Compute total based on your original description
    $total = $data[35] + $data[36] + $data[37] + $data[38] + $data[39] + $data[40] + $data[41] + $data[42] + $data[43] + $data[44] + $data[45] + $data[46] + $data[47];

    // Write the transformed data to the output file
    fputcsv($outputHandle, [
        $language,
        $file,
        $x_translated,
        $repetitions,
        $segments,
        $words,
        $characters,
        $asian_characters,
        $tags,
        $total
    ]);
}

// Close file handles
fclose($handle);
fclose($outputHandle);

echo 'File has been successfully transformed.';
?>
