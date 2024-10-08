<?php
@include 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["lineIndex"]) && isset($_POST["options"]) && isset($_POST["lineIdentifier"])) {
        $option = $_POST["options"];
        $lineIndex = $_POST["lineIndex"];
        $lineIdentifier = $_POST["lineIdentifier"];
        $username = $_SESSION['username'];
        $dop = $_SESSION['dop'];
        $resc = $_SESSION['resc'];

        // Fetch the text content for the given line identifier
        $sql = "SELECT text FROM texts WHERE tid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $lineIdentifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $textContent = $row['text'];

        // Define the path for the CSV file
        $csvFilePath = 'Textannotation/' . $dop . '/response' . $resc . ' - ' . $username . '.csv';

        // Check if the file exists, create it if it doesn't
        if (!file_exists($csvFilePath)) {
            $directory = dirname($csvFilePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
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

            if ($stmt->error) {
                file_put_contents('error_log.txt', $stmt->error, FILE_APPEND);
            }
        }
    } elseif (isset($_POST["finishHere"])) {
        // Handle "Finish Here" button click
        $currentTextIndex = $_POST["currentTextIndex"];
        $remainingTexts = json_decode($_POST["remainingTexts"], true); // Decode JSON to array
        $username = $_SESSION['username'];
        $dop = $_SESSION['dop'];
        $resc = $_SESSION['resc'];

        // Define the path for the CSV file
        $csvFilePath = 'Textannotation/' . $dop . '/response' . $resc . ' - ' . $username . '.csv';

        // Open CSV file to append skipped responses
        $file = fopen($csvFilePath, 'a');
        if ($file) {
            foreach ($remainingTexts as $text) {
                $csvData = array($text['text'], '-');
                $csvRow = implode(',', $csvData);
                fwrite($file, $csvRow . PHP_EOL);
            }
            fclose($file);
        } else {
            echo 'Error: Unable to open file for writing.';
        }

        // Update database to set count unchanged for skipped texts
        foreach ($remainingTexts as $text) {
            $sql = "UPDATE texts SET count = count WHERE tid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $text['tid']);
            $stmt->execute();

            if ($stmt->error) {
                file_put_contents('error_log.txt', $stmt->error, FILE_APPEND);
            }
        }
    }
}
?>
