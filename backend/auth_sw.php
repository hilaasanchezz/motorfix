<?php

/**
 * Servicio web de autenticación: registro de clientes, login y logout.
 *
 * Recibe por POST un JSON con la acción y sus datos, y devuelve siempre
 * un JSON con la estructura {success, msg, data}.
 *
 * Acciones:
 * - registro: crea el usuario con rol cliente y su ficha de cliente.
 *   data: nombre, apellidos, dni, telefono, direccion, email, password
 *   Devuelve en data usuario_id, cliente_id y el auth_key para entrar sin iniciar sesión.
 * - login: comprueba el email y la contraseña y genera un token (auth_key).
 *   data: email, password
 *   Devuelve en data los datos del usuario y el auth_key.
 * - logout: borra el token del usuario.
 *   auth_key: el token, fuera de data
 *
 * Ejemplos de petición:
 * <code>
 * {"action": "login", "data": {"email": "juan@correo.es", "password": "MotorFix2026"}}
 * {"action": "logout", "auth_key": "..."}
 * </code>
 *
 * @author Hilario Sánchez Pérez
 */

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

                // Genera el token para que el cliente entre en su panel sin tener que iniciar sesión
                $data["auth_key"] = $usuario->generarAuthKey();
            }
        }
        break;

    case "login":
        $datos = $request["data"];

        // Comprueba el email y la contraseña con password_verify()
        $usuario = Usuario::login($datos["email"], $datos["password"]);

        if ($usuario === false) {
            $success = false;
            $msg = "Email o contraseña incorrectos";
        } else {
            /* Crea un objeto Usuario con el id y el email del usuario que ha iniciado sesión
               para poder llamar a generarAuthKey(), que guarda un token nuevo en la BD */
            $usuarioLogueado = new Usuario($usuario["id"], null, $usuario["email"]);
            $authKey = $usuarioLogueado->generarAuthKey();

            if ($authKey === false) {
                $success = false;
                $msg = "No se ha podido iniciar sesión, inténtalo de nuevo";
            } else {
                /* Devuelve los datos del usuario junto con el token. El navegador lo guardará
                   y lo enviará en cada petición para demostrar que ha iniciado sesión */
                $msg = "Sesión iniciada correctamente";
                $data = $usuario;
                $data["auth_key"] = $authKey;
            }
        }
        break;

    case "logout":
        // Busca en la BD el usuario con ese token (devuelve false si no existe o ha caducado)
        $usuario = Usuario::comprobarAuthKey($request["auth_key"] ?? null);

        if ($usuario === false) {
            $success = false;
            $msg = "No hay ninguna sesión iniciada con ese token";
        } else {
            // Borra el token del usuario en la BD para que ya no sirva
            $usuarioLogueado = new Usuario($usuario["id"]);

            if ($usuarioLogueado->borrarAuthKey()) {
                $msg = "Sesión cerrada correctamente";
            } else {
                $success = false;
                $msg = "No se ha podido cerrar la sesión, inténtalo de nuevo";
            }
        }
        break;

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
