<?php
set_time_limit(0);//snimaem ogranicheniya na vipolneniya skripta
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

function dump($data)
{
    echo '<pre>'.PHP_EOL;
    var_dump($data);
    echo '<br><hr>'.PHP_EOL;
}

function show($data)
{
    echo $data.PHP_EOL;
    echo '<br><hr>'.PHP_EOL;
}

function show_strong($data)
{
    echo '<strong>'.$data.'</strong>'.PHP_EOL;
    echo '<br><hr>'.PHP_EOL;
}

function my_mb_ucfirst($str) {
    $fc = mb_strtoupper(mb_substr($str, 0, 1));
    return $fc.mb_substr($str, 1);
}

function copy_image_to_store($picture, $offer_id, $shipper_id, $img_cnt, $image_path, $image_path_to_databaze){
    //sozdadim direktoriyu esli net
    if(!file_exists($image_path)){
        mkdir($image_path);
    }
    
    //put do kartinok offera
    $shipper_path = $image_path . '/' . $shipper_id;
    if(!file_exists($shipper_path)){
        mkdir($shipper_path);
    }
    $path = $image_path . '/' . $shipper_id . '/' . $offer_id;
    if(!file_exists($path)){
        mkdir($path);
    }
    //sozdadim direktoriyu esli net END
    
    //ubedimsa chto put img eto stroka
    $picture = strval($picture);
    //poluchim tolko samo nazvaniye kartinki
    $img_name = $img_cnt . '-' . basename($picture);
    //proverka na kirrilicu
    if( mb_detect_encoding($img_name) == "ASCII" ){
        //VSE OK KIRRILICI NET
        $new_picture = $path . '/' . $img_name;
        //Kopiruem
        if(!file_exists($new_picture)){
            if(!copy($picture, $new_picture)) {
                echo "не удалось скопировать $picture в $new_picture ...\n";
                return false;
            }
        }
        //Kopiruem END
        $picture_to_database = $image_path_to_databaze . '/' . $shipper_id . '/' . $offer_id . '/' . $img_name;
        return $picture_to_database;
        
        //if est kirrilica
    }else{
        //EST KIRRILICA
        $new_picture = $path . '/' . $img_cnt . '.jpg';
        //Kopiruem
        if(!file_exists($new_picture)){
            if(!copy($picture, $new_picture)) {
                echo "не удалось скопировать $picture в $new_picture ...\n";
                return false;
            }
        }
        //Kopiruem END
        $picture_to_database = $image_path_to_databaze . '/' . $shipper_id . '/' . $offer_id . '/' . $img_cnt . '.jpg';
        return $picture_to_database;
        
    }
    //proverka na kirrilicu END
}

function translit($s) {
    $s = (string) $s; // преобразуем в строковое значение
    $s = strip_tags($s); // убираем HTML-теги
    $s = str_replace(array("\n", "\r"), " ", $s); // убираем перевод каретки
    $s = preg_replace("/\s+/", ' ', $s); // удаляем повторяющие пробелы
    $s = trim($s); // убираем пробелы в начале и конце строки
    $s = function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s); // переводим строку в нижний регистр (иногда надо задать локаль)
    $s = strtr($s, array('а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'j','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'shch','ы'=>'y','э'=>'e','ю'=>'yu','я'=>'ya','ъ'=>'','ь'=>''));
    $s = preg_replace("/[^0-9a-z-_ ]/i", "", $s); // очищаем строку от недопустимых символов
    $s = str_replace(" ", "-", $s); // заменяем пробелы знаком минус
    $s = mb_strimwidth($s,0,27);
    return $s; // возвращаем результат
}

function createPrice($offer_price, $xml_rate, $xml_markup)
{
    $rezult = $offer_price * $xml_rate * $xml_markup;
    $rezult = round($rezult);
    $rezult = (integer) $rezult;
    return $rezult;
}


function shutdown()
{
    echo 'Выполнили функцию shutdown!';
    //file_put_contents('/public_html/_xml/var/shutdown_log.txt', 'Выполнили функцию shutdown!', FILE_APPEND);
    file_put_contents(LOG_DIR . '/shutdown_log.txt', 'Выполнили функцию shutdown!'.PHP_EOL, FILE_APPEND);
    $err_arr = error_get_last();
    $err = 'type - '.$err_arr['type'] . ' | message - '. $err_arr['message'] . ' | file - '.$err_arr['file']. ' | line - '.$err_arr['line'];
    //global $offer_cnt;
    global $offer_name;
    global $offer_id;
    global $offer_available;
    global $product_id;
    $msg = $product_id.' | '.$offer_id.' | '.$offer_name.' | '.$offer_available;
    $time = date('H-i-s');
    $msg = $time.' | '.$msg;
    echo $msg;
    echo $err;
    //antre
    //file_put_contents('/public_html/_xml/var/shutdown_log.txt', $msg.PHP_EOL, FILE_APPEND);
    //file_put_contents('/public_html/_xml/var/shutdown_log.txt', $err, FILE_APPEND);
    //birka
    file_put_contents(LOG_DIR . '/shutdown_log.txt', $msg.PHP_EOL, FILE_APPEND);
    file_put_contents(LOG_DIR . '/shutdown_log.txt', $err, FILE_APPEND);
    
}

function shutdown_time()
{
    echo 'Выполнили функцию shutdown!';
    file_put_contents(__DIR__ . '/log/shutdown_log.txt', 'Выполнили функцию shutdown!'.PHP_EOL, FILE_APPEND);
    $err_arr = error_get_last();
    $err = 'type - '.$err_arr['type'] . ' | message - '. $err_arr['message'] . ' | file - '.$err_arr['file']. ' | line - '.$err_arr['line'];
    echo $err;
    file_put_contents(__DIR__ . '/log/shutdown_log.txt', $err, FILE_APPEND);
    
}

function sig_handler_time($signo)
{
    $info = "\n" . 'received signal ' . $signo . "\n";
    $info .= "\n" . 'Выполнили функцию sig_handler! ' . $signo . "\n";
    echo $info;
    //file_put_contents('/public_html/_xml/var/sig_handler_log.txt', 'Выполнили функцию sig_handler!', FILE_APPEND);
    file_put_contents(__DIR__ . '/log/sig_handler_log.txt', $info.PHP_EOL, FILE_APPEND);
    $err_arr = error_get_last();
    $err = 'type - '.$err_arr['type'] . ' | message - '. $err_arr['message'] . ' | file - '.$err_arr['file']. ' | line - '.$err_arr['line'];
    //global $offer_cnt;
    echo $err;
    file_put_contents(__DIR__ . '/log/sig_handler_log.txt', $err, FILE_APPEND);
    exit;
}

// обработчик сигнала
function sig_handler($signo)
{
    $info = "\n" . 'received signal ' . $signo . "\n";
    $info .= "\n" . 'Выполнили функцию sig_handler! ' . $signo . "\n";
    echo $info;
    //file_put_contents('/public_html/_xml/var/sig_handler_log.txt', 'Выполнили функцию sig_handler!', FILE_APPEND);
    file_put_contents(LOG_DIR . '/sig_handler_log.txt', $info.PHP_EOL, FILE_APPEND);
    $err_arr = error_get_last();
    $err = 'type - '.$err_arr['type'] . ' | message - '. $err_arr['message'] . ' | file - '.$err_arr['file']. ' | line - '.$err_arr['line'];
    //global $offer_cnt;
    global $offer_name;
    global $offer_id;
    global $offer_available;
    global $product_id;
    $time = date('H-i-s');
    $msg = $time.' | '.$product_id.' | '.$offer_id.' | '.$offer_name.' | '.$offer_available;
    echo $msg;
    echo $err;
    //antre
    //file_put_contents('/public_html/_xml/var/sig_handler_log.txt', $msg.PHP_EOL, FILE_APPEND);
    //file_put_contents('/public_html/_xml/var/sig_handler_log.txt', $err, FILE_APPEND);
    //birka
    file_put_contents(LOG_DIR . '/sig_handler_log.txt', $msg.PHP_EOL, FILE_APPEND);
    file_put_contents(LOG_DIR . '/sig_handler_log.txt', $err, FILE_APPEND);
    exit;
}
