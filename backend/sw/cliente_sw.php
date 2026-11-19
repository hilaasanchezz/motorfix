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
 * - insertar: añade un cliente sin cuenta de usuario.
 *   data: dni, nombre, apellidos, telefono, email, direccion
 *   Devuelve en data el id del cliente nuevo.
 * - actualizar: cambia los datos de un cliente. No cambia su cuenta de usuario.
 *   data: id y los mismos campos que insertar
 * - eliminar: borra un cliente con sus vehículos y sus citas. No se puede si alguna
 *   de sus citas tiene factura.
 *   data: id
 *
 * Ejemplos de petición:
 * <code>
 * {"action": "listar", "auth_key": "..."}
 * {"action": "insertar", "auth_key": "...", "data": {"dni": "12345678Z", "nombre": "Ana",
 *  "apellidos": "López García", "telefono": "600123123", "email": "", "direccion": ""}}
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

        case "insertar":
            $datos = $request["data"];

            /* Convierte los campos vacíos en null: así la BD rechaza el cliente si falta
               el DNI, el nombre, los apellidos o el teléfono, y deja vacíos el email y la dirección */
            $dni       = $datos["dni"] === "" ? null : $datos["dni"];
            $nombre    = $datos["nombre"] === "" ? null : $datos["nombre"];
            $apellidos = $datos["apellidos"] === "" ? null : $datos["apellidos"];
            $telefono  = $datos["telefono"] === "" ? null : $datos["telefono"];
            $email     = $datos["email"] === "" ? null : $datos["email"];
            $direccion = $datos["direccion"] === "" ? null : $datos["direccion"];

            // Crea el cliente sin usuario vinculado: oficina da de alta clientes sin cuenta
            $cliente = new Cliente(null, null, $dni, $nombre, $apellidos, $telefono, $email, $direccion);
            $clienteId = $cliente->insertar();

            if ($clienteId === false) {
                $success = false;
                $msg = "No se ha podido añadir el cliente (¿DNI repetido o falta algún dato?)";
            } else {
                $msg = "Cliente añadido correctamente";
                $data = ["id" => $clienteId];
            }
            break;

        case "actualizar":
            $datos = $request["data"];

            // Carga el cliente de la BD para comprobar que existe y no perder su usuario vinculado
            $cliente = new Cliente((int) $datos["id"]);

            if ($cliente->obtener() === false) {
                $success = false;
                $msg = "No se ha encontrado el cliente";
            } else {
                /* Cambia sus datos con el setter mágico (__set). Los campos vacíos se guardan
                   como null, igual que al añadir. El usuario_id no se toca */
                $cliente->dni       = $datos["dni"] === "" ? null : $datos["dni"];
                $cliente->nombre    = $datos["nombre"] === "" ? null : $datos["nombre"];
                $cliente->apellidos = $datos["apellidos"] === "" ? null : $datos["apellidos"];
                $cliente->telefono  = $datos["telefono"] === "" ? null : $datos["telefono"];
                $cliente->email     = $datos["email"] === "" ? null : $datos["email"];
                $cliente->direccion = $datos["direccion"] === "" ? null : $datos["direccion"];

                if ($cliente->actualizar()) {
                    $msg = "Cliente actualizado correctamente";
                } else {
                    $success = false;
                    $msg = "No se ha podido guardar el cliente (¿DNI repetido o falta algún dato?)";
                }
            }
            break;

        case "eliminar":
            $cliente = new Cliente((int) $request["data"]["id"]);

            // Comprueba que el cliente existe antes de intentar borrarlo
            if ($cliente->obtener() === false) {
                $success = false;
                $msg = "No se ha encontrado el cliente";
            } else if ($cliente->eliminar()) {
                $msg = "Cliente eliminado correctamente";
            } else {
                /* La BD no deja borrarlo si alguna cita de sus vehículos tiene factura;
                   los vehículos y las citas sin factura se borran con el cliente */
                $success = false;
                $msg = "No se ha podido eliminar el cliente (¿tiene citas con factura?)";
            }
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
