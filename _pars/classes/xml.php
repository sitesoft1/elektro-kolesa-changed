<?php
set_time_limit(0);//snimaem ogranicheniya na vipolneniya skripta
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/db.php';

class XML
{
    public $xml_url;
    public $xml;
    public $xml_catalog;
    public $xml_status;
   
   public function __construct($xml_url)
   {
       $this->xml_url = $xml_url;
       //poluchim XML fail dla raboti
       if($this->xml = file_get_contents($xml_url)){
           $this->xml_catalog = new SimpleXMLElement($this->xml);
           $this->xml_status = true;
       }else{
           show_strong("Не удалось загрузить xml файл по ссылке $xml_url");
           $this->xml_status = false;
           //die("Выполнение скрипта было остановлено!!!");
       }
       
       
   }
}