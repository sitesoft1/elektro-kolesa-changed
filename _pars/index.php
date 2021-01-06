<?php
require_once __DIR__.'/functions.php';
require_once __DIR__.'/lib/phpQuery-onefile.php';
require_once __DIR__.'/classes/db.php';

//Подключение к базе
$db = new Db();

//Парсер
//зададим необходимые переменные
$store_id = 0;
if(isset($_GET['store_id']) and !empty($_GET['store_id'])){
    $store_id = $_GET['store_id'];
}
$domain = 'https://eko-bike.ru/';
$sleep = 1;
//$sub_categories = 'div.menu_tags ul.menu_group--categories_verh > li.menu_group__item--2 > ul.menu_group--categories_verh > li.categories_menu__item menu_group__item--3 > a.categories_menu__link';
$sub_categories_a = '.menu_tags .menu_hide a';
//$sub_categories_button = '.menu_tags button';
$sub_categories_button = '.menu_tags .menu_hide button';

//Получим данны от скрипта
if(isset($_GET['dn_id']) and !empty($_GET['dn_id'])){
    $dn_id = $_GET['dn_id'];
    dump($dn_id);
    
    //Получили параметр dn_id проэкта можно работать
    
    //Получим стартовую ссылку и категорию по умолчанию
    $start_link = $db->query_assoc("SELECT `start_link` FROM `oc_pars_setting` WHERE `dn_id`='$dn_id'", "start_link");
    dump($start_link);
    
    $cat_d = $db->query_assoc("SELECT `cat_d` FROM `oc_pars_prsetup` WHERE `dn_id`='$dn_id'", "cat_d");
    dump($cat_d);
    
    //начнем парсинг
    $file = file_get_contents($start_link);
    sleep($sleep);
    $html = phpQuery::newDocument($file);

    //find all pagination hrefs
    $SubCategoriesLinks = [];
    $WhatFind = $html->find($sub_categories_a);
    foreach ($WhatFind as $element) {
        $pq = pq($element); // pq() - Это аналог $ в jQuery
        $href = $pq->attr('href');
        if(!empty($href)){
            $SubCategoriesLinks[] = array(
                'name' => trim($pq->text()),
                'href' => $domain.$href
            );
        }
    }
    //dump($SubCategoriesLinks);
    

    //find all pagination buttons
    $WhatFind = $html->find($sub_categories_button);
    foreach ($WhatFind as $element) {
        $pq = pq($element); // pq() - Это аналог $ в jQuery
        $href = $pq->attr('value');
        if(!empty($href) and strlen($href)>21){
            $SubCategoriesLinks[] = array(
                'name' => trim($pq->text()),
                'href' => $href
            );
        }
    }
    dump($SubCategoriesLinks);
    
    
    foreach($SubCategoriesLinks as $SubCategory){
        $category_name = $SubCategory['name'];
        $category_id = $db->query_assoc("SELECT `category_id` FROM `oc_category_description` WHERE `category_id` IN(SELECT `category_id` FROM `oc_category_path` WHERE path_id='$cat_d' AND `category_id`<>'$cat_d') AND `language_id`='1' AND `name`='$category_name'", "category_id");
        dump($category_id);
        
        //Создадим категорию если она не существует и запишем ее в таблицу
        if($category_id){
        
        }else{
            $data = [];
            $data['parent_id'] = $cat_d;
            $data['top'] = 1;
            $data['top'] = 1;
        }
        //Создадим категорию если она не существует и запишем ее в таблицу КОНЕЦ
        
    }
    
    
    
    
}//end if


