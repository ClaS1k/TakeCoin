<?php
function route($method, $urlData, $formData){

  include 'config.php';

  $headers = apache_request_headers();

  $is_error=false;
  $response=array();

  if($urlData[0] == "telegram"){

      if(!isset($urlData[1])){
        // [GET] api/auth/telegram

        if($method!='GET'){
          header('HTTP/1.1 405 Method Not Allowed');
          exit();
        }

        $token = generateRandomString(12);
        $ip = get_ip();

        $sql = "INSERT INTO `telegram_tokens`(`token`, `status`, `creation_date`, `veirifed_by`, `ip`) VALUES ('$token','created',NOW(),'0', '$ip')";
        mysqli_query($dbc, $sql);

        $response = array(
          "result" => $token
        );

        header('HTTP/1.1 200 Success');
        echo json_encode($response);
        exit();
      }

      if($urlData[1] == "status"){
        // [GET] api/auth/telegram/status/<token>

        if($method!='GET'){
          header('HTTP/1.1 405 Method Not Allowed');
          exit();
        }

        $token = $urlData[2];

        $sql = "SELECT * FROM `telegram_tokens` WHERE `token`='$token'";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) == 0){
          $response = array(
            "message" => "Token not found!"
          );

          header('HTTP/1.1 404 Not found');
          echo json_encode($response);
          exit();
        }

        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

        if($row['status'] == "created" OR $row['status'] == "expired"){
          $token_data = array(
            "token" => $token,
            "status" => $row['status'],
            "creation_date" => $row['creation_date']
          );

          $response = array(
            "result" => $token_data
          );

          header('HTTP/1.1 200 Success');
          echo json_encode($response);
          exit();
        }else if($row['status'] == "veirifed"){
          $sql = "UPDATE `telegram_tokens` SET `status`='expired' WHERE `token`='$token'";
          mysqli_query($dbc, $sql);

          $user_id = $row['veirifed_by'];

          $sql = "SELECT * FROM `tokens` WHERE `user_id`='$user_id' AND `type`='user'";
          $tokens_res = mysqli_query($dbc, $sql);

          $auth_token_data = mysqli_fetch_array($tokens_res, MYSQLI_ASSOC);

          $token_data = array(
            "token" => $token,
            "status" => $row['status'],
            "creation_date" => $row['creation_date'],
            "data" => array(
              "user_id" => $user_id,
              "token" => $auth_token_data['token']
            )
          );

          $response = array(
            "result" => $token_data
          );

          header('HTTP/1.1 200 Success');
          echo json_encode($response);
          exit();
        }
      }
  }

  if($urlData[0] == "admin"){

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

    if(mysqli_num_rows($result) < 1){
      header('HTTP/1.0 409 Unauthoraized');
      echo json_encode(array(
        'message' => 'Incorrect username or password!'
      ));
      exit();
    }

    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

    if(!password_verify($password, $row['password_hash'])){
      header('HTTP/1.0 409 Unauthoraized');
      echo json_encode(array(
        'message' => 'Incorrect username or password!'
      ));
      exit();
    }

    $admin_id = $row['id'];

    $sql = "SELECT * FROM `tokens` WHERE `user_id`='$admin_id' AND `type`='admin'";
    $result = mysqli_query($dbc, $sql);

    if(mysqli_num_rows($result) < 1){
      // если у пользователя нет токена - создаем
      $auth_token = generateRandomString(20);

      $sql = "INSERT INTO `tokens`(`user_id`, `token`, `type`) VALUES ('$admin_id','$auth_token', 'admin')";
      mysqli_query($dbc, $sql);
    }else{
      $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

      $auth_token = $row['token'];
    }

    $response = array(
      "result" => array(
        "user_id" => $admin_id,
        "token" => $auth_token
      )
    );
    
    header('HTTP/1.0 200 Success');
    echo json_encode($response);
    exit();
  }

}

function get_ip(){
	$value = '';
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$value = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$value = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} elseif (!empty($_SERVER['REMOTE_ADDR'])) {
		$value = $_SERVER['REMOTE_ADDR'];
	}
  
	return $value;
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
