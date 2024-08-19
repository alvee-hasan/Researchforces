<?php
@include 'config.php';
session_start();

$username = "";
$defaultdp = "img/profile1.png";  // Path to default DP

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $sql = "SELECT * FROM user WHERE username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        $row = $res->fetch_assoc();
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
    exit;
}

if (isset($_POST['secbutton'])) {
    unset($_POST['secbutton']);
    $_SESSION['username'] = $username;
    header('Location: profile.php');
    exit;
}

if (isset($_POST['submitBtn'])) {
    if (isset($_POST['title']) && isset($_POST['description']) && isset($_POST['instruction']) && isset($_POST['opt1']) && isset($_POST['opt2']) && isset($_FILES['files'])) {
        $title = $_POST['title'];
        $desc = $_POST['description'];
        $ins = $_POST['instruction'];
        $author = $username;
        $type = "audio";
        $dop = time();
        $option1 = $_POST['opt1'];
        $option2 = $_POST['opt2'];
        $noi = count($_FILES['files']['name']); // Automatically calculate the number of audio files

        // Insert resource data and get the inserted ID
        $sql = "INSERT INTO resource (type, name, author, dop, description, instructions, imgquantity, option1, option2) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssiss", $type, $title, $author, $dop, $desc, $ins, $noi, $option1, $option2);
        $stmt->execute();
        $resource_id = $stmt->insert_id;

        $pts -= 50;
        $qry = "UPDATE user SET point=? WHERE username=?";
        $stmt = $conn->prepare($qry);
        $stmt->bind_param("is", $pts, $username);
        $stmt->execute();

        $foldername = "AudioFiles/" . $dop;
        if (!is_dir($foldername)) mkdir($foldername);

        foreach ($_FILES['files']['name'] as $i => $name) {
            if (strlen($_FILES['files']['name'][$i]) > 1) {
                move_uploaded_file($_FILES['files']['tmp_name'][$i], $foldername . "/" . $name);

                // Insert audio information into the new table
                $audio_sql = "INSERT INTO audiofiles (rid, name, count) VALUES (?, ?, 0)";
                $stmt = $conn->prepare($audio_sql);
                $stmt->bind_param("is", $resource_id, $name);
                $stmt->execute();
            }
        }

        header('Location: annotelist.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Create Audio | ResearchForces</title>
    <link rel="stylesheet" href="createannotestyle.css">
    <meta name='viewport' content='width=device-width, initial-scale=1'>
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
                <li><a href="audiolist.php">List</a></li>
                <li><a href="createannote.php">Create</a></li>
                <li><a href="createtext2.php">Text</a></li>
                <li class="active">Audio</li>
                <li><a href="#">Help</a></li>
                <li><a href="search.php">Search</a></li>
            </ul>
        </nav>
        <article>
            <?php if ($pts < 50) {
                echo "<h1 class='warning'>Ooops!!! You don't have sufficient points. Please collect some points and come back later.</h1>";
            }?>
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
                <label> &nbsp;&nbsp;&nbsp; Upload Audio Files</label>
                <input type="file" name="files[]" id="files" multiple accept="audio/*" />
                <br>
                <label> &nbsp;&nbsp;&nbsp; Number of Audio Files</label>
                <input type="number" name="noi" id="noi" readonly />
                <br>
                <label> &nbsp;&nbsp;&nbsp; Set the options</label>
                <input type="text" class="input_text" name="opt1" Placeholder="Option 1" />
                <input type="text" class="input_text" name="opt2" Placeholder="Option 2" />
                <div id="wrapper">
                    <div id="field_div">
                        <button class="bttn" type="button" onclick="add_field();">+</button>
                    </div>
                </div>
                <?php if ($pts >= 50) {
                    echo '<button type="submit" name="submitBtn" class="btn">Submit</button>';
                }?>
            </form>
        </article>
    </div>

    <script>
        document.getElementById('files').addEventListener('change', function() {
            document.getElementById('noi').value = this.files.length;
        });

        function add_field() {
            var total_text = document.getElementsByClassName("input_text").length + 1;
            var field_div = document.getElementById("field_div");
            var newField = document.createElement('p');
            newField.id = 'input_text' + total_text + '_wrapper';
            newField.innerHTML = "<input type='text' class='input_text' id='input_text" + total_text + "' placeholder='Enter Text'><button class='rmvbtn' type='button' onclick=remove_field('input_text" + total_text + "');>-</button>";
            field_div.appendChild(newField);
        }

        function remove_field(id) {
            var field = document.getElementById(id + '_wrapper');
            field.parentNode.removeChild(field);
        }
    </script>

</body>
</html>
