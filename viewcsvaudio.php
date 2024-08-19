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

// Function to merge the CSV files into one by columns with filenames and options
function mergeCSVFiles() {
    global $conn;
    $dop = $_SESSION['dop'] ?? 0;
    $id = $_SESSION['id'] ?? 0;

    $folderPath = "AudioFiles/" . $dop;
    $outputFilePath = "AudioFiles/" . $dop . "/Final.csv";
    $mergedData = [];
    $headers = [];

    // Get a list of CSV files in the folder
    $csvFiles = glob($folderPath . "/*.csv");

    // Filter out 'data.csv'
    $csvFiles = array_filter($csvFiles, function($file) {
        return basename($file) !== 'data.csv';
    });

    // Read data from each CSV file and store it in the mergedData array
    foreach ($csvFiles as $csvFile) {
        $csvData = readCSV($csvFile);
        $fileName = pathinfo($csvFile, PATHINFO_FILENAME);
        $username = explode('_', $fileName)[0]; // Extract username from file name
        $responseName = implode('_', array_slice(explode('_', $fileName), 1)); // Response name

        $headerFileName = $username . '_' . $responseName . '_File';
        $headerOption = $username . '_' . $responseName . '_Option';

        if (!isset($mergedData[$responseName])) {
            $mergedData[$responseName] = ['file_names' => [], 'options' => []];
        }

        foreach ($csvData as $row) {
            if (isset($row[0]) && isset($row[1])) {
                $mergedData[$responseName]['file_names'][] = $row[0];
                $mergedData[$responseName]['options'][] = $row[1];
            }
        }

        // Add headers for this response file
        if (!in_array($headerFileName, $headers)) {
            $headers[] = $headerFileName;
            $headers[] = $headerOption;
        }
    }

    // Write the data to the new merged CSV file with column headings
    $fileHandle = fopen($outputFilePath, 'w');
    if ($fileHandle === false) {
        die('Failed to open file for writing.');
    }

    // Write the top row with headers
    fputcsv($fileHandle, $headers);

    // Determine the maximum number of rows in any column
    $maxRows = 0;
    foreach ($mergedData as $data) {
        $maxRows = max($maxRows, count($data['file_names']));
    }

    for ($i = 0; $i < $maxRows; $i++) {
        $rowData = [];
        foreach ($headers as $header) {
            // Extract response name from the header
            $responseName = implode('_', array_slice(explode('_', $header), 1, -1));
            $isFileColumn = strpos($header, 'File') !== false;

            if ($isFileColumn) {
                $rowData[] = $mergedData[$responseName]['file_names'][$i] ?? '';
            } else {
                $rowData[] = $mergedData[$responseName]['options'][$i] ?? '-';
            }
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
    $folderPath = 'AudioFiles/' . $dop;
    $csvFiles = glob($folderPath . '/*.csv');

    if (count($csvFiles) > 0) {
        echo '<ul>';
        foreach ($csvFiles as $csvFile) {
            if (basename($csvFile) !== 'data.csv') {
                echo '<li><a href="csvview.php?file=' . urlencode($csvFile) . '" target="_blank">' . basename($csvFile) . '</a></li>';
            }
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
