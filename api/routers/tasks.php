<?php
function route($method, $urlData, $formData){

    include 'config.php';

    $is_error=false;
    $response=array();

    if(!isset($urlData[0])){

        if($method!='GET'){
            header('HTTP/1.1 405 Method Not Allowed');
            exit();
        }

        $sql = "SELECT * FROM `tasks` ORDER BY `id` ASC";
        $result = mysqli_query($dbc, $sql);

        $tasks_list = array();

        while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
            $app_id = $row['app_id'];
            $partner_id = $row['partner_id'];

            $sql = "SELECT * FROM `apps` WHERE `id`='$app_id'";
            $app_res = mysqli_query($dbc, $sql);
            $app_data = mysqli_fetch_array($app_res, MYSQLI_ASSOC);

            $sql = "SELECT * FROM `partners` WHERE `id`='$partner_id'";
            $partner_res = mysqli_query($dbc, $sql);
            $partner_data = mysqli_fetch_array($partner_res, MYSQLI_ASSOC);

            $task = array(
                "id" => $row['id'],
                "title" => $row['title'],
                "description" => $row['description'],
                "is_recommended" => $row['is_recommended'],
                "reward" => $row['reward'],
                "app" => array(
                    "id" => $app_data['id'],
                    "name" => $app_data['name'],
                    "image" => $APP_ADDRESS . "uploads/" . $app_data['image']
                ),
                "partner" => array(
                    "id" => $partner_data['id'],
                    "name" => $partner_data['name'],
                    "description" => $partner_data['description'],
                    "link" => $partner_data['link'],
                    "logo_image" => $APP_ADDRESS . "uploads/" . $partner_data['logo_image'],
                    "background_image" => $APP_ADDRESS . "uploads/" . $partner_data['background_image']
                )
            );

            array_push($tasks_list, $task);
        }

        $response = array(
            "result" => $tasks_list
        );
        
        header('HTTP/1.1 200 Success');
        echo json_encode($response);
        exit();
    }

    if($urlData[0] == "create"){
         $headers = apache_request_headers();

        if (!isset($headers['auth'])){
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(array(
                'message' => 'You need authorization'
            ));
            exit();
        }

        include "token_validation.php";
        
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

        if(empty($formData -> title)){
            header('HTTP/1.0 400 Bad request');
            echo json_encode(array(
                'message' => 'Name is empty'
            ));
            exit();
        }

        if(empty($formData -> description)){
            header('HTTP/1.0 400 Bad request');
            echo json_encode(array(
                'message' => 'Description is empty'
            ));
            exit();
        }

        if(empty($formData -> partner_id)){
            header('HTTP/1.0 400 Bad request');
            echo json_encode(array(
                'message' => 'Partner id is empty'
            ));
            exit();
        }

        if(empty($formData -> app_id)){
            header('HTTP/1.0 400 Bad request');
            echo json_encode(array(
                'message' => 'App id is empty'
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

        $title = $formData -> title;
        $description = $formData -> description;
        $is_recommended = empty($formData -> is_recommended) ? 0 : $formData -> is_recommended;
        $partner_id = $formData -> partner_id;
        $app_id = $formData -> app_id;
        $reward = $formData -> reward;

        $sql = "SELECT * FROM `apps` WHERE `id`='$app_id'";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) == 0){
            header('HTTP/1.0 400 Bad request');
            echo json_encode(array(
                'message' => 'App not found!'
            ));
            exit();
        }

        $sql = "SELECT * FROM `partners` WHERE `id`='$partner_id'";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) == 0){
            header('HTTP/1.0 400 Bad request');
            echo json_encode(array(
                'message' => 'Partner not found!'
            ));
            exit();
        }

        $sql = "INSERT INTO `tasks`(`title`, `description`, `is_recommended`, `partner_id`, `app_id`, `reward`) VALUES ('$title','$description','$is_recommended','$partner_id','$app_id','$reward')";
        mysqli_query($dbc, $sql);

        header('HTTP/1.0 201 Created');
        exit();
    }

    if($urlData[0] == "recommended"){
        if($method!='GET'){
            header('HTTP/1.1 405 Method Not Allowed');
            exit();
        }

        $sql = "SELECT * FROM `tasks` WHERE `is_recommended`='1' ORDER BY `id` ASC";
        $result = mysqli_query($dbc, $sql);

        $tasks_list = array();

        while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
            $app_id = $row['app_id'];
            $partner_id = $row['partner_id'];

            $sql = "SELECT * FROM `apps` WHERE `id`='$app_id'";
            $app_res = mysqli_query($dbc, $sql);
            $app_data = mysqli_fetch_array($app_res, MYSQLI_ASSOC);

            $sql = "SELECT * FROM `partners` WHERE `id`='$partner_id'";
            $partner_res = mysqli_query($dbc, $sql);
            $partner_data = mysqli_fetch_array($partner_res, MYSQLI_ASSOC);

            $task = array(
                "id" => $row['id'],
                "title" => $row['title'],
                "description" => $row['description'],
                "is_recommended" => $row['is_recommended'],
                "reward" => $row['reward'],
                "app" => array(
                    "id" => $app_data['id'],
                    "name" => $app_data['name'],
                    "image" => $APP_ADDRESS . "uploads/" . $app_data['image']
                ),
                "partner" => array(
                    "id" => $partner_data['id'],
                    "name" => $partner_data['name'],
                    "description" => $partner_data['description'],
                    "link" => $partner_data['link'],
                    "logo_image" => $APP_ADDRESS . "uploads/" . $partner_data['logo_image'],
                    "background_image" => $APP_ADDRESS . "uploads/" . $partner_data['background_image']
                )
            );

            array_push($tasks_list, $task);
        }

        $response = array(
            "result" => $tasks_list
        );
        
        header('HTTP/1.1 200 Success');
        echo json_encode($response);
        exit();
    }
}
?>