<?php
session_start();

function fetch_records($connection) {
    $query = "SELECT * FROM ava.`merged_view` WHERE 1=1";

    if (isset($_SESSION['filter_type'])) {
        $filterType = $_SESSION['filter_type'];

        switch ($filterType) {
            case 'year':
                if (isset($_SESSION['year'])) {
                    $year = mysqli_real_escape_string($connection, $_SESSION['year']);
                    $query .= " AND `Invoice Month` BETWEEN '01/$year' AND '12/$year'";
                }
                break;

            case 'quarter':
                if (isset($_SESSION['quarter']) && isset($_SESSION['year_quarter'])) {
                    $quarter = mysqli_real_escape_string($connection, $_SESSION['quarter']);
                    $yearQuarter = mysqli_real_escape_string($connection, $_SESSION['year_quarter']);
                    $startMonth = ($quarter - 1) * 3 + 1;
                    $endMonth = $startMonth + 2;
                    $startMonthValue = str_pad($startMonth, 2, '0', STR_PAD_LEFT);
                    $endMonthValue = str_pad($endMonth, 2, '0', STR_PAD_LEFT);
                    $startInvoiceMonth = $startMonthValue . '/' . $yearQuarter;
                    $endInvoiceMonth = $endMonthValue . '/' . $yearQuarter;
                    $query .= " AND `Invoice Month` BETWEEN '$startInvoiceMonth' AND '$endInvoiceMonth'";
                }
                break;

            case 'month':
                if (isset($_SESSION['month']) && isset($_SESSION['year_month'])) {
                    $monthValue = mysqli_real_escape_string($connection, $_SESSION['month']);
                    $yearMonth = mysqli_real_escape_string($connection, $_SESSION['year_month']);
                    $invoiceMonth = $monthValue . '/' . $yearMonth;
                    $query .= " AND `Invoice Month` = '$invoiceMonth'";
                }
                break;

            case 'interval':
                if (isset($_SESSION['interval_start']) && isset($_SESSION['interval_end']) && isset($_SESSION['year_interval'])) {
                    $intervalStart = mysqli_real_escape_string($connection, $_SESSION['interval_start']);
                    $intervalEnd = mysqli_real_escape_string($connection, $_SESSION['interval_end']);
                    $yearInterval = mysqli_real_escape_string($connection, $_SESSION['year_interval']);
                    $startInvoiceMonth = $intervalStart . '/' . $yearInterval;
                    $endInvoiceMonth = $intervalEnd . '/' . $yearInterval;
                    $query .= " AND `Invoice Month` BETWEEN '$startInvoiceMonth' AND '$endInvoiceMonth'";
                }
                break;
        }
    }

    echo "<p>Constructed Query: $query</p>";
    echo "<p>Session Variables:</p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";

    $result = mysqli_query($connection, $query);
    $rowCount = mysqli_num_rows($result);
    echo "<p>Query executed successfully. Number of rows: $rowCount</p>";

    return array($result, $rowCount);
}

$filterApplied = false;
$resultHtml = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['filter']) || isset($_POST['export_csv'])) {
        $_SESSION['filter_type'] = $_POST['filter_type'];
        $_SESSION['year'] = $_POST['year'] ?? null;
        $_SESSION['quarter'] = $_POST['quarter'] ?? null;
        $_SESSION['year_quarter'] = $_POST['year_quarter'] ?? null;
        $_SESSION['month'] = $_POST['month'] ?? null;
        $_SESSION['year_month'] = $_POST['year_month'] ?? null;
        $_SESSION['interval_start'] = $_POST['interval_start'] ?? null;
        $_SESSION['interval_end'] = $_POST['interval_end'] ?? null;
        $_SESSION['year_interval'] = $_POST['year_interval'] ?? null;

        $filterApplied = true;
    } else {
        $_SESSION['filter_type'] = 'month';
        $_SESSION['month'] = '06';
        $_SESSION['year_month'] = '2024';
    }

    $connection = mysqli_connect("10.0.0.223", "pbento", "bento2024$%", "ava");
    if (!$connection) {
        die("Connection failed: " . mysqli_connect_error());
    }

    list($result, $rowCount) = fetch_records($connection);

    if ($result) {
        if (isset($_POST['export_csv'])) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename=export.csv');
            $output = fopen('php://output', 'w');

            $fieldInfo = mysqli_fetch_fields($result);
            $headers = array();
            foreach ($fieldInfo as $field) {
                $headers[] = $field->name;
            }
            fputcsv($output, $headers);

            while ($row = mysqli_fetch_assoc($result)) {
                fputcsv($output, $row);
            }

            fclose($output);
            mysqli_close($connection);
            exit();
        }

        echo "<h2>Preview of Results:</h2>";
        echo "<table border='1'>";
        $fields = mysqli_fetch_fields($result);
        echo "<tr>";
        foreach ($fields as $field) {
            echo "<th>{$field->name}</th>";
        }
        echo "</tr>";
        for ($i = 0; $i < min(5, $rowCount); $i++) {
            $row = mysqli_fetch_assoc($result);
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";

        mysqli_data_seek($result, 0);

        $resultHtml .= "<p>Total records found: $rowCount</p>";
        $resultHtml .= "<table border='1'>";
        $resultHtml .= "<tr>";
        foreach ($fields as $field) {
            $resultHtml .= "<th>{$field->name}</th>";
        }
        $resultHtml .= "</tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            $resultHtml .= "<tr>";
            foreach ($row as $cell) {
                $resultHtml .= "<td>" . htmlspecialchars($cell) . "</td>";
            }
            $resultHtml .= "</tr>";
        }
        $resultHtml .= "</table>";
    } else {
        $resultHtml .= "<p>No records found for the selected criteria.</p>";
    }

    mysqli_close($connection);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Alpha - Amazon Reports</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid black;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }
    </style>
</head>
<body>

<h1>Amazon Reports</h1>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <label>
        <input type="radio" name="filter_type" value="year" <?php echo (isset($_SESSION['filter_type']) && $_SESSION['filter_type'] == 'year') ? 'checked' : ''; ?>> Year
    </label>
    <label>
        <input type="radio" name="filter_type" value="quarter" <?php echo (isset($_SESSION['filter_type']) && $_SESSION['filter_type'] == 'quarter') ? 'checked' : ''; ?>> Quarter
    </label>
    <label>
        <input type="radio" name="filter_type" value="month" <?php echo (isset($_SESSION['filter_type']) && $_SESSION['filter_type'] == 'month') ? 'checked' : ''; ?>> Month
    </label>
    <label>
        <input type="radio" name="filter_type" value="interval" <?php echo (isset($_SESSION['filter_type']) && $_SESSION['filter_type'] == 'interval') ? 'checked' : ''; ?>> Month Interval
    </label>

    <div id="year-filter" class="filter-option">
        <label for="year">Select Year:</label>
        <select name="year">
            <?php for ($i = date("Y"); $i <= date("Y") + 4; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo (isset($_SESSION['year']) && $_SESSION['year'] == $i) ? 'selected' : ''; ?>>
                    <?php echo $i; ?>
                </option>
            <?php endfor; ?>
        </select>
    </div>

    <div id="quarter-filter" class="filter-option">
        <label for="quarter">Select Quarter:</label>
        <select name="quarter">
            <option value="1" <?php echo (isset($_SESSION['quarter']) && $_SESSION['quarter'] == '1') ? 'selected' : ''; ?>>Q1 (Jan - Mar)</option>
            <option value="2" <?php echo (isset($_SESSION['quarter']) && $_SESSION['quarter'] == '2') ? 'selected' : ''; ?>>Q2 (Apr - Jun)</option>
            <option value="3" <?php echo (isset($_SESSION['quarter']) && $_SESSION['quarter'] == '3') ? 'selected' : ''; ?>>Q3 (Jul - Sep)</option>
            <option value="4" <?php echo (isset($_SESSION['quarter']) && $_SESSION['quarter'] == '4') ? 'selected' : ''; ?>>Q4 (Oct - Dec)</option>
        </select>
        <label for="year_quarter">Select Year:</label>
        <select name="year_quarter">
            <?php for ($i = date("Y"); $i <= date("Y") + 4; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo (isset($_SESSION['year_quarter']) && $_SESSION['year_quarter'] == $i) ? 'selected' : ''; ?>>
                    <?php echo $i; ?>
                </option>
            <?php endfor; ?>
        </select>
    </div>

    <div id="month-filter" class="filter-option">
        <label for="month">Select Month:</label>
        <select name="month">
            <?php for ($i = 1; $i <= 12; $i++) {
                $monthValue = str_pad($i, 2, '0', STR_PAD_LEFT);
                ?>
                <option value="<?php echo $monthValue; ?>" <?php echo (isset($_SESSION['month']) && $_SESSION['month'] == $monthValue) ? 'selected' : ''; ?>>
                    <?php echo $monthValue; ?>
                </option>
            <?php } ?>
        </select>
        <label for="year_month">Select Year:</label>
        <select name="year_month">
            <?php for ($i = date("Y"); $i <= date("Y") + 4; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo (isset($_SESSION['year_month']) && $_SESSION['year_month'] == $i) ? 'selected' : ''; ?>>
                    <?php echo $i; ?>
                </option>
            <?php endfor; ?>
        </select>
    </div>

    <div id="interval-filter" class="filter-option">
        <label for="interval_start">Select Start Month:</label>
        <select name="interval_start">
            <?php
            $currentMonth = date('m') - 1;
            $nextMonth = date('m', strtotime('+1 month')) - 1;

            for ($i = 1; $i <= 12; $i++):
                $monthValue = str_pad($i, 2, '0', STR_PAD_LEFT);
                ?>
                <option value="<?php echo $monthValue; ?>" <?php echo ($currentMonth == $monthValue) ? 'selected' : ''; ?>>
                    <?php echo $monthValue; ?>
                </option>
            <?php endfor; ?>
        </select>
        <label for="interval_end">Select End Month:</label>
        <select name="interval_end">
            <?php for ($i = 1; $i <= 12; $i++):
                $monthValue = str_pad($i, 2, '0', STR_PAD_LEFT);
                ?>
                <option value="<?php echo $monthValue; ?>" <?php echo ($nextMonth == $monthValue) ? 'selected' : ''; ?>>
                    <?php echo $monthValue; ?>
                </option>
            <?php endfor; ?>
        </select>
        <label for="year_interval">Select Year:</label>
        <select name="year_interval">
            <?php for ($i = date("Y"); $i <= date("Y") + 4; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo (isset($_SESSION['year_interval']) && $_SESSION['year_interval'] == $i) ? 'selected' : ''; ?>>
                    <?php echo $i; ?>
                </option>
            <?php endfor; ?>
        </select>
    </div>

    <br>
    <button type="submit" name="filter">Filter</button>
    <button type="submit" name="export_csv">Export as CSV</button>
</form>

<div id="results">
    <?php echo $resultHtml; ?>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const filterTypeRadios = document.querySelectorAll('input[name="filter_type"]');
        const filterOptions = document.querySelectorAll('.filter-option');

        function updateFilterOptions() {
            filterOptions.forEach(option => option.style.display = 'none');
            const selectedFilter = document.querySelector('input[name="filter_type"]:checked').value;
            document.getElementById(`${selectedFilter}-filter`).style.display = 'block';
        }

        filterTypeRadios.forEach(radio => {
            radio.addEventListener('change', updateFilterOptions);
        });

        updateFilterOptions();
    });
</script>

</body>
</html>
