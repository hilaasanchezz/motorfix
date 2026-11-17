<?php

/**
 * Servicio web de los clientes del taller (panel de oficina).
 *
 * Recibe por POST un JSON con la acción, el token (auth_key) y sus datos, y devuelve
 * siempre un JSON con la estructura {success, msg, data}. Solo pueden usarlo los
 * usuarios con rol oficina.
 *
 * Acciones:
 * - listar: devuelve en data todos los clientes, ordenados por apellidos y nombre.
 *
 * Ejemplo de petición:
 * <code>
 * {"action": "listar", "auth_key": "..."}
 * </code>
 *
 * @author Hilario Sánchez Pérez
 */

// Indica que la respuesta es un JSON
header('Content-Type: application/json; charset=UTF-8');

// Comprueba el token y deja en $usuarioAuth los datos del usuario que ha iniciado sesión
require_once __DIR__ . '/../auth.inc.php';
require_once __DIR__ . '/../model/Cliente.php';

// Lee el JSON que envía el fetch() y lo convierte en un array asociativo
$request = json_decode(file_get_contents('php://input'), true);

$success = true;
$msg = "";
$data = [];

// Solo pueden entrar los usuarios de oficina
if ($usuarioAuth["rol"] !== "oficina") {
    $success = false;
    $msg = "No tienes permisos para acceder a esta sección";
} else {
    switch ($request["action"] ?? "") {
        case "listar":
            // Devuelve todos los clientes del taller
            $data = Cliente::listar();
            $msg = "Clientes obtenidos correctamente";
            break;

        default:
            $success = false;
            $msg = "Acción no soportada";
    }
}

// Devuelve la respuesta con la estructura estándar
$arrSalida = [
    "success" => $success,
    "msg"     => $msg,
    "data"    => $data
];

echo json_encode($arrSalida);
