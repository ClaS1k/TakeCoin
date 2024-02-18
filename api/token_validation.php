<?php
  //тут проверяем токен, если она задан
  $token=$headers['auth'];

  $sql = "SELECT * FROM `tokens` WHERE `token`='$token'";
  $result = mysqli_query($dbc, $sql);

  if(mysqli_num_rows($result)==0){
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(array(
        'message' => 'You need authorization'
    ));
    exit();
  }

  $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
  $token_type = $row['type'];
  $user_id = $row['user_id'];
?>
