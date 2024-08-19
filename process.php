<?php
@include 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["action"]) && $_POST["action"] == "annotate" && isset($_POST["options"]) && isset($_POST["image_id"]) && isset($_POST["image_name"])) {
        $option = $_POST["options"];
        $imgId = $_POST["image_id"];
        $imageName = $_POST["image_name"]; // Retrieve image name
        $username = $_SESSION['username']; // Assuming 'username' is stored in session

        // Create a unique filename for each user
        $csvFilePath = 'Annotefiles/' . $_SESSION['dop'] . '/' . $username . '_response' . $_SESSION['resc'] . '.csv';

        // Save response to CSV
        $csvData = array($imageName, $option); // Include image name and option
        $csvRow = implode(',', $csvData);
        $file = fopen($csvFilePath, 'a');
        if ($file) {
            fwrite($file, $csvRow . PHP_EOL);
            fclose($file);
        }

        // Increment image count only if the option is not "-", For both Finish here and Skip
        if ($option !== "-") {
            $sql = "UPDATE imagecollection SET count = count + 1 WHERE imgid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $imgId);
            $stmt->execute();

            // Check for errors
            if ($stmt->error) {
                file_put_contents('error_log.txt', $stmt->error, FILE_APPEND);
            }
        }
    }
}
