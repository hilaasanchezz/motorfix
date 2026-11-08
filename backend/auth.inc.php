<?php

require_once __DIR__ . '/model/Usuario.php';

// Lee el JSON que envía el fetch() para sacar el token
$peticionAuth = json_decode(file_get_contents('php://input'), true);

// Busca en la BD el usuario con ese token (devuelve false si no existe o ha caducado)
$usuarioAuth = Usuario::comprobarAuthKey($peticionAuth["auth_key"] ?? null);

if ($usuarioAuth === false) {
    /* Si el token no es válido devuelve un JSON de error y para con exit()
       para que no se ejecute nada más del servicio web que lo ha incluido */
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        "success" => false,
        "msg"     => "No tienes permisos para acceder a esta sección",
        "data"    => []
    ]);
    exit();
}

/* Si el token es válido, sigue la ejecución del servicio web y en $usuarioAuth
   quedan los datos del usuario (id, nombre, email y rol) */
