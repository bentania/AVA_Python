<?php
// Path to the Outputs directory
$directory = 'Outputs/';

// Function to clear the directory
function clearDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    // Get all files and directories within the specified directory
    $files = glob(rtrim($dir, '/') . '/*');

    foreach ($files as $file) {
        if (is_dir($file)) {
            // Recursively delete directories
            clearDirectory($file);
            rmdir($file);
        } else {
            // Delete files
            unlink($file);
        }
    }

    return true;
}

// Clear the Outputs directory
if (clearDirectory($directory)) {
    echo "Directory cleared successfully.";
} else {
    echo "Failed to clear directory.";
}
?>
