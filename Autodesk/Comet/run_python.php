<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comparison = $_POST['comparison'];
    
    // Get the uploaded folder path
    $folder = $_FILES['folder'];
    
    // Handle file uploads here as needed
    $upload_directory = "uploads/";
    foreach ($folder['name'] as $key => $filename) {
        $temp_file = $folder['tmp_name'][$key];
        $target_file = $upload_directory . basename($filename);
        move_uploaded_file($temp_file, $target_file);
    }

    // Create a string with the selected comparison options
    $comparison_options = implode(",", $comparison);

    // Call Python script, passing folder path and comparison options
    $command = escapeshellcmd("python3 Load_JSON.py '$upload_directory' '$comparison_options'");

    // Execute the Python script
    $output = shell_exec($command);

    // Display the output
    echo "<pre>$output</pre>";
}
?>
