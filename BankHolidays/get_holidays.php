<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holiday Calendar</title>
    <link rel="stylesheet" href="/css/styles.css">
    <!-- Link to FullCalendar styles -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet" />
    <style>
        #holiday-calendar {
            display: grid;
            grid-template-columns: repeat(1, 1fr); /* Default: 1 month per row */
            gap: 10px; /* Space between months */
        }

        .calendar-month {
            border: 1px solid #ccc;
            padding: 10px;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>

    <!-- Home Button -->
    <a href="/bankholidays" class="home-button">Home</a>

    <!-- Dropdown to select months per row -->
    <label for="months-per-row">Months per row: </label>
    <select id="months-per-row" onchange="updateCalendarGrid()">
        <option value="1" selected>1</option>
        <option value="2">2</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="6">6</option>
    </select>

    <!-- Buttons to toggle between views -->
    <button id="calendarViewButton" onclick="showCalendar()">Calendar View</button>
    <button id="tableViewButton" style="display:none;" onclick="showTable()">Table View</button>
    <button id="exportPDFButton" style="display:none;" onclick="exportCalendarToPDF()">Export Calendar to PDF</button>

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

// Get the selected client ID, year, and languages from the form
$client_id = $_POST['client_id'];
$year = $_POST['year'];
$language_ids = isset($_POST['language_ids']) ? $_POST['language_ids'] : [];

// Ensure year and language_ids are set
if (!$year || empty($language_ids)) {
    die("Year or languages were not selected.");
}

// Convert language IDs to a comma-separated string
$language_ids_string = implode(',', $language_ids);

// Run the Python script to fetch holidays for the selected year and languages
$output = shell_exec("python fetch_holidays.py $language_ids_string $year");

// Remove any surrounding whitespace (optional)
$output = trim($output);

// Decode the JSON response
$holidays = json_decode($output, true);

// Check for JSON decoding errors
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error decoding JSON: " . json_last_error_msg() . "<br>";
    exit();
}

// Initialize arrays to store holidays by national and regional
$national_holidays = [];
$regional_holidays = [];

// Separate national and regional holidays
foreach ($holidays as $holiday) {
    if ($holiday['subdivision'] == 'National') {
        $national_holidays[$holiday['date']][] = $holiday;
    } else {
        $regional_holidays[$holiday['date']][] = $holiday;
    }
}

// Final array to store holidays for display (national + non-overlapping regional)
$display_holidays = [];

// First, add all national holidays to the display list
foreach ($national_holidays as $date => $national_holiday_list) {
    foreach ($national_holiday_list as $national_holiday) {
        $display_holidays[] = $national_holiday;
    }
}

// Now, add regional holidays that do NOT overlap with national holidays
foreach ($regional_holidays as $date => $regional_holiday_list) {
    if (!isset($national_holidays[$date])) {
        foreach ($regional_holiday_list as $regional_holiday) {
            $display_holidays[] = $regional_holiday;
        }
    }
}

// Sort the holidays by date
usort($display_holidays, function($a, $b) {
    return strtotime($a['date']) - strtotime($b['date']);
});

// Fetch the client name for CSV file naming
$client_query = $conn->prepare("SELECT name FROM clients WHERE id = ?");
$client_query->bind_param("i", $client_id);
$client_query->execute();
$client_result = $client_query->get_result();
$client_name = $client_result->fetch_assoc()['name'];

// Prepare holidays data in the format required for FullCalendar.js
$calendar_events = [];
foreach ($display_holidays as $holiday) {
    $calendar_events[] = [
        'title' => $holiday['country'], // Only country name is shown
        'start' => $holiday['date'],
        'description' => $holiday['holiday'] // Keep holiday name in description for internal use if needed
    ];
}

// Convert the calendar events to JSON
$calendar_events_json = json_encode($calendar_events);

// Display the translation and holidays
if (!empty($display_holidays)) {
    // Create the filename for the CSV
    $csv_filename = $year . '_' . $client_name . '_BankHolidays_' . date('dmY_Hi') . '.csv';

    echo "<h2>Holidays for $client_name for $year</h2>";
    echo "<form id='csvForm' method='POST' action='export_holidays.php'>
            <input type='hidden' id='holidays_data' name='holidays_data' value='" . htmlentities(json_encode($display_holidays)) . "'>
            <input type='hidden' name='csv_filename' value='$csv_filename'>
            <input type='hidden' id='translated_content' name='translated_content' value=''>
            <button type='submit'>Export to CSV</button>
          </form>";

    // Display the holidays in a table
    echo "<div id='holiday-table'>
            <table border='1'>
                <tr>
                    <th>Date</th>
                    <th>Holiday</th>
                    <th>Country</th>
                </tr>";
    
    foreach ($display_holidays as $holiday) {
        // Convert the date format to DD/MM/YYYY
        $date = date('d/m/Y', strtotime($holiday['date']));
        
        echo "<tr class='holiday-row'>
                <td>{$date}</td>
                <td>{$holiday['holiday']}</td>
                <td>{$holiday['country']}</td>
              </tr>";
    }
    echo "</table></div>";

    // Div to display the calendar
    echo "<div id='holiday-calendar' style='display:none;'></div>";
} else {
    echo "No holidays found for the selected year and languages.";
}
?>

<!-- Add FullCalendar.js and its dependencies -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>

<script>
    // Function to update the grid layout based on months per row selection
    function updateCalendarGrid() {
        const monthsPerRow = document.getElementById('months-per-row').value;
        document.getElementById('holiday-calendar').style.gridTemplateColumns = `repeat(${monthsPerRow}, 1fr)`;
    }

    // Function to show the calendar and hide the table
    function showCalendar() {
        document.getElementById('holiday-table').style.display = 'none'; // Hide the table
        document.getElementById('holiday-calendar').style.display = 'grid'; // Show the calendar
        document.getElementById('tableViewButton').style.display = 'inline'; // Show the "Table View" button
        document.getElementById('calendarViewButton').style.display = 'none'; // Hide the "Calendar View" button
        document.getElementById('exportPDFButton').style.display = 'inline'; // Show the "Export PDF" button

        // Clear existing calendar content
        document.getElementById('holiday-calendar').innerHTML = '';

        // Render all 12 months manually
        for (let month = 0; month < 12; month++) {
            let calendarEl = document.createElement('div');
            calendarEl.classList.add('calendar-month');
            document.getElementById('holiday-calendar').appendChild(calendarEl);

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth', // Display monthly grid
                initialDate: new Date(<?php echo $year; ?>, month, 1), // Display each month
                events: <?php echo $calendar_events_json; ?>, // Use the event data
                dayMaxEvents: false, // Do not limit the number of events shown in a day
                height: 'auto', // Adjust height dynamically
                width: 'auto',  // Adjust width dynamically
                eventClick: function(info) {
                    // Display only the country when clicked
                    alert('Country: ' + info.event.title); // Only show the country
                }
            });
            calendar.render();
        }
    }

    // Function to show the table and hide the calendar
    function showTable() {
        document.getElementById('holiday-table').style.display = 'block'; // Show the table
        document.getElementById('holiday-calendar').style.display = 'none'; // Hide the calendar
        document.getElementById('tableViewButton').style.display = 'none'; // Hide the "Table View" button
        document.getElementById('calendarViewButton').style.display = 'inline'; // Show the "Calendar View" button
        document.getElementById('exportPDFButton').style.display = 'none'; // Hide the "Export PDF" button
    }

// Function to export the calendar to PDF
function exportCalendarToPDF() {
    const calendarEl = document.getElementById('holiday-calendar');

    const opt = {
        margin: [10, 10, 10, 10], // Margins for the PDF (in mm)
        filename: 'calendar_view.pdf',
        image: { type: 'jpeg', quality: 1.0 },
        html2canvas: {
            scale: 4,  // Increase the scale for better resolution
            useCORS: true,  // Allow cross-origin images
            scrollX: 0,
            scrollY: 0,
            width: calendarEl.scrollWidth, // Ensure full width is captured
            height: calendarEl.scrollHeight, // Ensure full height is captured
            windowWidth: calendarEl.scrollWidth,
            windowHeight: calendarEl.scrollHeight
        },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
    };

    html2pdf()
        .from(calendarEl)
        .set(opt)
        .toPdf()
        .get('pdf')
        .then(function (pdf) {
            pdf.save('calendar_view.pdf'); // Save the PDF after rendering
        });
}

</script>

</body>
</html>
