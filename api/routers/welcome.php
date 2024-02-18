<?php
function route($method, $urlData, $formData){

    include 'config.php';

    $headers = apache_request_headers();

    if (!isset($headers['auth'])){
        header('HTTP/1.0 403 Forbidden');
        echo json_encode(array(
            'message' => 'You need authorization'
        ));
        exit();
    }

    include "token_validation.php";

    $is_error=false;
    $response=array();

    if($urlData[0] == "status"){

        if($method!='GET'){
            header('HTTP/1.1 405 Method Not Allowed');
            exit();
        }

        $sql = "SELECT * FROM `welcome_bonuses` WHERE `user_id`='$user_id'";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) == 0){
            $data = array(
                "status" => "available" 
            );
        }else{
            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

            $data = array(
                "status" => "unavailable",
                "amount" => $row['amount'],
                "get_date" => $row['date']
            );
        }

        $response = array(
            "result" => $data
        );

        header('HTTP/1.1 200 Success');
        echo json_encode($response);
        exit();
    }

    if($urlData[0] == "get"){

        if($method!='POST'){
            header('HTTP/1.1 405 Method Not Allowed');
            exit();
        }

        $sql = "SELECT * FROM `welcome_bonuses` WHERE `user_id`='$user_id'";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) > 0){
            $response = array(
                "message" => "Reward already getted!"
            );

            header('HTTP/1.1 403 Forbidden');
            echo json_encode($response);
            exit();
        }

        $bonuse_type = random_int(1, 1000);

        if($bonuse_type < 51){
            $bonuse_amount = 50;
        }else if($bonuse_type >= 51 and $bonuse_type < 76){
            $bonuse_amount = 125;
        }else if($bonuse_type >= 76 and $bonuse_type < 96){
            $bonuse_amount = 500;
        }else if($bonuse_type >= 96){
            $bonuse_amount = 1000;
        }

        $sql = "SELECT * FROM `balance` WHERE `user_id`='$user_id'";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) == 0){
            $sql = "INSERT INTO `balance`(`user_id`, `value`) VALUES ('$user_id','$bonuse_amount')";
        }else{
            $sql = "UPDATE `balance` SET `value`=`value`+$bonuse_amount WHERE `user_id`='$user_id'";
        }

        mysqli_query($dbc, $sql);

        $sql = "INSERT INTO `transactions`(`user_id`, `type`, `advanced_type`, `amount`, `date`) VALUES ('$user_id','deposit','bonuse','$bonuse_amount',NOW())";
        mysqli_query($dbc, $sql);

        $sql = "INSERT INTO `welcome_bonuses`(`user_id`, `amount`, `date`) VALUES ('$user_id','$bonuse_amount', NOW())";
        mysqli_query($dbc, $sql);

        $response = array(
            "result" => array(
                "status" => "successful",
                "amount" => $bonuse_amount
            )
        );

        header('HTTP/1.1 200 Success');
        echo json_encode($response);
        exit();
    }
}
?>