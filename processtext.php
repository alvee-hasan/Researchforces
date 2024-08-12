<?php
@include 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["lineIndex"]) && isset($_POST["options"]) && isset($_POST["lineIdentifier"])) {
        $option = $_POST["options"];
        $lineIndex = $_POST["lineIndex"];
        $lineIdentifier = $_POST["lineIdentifier"];
        $username = $_SESSION['username']; // Assuming 'username' is stored in session
        $dop = $_SESSION['dop']; // Assuming 'dop' is stored in session
        $resc = $_SESSION['resc']; // Assuming 'resc' is stored in session

        // Fetch the text content for the given line identifier
        $sql = "SELECT text FROM texts WHERE tid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $lineIdentifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $textContent = $row['text'];

        // Define the path for the CSV file
        $csvFilePath = 'Textannotation/' . $dop . '/response'.$resc.' - '.$username.'.csv';

        // Check if the file exists, create it if it doesn't
        if (!file_exists($csvFilePath)) {
            // Create the directory if it doesn't exist
            $directory = dirname($csvFilePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true); // Create directory recursively with full permissions
            }

            // Create an empty CSV file
            touch($csvFilePath);
        }

        // Append response to the CSV file with text content first
        $csvData = array($textContent, $option);
        $csvRow = implode(',', $csvData);

        $file = fopen($csvFilePath, 'a');
        if ($file) {
            fwrite($file, $csvRow . PHP_EOL);
            fclose($file);
        } else {
            echo 'Error: Unable to open file for writing.';
        }

        // Increment text count only if the option is not "-"
        if ($option !== "-") {
            $sql = "UPDATE texts SET count = count + 1 WHERE tid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $lineIdentifier);
            $stmt->execute();

            // Check for errors
            if ($stmt->error) {
                file_put_contents('error_log.txt', $stmt->error, FILE_APPEND);
            }
        }
    }
}
?>
