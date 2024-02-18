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

    if($urlData[0] == "methods"){

        if(!isset($urlData[1])){
            // [GET] api/payment/methods

            if($method!='GET'){
                header('HTTP/1.1 405 Method Not Allowed');
                exit();
            }

            $sql = "SELECT * FROM `payment_methods` ORDER BY `id` ASC";
            $result = mysqli_query($dbc, $sql);

            $methods_list = array();

            while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
                $method = array(
                    "id" => $row['id'],
                    "name" => $row['name'],
                    "description" => $row['description'],
                    "image" => $APP_ADDRESS . "uploads/" . $row['image']
                );

                array_push($methods_list, $method);
            }

            $response = array(
                "result" => $methods_list
            );
            
            header('HTTP/1.1 200 Success');
            echo json_encode($response);
            exit();
        }

        if($urlData[1] == "create"){
            // [POST] api/payment/methods/create

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

            if(empty($formData -> name)){
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

            $method_name = $formData -> name;
            $method_description = $formData -> description;

            $sql = "SELECT * FROM `payment_methods` WHERE `name`='$method_name'";
            $result = mysqli_query($dbc, $sql);

            if(mysqli_num_rows($result) != 0){
                header('HTTP/1.1 409 Conflict');
                echo json_encode(array(
                    'message' => 'Name already taken!'
                ));
                exit();
            }

            $sql = "INSERT INTO `payment_methods`(`name`, `description`, `image`) VALUES ('$method_name','$method_description','default.png')";
            mysqli_query($dbc, $sql);

            $sql = "SELECT * FROM `payment_methods` WHERE `name`='$method_name'";
            $result = mysqli_query($dbc, $sql);
            // получаем id метода в системе

            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $method_id = $row['id'];

            $response = array(
                "result" => $method_id
            );

            header('HTTP/1.0 201 Created');
            echo json_encode($response);
            exit();
        }

        if($urlData[1] == "picture"){
            // [POST] api/payment/methods/picture/<method_id>

            if($token_type != "admin"){
                header('HTTP/1.1 403 Forbidden');
                echo json_encode(array(
                    'message' => 'You dont have access!'
                ));
                exit();
            }

            if($method != 'POST'){
                header('HTTP/1.0 405 Method Not Allowed');
                exit();
            } 

            $method_id = $urlData[2];

            $sql = "SELECT * FROM `payment_methods` WHERE `id`='$method_id'";
            $result = mysqli_query($dbc, $sql);

            if(mysqli_num_rows($result) == 0){
                header('HTTP/1.0 404 Not found');
                echo json_encode(array(
                    'message' => 'Payment method not found!'
                ));
                exit();
            }

            if (isset($_FILES['image'])) {
                $image = $_FILES['image'];
                // Получаем нужные элементы массива "image"
                $fileTmpName = $_FILES['image']['tmp_name'];
                $errorCode = $_FILES['image']['error'];
                // Проверим на ошибки
                if ($errorCode !== UPLOAD_ERR_OK || !is_uploaded_file($fileTmpName)) {
                    // Массив с названиями ошибок
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE   => 'Размер файла превысил значение upload_max_filesize в конфигурации PHP.',
                        UPLOAD_ERR_FORM_SIZE  => 'Размер загружаемого файла превысил значение MAX_FILE_SIZE в HTML-форме.',
                        UPLOAD_ERR_PARTIAL    => 'Загружаемый файл был получен только частично.',
                        UPLOAD_ERR_NO_FILE    => 'Файл не был загружен.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка.',
                        UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск.',
                        UPLOAD_ERR_EXTENSION  => 'PHP-расширение остановило загрузку файла.',
                    ];
                    // Зададим неизвестную ошибку
                    $unknownMessage = 'При загрузке файла произошла неизвестная ошибка.';
                    // Если в массиве нет кода ошибки, скажем, что ошибка неизвестна
                    $outputMessage = isset($errorMessages[$errorCode]) ? $errorMessages[$errorCode] : $unknownMessage;
                    // Выведем название ошибки
                    die($outputMessage);
                } else {
                    // Создадим ресурс FileInfo
                    $fi = finfo_open(FILEINFO_MIME_TYPE);

                    // Получим MIME-тип
                    $mime = (string) finfo_file($fi, $fileTmpName);

                    // Проверим ключевое слово image (image/jpeg, image/png и т. д.)
                    if (strpos($mime, 'image') === false) die('Можно загружать только изображения.');

                    // Результат функции запишем в переменную
                    $image = getimagesize($fileTmpName);

                    // Зададим ограничения для картинок
                    $limitBytes  = 1024 * 1024 * 10;
                    $limitWidth  = 10000;
                    $limitHeight = 10000;

                    // Проверим нужные параметры
                    if (filesize($fileTmpName) > $limitBytes) die('Размер изображения не должен превышать 10 Мбайт.');
                    if ($image[1] > $limitHeight)             die('Высота изображения не должна превышать 10000 точек.');
                    if ($image[0] > $limitWidth)              die('Ширина изображения не должна превышать 10000 точек.');

                    // Сгенерируем новое имя файла на основе MD5-хеша
                    $name = md5_file($fileTmpName);

                    // Сгенерируем расширение файла на основе типа картинки
                    $extension = image_type_to_extension($image[2]);

                    // Сократим .jpeg до .jpg
                    $format = str_replace('jpeg', 'jpg', $extension);

                    // Переместим картинку с новым именем и расширением в папку /upload
                    if (!move_uploaded_file($fileTmpName, __DIR__ . '/../uploads/' . $name . $format)) {
                        die('При записи изображения на диск произошла ошибка.');
                    }

                    $image= $name . $format;

                    $sql = "UPDATE `payment_methods` SET `image`='$image' WHERE `id`='$method_id'";
                    mysqli_query($dbc, $sql);

                    header('HTTP/1.0 200 Success');
                    exit();
                }
            }else{
                header('HTTP/1.0 400 Bad request');
                echo json_encode(array(
                    'message' => 'Image not selected!'
                ));
                exit();
            }
        }
    }

    if($urlData[0] == "requests"){
        if(!isset($urlData[1])){
            // [GET] api/payment/requests

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
    
            $sql = "SELECT * FROM `payments` ORDER BY `last_update` DESC";
            $result = mysqli_query($dbc, $sql);
    
            $payments_list = array();
    
            while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
                $method_id = $row['payment_method_id'];
    
                $sql = "SELECT * FROM `payment_methods` WHERE `id`='$method_id'";
                $res = mysqli_query($dbc, $sql);
    
                $method_data = mysqli_fetch_array($res, MYSQLI_ASSOC);
    
                $payment = array(
                    "id" => $row['id'],
                    "user_id" => $row['user_id'],
                    "amount" => $row['amount'],
                    "data" => $row['data'],
                    "status" => $row['status'],
                    "method" => $method_data['name'],
                    "creation_date" => $row['creation_date'],
                    "last_update" => $row['last_update']
                );
    
                array_push($payments_list, $payment);
            }
    
            $response = array(
                "result" => $payments_list
            );
    
            header('HTTP/1.1 200 Success');
            echo json_encode($response);
            exit();
        }

        if($urlData[1] == "update"){
            // [POST] api/payment/requests/update

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

            if(empty($formData -> request_id)){
                header('HTTP/1.0 400 Bad request');
                echo json_encode(array(
                    'message' => 'Request id is empty'
                ));
                exit();
            }

            if(empty($formData -> status)){
                header('HTTP/1.0 400 Bad request');
                echo json_encode(array(
                    'message' => 'Status is empty'
                ));
                exit();
            }

            $request_id = $formData -> request_id;
            $status = $formData -> status;
            $message = empty($formData -> message) ? "-" : $formData -> message;

            $sql = "SELECT * FROM `payments` WHERE `id`='$request_id'";
            $result = mysqli_query($dbc, $sql);

            if(mysqli_num_rows($result) == 0){
                header('HTTP/1.1 404 Not found');
                echo json_encode(array(
                    'message' => 'Request not found!'
                ));
                exit();
            }

            $sql = "UPDATE `payments` SET `status`='$status', `message`='$message', `last_update`=NOW() WHERE `id`='$request_id'";
            mysqli_query($dbc, $sql);

            $sql = "INSERT INTO `payments_log`(`payment_id`, `date`, `text`) VALUES ('$request_id',NOW(),'Изменён статус заявки на $status')";
            mysqli_query($dbc, $sql);

            header('HTTP/1.1 200 Success');
            exit();
        }
    }

    if($urlData[0] == "status"){
        if($token_type != "user"){
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(array(
                'message' => 'Only for users!'
            ));
            exit();
        }
    
        if($method!='GET'){
            header('HTTP/1.1 405 Method Not Allowed');
            exit();
        }

        $filter = isset($urlData[1]) ? $urlData[1] : "all";

        $sql = "SELECT * FROM `payments` WHERE `user_id`='$user_id' ORDER BY `creation_date` ASC";
        $result = mysqli_query($dbc, $sql);
        
        $payments = array();

        $payments_list = array();
    
        while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
            $method_id = $row['payment_method_id'];
    
            $sql = "SELECT * FROM `payment_methods` WHERE `id`='$method_id'";
            $res = mysqli_query($dbc, $sql);
    
            $method_data = mysqli_fetch_array($res, MYSQLI_ASSOC);
    
            $payment = array(
                "id" => $row['id'],
                "user_id" => $row['user_id'],
                "amount" => $row['amount'],
                "data" => $row['data'],
                "status" => $row['status'],
                "method" => $method_data['name'],
                "creation_date" => $row['creation_date'],
                "last_update" => $row['last_update']
            );
    
            array_push($payments_list, $payment);
        }

        $response = array(
            "result" => $payments_list
        );
    
        header('HTTP/1.1 200 Success');
        echo json_encode($response);
        exit();
    }

    if(!isset($urlData[0])){
        // [POST] api/payment

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

        if(empty($formData -> amount)){
            header('HTTP/1.0 400 Bad request');
            echo json_encode(array(
                'message' => 'Amount is empty'
            ));
            exit();
        }

        if(empty($formData -> method_id)){
            header('HTTP/1.0 400 Bad request');
            echo json_encode(array(
                'message' => 'Method id is empty'
            ));
            exit();
        }

        if(empty($formData -> data)){
            header('HTTP/1.0 400 Bad request');
            echo json_encode(array(
                'message' => 'Data is empty'
            ));
            exit();
        }

        $amount = $formData -> amount;
        $method_id = $formData -> method_id;
        $data = $formData -> data;

        $sql = "SELECT * FROM `balance` WHERE `user_id`='$user_id'";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) == 0){
            //баланс не создан, т.е. 0
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(array(
                'message' => 'Insufficient balance'
            ));
            exit();
        }

        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        
        if($amount > $row['value']){
            // баланс не достаточен
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(array(
                'message' => 'Insufficient balance'
            ));
            exit();
        }

        $sql = "SELECT * FROM `payment_methods` WHERE `id`='$method_id'";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) == 0){
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(array(
                'message' => 'Invalid method id!'
            ));
            exit();
        }

        $sql = "INSERT INTO `payments`(`user_id`, `amount`, `data`, `payment_method_id`, `status`, `message`, `creation_date`, `last_update`) VALUES ('$user_id','$amount','$data','$method_id','created','-',NOW(),NOW())";
        mysqli_query($dbc, $sql);

        $sql = "SELECT * FROM `payments` WHERE `user_id`='$user_id' AND`creation_date`>NOW() - INTERVAL 5 SECOND";
        $result = mysqli_query($dbc, $sql);
        // получаем ID созданного платежа

        $created_payment_data = mysqli_fetch_array($result, MYSQLI_ASSOC);

        $created_payment_id = $created_payment_data['id'];

        $sql = "INSERT INTO `payments_log`(`payment_id`, `date`, `text`) VALUES ('$created_payment_id',NOW(),'Заявка на вывод создана')";
        mysqli_query($dbc, $sql);

        $sql = "UPDATE `balance` SET `value`=`value`-$amount WHERE `user_id`='$user_id'";
        mysqli_query($dbc, $sql);

        $sql = "INSERT INTO `transactions`(`user_id`, `type`, `advanced_type`, `amount`, `date`) VALUES ('$user_id','withdrawal','withdrawal','$amount',NOW())";
        mysqli_query($dbc, $sql);

        header('HTTP/1.1 201 Created');
        exit();
    }
}
?>