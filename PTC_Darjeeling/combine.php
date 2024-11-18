<?php

// Define the absolute path to the Outputs directory
$uploadDirectory = __DIR__ . '/Outputs';

// Ensure Outputs directory exists
if (!is_dir($uploadDirectory)) {
    if (!mkdir($uploadDirectory, 0777, true)) {
        die("Failed to create directory: $uploadDirectory");
    }
}

// Define the absolute path to the combined file
$combinedFilePath = $uploadDirectory . '/combined_languages.txt';

// Debugging output for path checking
echo "Attempting to open file at: $combinedFilePath\n";

// Check if the file exists and the directory is writable
if (!file_exists($combinedFilePath)) {
    echo "File does not exist: $combinedFilePath\n";
}

if (!is_writable(dirname($combinedFilePath))) {
    echo "Directory is not writable: " . dirname($combinedFilePath) . "\n";
}

// Attempt to create and write to the combined file
$file = fopen($combinedFilePath, 'w');
if ($file === false) {
    die("Failed to open file: $combinedFilePath\n");
}

// Example content to write to the file
$content = "Example content\n";
fwrite($file, $content);
fclose($file);

echo "Combined file created at $combinedFilePath\n";

// Additional logic for combining files and processing would go here

// Print completion message
echo "Process completed.\n";
