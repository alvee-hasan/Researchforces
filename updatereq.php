<?php
@include 'config.php' ;

header('Content-Type: application/json');

$response = array('success' => false, 'message' => 'Something went wrong');

if(isset($_POST['cellData'])) {
    $reqid = $_POST['cellData'] ;
    $sql = "UPDATE requests SET status='accepted' WHERE reqid='$reqid'" ;
    if (mysqli_query($conn, $sql)) {
        $response['success'] = true;
        $response['message'] = 'Accepted';
    } else {
        $response['success'] = false;
        $response['message'] = '';
    }
   
    echo json_encode($response);
}
?>