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

        if($method!='GET'){
            header('HTTP/1.1 405 Method Not Allowed');
            exit();
        }

        $sql = "SELECT * FROM `partners` ORDER BY `id` ASC";
        $result = mysqli_query($dbc, $sql);

        $partners_list = array();

        while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
            $partner = array(
                "id" => $row['id'],
                "name" => $row['name'],
                "description" => $row['description'],
                "link" => $row['link'],
                "logo_image" => $APP_ADDRESS . "uploads/" . $row['logo_image'],
                "background_image" => $APP_ADDRESS . "uploads/" . $row['background_image']
            );

            array_push($partners_list, $partner);
        }

        $response = array(
            "result" => $partners_list
        );
        
        header('HTTP/1.1 200 Success');
        echo json_encode($response);
        exit();
    }

    if($urlData[0] == "create"){

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

        if(empty($formData -> link)){
            header('HTTP/1.0 400 Bad request');
            echo json_encode(array(
                'message' => 'Link is empty'
            ));
            exit();
        }

        $partner_name = $formData -> name;
        $partner_description = $formData -> description;
        $partner_link = $formData -> link;

        if(!filter_var($partner_link, FILTER_VALIDATE_URL)){
            header('HTTP/1.0 400 Bad request');
            echo json_encode(array(
                'message' => 'Incorrect link'
            ));
            exit();
        }

        $sql = "SELECT * FROM `partners` WHERE `name`='$partner_name' OR `link`='$partner_link'";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) > 0){
            header('HTTP/1.0 409 Conflict');
            echo json_encode(array(
                'message' => 'Name or link not unique!'
            ));
            exit();
        }

        $sql = "INSERT INTO `partners`(`name`, `description`, `link`, `logo_image`, `background_image`) VALUES ('$partner_name','$partner_description','$partner_link','partner_default_logo.png','partner_default_background.png')";
        mysqli_query($dbc, $sql);

        $sql = "SELECT * FROM `partners` WHERE `link`='$partner_link'";
        $result = mysqli_query($dbc, $sql);
        // получаем id пользователя в системе

        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $partner_id = $row['id'];

        $response = array(
            "result" => $partner_id
        );

        header('HTTP/1.0 201 Created');
        echo json_encode($response);
        exit();
    }

    if($urlData[0] == "picture"){
        // [POST] api/partners/picture/<partner_id>

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

        $partner_id = $urlData[1];

        $sql = "SELECT * FROM `partners` WHERE `id`='$partner_id'";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) == 0){
            header('HTTP/1.0 404 Not found');
            echo json_encode(array(
                'message' => 'Partner not found!'
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

                $sql = "UPDATE `partners` SET `logo_image`='$image' WHERE `id`='$partner_id'";
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

    if($urlData[0] == "background"){
        // [POST] api/partners/background/<partner_id>

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

        $partner_id = $urlData[1];

        $sql = "SELECT * FROM `partners` WHERE `id`='$partner_id'";
        $result = mysqli_query($dbc, $sql);

        if(mysqli_num_rows($result) == 0){
            header('HTTP/1.0 404 Not found');
            echo json_encode(array(
                'message' => 'Partner not found!'
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

                $sql = "UPDATE `partners` SET `background_image`='$image' WHERE `id`='$partner_id'";
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
?>