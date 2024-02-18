<?php
function route($method, $urlData, $formData){

    include 'config.php';

    $headers = apache_request_headers();

    if (!isset($headers['auth'])){
        header('HTTP/1.0 401 Unauthorazed');
        echo json_encode(array(
            'message' => 'You need authorization'
        ));
        exit();
    }

    if ($headers['auth'] != $SERVICE_TOKEN){
            header('HTTP/1.0 401 Unauthorazed');
            echo json_encode(array(
                'message' => 'You need authorization'
            ));
            exit();
    }

    $is_error=false;
    $response=array();

    if ($urlData[0] == "telegram"){
        if($urlData[1] == "veirify"){
            // [POST] api/service/telegram/veirify

            if($method!='POST'){
                header('HTTP/1.1 405 Method Not Allowed');
                exit();
            }

            if(empty($formData -> telegram_id)){
                header('HTTP/1.0 400 Bad request');
                echo json_encode(array(
                    'message' => 'Telegram id is empty'
                ));
                exit();
            }

            if(empty($formData -> token)){
                header('HTTP/1.0 400 Bad request');
                echo json_encode(array(
                    'message' => 'Telegram id is empty'
                ));
                exit();
            }

            $telegram_id = $formData -> telegram_id;
            $token = $formData -> token;

            $sql = "SELECT * FROM `telegram_tokens` WHERE `token`='$token' AND `status`='created'";
            $result = mysqli_query($dbc, $sql);

            if(mysqli_num_rows($result) == 0){
                $response = array(
                    "message" => "Token not found or expired!"
                );

                header('HTTP/1.1 404 Not found');
                echo json_encode($response);
                exit();
            }

            $token_data = mysqli_fetch_array($result, MYSQLI_ASSOC);

            $sql = "SELECT * FROM `users` WHERE `telegram_id`='$telegram_id'";
            $result = mysqli_query($dbc, $sql);

            if(mysqli_num_rows($result) == 0){
                // пользователь не зарегестрирован
                $response = array(
                    "result" => array( 
                        "status" => "account_not_found"
                    )
                );

                header('HTTP/1.1 200 Success');
                echo json_encode($response);
                exit();
            }else{
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

                $auth_user_id = $row['id'];

                $sql = "UPDATE `telegram_tokens` SET `status`='veirifed', `veirifed_by`='$auth_user_id' WHERE `token`='$token'";
                mysqli_query($dbc, $sql);

                $response = array(
                    "result" => array( 
                        "status" => "success_veirify"
                    )
                );

                header('HTTP/1.1 200 Success');
                echo json_encode($response);
                exit();
            }
        }

        if($urlData[1] == "signup"){
            // [POST] api/service/telegram/signup

            if($method!='POST'){
                header('HTTP/1.1 405 Method Not Allowed');
                exit();
            }

            if(empty($formData -> telegram_id)){
                header('HTTP/1.0 400 Bad request');
                echo json_encode(array(
                    'message' => 'Telegram id is empty'
                ));
                exit();
            }

            if(empty($formData -> username)){
                header('HTTP/1.0 400 Bad request');
                echo json_encode(array(
                    'message' => 'Username is empty'
                ));
                exit();
            }

            if(empty($formData -> name)){
                header('HTTP/1.0 400 Bad request');
                echo json_encode(array(
                    'message' => 'Name is empty'
                ));
                exit();
            }

            if(empty($formData -> last_name)){
                header('HTTP/1.0 400 Bad request');
                echo json_encode(array(
                    'message' => 'Last name is empty'
                ));
                exit();
            }

            if(empty($formData -> token)){
                header('HTTP/1.0 400 Bad request');
                echo json_encode(array(
                    'message' => 'Token is empty'
                ));
                exit();
            }

            $telegram_id = $formData -> telegram_id;
            $username = $formData -> username;
            $name = $formData -> name;
            $last_name = $formData -> last_name;
            $token = $formData -> token;

            $sql = "SELECT * FROM `telegram_tokens` WHERE `token`='$token' AND `status`='created'";
            $result = mysqli_query($dbc, $sql);

            if(mysqli_num_rows($result) == 0){
                $response = array(
                    "message" => "Token not found or expired!"
                );

                header('HTTP/1.1 404 Not found');
                echo json_encode($response);
                exit();
            }

            $sql = "SELECT * FROM `users` WHERE `telegram_id`='$telegram_id'";
            $result = mysqli_query($dbc, $sql);
            // проверка на конфликт телеграмов

            if(mysqli_num_rows($result) != 0){
                $response = array(
                    "message" => "Telegram already used!"
                );

                header('HTTP/1.1 409 Conflict');
                echo json_encode($response);
                exit();
            }

            $sql = "INSERT INTO `users`(`username`, `first_name`, `last_name`, `email`, `phone`, `telegram_id`) VALUES ('$username','$name','$last_name','-','-','$telegram_id')";
            $result = mysqli_query($dbc, $sql);
            // создаем пользователя

            $sql = "SELECT * FROM `users` WHERE `telegram_id`='$telegram_id'";
            $result = mysqli_query($dbc, $sql);
            // получаем id пользователя в системе

            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $user_id = $row['id'];

            $sql = "SELECT * FROM `tokens` WHERE `user_id`='$user_id'";
            $result = mysqli_query($dbc, $sql);

            if(mysqli_num_rows($result) == 0){
                // если у пользователя нет токена - создаем
                $auth_token = generateRandomString(20);

                $sql = "INSERT INTO `tokens`(`user_id`, `token`, `type`) VALUES ('$user_id','$auth_token', 'user')";
                mysqli_query($dbc, $sql);
            }

            $sql = "UPDATE `telegram_tokens` SET `status`='veirifed',`veirifed_by`='$user_id' WHERE `token`='$token'";
            mysqli_query($dbc, $sql);

            $response = array(
                "result" => $user_id
            );

            header('HTTP/1.1 200 Success');
            echo json_encode($response);
            exit();
        }
    }

    if($urlData[0] == "admin"){
        if($urlData[1] == "create"){

            if($method!='POST'){
                header('HTTP/1.1 405 Method Not Allowed');
                exit();
            }

            if(empty($formData -> username)){
                header('HTTP/1.0 400 Bad request');
                echo json_encode(array(
                    'message' => 'Username is empty'
                ));
                exit();
            }

            if(empty($formData -> password)){
                header('HTTP/1.0 400 Bad request');
                echo json_encode(array(
                    'message' => 'Password is empty'
                ));
                exit();
            }

            $username = $formData -> username;
            $password = $formData -> password;

            $sql = "SELECT * FROM `admins` WHERE `username`='$username'";
            $result = mysqli_query($dbc, $sql);

            if(mysqli_num_rows($result) > 0){
                header('HTTP/1.0 409 Conflict');
                echo json_encode(array(
                    'message' => 'Username already taken!'
                ));
                exit();
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO `admins`(`username`, `password_hash`) VALUES ('$username','$password_hash')";
            mysqli_query($dbc, $sql);

            header('HTTP/1.0 201 Created');
            exit();
        }
    }
}

function generateRandomString($length) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}
?>
