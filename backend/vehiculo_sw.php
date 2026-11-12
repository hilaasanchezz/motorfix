<?php

// Servicio web de los vehículos del cliente que ha iniciado sesión

// Indica que la respuesta es un JSON
header('Content-Type: application/json; charset=UTF-8');

// Comprueba el token y deja en $usuarioAuth los datos del usuario que ha iniciado sesión
require_once __DIR__ . '/auth.inc.php';
require_once __DIR__ . '/model/Cliente.php';
require_once __DIR__ . '/model/Vehiculo.php';

// Lee el JSON que envía el fetch() y lo convierte en un array asociativo
$contenidoJSON = file_get_contents('php://input');
$request = json_decode($contenidoJSON, true);

$success = true;
$msg = "";
$data = [];

// De momento solo pueden entrar los clientes (oficina se añadirá en la semana 5)
if ($usuarioAuth["rol"] !== "cliente") {
    $success = false;
    $msg = "No tienes permisos para acceder a esta sección";
} else {
    // Busca la ficha de cliente del usuario para saber qué vehículos son suyos
    $cliente = Cliente::obtenerPorUsuario($usuarioAuth["id"]);

    if ($cliente === false) {
        $success = false;
        $msg = "No se ha encontrado tu ficha de cliente";
    } else {
        switch ($request["action"] ?? "") {
            case "listar":
                // Devuelve solo los vehículos del cliente que ha iniciado sesión
                $data = Vehiculo::listarPorCliente($cliente["id"]);
                $msg = "Vehículos obtenidos correctamente";
                break;

            default:
                $success = false;
                $msg = "Acción no soportada";
        }
    }
}

// Devuelve la respuesta con la estructura estándar
$arrSalida = [
    "success" => $success,
    "msg"     => $msg,
    "data"    => $data
];

echo json_encode($arrSalida);
