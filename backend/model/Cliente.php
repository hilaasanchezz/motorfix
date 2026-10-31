<?php

require_once __DIR__ . '/conectionBD.php';

/**
 * Modelo de los clientes del taller (tabla clientes).
 *
 * Gestiona el CRUD de clientes. Un cliente puede estar vinculado a un usuario
 * (para entrar en la aplicación) y tiene varios vehículos (relación 1:N).
 *
 * Ejemplo de uso:
 * <code>
 * $cliente = new Cliente(null, null, '12345678Z', 'Ana', 'López García', '600123123');
 * $id = $cliente->insertar();
 *
 * $clientes = Cliente::listar();
 * </code>
 *
 * @author Hilario Sánchez Pérez
 */
class Cliente {
    /** @var int|null Identificador del cliente (null si aún no está insertado) */
    private ?int $id;
    /** @var int|null Id del usuario vinculado (null si no tiene cuenta) */
    private ?int $usuarioId;
    /** @var string|null DNI del cliente (único) */
    private ?string $dni;
    /** @var string|null Nombre del cliente */
    private ?string $nombre;
    /** @var string|null Apellidos del cliente */
    private ?string $apellidos;
    /** @var string|null Teléfono de contacto */
    private ?string $telefono;
    /** @var string|null Email de contacto */
    private ?string $email;
    /** @var string|null Dirección postal */
    private ?string $direccion;

    /** @var PDO Conexión compartida obtenida del Singleton Conection */
    private PDO $pdo;

    /**
     * Crea un cliente con los datos indicados y obtiene la conexión PDO.
     *
     * @param int|null    $id        Identificador del cliente
     * @param int|null    $usuarioId Id del usuario vinculado
     * @param string|null $dni       DNI del cliente
     * @param string|null $nombre    Nombre del cliente
     * @param string|null $apellidos Apellidos del cliente
     * @param string|null $telefono  Teléfono de contacto
     * @param string|null $email     Email de contacto
     * @param string|null $direccion Dirección postal
     */
    public function __construct(?int $id = null, ?int $usuarioId = null, ?string $dni = null,
                                ?string $nombre = null, ?string $apellidos = null, ?string $telefono = null,
                                ?string $email = null, ?string $direccion = null) {
        $this->id        = $id;
        $this->usuarioId = $usuarioId;
        $this->dni       = $dni;
        $this->nombre    = $nombre;
        $this->apellidos = $apellidos;
        $this->telefono  = $telefono;
        $this->email     = $email;
        $this->direccion = $direccion;
        $this->pdo       = Conection::getInstance()->getPDO();
    }

    /**
     * Getter mágico: devuelve el valor de una propiedad del cliente.
     *
     * No da acceso a la conexión PDO.
     *
     * @param string $propiedad Nombre de la propiedad
     * @return mixed El valor de la propiedad, o null si no existe o no se puede leer
     */
    public function __get(string $propiedad): mixed {
        if ($propiedad === 'pdo' || !property_exists($this, $propiedad)) {
            return null;
        }
        return $this->$propiedad;
    }

    /**
     * Setter mágico: cambia el valor de una propiedad del cliente.
     *
     * No permite cambiar el id ni la conexión PDO.
     *
     * @param string $propiedad Nombre de la propiedad
     * @param mixed  $valor     Nuevo valor
     */
    public function __set(string $propiedad, mixed $valor): void {
        if (in_array($propiedad, ['id', 'pdo'], true) || !property_exists($this, $propiedad)) {
            return;
        }
        $this->$propiedad = $valor;
    }

    /**
     * Inserta el cliente en la base de datos.
     *
     * @return int|false El id del nuevo cliente, o false si falla la consulta
     *                   (por ejemplo, si el DNI ya existe)
     */
    public function insertar(): int|false {
        try {
            $sql = "INSERT INTO clientes (usuario_id, dni, nombre, apellidos, telefono, email, direccion)
                    VALUES (:usuario_id, :dni, :nombre, :apellidos, :telefono, :email, :direccion)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':usuario_id' => $this->usuarioId,
                ':dni'        => $this->dni,
                ':nombre'     => $this->nombre,
                ':apellidos'  => $this->apellidos,
                ':telefono'   => $this->telefono,
                ':email'      => $this->email,
                ':direccion'  => $this->direccion,
            ]);

            $this->id = (int) $this->pdo->lastInsertId();
            return $this->id;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Carga en el objeto los datos del cliente con el id actual.
     *
     * @return array|false Los datos del cliente, o false si no existe o falla la consulta
     */
    public function obtener(): array|false {
        try {
            $sql = "SELECT id, usuario_id, dni, nombre, apellidos, telefono, email, direccion, fecha_alta
                    FROM clientes WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $this->id]);
            $datos = $stmt->fetch();

            if ($datos) {
                $this->usuarioId = $datos['usuario_id'] !== null ? (int) $datos['usuario_id'] : null;
                $this->dni       = $datos['dni'];
                $this->nombre    = $datos['nombre'];
                $this->apellidos = $datos['apellidos'];
                $this->telefono  = $datos['telefono'];
                $this->email     = $datos['email'];
                $this->direccion = $datos['direccion'];
                return $datos;
            }

            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Devuelve todos los clientes ordenados por apellidos y nombre.
     *
     * @return array Lista de clientes, vacía si falla la consulta
     */
    public static function listar(): array {
        try {
            $sql = "SELECT id, usuario_id, dni, nombre, apellidos, telefono, email, direccion, fecha_alta
                    FROM clientes ORDER BY apellidos, nombre";

            return Conection::getInstance()->getPDO()->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Actualiza todos los datos del cliente con el id actual.
     *
     * @return bool true si el cliente existe y se ha actualizado, false si no existe
     *              o falla la consulta
     */
    public function actualizar(): bool {
        try {
            $sql = "UPDATE clientes
                    SET usuario_id = :usuario_id, dni = :dni, nombre = :nombre, apellidos = :apellidos,
                        telefono = :telefono, email = :email, direccion = :direccion
                    WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id'         => $this->id,
                ':usuario_id' => $this->usuarioId,
                ':dni'        => $this->dni,
                ':nombre'     => $this->nombre,
                ':apellidos'  => $this->apellidos,
                ':telefono'   => $this->telefono,
                ':email'      => $this->email,
                ':direccion'  => $this->direccion,
            ]);

            // rowCount() sería 0 si los datos no cambian, así que se comprueba
            // con existe() que el cliente está en la tabla
            return $stmt->rowCount() > 0 || $this->existe();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Elimina el cliente con el id actual junto con sus vehículos.
     *
     * @return bool true si se ha eliminado, false si no existía o falla la consulta
     */
    public function eliminar(): bool {
        try {
            // Los vehículos del cliente se borran también (ON DELETE CASCADE)
            $sql = "DELETE FROM clientes WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $this->id]);

            // Si rowCount() es 0, no existía ningún cliente con ese id
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Comprueba si existe un cliente con el id actual.
     *
     * @return bool true si existe, false si no
     */
    private function existe(): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM clientes WHERE id = :id");
        $stmt->execute([':id' => $this->id]);
        return (bool) $stmt->fetchColumn();
    }
}
