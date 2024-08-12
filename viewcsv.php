<?php
session_start();
@include 'config.php';

$dop = $_SESSION['dop'] ?? 0;
$id = $_SESSION['id'] ?? 0;

// Function to read the data from a CSV file and return as an array
function readCSV($filename) {
    $data = [];
    if (($handle = fopen($filename, "r")) !== false) {
        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            $data[] = $row;
        }
        fclose($handle);
    }
    return $data;
}

// Function to merge the CSV files into one by columns with usernames on the top row
function mergeCSVFiles() {
    global $conn;
    $dop = $_SESSION['dop'] ?? 0;
    $id = $_SESSION['id'] ?? 0;

    $folderPath = "Annotefiles/" . $dop;
    $outputFilePath = "Annotefiles/" . $dop . "/Final.csv";
    $mergedData = [];

    // Get a list of CSV files in the folder
    $csvFiles = glob($folderPath . "/*.csv");

    // Read data from each CSV file and store it in the mergedData array
    foreach ($csvFiles as $csvFile) {
        $csvData = readCSV($csvFile);
        $fileName = pathinfo($csvFile, PATHINFO_FILENAME);
        $username = explode('_', $fileName)[0]; // Extract username from file name
        if (!isset($mergedData[$username])) {
            $mergedData[$username] = ['file_names' => [], 'options' => []];
        }
        foreach ($csvData as $row) {
            $mergedData[$username]['file_names'][] = $row[0];
            $mergedData[$username]['options'][] = $row[1];
        }
    }

    // Write the data to the new merged CSV file with column headings
    $fileHandle = fopen($outputFilePath, 'w');

    // Write the top row with usernames
    $header = [];
    foreach (array_keys($mergedData) as $username) {
        $header[] = $username . ' - File Name';
        $header[] = $username . ' - Option';
    }
    fputcsv($fileHandle, $header);

    // Determine the maximum number of rows in any column
    $maxRows = 0;
    foreach ($mergedData as $data) {
        $maxRows = max($maxRows, count($data['file_names']));
    }

    for ($i = 0; $i < $maxRows; $i++) {
        $rowData = [];
        foreach (array_keys($mergedData) as $username) {
            $rowData[] = $mergedData[$username]['file_names'][$i] ?? '';
            $rowData[] = $mergedData[$username]['options'][$i] ?? '-';
        }
        fputcsv($fileHandle, $rowData);
    }

    fclose($fileHandle);
}

$sql = "SELECT * FROM resource WHERE rid='$id'";
$res = mysqli_query($conn, $sql);
if ($res) {
    $row = mysqli_fetch_assoc($res);
    $fileprepared = $row['finalcsvprepared'];
    if ($fileprepared == 1) {
        $showmcsv = 1;
    }
}

if (isset($_POST['merge'])) {
    mergeCSVFiles();
    $showmcsv = 1;
    $sql = "UPDATE resource SET finalcsvprepared=1 WHERE rid='$id'";
    mysqli_query($conn, $sql);
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Responses of <?php echo htmlspecialchars($id); ?></title>
  <style>
    body {
        background-image: url('img/bg111.jpg');
        background-size: cover;
        background-repeat: no-repeat;
    }
    h2 {
        color: #ffffff;
    }
    .container {
        background: #ffffff2d;
        width: 400px;
        height: 500px;
        margin-left: 450px;
        margin-top: 70px;
        padding-left: 50px;
        padding-top: 20px;
        border-radius: 30px;
    }
    a {
        text-decoration: none;
        color: #ffffff;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>List of Response files:</h2>
    <?php
    $folderPath = 'Annotefiles/' . $dop;
    $csvFiles = glob($folderPath . '/*.csv');

    if (count($csvFiles) > 0) {
        echo '<ul>';
        foreach ($csvFiles as $csvFile) {
            echo '<li><a href="csvview.php?file=' . urlencode($csvFile) . '" target="_blank">' . basename($csvFile) . '</a></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No CSV files found in the folder.</p>';
    }
    ?>
    <br>
    <br>
    <br>
    <?php if (!isset($showmcsv)) {
        echo '<form action="" method="post">
            <button name="merge">Prepare Final Report</button>
        </form>';
    } ?>
  </div>
</body>
</html>
