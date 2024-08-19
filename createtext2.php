<?php
@include 'config.php';
session_start();

$username = "";
$defaultdp = "img/profile1.png";  // Path to default DP

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $sql = "SELECT * FROM user WHERE username='$username'";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $image = "img/dp/" . $row['dp'];
        $pts = $row['point'];

        if (!file_exists($image) || empty($row['dp'])) {
            $image = $defaultdp;  // Use default DP
        }
    }
}

$_SESSION['username'] = $username;
$sectext = "Log out";

if (isset($_POST['sectextbutton'])) {
    unset($_POST['sectextbutton']);
    session_destroy();
    header('Location: index.php');
}

if (isset($_POST['secbutton'])) {
    unset($_POST['secbutton']);
    $_SESSION['username'] = $username;
    header('Location: profile.php');
}

if (isset($_POST['submitBtn'])) {
    if (isset($_POST['title']) && isset($_POST['description']) && isset($_POST['instruction']) && isset($_POST['opt1']) && isset($_POST['opt2']) && isset($_FILES['csv_file'])) {
        $title = $_POST['title'];
        $desc = $_POST['description'];
        $ins = $_POST['instruction'];
        $author = $username;
        $type = "textannote";
        $dop = time();
        $option1 = $_POST['opt1'];
        $option2 = $_POST['opt2'];

        // Insert resource information into the database and get resource ID
        $sql = "INSERT INTO resource (type, name, author, dop, description, instructions, option1, option2) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $type, $title, $author, $dop, $desc, $ins, $option1, $option2);
        $stmt->execute();
        $resource_id = $stmt->insert_id;

        $csvFile = $_FILES['csv_file']['tmp_name'];
        $foldername = "Textannotation/" . $dop;
        if (!is_dir($foldername)) mkdir($foldername);

        // Move the uploaded CSV file to the designated folder
        $csvFilePath = $foldername . "/data.csv";
        move_uploaded_file($_FILES['csv_file']['tmp_name'], $csvFilePath);

        // Initialize text counter
        $textCount = 0;

        //The CSV file is read using file_get_contents and explode("\r", $fileContent) to split the contents into lines based on the \r delimiter.This approach ensures that each line of the CSV file is processed individually.
        // Read the entire CSV file content
        $fileContent = file_get_contents($csvFilePath);
        $lines = explode("\r", $fileContent); // Split by carriage return

        // Insert each line from the CSV file into the 'texts' table
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $text_sql = "INSERT INTO texts (rid, text, count) VALUES (?, ?, 0)";
                $stmt = $conn->prepare($text_sql);
                $stmt->bind_param("is", $resource_id, $line);
                $stmt->execute();

                $textCount++;
            }
        }

        // Update the number of texts in the resource table
        $sql = "UPDATE resource SET imgquantity=? WHERE rid=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $textCount, $resource_id);
        $stmt->execute();

        // Deduct points from the user
        $pts = $pts - 50;
        $qry = "UPDATE user SET point=? WHERE username=?";
        $stmt = $conn->prepare($qry);
        $stmt->bind_param("is", $pts, $username);
        $stmt->execute();

        header('Location: annotelist.php');
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Create Annotation | ResearchForces</title>
    <link rel="stylesheet" href="createannotestyle.css">
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <script src="app.js"></script>
</head>

<body>
<header>
    <a href="index.php" class="noref"><div class="refr"><span class="refrs">Research</span>Forces</div></a>

    <div class="profile">
        <img class="avatar" src="<?php echo $image; ?>">
        <a href="profile.php" style="text-decoration:none;color:inherit;"><?php echo $username; ?></a>
        <span> | </span>
        <form action="" method="post" style="display:inline;">
            <button type="submit" name='sectextbutton' style="background:none;border:none;color:inherit;padding:0;margin:0;cursor:pointer;font:inherit;"> <?php echo $sectext; ?> </button>
        </form>
    </div>
</header>

    <div class="container">
        <nav class="menu">
            <ul class="main-menu">
                <li><a href="annotelist.php">List</a></li>
                <li><a href="createannote.php">Create</a></li>
                <li class="active">Text</li>
                <li><a href="createaudio.php">Audio</a></li>
                <li><a href="#">Help</a></li>
                <li><a href="search.php">Search</a></li>
            </ul>
        </nav>
        <article>
            <?php if ($pts < 50) {
                echo "<h1 class='warning'>Ooops!!! You don't have sufficient points. Please collect some points and come back later.</h1>";
            } ?>
            <form action="" method="post" enctype="multipart/form-data">
                <label> &nbsp;&nbsp;&nbsp; Title</label>
                <input type="text" placeholder="A Beautiful title attracts all" name="title" />
                <br>
                <label> &nbsp;&nbsp;&nbsp; Description</label>
                <input type="text" placeholder="Explain about your work" name="description" />
                <br>
                <label> &nbsp;&nbsp;&nbsp; Instructions</label>
                <input type="text" placeholder="Clear and Concise Instruction will provide best collaboration" name="instruction" />
                <br>
                <label> &nbsp;&nbsp;&nbsp; Upload CSV File</label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv">
                <br>
                <label> &nbsp;&nbsp;&nbsp; Number Of Texts</label>
                <input type="number" name="noi" id="noi" readonly />
                <br>
                <label> &nbsp;&nbsp;&nbsp; Set the options</label>
                <input type="text" class="input_text" name="opt1" Placeholder="Option 1" />
                <input type="text" class="input_text" name="opt2" Placeholder="Option 2" />
                <div id="wrapper">
                    <div id="field_div">
                        <button class="bttn" onclick="add_field();">+</button>
                    </div>
                </div>
                <?php if ($pts >= 50) {
                    echo '<button type="submit" name="submitBtn" class="btn">Submit</button>';
                }
                ?>
            </form>
        </article>
    </div>

    <script>
        document.getElementById('csv_file').addEventListener('change', function () {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    var lines = e.target.result.split('\n').length;
                    document.getElementById('noi').value = lines;
                };
                reader.readAsText(file);
            }
        });

        function add_field() {
            var total_text = document.getElementsByClassName("input_text");
            total_text = total_text.length + 1;
            document.getElementById("field_div").innerHTML = document.getElementById("field_div").innerHTML +
                "<p id='input_text" + total_text + "_wrapper'><input type='text' class='input_text' id='input_text" + total_text + "' placeholder='Enter Text'><button class='rmvbtn' onclick=remove_field('input_text" + total_text + "');>-</button></p>";
        }

        function remove_field(id) {
            document.getElementById(id + "_wrapper").innerHTML = "";
        }
    </script>

</body>

</html>
