<?php
session_start();
@include 'config.php';

$option1 = $_SESSION['opt1'];
$option2 = $_SESSION['opt2'];
$rid = $_SESSION['id'];
$dop = $_SESSION['dop'];
$resc = $_SESSION['resc'];
$_SESSION['resc'] = $resc;
$username = $_SESSION['username'];

// Update user points
$sql = "SELECT point FROM user WHERE username=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$pts = $row['point'] + 20;
$qry = "UPDATE user SET point=? WHERE username=?";
$stmt = $conn->prepare($qry);
$stmt->bind_param("is", $pts, $username);
$stmt->execute();

// Fetch texts for the specific resource, ordered by count and text
$sql = "SELECT * FROM texts WHERE rid = ? ORDER BY count ASC, REPLACE(REPLACE(text, '''', ''), '\"', '') ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $rid);
$stmt->execute();
$result = $stmt->get_result();

$texts = [];
while ($row = $result->fetch_assoc()) {
    $texts[] = [
        'tid' => $row['tid'],
        'text' => $row['text']
    ];
}

$currentTextIndex = 0; // Initialize currentTextIndex
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $rid; ?> | ResearchForces</title>
    <style>
        body {
            background-image: url('img/bg111.jpg');
            background-size: cover;
            background-repeat: no-repeat;
        }
        textarea {
            width: 100%;
            height: 200px;
        }
        p {
            display: none;
            color: #ffffff;
        }
        input {
            color: #ffffff;
        }
        label {
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div id="textFieldContainer">
        <textarea id="textField" readonly></textarea>
    </div>
    <form id="optionsForm">
        <input type="hidden" name="lineIndex" id="lineIndex">
        <input type="hidden" name="lineIdentifier" id="lineIdentifier"> <!-- Add hidden field for line identifier -->
        <input type="radio" name="options" value="<?php echo $option1; ?>" id="option1"> <label for="option1"><?php echo $option1; ?></label><br>
        <input type="radio" name="options" value="<?php echo $option2; ?>" id="option2"> <label for="option2"><?php echo $option2; ?></label><br>
        <button type="button" id="nextButton">Next</button>
        <button type="button" id="skipButton">Skip</button> <!-- Add Skip Button -->
    </form>
    <p id='final'>Thanks a lot for collaboration. You have earned 20 points. <br>Go to <a href='index.php'>Home</a></p>
        <!--Added New-->
    <?php 
    @include 'config.php' ;
    $resc = $resc + 1 ;
    $sql = "UPDATE resource SET rescount='$resc' WHERE rid='$rid'" ;
    mysqli_query($conn, $sql) ;
    $qry = "UPDATE requests SET status='finished' WHERE rid='$rid' and username='$username'" ;
    mysqli_query($conn, $qry) ;
  ?>

    <script>
        let texts = <?php echo json_encode($texts); ?>;
        let currentTextIndex = 0;

        function showText() {
            const textField = document.getElementById('textField');
            if (texts[currentTextIndex]) {
                textField.value = texts[currentTextIndex].text; // Update text area with the text from texts
                document.getElementById('lineIndex').value = currentTextIndex;
                document.getElementById('lineIdentifier').value = texts[currentTextIndex].tid; // Use tid here
            } else {
                console.error('No data found for current text index:', currentTextIndex);
            }
        }

        function onNextClick() {
            const selectedOption = document.querySelector('input[name="options"]:checked');
            if (!selectedOption) {
                alert('Please select an option.');
                return;
            }

            const formData = new FormData();
            formData.append('lineIndex', currentTextIndex);
            formData.append('options', selectedOption.value);
            formData.append('lineIdentifier', texts[currentTextIndex].tid); // Use tid here

            fetch('processtext.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log(data); // Check if "ok" is logged
            })
            .catch(error => {
                console.error('Error sending data to PHP:', error);
            });

            currentTextIndex++;
            if (currentTextIndex < texts.length) {
                showText();
                resetOptions();
            } else {
                finishAnnotation();
            }
        }

        function onSkipClick() {
            const formData = new FormData();
            formData.append('lineIndex', currentTextIndex);
            formData.append('options', '-'); // Default option for skip
            formData.append('lineIdentifier', texts[currentTextIndex].tid); // Use tid here

            fetch('processtext.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log(data); // Check if "ok" is logged
            })
            .catch(error => {
                console.error('Error sending data to PHP:', error);
            });

            currentTextIndex++;
            if (currentTextIndex < texts.length) {
                showText();
                resetOptions();
            } else {
                finishAnnotation();
            }
        }

        function finishAnnotation() {
            document.getElementById('textFieldContainer').style.display = 'none';
            document.getElementById('optionsForm').style.display = 'none';
            document.getElementById('nextButton').style.display = 'none';
            document.getElementById('skipButton').style.display = 'none';
            document.getElementById('final').style.display = 'block';
            alert('Your response has been recorded. Thanks a lot for collaboration.');
        }

        function resetOptions() {
            const selectedOption = document.querySelector('input[name="options"]:checked');
            if (selectedOption) {
                selectedOption.checked = false;
            }
        }

        document.getElementById('nextButton').addEventListener('click', onNextClick);
        document.getElementById('skipButton').addEventListener('click', onSkipClick);

        showText();
    </script>
</body>
</html>
