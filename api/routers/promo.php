<?php
function route($method, $urlData, $formData){

    include 'config.php';

    $headers = apache_request_headers();

    if (!isset($headers['auth'])){
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(array(
            'message' => 'You need authorization'
        ));
        exit();
    }

    include "token_validation.php";

    $is_error=false;
    $response=array();

    if(!isset($urlData[0])){
        // [GET] api/promo

        if($token_type != "admin"){
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(array(
                'message' => 'You dont have access!'
            ));
            exit();
        }

        if($method!='GET'){
            header('HTTP/1.1 405 Method Not Allowed');
            exit();
        }

        $sql = "SELECT * FROM `promo` ORDER BY `creation_date` DESC";
        $result = mysqli_query($dbc, $sql);

        $codes_list = array();

        while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
            $code = array(
                "id" => $row['id'],
                "code" => $row['code'],
                "reward" => $row['reward'],
                "active_from" => $row['date_from'],
                "active_to" => $row['date_to'],
                "active_limit" => $row['activation_limit'],
                "creation_date" => $row['creation_date']
            );

            array_push($codes_list, $code);
        }

        $response = array(
            "result" => $codes_list
        );
        
        header('HTTP/1.1 200 Success');
        echo json_encode($response);
        exit();
    }

    if($urlData[0] == "create"){
        // [POST] api/promo/create

        if($token_type != "admin"){
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(array(
                'message' => 'You dont have access!'
            ));
            exit();
        }

        if($method!='POST'){
            header('HTTP/1.1 405 Method Not Allowed');
            exit();
        }

        if(empty($formData -> code)){
            header('HTTP/1.0 400 Bad request');
            echo json_encode(array(
                'message' => 'Code is empty'
            ));
            exit();
        }

        if(empty($formData -> reward)){
            header('HTTP/1.0 400 Bad request');
            echo json_encode(array(
                'message' => 'Reward is empty'
            ));
            exit();
        }

        if(empty($formData -> active_from)){
            header('HTTP/1.0 400 Bad request');
            echo json_encode(array(
                'message' => 'Active from is empty'
            ));
            exit();
        }

        if(empty($formData -> active_to)){
            header('HTTP/1.0 400 Bad request');
            echo json_encode(array(
                'message' => 'Active to is empty'
            ));
            exit();
        }

        if(empty($formData -> active_limit)){
            header('HTTP/1.0 400 Bad request');
            echo json_encode(array(
                'message' => 'Active limit is empty'
            ));
            exit();
        }

        $code = $formData -> code;
        $reward = $formData -> reward;
        $active_from = $formData -> active_from;
        $active_to = $formData -> active_to;
        $active_limit = $formData -> active_limit;

        $sql = "SELECT * FROM `promo` WHERE `code`='$code'";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) > 0){
            header('HTTP/1.0 409 Conflict');
            echo json_encode(array(
                'message' => 'Code is not unique!'
            ));
            exit();
        }

        $sql = "INSERT INTO `promo`(`code`, `reward`, `date_from`, `date_to`, `activation_limit`, `creation_date`) VALUES ('$code','$reward','$active_from','$active_to','$active_limit',NOW())";
        mysqli_query($dbc, $sql);

        header('HTTP/1.0 201 Created');
        exit();
    }

    if($urlData[0] == "activation"){
        // [POST] api/promo/activation/<code>

        if($token_type != "user"){
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(array(
                'message' => 'Only for users!'
            ));
            exit();
        }

        if($method!='POST'){
            header('HTTP/1.1 405 Method Not Allowed');
            exit();
        }

        $code = $urlData[1];

        $sql = "SELECT * FROM `promo` WHERE `code`='$code' AND `date_from`<NOW() AND `date_to`>NOW()";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) == 0){
            header('HTTP/1.1 404 Not found');
            echo json_encode(array(
                'message' => 'The code does not exist or is not relevant!'
            ));
            exit();
        }

        $code_data = mysqli_fetch_array($result, MYSQLI_ASSOC);

        $code_id = $code_data['id'];
        $code_limit = $code_data['activation_limit'];
        $code_reward = $code_data['reward'];

        $sql = "SELECT * FROM `promo_activations` WHERE `code_id`='$code_id' AND `user_id`='$user_id'";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) != 0){
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(array(
                'message' => 'You have already activated the code!'
            ));
            exit();
        }

        $sql = "SELECT * FROM `promo_activations` WHERE `code_id`='$code_id'";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) >= $code_limit){
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(array(
                'message' => 'The activation limit has been reached!'
            ));
            exit();
        }

        $sql = "SELECT * FROM `balance` WHERE `user_id`='$user_id'";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) == 0){
            $sql = "INSERT INTO `balance`(`user_id`, `value`) VALUES ('$user_id','$code_reward')";
        }else{
            $sql = "UPDATE `balance` SET `value`=`value`+$code_reward WHERE `$user_id`='$user_id'";
        }

        mysqli_query($dbc, $sql);

        $sql = "INSERT INTO `transactions`(`user_id`, `type`, `advanced_type`, `amount`, `date`) VALUES ('$user_id','deposit','bonuse','$code_reward',NOW())";
        mysqli_query($dbc, $sql);

        $sql = "INSERT INTO `promo_activations`(`code_id`, `user_id`, `date`) VALUES ('$code_id','$user_id',NOW())";
        mysqli_query($dbc, $sql);

        $response = array(
            'result' => array(
                'status' => 'success',
                'reward' => $code_reward
            )
        );

        header('HTTP/1.1 200 Success');
        echo json_encode($response);
        exit();
    }

    if($urlData[0] == "history"){
        // [POST] api/promo/history/<code_id>

        if($token_type != "admin"){
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(array(
                'message' => 'You have no access!'
            ));
            exit();
        }

        if($method!='GET'){
            header('HTTP/1.1 405 Method Not Allowed');
            exit();
        }  
    
        $code_id = $urlData[1];

        $sql = "SELECT * FROM `promo` WHERE `id`='$code_id'";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) == 0){
            header('HTTP/1.1 404 Not found');
            echo json_encode(array(
                'message' => 'Code not found!'
            ));
            exit();
        }

        $sql = "SELECT * FROM `promo_activations` WHERE `code_id`='$code_id'";
        $result = mysqli_query($dbc, $sql);

        $activations_list = array();

        while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
            $activation_user_id = $row['user_id'];

            $sql = "SELECT * FROM `users` WHERE `id`='$activation_user_id'";
            $res = mysqli_query($dbc, $sql);

            $activation_user_data = mysqli_fetch_array($res, MYSQLI_ASSOC);

            $activation = array(
                "id" => $row['id'],
                "code_id" => $row['code_id'],
                "date" => $row['date'],
                "user" => array(
                    "id" => $activation_user_data['id'],
                    "username" => $activation_user_data['username'],
                    "first_name" => $activation_user_data['first_name'],
                    "last_name" => $activation_user_data['last_name'],
                    "email" => $activation_user_data['email'],
                    "phone" => $activation_user_data['phone']
                )
            );

            array_push($activations_list, $activation);
        }

        $response = array(
            "result" => $activations_list
        );

        header('HTTP/1.1 200 Success');
        echo json_encode($response);
        exit();
    }

    if($urlData[0] == "user"){
        // [POST] api/promo/user/<user_id>

        if($token_type != "admin"){
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(array(
                'message' => 'You have no access!'
            ));
            exit();
        }

        if($method!='GET'){
            header('HTTP/1.1 405 Method Not Allowed');
            exit();
        }  
    
        $query_id = $urlData[1];

        $sql = "SELECT * FROM `users` WHERE `id`='$query_id'";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) == 0){
            header('HTTP/1.1 404 Not found');
            echo json_encode(array(
                'message' => 'User not found!'
            ));
            exit();
        }

        $sql = "SELECT * FROM `promo_activations` WHERE `user_id`='$query_id'";
        $result = mysqli_query($dbc, $sql);

        $activations_list = array();

        while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
            $activation_user_id = $row['user_id'];

            $sql = "SELECT * FROM `users` WHERE `id`='$activation_user_id'";
            $res = mysqli_query($dbc, $sql);

            $activation_user_data = mysqli_fetch_array($res, MYSQLI_ASSOC);

            $activation = array(
                "id" => $row['id'],
                "code_id" => $row['code_id'],
                "date" => $row['date'],
                "user" => array(
                    "id" => $activation_user_data['id'],
                    "username" => $activation_user_data['username'],
                    "first_name" => $activation_user_data['first_name'],
                    "last_name" => $activation_user_data['last_name'],
                    "email" => $activation_user_data['email'],
                    "phone" => $activation_user_data['phone']
                )
            );

            array_push($activations_list, $activation);
        }

        $response = array(
            "result" => $activations_list
        );

        header('HTTP/1.1 200 Success');
        echo json_encode($response);
        exit();
    }
}
?>