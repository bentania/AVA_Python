<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capture the content_type
    $content_type = $_POST['content_type'];
    
    if (isset($_FILES['input_file_memoq']) && $_FILES['input_file_memoq']['error'] == UPLOAD_ERR_OK) {
        $input_file = $_FILES['input_file_memoq']['tmp_name'];
        $original_file_name = $_FILES['input_file_memoq']['name'];
        
        // Append suffix with current date
        $date_suffix = date('dmy');
        $output_file = preg_replace('/\.csv$/i', "_processed_$date_suffix.csv", $original_file_name);
        
        // Validate output file name
        $output_file = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $output_file);
        
        // Define the command to execute the Python script for memoQ
        $command = "python process_csv.py \"$input_file\" \"$output_file\" \"$content_type\" 2>&1";
        
        // Print command for debugging
        #echo "<p>Command: $command</p>";

        // Execute the command
        $output = shell_exec($command);
        
        if ($output) {
            // Check if the output file was created
            $file_exists = file_exists($output_file);
            echo "<h2>Processing Result:</h2>";
            echo "<pre>$output</pre>";

            // Move the download link above the pivot table
            if ($file_exists) {
                echo "<a href=\"$output_file\" download>Download Processed File</a><br><br>";
            } else {
                echo "<p>The output file was not created.</p>";
            }

            // Here, you would include your code to display the pivot table
            // echo "<h2>Pivot Table:</h2>";
            // Your pivot table display logic goes here
        } else {
            echo "An error occurred during processing.";
        }
    } elseif (isset($_FILES['input_file_xtm']) && $_FILES['input_file_xtm']['error'] == UPLOAD_ERR_OK) {
        $input_file = $_FILES['input_file_xtm']['tmp_name'];
        $original_file_name = $_FILES['input_file_xtm']['name'];

        // Append suffix with current date
        $date_suffix = date('dmy');
        $output_file = preg_replace('/\.csv$/i', "_processed_$date_suffix.csv", $original_file_name);
        
        // Validate output file name
        $output_file = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $output_file);
        
        // Define the command to execute the Python script for XTM
        $command = "python process_xtm.py \"$input_file\" \"$output_file\" \"$content_type\" 2>&1";

        // Print command for debugging
        #echo "<p>Command: $command</p>";

        // Execute the command
        $output = shell_exec($command);
        
        if ($output) {
            // Check if the output file was created
            $file_exists = file_exists($output_file);
            echo "<h2>Processing Result:</h2>";
            echo "<pre>$output</pre>";

            // Move the download link above the pivot table
            if ($file_exists) {
                echo "<a href=\"$output_file\" download>Download Processed File</a><br><br>";
            } else {
                echo "<p>The output file was not created.</p>";
            }

            // Here, you would include your code to display the pivot table
            // echo "<h2>Pivot Table:</h2>";
            // Your pivot table display logic goes here
        } else {
            echo "An error occurred during processing.";
        }
    } else {
        echo "Error uploading file.";
    }
}
?>
