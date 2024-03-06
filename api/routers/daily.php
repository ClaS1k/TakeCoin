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
        // [GET] api/daily

        if($method!='GET'){
            header('HTTP/1.1 405 Method Not Allowed');
            exit();
        }
    
        // get user activations
    
        // count current day
    
        // get sheldue settings
    
        // count current reward
            
        header('HTTP/1.1 200 Success');
        echo json_encode($response);
        exit();
    }

    if($urlData[0] == "complete"){
        if($method!='POST'){
            header('HTTP/1.1 405 Method Not Allowed');
            exit();
        }

        $sql = "SELECT * FROM `daily_sheldue`";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) == 0){
            // sheldue is not created, reward is disabled
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(array(
                'message' => 'Daily reward is unavailable!'
            ));
            exit();
        }  

        $sheldue_data = mysqli_fetch_array($result, MYSQLI_ASSOC);

        $sql = "SELECT * FROM `daily_completions` WHERE `user_id`='$user_id' LIMIT 200";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) == 0){
            // user dont get reward at last 90 days
            // get first day
            $reward = $sheldue_data['reward'];
            daily_deposit_user($user_id, $reward);

            $sql = "INSERT INTO `daily_completions`(`user_id`, `reward`, `date`) VALUES ('$user_id','$reward',NOW())";
            mysqli_query($dbc, $sql);

            header('HTTP/1.1 200 Success');
            exit();
        }

        $user_completions = mysqli_fetch_array($result, MYSQLI_ASSOC);
        
        $current_date = time();

        $last_date = strtotime($user_completions['date']);

        // Вычисляем разницу между текущим моментом и последней наградой
        $diff = $current_date - $last_date;

        if($diff > 60*60*24){
            // разница между текущим моментом и последней наградой более 24 часов
            $reward = $sheldue_data['reward'];
            daily_deposit_user($user_id, $reward);

            $sql = "INSERT INTO `daily_completions`(`user_id`, `reward`, `date`) VALUES ('$user_id','$reward',NOW())";
            mysqli_query($dbc, $sql);

            header('HTTP/1.1 200 Success');
            exit();
        }

        $sql = "SELECT * FROM `daily_completions` WHERE `user_id`='$user_id' LIMIT 200";
        $result = mysqli_query($dbc, $sql);

        $days_in_row = 0;
        // здесь будет кол-во дней, которое пользователь получал награду

        $prevDate = time();
        //echo $prevDate . "<br>";

        while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
            $currDate = strtotime($row['date']);
            $diff = $currDate - $prevDate;
            
            if($diff > 60*60*24){
                echo $diff;
                return;
            }

            $days_in_row = $days_in_row + 1;
        }

        // count prize today 

        // deposit reward
    }

    if($urlData[0] == "sheldue"){
        if($token_type != "admin"){
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(array(
                'message' => 'You dont have access!'
            ));
            exit();
        }

        if(!isset($urlData[1])){
            // [GET] api/daily/sheldue

            if($method!='GET'){
                header('HTTP/1.1 405 Method Not Allowed');
                exit();
            }

            $sql = "SELECT * FROM `daily_sheldue`";
            $result = mysqli_query($dbc, $sql);
    
            $sheldue = array();

            while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
                $current_day = array(
                    "id" => $row['id'],
                    "reward" => $row['reward']
                );

                array_push($sheldue, $current_day);
            }

            $response = array(
                "result" => $sheldue
            );

            header('HTTP/1.1 200 Success');
            echo json_encode($response);
            exit();
        }

        if($urlData[1] == "clear"){
            // [POST] api/daily/sheldue/clear

            if($method!='POST'){
                header('HTTP/1.1 405 Method Not Allowed');
                exit();
            }

            $sql = "TRUNCATE `daily_sheldue`"; 
            mysqli_query($dbc, $sql);

            header('HTTP/1.1 200 Success');
            exit();
        }
    }

    if($urlData[0] == "day"){

        if($token_type != "admin"){
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(array(
                'message' => 'You dont have access!'
            ));
            exit();
        }

        if($urlData[1] == "add"){
            // [POST] api/daily/day/add/<reward>

            if($method!='POST'){
                header('HTTP/1.1 405 Method Not Allowed');
                exit();
            }

            if(!isset($urlData[2])){
                header('HTTP/1.1 403 Forbidden');
                echo json_encode(array(
                    'message' => 'Incorrect reward!'
                ));
                exit();
            }

            $reward = $urlData[2];

            $sql = "SELECT * FROM `daily_sheldue`";
            $result = mysqli_query($dbc, $sql);

            if(mysqli_num_rows($result) > 90){
                // cycle days limit reached
                header('HTTP/1.1 403 Forbidden');
                echo json_encode(array(
                    'message' => 'Days limit reached!'
                ));
                exit();
            }

            $sql = "INSERT INTO `daily_sheldue`(`reward`) VALUES ('$reward')";
            mysqli_query($dbc, $sql);

            header('HTTP/1.1 201 Created');
            exit();
        }

        if($urlData[1] == "update"){
            // [POST] api/daily/day/update/<id>/<reward>

            if($method!='POST'){
                header('HTTP/1.1 405 Method Not Allowed');
                exit();
            }

            if(!isset($urlData[2]) or !isset($urlData[3])){
                header('HTTP/1.1 403 Forbidden');
                echo json_encode(array(
                    'message' => 'Incorrect day id or reward!'
                ));
                exit();
            }

            $day_id = $urlData[2];
            $reward = $urlData[3];

            $sql = "SELECT * FROM `daily_sheldue` WHERE `id`='$day_id'";
            $result = mysqli_query($dbc, $sql);

            if(mysqli_num_rows($result) == 0){
                header('HTTP/1.1 403 Forbidden');
                echo json_encode(array(
                    'message' => 'Day not found!'
                ));
                exit();
            }

            $sql = "UPDATE `daily_sheldue` SET `reward`='$reward' WHERE `id`='$day_id'";
            mysqli_query($dbc, $sql);

            header('HTTP/1.1 200 Success');
            exit();
        }
    }
}

function daily_deposit_user($user_id, $amount){
    // зачисляет пользователю средства
    $sql = "SELECT * FROM `balance` WHERE `user_id`='$user_id'";
    $result = mysqli_query($dbc, $sql);

    if(mysqli_num_rows($result) == 0){
        //баланс не создан, т.е. 0
        $sql = "INSERT INTO `balance`(`user_id`, `value`) VALUES ('$user_id','$amount')";
        mysqli_query($dbc, $sql);

        $sql = "INSERT INTO `transactions`(`user_id`, `type`, `advanced_type`, `amount`, `date`) VALUES ('$user_id','deposit','bonuse','$amount',NOW())";
        mysqli_query($dbc, $sql);
        return;
    }

    $sql = "UPDATE `balance` SET `value`=`value`+$amount WHERE `user_id`='$user_id'";
    mysqli_query($dbc, $sql);

    $sql = "INSERT INTO `transactions`(`user_id`, `type`, `advanced_type`, `amount`, `date`) VALUES ('$user_id','deposit','bonuse','$amount',NOW())";
    mysqli_query($dbc, $sql);
}
?>