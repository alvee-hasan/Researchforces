<?php
@include 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["action"]) && $_POST["action"] == "annotate" && isset($_POST["options"]) && isset($_POST["audio_id"]) && isset($_POST["audio_name"])) {
        $option = $_POST["options"];
        $audioId = $_POST["audio_id"];
        $audioName = $_POST["audio_name"]; // Retrieve audio file name
        $username = $_SESSION['username']; // Assuming 'username' is stored in session

        // Create a unique filename for each user
        $csvFilePath = 'AudioFiles/' . $_SESSION['dop'] . '/' . $username . '_response' . $_SESSION['resc'] . '.csv';

        // Save response to CSV
        $csvData = array($audioName, $option); // Include audio name and option
        $csvRow = implode(',', $csvData);
        $file = fopen($csvFilePath, 'a');
        if ($file) {
            fwrite($file, $csvRow . PHP_EOL);
            fclose($file);
        }

        // Increment audio file count only if the option is not "-"
        if ($option !== "-") {
            $sql = "UPDATE audiofiles SET count = count + 1 WHERE aid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $audioId);
            $stmt->execute();

            // Check for errors
            if ($stmt->error) {
                file_put_contents('error_log.txt', $stmt->error, FILE_APPEND);
            }
        }
    }
}
?>
