<?php

/**
 * Servicio web de las facturas del cliente que ha iniciado sesión (solo para consultarlas).
 *
 * Recibe por POST un JSON con la acción y el token (auth_key), y devuelve siempre
 * un JSON con la estructura {success, msg, data}. Solo pueden usarlo los usuarios
 * con rol cliente, y cada cliente solo ve las facturas de las citas de sus vehículos.
 *
 * Acciones:
 * - listar: devuelve en data las facturas del cliente, de la más reciente a la más
 *   antigua, con número, fecha, subtotal, IVA, total y estado.
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
require_once __DIR__ . '/../model/Factura.php';

// Lee el JSON que envía el fetch() y lo convierte en un array asociativo
$request = json_decode(file_get_contents('php://input'), true);

$success = true;
$msg = "";
$data = [];

// De momento solo pueden entrar los clientes (oficina se añadirá en la semana 5)
if ($usuarioAuth["rol"] !== "cliente") {
    $success = false;
    $msg = "No tienes permisos para acceder a esta sección";
} else {
    // Busca la ficha de cliente del usuario para saber qué facturas son suyas
    $cliente = Cliente::obtenerPorUsuario($usuarioAuth["id"]);

    if ($cliente === false) {
        $success = false;
        $msg = "No se ha encontrado tu ficha de cliente";
    } else {
        switch ($request["action"] ?? "") {
            case "listar":
                // Devuelve solo las facturas de las citas de los vehículos del cliente que ha iniciado sesión
                $data = Factura::listarPorCliente($cliente["id"]);
                $msg = "Facturas obtenidas correctamente";
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
