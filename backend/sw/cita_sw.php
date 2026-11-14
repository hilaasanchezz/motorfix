<?php

/**
 * Servicio web de las citas del cliente que ha iniciado sesión.
 *
 * Recibe por POST un JSON con la acción, el token (auth_key) y sus datos, y devuelve
 * siempre un JSON con la estructura {success, msg, data}. Solo pueden usarlo los
 * usuarios con rol cliente, y cada cliente solo ve y pide citas para sus propios vehículos.
 *
 * Acciones:
 * - listar: devuelve en data las citas de los vehículos del cliente, de la más reciente
 *   a la más antigua, con la matrícula de cada vehículo.
 * - insertar: pide una cita nueva, en estado pendiente y sin mecánico. El vehículo tiene
 *   que ser del cliente, y la fecha tiene que ser válida y posterior a ahora.
 *   data: vehiculo_id, fecha_cita (posterior a ahora), motivo
 *   Devuelve solo success y msg.
 *
 * Ejemplos de petición:
 * <code>
 * {"action": "listar", "auth_key": "..."}
 * {"action": "insertar", "auth_key": "...", "data": {"vehiculo_id": "1", "fecha_cita": "2026-12-15T10:30",
 *  "motivo": "Cambio de aceite"}}
 * </code>
 *
 * @author Hilario Sánchez Pérez
 */

// Indica que la respuesta es un JSON
header('Content-Type: application/json; charset=UTF-8');

// Comprueba el token y deja en $usuarioAuth los datos del usuario que ha iniciado sesión
require_once __DIR__ . '/../auth.inc.php';
require_once __DIR__ . '/../model/Cliente.php';
require_once __DIR__ . '/../model/Cita.php';
require_once __DIR__ . '/../model/Vehiculo.php';

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
    // Busca la ficha de cliente del usuario para saber qué citas son suyas
    $cliente = Cliente::obtenerPorUsuario($usuarioAuth["id"]);

    if ($cliente === false) {
        $success = false;
        $msg = "No se ha encontrado tu ficha de cliente";
    } else {
        switch ($request["action"] ?? "") {
            case "listar":
                // Devuelve solo las citas de los vehículos del cliente que ha iniciado sesión
                $data = Cita::listarPorCliente($cliente["id"]);
                $msg = "Citas obtenidas correctamente";
                break;

            case "insertar":
                $datos = $request["data"];

                // Convierte los campos vacíos en null, igual que en vehiculo_sw.php
                $vehiculoId = $datos["vehiculo_id"] === "" ? null : (int) $datos["vehiculo_id"];
                $fechaCita  = $datos["fecha_cita"] === "" ? null : $datos["fecha_cita"];
                $motivo     = $datos["motivo"] === "" ? null : $datos["motivo"];

                /* Comprueba que el vehículo existe y que es del cliente que ha iniciado sesión,
                   para que nadie pueda pedir una cita para el vehículo de otro cliente */
                $vehiculo = new Vehiculo($vehiculoId);
                $datosVehiculo = $vehiculo->obtener();

                if ($vehiculoId === null) {
                    // Si no se ha elegido ningún vehículo en el desplegable, avisa al cliente
                    $success = false;
                    $msg = "Elige un vehículo";
                } else if ($datosVehiculo === false || (int) $datosVehiculo["cliente_id"] !== (int) $cliente["id"]) {
                    $success = false;
                    $msg = "No se ha encontrado el vehículo";
                } else if ($fechaCita !== null && strtotime($fechaCita) === false) {
                    // Si el texto de la fecha no es una fecha válida, avisa al cliente
                    $success = false;
                    $msg = "La fecha no es válida";
                } else if ($fechaCita !== null && strtotime($fechaCita) <= time()) {
                    // No deja pedir una cita para una fecha que ya ha pasado
                    $success = false;
                    $msg = "La fecha de la cita tiene que ser posterior a ahora";
                } else {
                    // Crea la cita sin mecánico (el taller lo asignará después); el estado es "pendiente" por defecto
                    $cita = new Cita(null, $vehiculoId, null, $fechaCita, $motivo);

                    if ($cita->insertar() === false) {
                        $success = false;
                        $msg = "No se ha podido pedir la cita (¿falta algún dato?)";
                    } else {
                        $msg = "Cita solicitada correctamente";
                    }
                }
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
