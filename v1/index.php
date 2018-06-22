<?php

require '../vendor/autoload.php';
require_once '../includes/DbHandler.php';
require_once '../includes/PassHash.php';

$app = new Slim\App();

/**
 * Funcion que recibe un array con el nombre de los campos obligatorios y el body de la peticion
 * y devuelve los campos necesarios que faltan.
 * @param type $required_fields
 * @param type $body
 * @return type string
 */
function verifyParams($required_fields, $body){
    $error = false;
    $error_fields = '';
    
    foreach ($required_fields as $field){
        if (!isset($body->$field) || strlen(trim($body->$field)) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
    if ($error) {
        return ($error_fields);
    }else{
        return NULL;
    }
}

/**
 * Ruta: /register
 * Method: POST
 * Fields: codagencia, nombrecliente, password
 * Return: bool error, string message, http code
 */
$app->post('/register', function($request, $response){
    $body = json_decode($request->getBody());
    $requiredFields = verifyParams(array('codagencia', 'nombrecliente', 'password'),$body);
    
    if ($requiredFields === NULL) {
        $codAgencia = $body->codagencia;
        $nombreCliente = $body->nombrecliente;
        $password = $body->password;
        
        $db = new DbHandler();
        $result = $db->createDossier($codAgencia, $nombreCliente, $password);
        
        if($result == DOSSIER_CREATED_SUCCESSFULLY){
            return $response->withJson(array('error' => 'false', 'message' => 'Dossier creado correctamente'), 201);
        }else if($result == DOSSIER_CREATE_FAILED){
            return $response->withJson(array('error' => 'true', 'message' => 'Fallo al crear el dossier'), 200);
        }
    }else{
        return $response->withJson(array('error' => 'true', 'message' => 'Falta/n el/los campo/s '.$requiredFields), 400);
    }
});

/**
 * Ruta: /login
 * Method: POST
 * Fields: coddossier, password
 * Return: bool error, string message, http code || dossier object
 */
$app->post('/login', function($request, $response){
    $body = json_decode($request->getBody());
    $requiredFields = verifyParams(array('coddossier', 'password'), $body);
    
    if ($requiredFields === NULL) {
        $coddossier = $body->coddossier;
        $password = $body->password;
        
        $db = new DbHandler();
        
        if ($db->login($coddossier, $password)) {
            $dossier = $db->getDossierByCoddossier($coddossier);
            if ($dossier != NULL) {
                return $response->withJson($dossier, 200);
            }else{
                return $response->withJson(array('error' => 'true', 'message' => 'Algo no ha ido bien... Vuelve a intentarlo'), 200);
            }
        }else{
            return $response->withJson(array('error' => 'true', 'message' => 'Codigo de dossier o contraseña incorrecta'), 200);
        }     
    }else{
        return $response->withJson(array('error' => 'true', 'message' => 'Falta/n el/los campo/s '.$requiredFields), 400);
    }
});

/**
 * Ruta: /servicios/coddossier
 * Method: GET
 * Headers: token
 * Args: coddossier
 * Return: bool error, string message, http code || servicios array object
 */
$app->get('/servicios/{coddossier}', function($request, $response, $args){
    $token = $request->getHeader('token');
    $coddossier = $args['coddossier'];
    if ($coddossier != NULL && $token[0] != null) {
        $db = new DbHandler();
        if ($db->isValidToken($token[0])) {
            if ($db->getCoddossierByToken($token[0]) == $coddossier ) {
                $servicios = $db->getServiciosByCodDossier($coddossier);
                if ($servicios != null) {
                    return $response->withJson($servicios, 200);
                }
            }else{
                return $response->withJson(array('error' => 'true', 'message' => 'El token no pertenece a ese codigo de dossier...'), 200);
            }
        }else{
            return $response->withJson(array('error' => 'true', 'message' => 'El token no es valido...'), 200);
        }
    }else{
       return $response->withJson(array('error' => 'true', 'message' => 'Para esta peticion es necesario un token...'), 400);
    }
});

$app->get('/servicio/{codservicio}', function($request, $response, $args){
    $token = $request->getHeader('token');
    $codservicio = $args['codservicio'];
    if ($codservicio != NULL && $token[0] != null) {
        $db = new DbHandler();
        if ($db->isValidToken($token[0])) {
            $servicio = $db->getServicioByCodservicio($codservicio);
            if ($servicio != null) {
                return $response->withJson($servicio, 200);
            }
        }else{
            return $response->withJson(array('error' => 'true', 'message' => 'El token no es valido...'), 200);
        }
    }else{
       return $response->withJson(array('error' => 'true', 'message' => 'Para esta peticion es necesario un token...'), 400);
    }
});

$app->get('/lugares/{ciudad}', function($request, $response, $args){
    $ciudad = $args['ciudad'];
    $db = new DbHandler();
    $result = $db->getLugaresByCiudad($ciudad);
    
    if (count($result) > 0) {
        return $response->withJson($result, 200);
    }else{
        return $response->withJson(array('error' => 'true', 'message' => 'No hay ningun lugar en ' . $ciudad), 400);
    }
});

$app->get('/lugar/{codlugar}', function($request, $response, $args){
    $codlugar = $args['codlugar'];
    $db = new DbHandler();
    $result = $db->getLugarByCodlugar($codlugar);
    
    if ($result != false) {
        return $response->withJson($result, 200);
    }else{
        return $response->withJson(array('error' => 'true', 'message' => 'No se ha podido obtener el lugar'), 400);
    }
});

$app->run();