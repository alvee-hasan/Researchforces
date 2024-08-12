<?php
@include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $imgid = isset($_POST['imgid']) ? $_POST['imgid'] : '';
    $option = isset($_POST['option']) ? $_POST['option'] : '';
    $rid = isset($_POST['rid']) ? $_POST['rid'] : '';

    if ($imgid !== '' && $rid !== '' && $option !== '') {
        $sql = "UPDATE imagecollection SET count = count + 1 WHERE imgid = '$imgid' AND rid = '$rid'";
        mysqli_query($conn, $sql);
    }
}
?>
