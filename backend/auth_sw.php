<?php

// Indica que la respuesta es un JSON
header('Content-Type: application/json; charset=UTF-8');

// Lee el JSON que envía el fetch() y lo convierte en un array asociativo
$contenidoJSON = file_get_contents('php://input');
$request = json_decode($contenidoJSON, true);

$success = true;
$msg = "";
$data = [];

switch ($request["action"] ?? "") {
    // Aquí irán las acciones de registro y login

    default:
        $success = false;
        $msg = "Acción no soportada";
}

// Devuelve la respuesta con la estructura estándar
$arrSalida = [
    "success" => $success,
    "msg"     => $msg,
    "data"    => $data
];

echo json_encode($arrSalida);
