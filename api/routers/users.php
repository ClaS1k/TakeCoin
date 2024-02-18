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

  if(!isset($urlData[0])){
    // [GET] api/users

    if($token_type != "admin"){
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(array(
            'message' => 'You dont have access!'
        ));
        exit();
    }

    if($method != 'GET'){
       header('HTTP/1.0 405 Method Not Allowed');
       exit();
    }

    $sql = "SELECT * FROM `users`";
    $result = mysqli_query($dbc, $sql);

    $users_list = array();

    while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
        $current_user_id = $row['id'];

        $sql = "SELECT * FROM `balance` WHERE `user_id`='$current_user_id'";
        $balance_res = mysqli_query($dbc, $sql);

        $balance = 0;

        if(mysqli_num_rows($balance_res) != 0){
            $balance_data = mysqli_fetch_array($balance_res, MYSQLI_ASSOC);
            $balance = $balance_data['value'];
        }

        $response_user = array(
            "id" => $row['id'],
            "username" => $row['username'],
            "first_name" => $row['first_name'],
            "last_name" => $row['last_name'],
            "email" => $row['email'],
            "phone" => $row['phone'],
            "balance" => $balance
        );

        array_push($users_list, $response_user);
    }

    $response = array(
        "result" => $users_list
    );

    header('HTTP/1.1 200 Success');
    echo json_encode($response);
    exit();
  }
  
}
?>
