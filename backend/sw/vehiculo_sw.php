<?php

// Servicio web de los vehículos del cliente que ha iniciado sesión

// Indica que la respuesta es un JSON
header('Content-Type: application/json; charset=UTF-8');

// Comprueba el token y deja en $usuarioAuth los datos del usuario que ha iniciado sesión
require_once __DIR__ . '/../auth.inc.php';
require_once __DIR__ . '/../model/Cliente.php';
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

            case "insertar":
                $datos = $request["data"];

                /* Convierte los campos vacíos en null: así la BD rechaza el vehículo si falta
                   la matrícula, la marca o el modelo, y deja vacíos el año, el color y los kilómetros */
                $matricula = $datos["matricula"] === "" ? null : $datos["matricula"];
                $marca     = $datos["marca"] === "" ? null : $datos["marca"];
                $modelo    = $datos["modelo"] === "" ? null : $datos["modelo"];
                $color     = $datos["color"] === "" ? null : $datos["color"];

                // El año y los kilómetros llegan como texto y se pasan a número entero
                $anio       = $datos["anio"] === "" ? null : (int) $datos["anio"];
                $kilometros = $datos["kilometros"] === "" ? null : (int) $datos["kilometros"];

                /* Crea el vehículo con el id del cliente que ha iniciado sesión. No se usa
                   ningún cliente_id que venga del navegador, así nadie puede añadirlo a otro cliente */
                $vehiculo = new Vehiculo(null, $cliente["id"], $matricula, $marca, $modelo, $anio,
                                         $color, $kilometros, $datos["combustible"]);
                $vehiculoId = $vehiculo->insertar();

                if ($vehiculoId === false) {
                    $success = false;
                    $msg = "No se ha podido añadir el vehículo (¿matrícula repetida o falta algún dato?)";
                } else {
                    $msg = "Vehículo añadido correctamente";
                    $data = ["id" => $vehiculoId];
                }
                break;

            case "actualizar":
                $datos = $request["data"];
                $id = (int) $datos["id"];

                /* Comprueba que el vehículo existe y que es del cliente que ha iniciado sesión,
                   para que nadie pueda editar el vehículo de otro cliente */
                $vehiculoActual = new Vehiculo($id);
                $datosActuales = $vehiculoActual->obtener();

                if ($datosActuales === false || (int) $datosActuales["cliente_id"] !== (int) $cliente["id"]) {
                    $success = false;
                    $msg = "No se ha encontrado el vehículo";
                } else {
                    // Convierte los campos vacíos en null, igual que al añadir
                    $matricula = $datos["matricula"] === "" ? null : $datos["matricula"];
                    $marca     = $datos["marca"] === "" ? null : $datos["marca"];
                    $modelo    = $datos["modelo"] === "" ? null : $datos["modelo"];
                    $color     = $datos["color"] === "" ? null : $datos["color"];

                    /* El combustible también, por si el vehículo no tenía ninguno guardado
                       y el desplegable llega sin opción elegida */
                    $combustible = $datos["combustible"] === "" ? null : $datos["combustible"];

                    // El año y los kilómetros llegan como texto y se pasan a número entero
                    $anio       = $datos["anio"] === "" ? null : (int) $datos["anio"];
                    $kilometros = $datos["kilometros"] === "" ? null : (int) $datos["kilometros"];

                    // Crea el vehículo con su id y el id del cliente que ha iniciado sesión
                    $vehiculo = new Vehiculo($id, $cliente["id"], $matricula, $marca, $modelo, $anio,
                                             $color, $kilometros, $combustible);

                    if ($vehiculo->actualizar()) {
                        $msg = "Vehículo actualizado correctamente";
                    } else {
                        $success = false;
                        $msg = "No se ha podido guardar el vehículo (¿matrícula repetida o falta algún dato?)";
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
