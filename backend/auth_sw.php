<?php

require_once __DIR__ . '/model/Usuario.php';
require_once __DIR__ . '/model/Cliente.php';

// Indica que la respuesta es un JSON
header('Content-Type: application/json; charset=UTF-8');

// Lee el JSON que envía el fetch() y lo convierte en un array asociativo
$contenidoJSON = file_get_contents('php://input');
$request = json_decode($contenidoJSON, true);

$success = true;
$msg = "";
$data = [];

switch ($request["action"] ?? "") {
    case "registro":
        $datos = $request["data"];

        // Guarda el usuario y el cliente juntos: si uno falla, no se guarda ninguno
        $pdo = Conection::getInstance()->getPDO();
        $pdo->beginTransaction();

        // Crea la cuenta de usuario con el rol de cliente
        $usuario = new Usuario(null, $datos["nombre"] . " " . $datos["apellidos"], $datos["email"], $datos["password"]);
        $usuarioId = $usuario->insertar();

        if ($usuarioId === false) {
            $pdo->rollBack();
            $success = false;
            $msg = "No se ha podido crear la cuenta (¿email ya registrado o falta la contraseña?)";
        } else {
            // Crea la ficha de cliente vinculada al usuario
            $cliente = new Cliente(null, $usuarioId, $datos["dni"], $datos["nombre"], $datos["apellidos"],
                                   $datos["telefono"], $datos["email"], $datos["direccion"]);
            $clienteId = $cliente->insertar();

            if ($clienteId === false) {
                // Deshace también el usuario para que no quede una cuenta sin cliente
                $pdo->rollBack();
                $success = false;
                $msg = "No se ha podido crear la cuenta (¿DNI ya registrado?)";
            } else {
                $pdo->commit();
                $msg = "Cuenta creada correctamente";
                $data = ["usuario_id" => $usuarioId, "cliente_id" => $clienteId];
            }
        }
        break;

    // Aquí irá la acción de login

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
