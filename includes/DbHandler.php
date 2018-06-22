<?php
class DbHandler{
    
    private $conn;
    
    public function __construct() {
        require_once 'DbConnect.php';
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
    
    /*---------------- DOSSIERS METHODS -----------------*/
    
    public function createDossier($codAgencia, $nombreCliente, $password){
        require_once 'PassHash.php';
        $response = array();
        
        $password_hash = PassHash::hash($password);
        $token = $this->generateToken($password);
        
        try{
        $stmt = $this->conn->prepare('INSERT INTO dossiers (codagencia, nombrecliente, password, token, estado) values (:codagencia, :nombrecliente, :password, :token, "creado")');
        
        $result = $stmt->execute(array(':codagencia' => $codAgencia, 'nombrecliente' => $nombreCliente, ':password' => $password_hash, ':token' => $token));
        } catch (Exception $e){
            echo $e->getCode() . ': ' . $e->getMessage();
        }
        
        if ($result) {
            return DOSSIER_CREATED_SUCCESSFULLY;
        }else{
            return DOSSIER_CREATE_FAILED;
        }
        return $response;
    }
    
    public function login($coddossier, $password){
        $stmt = $this->conn->prepare('SELECT password FROM dossiers WHERE coddossier = :coddossier');
        $stmt->execute(array(':coddossier' => $coddossier));
        if ($stmt->rowCount() > 0) {
            if (PassHash::check_password($stmt->fetch()['password'], $password)) {
                return true;
            }else{
                return false;
            }
        }
        return false;
    }
    
    public function getDossierByCoddossier($coddossier) {
        try{
        $stmt = $this->conn->prepare('SELECT coddossier, codagencia, receptivo, tipodossier, destinos, nombrecliente,'
                . 'fechainicio, fechafin, vuelollegada, vuelosalida, horallegada, horasalida, adultos, ninos, bebes, '
                . 'guiaacompanante, token, responsable, estado FROM dossiers WHERE coddossier = :coddossier');
        
        if($stmt->execute(array('coddossier' => $coddossier))){
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        } catch (Exception $e){
            echo $e->getCode() . ': ' . $e->getMessage();
        }
    return $result;            
    }
    
    /*
    public function getTokenById($coddossier){
        $stmt->$this->conn->prepare('SELECT token FROM dossiers WHERE coddossier = ?');
        $stmt->bind_param('i', $coddossier);
        if ($stmt->execute()) {
            $token = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $token;
        }else{
            return null;
        }
    }
    
    */
    public function getCoddossierByToken($token){
        $stmt = $this->conn->prepare('SELECT coddossier FROM dossiers WHERE token = :token');
        if ($stmt->execute(array('token' => $token))) {
            $coddossier = $stmt->fetch()['coddossier'];
            return $coddossier;
        }else{
            return null;
        }
    }
    
    
    public function isValidToken($token){
        $stmt = $this->conn->prepare('SELECT coddossier FROM dossiers WHERE token = "'.$token.'"');
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
    
    //------------------------------------------- SERVICIOS METHODS -----------------------------------------------
    
    public function getServiciosByCodDossier($coddossier){
        try{
            $stmt = $this->conn->prepare('SELECT codservicio, servicios.codlugar, fechainicio, nombre, ciudad, tipo, latitud, '
                    . 'longitud FROM servicios INNER JOIN lugares WHERE coddossier = :coddossier AND servicios.codlugar'
                    . ' = lugares.codlugar');

            if($stmt->execute(array('coddossier' => $coddossier))){
                $result = $stmt->fetchAll(PDO::FETCH_NUM);
            }
        } catch (Exception $e){
            echo $e->getCode() . ': ' . $e->getMessage();
        }
        return $result;
    }
    
    public function getServicioByCodservicio($codservicio){
        $result = array();
        try{
            $stmt = $this->conn->prepare('SELECT codservicio, servicios.codlugar, uribono, fechainicio, nombre, ciudad, tipo, '
                    . 'descripcion, latitud, longitud FROM servicios INNER JOIN lugares WHERE codservicio = :codservicio AND'
                    . ' lugares.codlugar = servicios.codlugar');

            if($stmt->execute(array('codservicio' => $codservicio))){
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e){
            echo $e->getCode() . ': ' . $e->getMessage();
        }
        return $result;
    }
    
    
    //------------------------------------------- LUGARES METHODS -------------------------------------------------
    
    public function getLugaresByCiudad($ciudad) {
        try{
            $stmt = $this->conn->prepare('SELECT codlugar, nombre, ciudad, tipo, descripcion, latitud, longitud FROM lugares'
                    . ' WHERE ciudad = :ciudad ');

            if($stmt->execute(array('ciudad' => $ciudad))){
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e){
            echo $e->getCode() . ': ' . $e->getMessage();
        }
        return $result;
    }
    
        public function getLugarByCodlugar($codlugar) {
        try{
            $stmt = $this->conn->prepare('SELECT codlugar, nombre, ciudad, tipo, descripcion, latitud, longitud FROM lugares'
                    . ' WHERE codlugar = :codlugar');

            if($stmt->execute(array('codlugar' => $codlugar))){
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e){
            echo $e->getCode() . ': ' . $e->getMessage();
        }
        return $result;
    }
    
    //------------------------------------------- UTILS -----------------------------------------------------------
    
    /**
     * Function that generates a password hash bassed on blowfish encription
     * and return this hash.
     * @param type $password
     * @return type string
     */
    private function generateToken($password){
        $salt = substr(base64_encode(openssl_random_pseudo_bytes('30')), 0, 22);
        
        $salt = strtr($salt, array('+' => '.'));
        
        $hash = crypt($password, '$2y$10$'.$salt);
        
        return $hash;
    }
}

