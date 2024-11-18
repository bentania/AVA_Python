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

// Set the charset to UTF-8
$conn->set_charset("utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $language = $conn->real_escape_string($_POST['language']);
    $searchString = $conn->real_escape_string($_POST['searchString']);
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $limit = 50; // Limit the number of records fetched in one request

    // SQL query to count the total number of records for the selected language
    $totalQuery = "SELECT COUNT(*) as total FROM `siemens_tm` WHERE `TARGET` = '$language'";
    $totalResult = $conn->query($totalQuery);
    $totalRow = $totalResult->fetch_assoc();
    $totalRecords = $totalRow['total'];

    // SQL query to search for the SOURCE term and match the selected TARGET language
    $query = "SELECT `DATE`, `SOURCE`, `TRANSLATION`, `FILENAME`, `TARGET` 
              FROM `siemens_tm` 
              WHERE LOWER(CONVERT(`SOURCE` USING utf8mb4)) LIKE LOWER(CONVERT('%$searchString%' USING utf8mb4)) 
              AND `TARGET` = '$language'
              LIMIT $limit OFFSET $offset";

    $result = $conn->query($query);

    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }

    // Return the results as a JSON object
    echo json_encode([
        'totalRecords' => $totalRecords,
        'results' => $results,
        'offset' => $offset + $limit
    ]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AVA Search Tool for Siemens Translation Memories</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
            word-wrap: break-word;
            width: 200px; /* Fixed width for columns */
        }

        #loader {
            display: none;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<h2>AVA Search Tool for Siemens Translation Memories</h2>

<form id="searchForm">
    <label for="language">Select Language:</label>
    <select name="language" id="language" required>
        <option value="cs_CZ">Czech (cs_CZ)</option>
        <option value="de_DE">German (de_DE)</option>
        <option value="es_ES">Spanish (es_ES)</option>
        <option value="nl_NL">Dutch (nl_NL)</option>
        <option value="fr_FR">French (fr_FR)</option>
        <option value="hu_HU">Hungarian (hu_HU)</option>
        <option value="it_IT">Italian (it_IT)</option>
        <option value="ja_JP">Japanese (ja_JP)</option>
        <option value="ko_KR">Korean  (ko_KR)</option>
        <option value="pl_PL">Polish (pl_PL)</option>
        <option value="pt_BR">Brazilian Portuguese (pt_BR)</option>
        <option value="ru_RU">Russian (ru_RU)</option>
        <option value="tr_TR">Turkish (tr_TR)</option>
        <option value="zh_CN">Chinese Simplified (zh_CN)</option>
        <option value="zh_TW">Chinese Traditional (zh_TW)</option>        
    </select>
    <br><br>

    <label for="searchString">Search in SOURCE:</label>
    <input type="text" name="searchString" id="searchString" required>
    <br><br>

    <input type="submit" value="Search">
</form>

<h3 id="resultCount" style="display: none;">Found 0 records out of 0 total records</h3>
<h4 id="elapsedTime">Elapsed Time: 00:00</h4>
<h1 id="completionMessage" style="display: none;"><i>Tempus Fugit</i>: Processing Complete!</h1> <!-- Completion Message -->

<div id="results">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Language</th>
                <th>Source</th>
                <th>Target</th>
                <th>Filename</th>
            </tr>
        </thead>
        <tbody id="resultsBody">
            <!-- Results will be appended here dynamically -->
        </tbody>
    </table>
</div>

<div id="loader">Loading...</div>

<script>
$(document).ready(function() {
    $('#searchForm').submit(function(event) {
        event.preventDefault(); // Prevent the default form submission
        $('#resultsBody').empty();
        $('#resultCount').hide(); // Hide the record count initially
        $('#elapsedTime').text('Elapsed Time: 00:00'); // Reset timer display to MM:SS format
        $('#loader').show();
        $('#completionMessage').hide(); // Hide completion message initially

        var searchString = $('#searchString').val();
        var language = $('#language').val();
        var offset = 0;
        var totalRecords = 0;
        var fetchedRecords = 0; // Initialize a counter to track fetched records
        var startTime = Date.now(); // Start the timer
        var timer; // Timer variable

        // Function to update the timer display
        function updateTimer() {
            var elapsedTime = Math.floor((Date.now() - startTime) / 1000); // Calculate elapsed time in seconds
            $('#elapsedTime').text('Elapsed Time: ' + formatTime(elapsedTime)); // Update timer display in MM:SS
        }

        // Function to format time as MM:SS
        function formatTime(seconds) {
            var minutes = Math.floor(seconds / 60);
            var seconds = seconds % 60;
            return (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
        }

        // Function to highlight the search term
        function highlightSearchTerm(text, searchString) {
            var regex = new RegExp('(' + searchString + ')', 'gi');
            return text.replace(regex, '<span style="background-color: yellow;">$1</span>');
        }

        function fetchRecords() {
            $.ajax({
                url: '', // This PHP file itself
                type: 'POST',
                data: { 
                    searchString: searchString, 
                    language: language, 
                    offset: offset, 
                    ajax: true 
                },
                dataType: 'json',
                success: function(data) {
                    // Set total records only once
                    if (totalRecords === 0) {
                        totalRecords = data.totalRecords;
                    }

                    // Append records to the table
                    $.each(data.results, function(index, record) {
                        $('#resultsBody').append(
                            '<tr>' +
                            '<td>' + record.DATE + '</td>' +
                            '<td>' + record.TARGET + '</td>' +
                            '<td>' + highlightSearchTerm(record.SOURCE, searchString) + '</td>' +
                            '<td>' + highlightSearchTerm(record.TRANSLATION, searchString) + '</td>' +
                            '<td>' + record.FILENAME + '</td>' +
                            '</tr>'
                        );

                        fetchedRecords++; // Increment the counter for each fetched record
                    });

                    // If more results exist, fetch the next chunk
                    if (data.results.length > 0) {
                        offset += data.results.length; // Increment offset
                        fetchRecords(); // Fetch the next chunk
                    } else {
                        // Stop the timer and hide loader
                        clearInterval(timer); 
                        $('#loader').hide(); // Hide loader when done
                        $('#resultCount').text('Found ' + fetchedRecords.toLocaleString() + ' records out of ' + totalRecords.toLocaleString() + ' total records').show(); // Show count with thousands separator
                        $('#elapsedTime').text('Elapsed Time: ' + formatTime(Math.floor((Date.now() - startTime) / 1000))); // Show final elapsed time in MM:SS
                        $('#completionMessage').show(); // Show completion message
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error: " + error);
                    $('#loader').hide();
                    clearInterval(timer); // Stop the timer on error
                }
            });
        }

        // Start the timer and update it every second
        timer = setInterval(updateTimer, 1000); // Update timer every second
        fetchRecords(); // Initial fetch
    });
});


</script>

</body>
</html>
