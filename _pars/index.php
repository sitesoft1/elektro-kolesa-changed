<?php
require_once __DIR__.'/functions.php';
require_once __DIR__.'/lib/phpQuery-onefile.php';
require_once __DIR__.'/classes/db.php';

//Подключение к базе
$db = new Db();

//Парсер
//зададим необходимые переменные
$domain = 'https://eko-bike.ru/';
$sleep = 1;
$url = 'https://eko-bike.ru/sigvei/';
//$sub_categories = 'div.menu_tags ul.menu_group--categories_verh > li.menu_group__item--2 > ul.menu_group--categories_verh > li.categories_menu__item menu_group__item--3 > a.categories_menu__link';
$sub_categories_a = '.menu_tags .menu_hide a';
//$sub_categories_button = '.menu_tags button';
$sub_categories_button = '.menu_tags .menu_hide button';

//начнем парсинг
$file = file_get_contents($url);

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
dump($SubCategoriesLinks);

sleep($sleep);

//find all pagination buttons
$SubCategoriesButtons = [];
$WhatFind = $html->find($sub_categories_button);
foreach ($WhatFind as $element) {
    $pq = pq($element); // pq() - Это аналог $ в jQuery
    $href = $pq->attr('value');
    if(!empty($href) and strlen($href)>21){
        $SubCategoriesButtons[] = array(
            'name' => trim($pq->text()),
            'href' => $href
        );
    }
}
dump($SubCategoriesButtons);
