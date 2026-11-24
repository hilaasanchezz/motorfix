<?php

/**
 * Servicio web de las citas (panel de cliente y panel de oficina).
 *
 * Recibe por POST un JSON con la acción, el token (auth_key) y sus datos, y devuelve
 * siempre un JSON con la estructura {success, msg, data}. Pueden usarlo los usuarios
 * con rol cliente, que solo ven y piden citas para sus propios vehículos, y los de
 * oficina, que trabajan con las citas de todos los clientes.
 *
 * Acciones del rol oficina:
 * - listar: devuelve en data todas las citas, ordenadas por fecha.
 * - mecanicos: devuelve en data los mecánicos (id y nombre) para asignarlos a las citas.
 * - actualizar: cambia el estado de una cita a pendiente, confirmada o cancelada, y le
 *   asigna un mecánico (o ninguno si mecanico_id llega vacío). No se puede si la cita está
 *   en reparación o finalizada.
 *   data: id, estado, mecanico_id
 *
 * Acciones del rol cliente:
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
require_once __DIR__ . '/../model/Usuario.php';

// Lee el JSON que envía el fetch() y lo convierte en un array asociativo
$request = json_decode(file_get_contents('php://input'), true);

$success = true;
$msg = "";
$data = [];

// Cada rol tiene sus acciones: el cliente trabaja con sus citas y oficina con todas
if ($usuarioAuth["rol"] === "cliente") {
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
} else if ($usuarioAuth["rol"] === "oficina") {
    switch ($request["action"] ?? "") {
        case "listar":
            // Devuelve todas las citas del taller
            $data = Cita::listar();
            $msg = "Citas obtenidas correctamente";
            break;

        case "mecanicos":
            // Devuelve los usuarios con rol mecánico (id y nombre) para poder asignarlos a las citas
            $data = Usuario::listarPorRol("mecanico");
            $msg = "Mecánicos obtenidos correctamente";
            break;

        case "actualizar":
            $datos = $request["data"];

            // Carga la cita de la BD para comprobar que existe y no perder el resto de sus datos
            $cita = new Cita((int) $datos["id"]);
            $datosCita = $cita->obtener();

            if ($datosCita === false) {
                $success = false;
                $msg = "No se ha encontrado la cita";
            } else if ($datosCita["estado"] === "en_reparacion" || $datosCita["estado"] === "finalizada") {
                // Las citas en reparación o finalizadas las lleva el mecánico, así que oficina no las cambia
                $success = false;
                $msg = "No se puede cambiar una cita en reparación o finalizada";
            } else if (!in_array($datos["estado"], ["pendiente", "confirmada", "cancelada"], true)) {
                // Oficina solo puede dejar la cita pendiente, confirmarla o cancelarla
                $success = false;
                $msg = "El estado no es válido";
            } else {
                /* El mecánico llega vacío si se ha elegido "Sin asignar". Si no, comprueba con un
                   bucle que el id es de uno de los mecánicos, para no asignar la cita a otro usuario */
                $mecanicoId = null;
                $mecanicoValido = true;

                if ($datos["mecanico_id"] !== "") {
                    $mecanicoId = (int) $datos["mecanico_id"];
                    $mecanicoValido = false;

                    $mecanicos = Usuario::listarPorRol("mecanico");
                    for ($i = 0; $i < count($mecanicos); $i++) {
                        if ((int) $mecanicos[$i]["id"] === $mecanicoId) {
                            $mecanicoValido = true;
                        }
                    }
                }

                if (!$mecanicoValido) {
                    $success = false;
                    $msg = "El mecánico no es válido";
                } else {
                    // Cambia el estado y el mecánico con el setter mágico (__set) y guarda la cita
                    $cita->estado = $datos["estado"];
                    $cita->mecanicoId = $mecanicoId;

                    if ($cita->actualizar()) {
                        $msg = "Cita actualizada correctamente";
                    } else {
                        $success = false;
                        $msg = "No se ha podido guardar la cita";
                    }
                }
            }
            break;

        default:
            $success = false;
            $msg = "Acción no soportada";
    }
} else if ($usuarioAuth["rol"] === "mecanico") {
    switch ($request["action"] ?? "") {
        case "listar":
            // Devuelve solo las citas que oficina ha asignado al mecánico que ha iniciado sesión
            $data = Cita::listarPorMecanico($usuarioAuth["id"]);
            $msg = "Citas obtenidas correctamente";
            break;

        case "actualizar":
            $datos = $request["data"];

            // Carga la cita de la BD para comprobar que existe y no perder el resto de sus datos
            $cita = new Cita((int) $datos["id"]);
            $datosCita = $cita->obtener();

            if ($datosCita === false || (int) $datosCita["mecanico_id"] !== (int) $usuarioAuth["id"]) {
                // El mecánico solo puede cambiar las citas que tiene asignadas
                $success = false;
                $msg = "No se ha encontrado la cita";
            } else if ($datos["estado"] === "en_reparacion" && $datosCita["estado"] !== "confirmada") {
                // Solo se empieza a reparar una cita que oficina ya ha confirmado
                $success = false;
                $msg = "Solo se puede poner en reparación una cita confirmada";
            } else if ($datos["estado"] === "finalizada" && $datosCita["estado"] !== "en_reparacion") {
                // Solo se finaliza una cita que está en reparación
                $success = false;
                $msg = "Solo se puede finalizar una cita en reparación";
            } else if ($datos["estado"] !== "en_reparacion" && $datos["estado"] !== "finalizada") {
                // El mecánico solo puede poner la cita en reparación o finalizarla
                $success = false;
                $msg = "El estado no es válido";
            } else {
                // Cambia el estado con el setter mágico (__set) y guarda la cita
                $cita->estado = $datos["estado"];

                if ($cita->actualizar()) {
                    $msg = "Estado actualizado correctamente";
                } else {
                    $success = false;
                    $msg = "No se ha podido guardar la cita";
                }
            }
            break;

        case "imputar_horas":
            $datos = $request["data"];

            // Carga la cita de la BD para comprobar que existe, que es del mecánico y su estado
            $cita = new Cita((int) $datos["id"]);
            $datosCita = $cita->obtener();

            if ($datosCita === false || (int) $datosCita["mecanico_id"] !== (int) $usuarioAuth["id"]) {
                // El mecánico solo puede imputar horas a las citas que tiene asignadas
                $success = false;
                $msg = "No se ha encontrado la cita";
            } else if ($datosCita["estado"] !== "en_reparacion") {
                // Solo se imputan horas mientras la cita está en reparación
                $success = false;
                $msg = "Solo se pueden imputar horas a una cita en reparación";
            } else if ($datos["descripcion"] === "") {
                $success = false;
                $msg = "Escribe qué trabajo has hecho";
            } else if (!is_numeric($datos["horas"]) || $datos["horas"] <= 0) {
                // Las horas tienen que ser un número (con punto para los decimales) mayor que 0
                $success = false;
                $msg = "Las horas tienen que ser un número mayor que 0";
            } else if ($cita->imputarHoras($datos["descripcion"], (float) $datos["horas"])) {
                $msg = "Horas imputadas correctamente";
            } else {
                $success = false;
                $msg = "No se han podido imputar las horas";
            }
            break;

        case "anadir_repuesto":
            $datos = $request["data"];

            // Carga la cita de la BD para comprobar que existe, que es del mecánico y su estado
            $cita = new Cita((int) $datos["id"]);
            $datosCita = $cita->obtener();

            if ($datosCita === false || (int) $datosCita["mecanico_id"] !== (int) $usuarioAuth["id"]) {
                // El mecánico solo puede añadir repuestos a las citas que tiene asignadas
                $success = false;
                $msg = "No se ha encontrado la cita";
            } else if ($datosCita["estado"] !== "en_reparacion") {
                // Solo se añaden repuestos mientras la cita está en reparación
                $success = false;
                $msg = "Solo se pueden añadir repuestos a una cita en reparación";
            } else if ($datos["repuesto_id"] === "") {
                $success = false;
                $msg = "Elige un repuesto";
            } else if ($cita->tieneRepuesto()) {
                // Cada cita solo puede llevar un repuesto
                $success = false;
                $msg = "Esta cita ya tiene un repuesto";
            } else if ($cita->anadirRepuesto((int) $datos["repuesto_id"])) {
                $msg = "Repuesto añadido correctamente";
            } else {
                $success = false;
                $msg = "No se ha podido añadir el repuesto";
            }
            break;

        default:
            $success = false;
            $msg = "Acción no soportada";
    }
} else {
    // El resto de roles no pueden entrar
    $success = false;
    $msg = "No tienes permisos para acceder a esta sección";
}

// Devuelve la respuesta con la estructura estándar
$arrSalida = [
    "success" => $success,
    "msg"     => $msg,
    "data"    => $data
];

echo json_encode($arrSalida);
