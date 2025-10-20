<?php



header('Content-Type: application/json; charset=utf-8');

header("Access-Control-Allow-Origin: *");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {



    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))

        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");



    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))

        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");



    exit(0);

}



$response = array();

$upload_dir = 'uploads/';

// $server_url = 'https://bizonline.co.ke/PointOfSaleWebAPI/api';
$server_url = 'http://localhost/PointOfSaleWebAPI/api';



if($_FILES['avatar'])

{

    $previuos_name = null;

    if (isset($_GET['previous_name'])) {

        $previuos_name=$_GET['previous_name'];

    }

    

    $avatar_name = $_FILES["avatar"]["name"];

    $avatar_tmp_name = $_FILES["avatar"]["tmp_name"];

    $error = $_FILES["avatar"]["error"];



    if($error > 0){

        $response = array(

            "status" => "error",

            "error" => true,

            "message" => "Error uploading the file1!"

        );

    }else 

    {

        if($previuos_name == null) {

            $random_name = rand(1000,1000000)."-".$avatar_name;

            $upload_name = $upload_dir.strtolower($random_name);

            $upload_name = preg_replace('/\s+/', '-', $upload_name);

        

            if(move_uploaded_file($avatar_tmp_name , $upload_name)) {

                $random_name = preg_replace('/\s+/', '-', $random_name);

                $response = array(

                    "status" => "success",

                    "name" => $random_name,

                    "error" => false,

                    "message" => "File uploaded successfully"

                  );

            }else

            {

                $response = array(

                    "status" => "error",

                    "error" => true,

                    "message" => "Error uploading the file2!"

                );

            }

        } else {

            if (file_exists($upload_dir.strtolower($previuos_name))) {

                

                unlink($upload_dir.strtolower($previuos_name));

                

                $random_name = rand(1000,1000000)."-".$avatar_name;

                $upload_name = $upload_dir.strtolower($random_name);

                $upload_name = preg_replace('/\s+/', '-', $upload_name);

            

                if(move_uploaded_file($avatar_tmp_name , $upload_name)) {

                    $random_name = preg_replace('/\s+/', '-', $random_name);

                    $response = array(

                        "status" => "success",

                        "name" => $random_name,

                        "error" => false,

                        "message" => "File uploaded successfully",

                        "url" => $server_url."/".$upload_name

                      );

                }else

                {

                    $response = array(

                        "status" => "error",

                        "error" => true,

                        "message" => "Error uploading the file!"

                    );

                }

            } else {

                $random_name = rand(1000,1000000)."-".$avatar_name;

                $upload_name = $upload_dir.strtolower($random_name);

                $upload_name = preg_replace('/\s+/', '-', $upload_name);

            

                if(move_uploaded_file($avatar_tmp_name , $upload_name)) {

                    $random_name = preg_replace('/\s+/', '-', $random_name);

                    $response = array(

                        "status" => "success",

                        "name" => $random_name,

                        "error" => false,

                        "message" => "File uploaded successfully",

                        "url" => $server_url."/".$upload_name

                      );

                }else

                {

                    $response = array(

                        "status" => "error",

                        "error" => true,

                        "message" => "Error uploading the file!"

                    );

                }

            }

        }

    }







    



}else{

    $response = array(

        "status" => "error",

        "error" => true,

        "message" => "No file was sent!"

    );

}



echo json_encode($response);



?>