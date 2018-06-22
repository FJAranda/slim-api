<?php
class DbConnect {
 
    private $conn;
 
    function __construct() {        
    }
 
    /**
     * Establishing database connection
     * @return database connection handler
     */
    public function connect() {
        include_once 'config.php';
        $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME;
        try{
        $this->conn = new PDO($dsn, DB_USERNAME, DB_PASSWORD, array('charset'=>'utf8'));
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->conn->query('SET CHARACTER SET utf8');
        return $this->conn;
        } catch (PDOException $e){
            echo 'Error al conectar a la base de datos: '.$e->getCode().': '. $e->getMessage();
        }
    }
    
    public function closeConnection(){
        $this->conn = null;
    }
 
}

