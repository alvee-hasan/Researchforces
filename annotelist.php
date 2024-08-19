<?php
@include 'config.php' ;
session_start() ;

$username = "" ;
$defaultdp = "img/profile1.png";  // Path to default DP

if(isset($_SESSION['username'])) {
    $username = $_SESSION['username'] ;
    $sql = "SELECT * FROM user WHERE username='$username'" ;
    $res = mysqli_query($conn, $sql) ;
    if($res) {
        $row = mysqli_fetch_assoc($res);
        $image = "img/dp/" . $row['dp'] ;

        if (!file_exists($image) || empty($row['dp'])) {
            $image = $defaultdp;  // Use default DP
        }
    }
}

$_SESSION['username'] = $username ;
if(isset($_SESSION['permit'])) {
    unset($_SESSION['permit']) ;
}

$sectext = "Log out" ;

if(isset($_POST['sectextbutton'])) {
    unset($_POST['sectextbutton']) ;
    session_destroy() ;
    header('Location: index.php') ;
}

if(isset($_POST['secbutton'])) {
    unset($_POST['secbutton']) ;
    $_SESSION['username'] = $username ;
    header('Location: profile.php') ;
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Annotation list | ResearchForces</title>
    <link rel="stylesheet" href="main-body.css">
    <link rel="stylesheet" href="annote.css">
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <script src="app.js"></script>
</head>
<body>
    <header>
        <a href="index.php" class="noref"><div class="refr"><span class="refrs">Research</span>Forces</div></a>

        <div class="profile">
          <img class="avatar" src="<?php echo $image; ?>">
          <form action="" class="nvsign" method="post">
            <input type="submit" class="ssnvsign" name='secbutton' value='<?php echo $username; ?>'/>
          </form>
          <p class="nvsign"> | </p>
          <form action="" class="nvsign" method="post">
            <input type="submit" class="ssnvsign" name='sectextbutton' value='<?php echo $sectext; ?>'/>
          </form>
        </div>
    </header>
    
    <div class="container">
        <nav class="menu">
            <ul class="main-menu">
                <li class="active">List</li>
                <li><a href="createannote.php">Create</a></li>
                <li><a href="createtext2.php">Text</a></li>
                <li><a href="createaudio.php">Audio</a></li>
                <li><a href="#">Help</a></li>
                <li><a href="search.php">Search</a></li>
            </ul>
        </nav>
        <article>
            <caption>Data Annotation</caption>
            <table>
                <thead>
                    <tr>
                        <th class="col-first">ID</th>
                        <th class="col-mid">Projects</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody id="table-body">
                    <?php @include 'fetch-data.php'; ?>
                </tbody>
            </table>
        </article>
        
    </div>

    


</body>
</html>