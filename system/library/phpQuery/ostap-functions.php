<?php
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

/*
function shutdown()
{
    $time = date('H-i-s');
    $stop = microtime(true) - START_TIME;
    file_put_contents(DIR_LOGS . 'shutdown_log.txt', 'Выполнили функцию shutdown!'.PHP_EOL, FILE_APPEND);
    $mem = 'Скушано памяти: ' . (memory_get_usage() - START_MEMORY) . ' байт';
    file_put_contents(DIR_LOGS . 'shutdown_log.txt', $mem.PHP_EOL, FILE_APPEND);
    file_put_contents(DIR_LOGS . 'time_stop_log.txt', $time . '|' . $stop.PHP_EOL, FILE_APPEND);
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
    echo $err;
    file_put_contents(LOG_DIR . '/sig_handler_log.txt', $err.PHP_EOL);
    exit;
}
*/

function addCategory($data) {
    $this->db->query("INSERT INTO " . DB_PREFIX . "category SET parent_id = '" . (int)$data['parent_id'] . "', `top` = '" . (isset($data['top']) ? (int)$data['top'] : 0) . "', `column` = '" . (int)$data['column'] . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', noindex = '" . (int)$data['noindex'] . "', date_modified = NOW(), date_added = NOW(), `is_tag` = '" . (isset($data['is_tag']) ? (int)$data['is_tag'] : 0) . "'");
    
    $category_id = $this->db->getLastId();
    
    if (isset($data['image'])) {
        $this->db->query("UPDATE " . DB_PREFIX . "category SET image = '" . $this->db->escape($data['image']) . "' WHERE category_id = '" . (int)$category_id . "'");
    }
    
    //замена слов в названии товара в зависимости от категории
    if (isset($data['replacement'])) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "category_replacement_rules SET category_id = '" . (int)$category_id . "', replacement = '" . $this->db->escape($data['replacement']) . "'");
    }
    //замена слов в названии товара в зависимости от категории КОНЕЦ
    
    foreach ($data['category_description'] as $language_id => $value) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int)$category_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
    }
    
    // MySQL Hierarchical Data Closure Table Pattern
    $level = 0;
    
    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$data['parent_id'] . "' ORDER BY `level` ASC");
    
    foreach ($query->rows as $result) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$result['path_id'] . "', `level` = '" . (int)$level . "'");
        
        $level++;
    }
    
    $this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$category_id . "', `level` = '" . (int)$level . "'");
    
    if (isset($data['category_filter'])) {
        foreach ($data['category_filter'] as $filter_id) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "category_filter SET category_id = '" . (int)$category_id . "', filter_id = '" . (int)$filter_id . "'");
        }
    }
    
    if (isset($data['category_store'])) {
        foreach ($data['category_store'] as $store_id) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "'");
        }
    }
    
    if (isset($data['category_seo_url'])) {
        foreach ($data['category_seo_url'] as $store_id => $language) {
            foreach ($language as $language_id => $keyword) {
                if (!empty($keyword)) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'category_id=" . (int)$category_id . "', keyword = '" . $this->db->escape($keyword) . "'");
                }
            }
        }
    }
    
    if (isset($data['product_related'])) {
        foreach ($data['product_related'] as $related_id) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_related_wb SET category_id = '" . (int)$category_id . "', product_id = '" . (int)$related_id . "'");
        }
    }
    
    if (isset($data['article_related'])) {
        foreach ($data['article_related'] as $related_id) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "article_related_wb SET category_id = '" . (int)$category_id . "', article_id = '" . (int)$related_id . "'");
        }
    }
    
    // Set which layout to use with this category
    if (isset($data['category_layout'])) {
        foreach ($data['category_layout'] as $store_id => $layout_id) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_layout SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
        }
    }
    
    $this->cache->delete('category');
    
    if($this->config->get('config_seo_pro')){
        $this->cache->delete('seopro');
    }
    
    return $category_id;
}
