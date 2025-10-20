<?php

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST");
header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

$response = array();
$upload_dir = 'uploads/';
$server_url = 'https://bizonline.co.ke/PointOfSaleWebAPI/';

if($_FILES['license']) {

    $license_name = $_FILES["license"]["name"];
    $license_tmp_name = $_FILES["license"]["tmp_name"];
    $error = $_FILES["license"]["error"];

    if($error > 0){
        $response = array(
            "status" => "error",
            "error" => $error,
            "message" => "Error uploading the image!"
        );
    } else { 
        $random_name = rand(1000,1000000)."-".$license_name;
        $upload_name = $upload_dir.strtolower($random_name);
        $upload_name = preg_replace('/\s+/', '-', $upload_name);
    
        if(move_uploaded_file($license_tmp_name , $upload_name)) {
            // insert record to database
            $response = array(
                "status" => "success",
                "name" => $random_name,
                "error" => false,
                "message" => "Image uploaded successfully",
                // "url" => $server_url."/".$upload_name
            );
        } else {
            $response = array(
                "status" => "error",
                "error" => true,
                "message" => "Error uploading the image file!"
            );
        }
    }

} else {
    $response = array(
        "status" => "error",
        "error" => true,
        "message" => "No file was sent!"
    );
}

echo json_encode($response);

?>