<?php
set_time_limit(0);//snimaem ogranicheniya na vipolneniya skripta
//define('LANGUAGE_ID', 1);
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/../functions.php';

class Db
{
    
    public $db;
    
    public function __construct()
    {
        require_once __DIR__ . '/../../config.php';
        //DB CONNECT
        $this->db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        if ($this->db->connect_errno) {
            echo "Не удалось подключиться к MySQL: (" . $this->db->connect_errno . ") " . $this->db->connect_error;
        }else{
            echo "Подключение к базе прошло успешно!";
        }
        ///$this->db->set_charset(DB_CHARSET);
        //DB CONNECT END
        
        //$this->db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        //$this->config = new Config();
    }
    
    public function query($sql)
    {
        
        if (!$result = $this->db->query($sql)) {
            echo "Не удалось выполнить запрос: $sql <br>";
            echo "Номер ошибки: " . $this->db->errno . "\n";
            echo "Ошибка: " . $this->db->error . "\n";
            return false;
        }
        
        if ($result->num_rows > 0) {
            return $result;
        } else {
            echo "Функция query по данным: <br> $sql <br> - mysql вернула пустой результат! <br><hr>";
            return false;
        }
        
    }
    
    public function query_assoc($sql, $row_filed)
    {
        
        if (!$result = $this->db->query($sql)) {
            echo "Не удалось выполнить запрос: $sql <br>";
            echo "Номер ошибки: " . $this->db->errno . "\n";
            echo "Ошибка: " . $this->db->error . "\n";
            return false;
        }
        
        if ($result->num_rows > 0) {
            $row = mysqli_fetch_assoc($result);
            return $row[$row_filed];
        } else {
            //echo "Функция query_assoc по данным: <br> $sql <br> $row_filed <br> - mysql вернула пустой результат! <br><hr>";
            return false;
        }
        
    }
    
    public function query_insert($sql)
    {
        if (!$result = $this->db->query($sql)) {
            echo "Не удалось выполнить запрос: (" . $this->db->errno . ") " . $this->db->error;
            echo "Номер ошибки: " . $this->db->errno . "\n";
            echo "Ошибка: " . $this->db->error . "\n";
            return false;
        }else{
            echo "Запрос <br> $sql <br> - выполнен удачно! <br><hr>";
            return true;
        }
    
    }
    
    function query_insert_id($sql)
    {
        if (!$result = $this->db->query($sql)) {
            echo "Не удалось выполнить запрос: (" . $this->db->errno . ") " . $this->db->error;
            echo "Номер ошибки: " . $this->db->errno . "\n";
            echo "Ошибка: " . $this->db->error . "\n";
            return false;
        }else{
            echo "Запрос <br> $sql <br> - выполнен удачно! <br><hr>";
            return $this->db->insert_id;
            //return mysqli_insert_id($this->db);
        }
        
    }
    
    public function query_update($sql)
    {
        if (!$result = $this->db->query($sql)) {
            echo "Не удалось выполнить запрос: (" . $this->db->errno . ") " . $this->db->error;
            echo "Номер ошибки: " . $this->db->errno . "\n";
            echo "Ошибка: " . $this->db->error . "\n";
            return false;
        }else{
            echo "Запрос <br> $sql <br> - выполнен удачно! <br><hr>";
            return true;
        }
        
    }
    
    public function query_delete($sql)
    {
        if (!$result = $this->db->query($sql)) {
            echo "Не удалось выполнить запрос: (" . $this->db->errno . ") " . $this->db->error;
            echo "Номер ошибки: " . $this->db->errno . "\n";
            echo "Ошибка: " . $this->db->error . "\n";
            return false;
        }else{
            echo "Запрос <br> $sql <br> - выполнен удачно! <br><hr>";
            return true;
        }
        
    }
    




    
   }