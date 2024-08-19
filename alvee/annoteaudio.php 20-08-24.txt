<?php
session_start();
@include 'config.php';

$option1 = $_SESSION['opt1'];
$option2 = $_SESSION['opt2'];
$rid = $_SESSION['id'];
$dop = $_SESSION['dop'];
$resc = $_SESSION['resc'];
$username = $_SESSION['username'];

// Fetch user points
$sql = "SELECT point FROM user WHERE username=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$pts = $row['point'];

// Update points
$pts += 20;
$qry = "UPDATE user SET point=? WHERE username=?";
$stmt = $conn->prepare($qry);
$stmt->bind_param("is", $pts, $username);
$stmt->execute();

// Fetch audio files for the specific resource, ordered by count and name
$sql = "SELECT * FROM audiofiles WHERE rid = ? ORDER BY count ASC, name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $rid);
$stmt->execute();
$result = $stmt->get_result();

$audioFiles = [];
while ($row = $result->fetch_assoc()) {
    $audioFiles[] = [
        'aid' => $row['aid'],
        'name' => $row['name']
    ];
}

$currentAudioIndex = 0; // Initialize currentAudioIndex
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
        audio {
            width: 300px;
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
    <div id="audioContainer">
        <audio id="currentAudio" controls></audio>
    </div>
    <form action="" id="optionsForm">
        <input type="radio" name="options" value="<?php echo $option1; ?>" id="option1">
        <label for="option1"><?php echo $option1; ?></label>
        <input type="radio" name="options" value="<?php echo $option2; ?>" id="option2">
        <label for="option2"><?php echo $option2; ?></label>
        <br>
        <button type="button" id="nextButton">Next</button>
        <button type="button" id="skipButton">Skip</button> <!-- Skip Button -->
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
        const audioFiles = <?php echo json_encode($audioFiles); ?>;
        let currentAudioIndex = 0;

        function showAudio() {
            if (audioFiles.length === 0) return; // Check if there are any audios
            const audioContainer = document.getElementById('currentAudio');
            const audioFilePath = 'AudioFiles/<?php echo $dop; ?>/' + audioFiles[currentAudioIndex].name;
            audioContainer.src = audioFilePath;  // Assign the path to the audio element's src
            audioContainer.play(); // Automatically play the audio
        }

        function onNextClick() {
            const selectedOption = document.querySelector('input[name="options"]:checked');
            if (!selectedOption) {
                alert('Please select an option.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'annotate');
            formData.append('audio_id', audioFiles[currentAudioIndex].aid); // Use aid here
            formData.append('options', selectedOption.value);
            formData.append('audio_name', audioFiles[currentAudioIndex].name); // Pass audio name

            fetch('processaudio.php', {
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

            currentAudioIndex++;
            if (currentAudioIndex < audioFiles.length) {
                showAudio();
                resetOptions();
            } else {
                finishAnnotation();
            }
        }

        function onSkipClick() {
            const formData = new FormData();
            formData.append('action', 'annotate');
            formData.append('audio_id', audioFiles[currentAudioIndex].aid); // Use aid here
            formData.append('options', '-'); // Default option for skip
            formData.append('audio_name', audioFiles[currentAudioIndex].name); // Pass audio name

            fetch('processaudio.php', {
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

            currentAudioIndex++;
            if (currentAudioIndex < audioFiles.length) {
                showAudio();
                resetOptions();
            } else {
                finishAnnotation();
            }
        }

        function finishAnnotation() {
            document.getElementById('audioContainer').style.display = 'none';
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

        showAudio();
    </script>
</body>
</html>
