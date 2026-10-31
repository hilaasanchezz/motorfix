<?php

require_once __DIR__ . '/conectionBD.php';

/**
 * Modelo de los usuarios de MotorFix (tabla usuarios).
 *
 * Gestiona el CRUD de usuarios y el login. Las contraseñas se guardan
 * cifradas con BCrypt y nunca se devuelven fuera de la clase.
 *
 * Ejemplo de uso:
 * <code>
 * $usuario = new Usuario(null, 'Ana', 'ana@motorfix.es', 'secreta', 'oficina');
 * $id = $usuario->insertar();
 *
 * $datos = Usuario::login('ana@motorfix.es', 'secreta');
 * </code>
 *
 * @author Hilario Sánchez Pérez
 */
class Usuario {
    /** @var string[] Roles permitidos (ENUM de la columna rol) */
    public const ROLES = ['admin', 'oficina', 'mecanico', 'cliente'];

    /** @var int|null Identificador del usuario (null si aún no está insertado) */
    private ?int $id;
    /** @var string|null Nombre del usuario */
    private ?string $nombre;
    /** @var string|null Email del usuario (único) */
    private ?string $email;
    /** @var string|null Contraseña en texto plano, se cifra al insertar */
    private ?string $password;
    /** @var string Rol del usuario, uno de ROLES */
    private string $rol;
    /** @var bool Indica si el usuario está activo */
    private bool $activo;

    /** @var PDO Conexión compartida obtenida del Singleton Conection */
    private PDO $pdo;

    /**
     * Crea un usuario con los datos indicados y obtiene la conexión PDO.
     *
     * @param int|null    $id       Identificador del usuario
     * @param string|null $nombre   Nombre del usuario
     * @param string|null $email    Email del usuario
     * @param string|null $password Contraseña en texto plano
     * @param string      $rol      Rol del usuario (por defecto 'cliente')
     * @param bool        $activo   Si el usuario está activo (por defecto true)
     */
    public function __construct(?int $id = null, ?string $nombre = null, ?string $email = null,
                                ?string $password = null, string $rol = 'cliente', bool $activo = true) {
        $this->id       = $id;
        $this->nombre   = $nombre;
        $this->email    = $email;
        $this->password = $password;
        $this->rol      = $rol;
        $this->activo   = $activo;
        $this->pdo      = Conection::getInstance()->getPDO();
    }

    /**
     * Getter mágico: devuelve el valor de una propiedad del usuario.
     *
     * No da acceso a la contraseña ni a la conexión PDO.
     *
     * @param string $propiedad Nombre de la propiedad
     * @return mixed El valor de la propiedad, o null si no existe o no se puede leer
     */
    public function __get(string $propiedad): mixed {
        if (in_array($propiedad, ['password', 'pdo'], true) || !property_exists($this, $propiedad)) {
            return null;
        }
        return $this->$propiedad;
    }

    /**
     * Setter mágico: cambia el valor de una propiedad del usuario.
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
     * Inserta el usuario en la base de datos con la contraseña cifrada con BCrypt.
     *
     * @return int|false El id del nuevo usuario, o false si el rol no es válido,
     *                   falta la contraseña o falla la consulta
     */
    public function insertar(): int|false {
        if (!in_array($this->rol, self::ROLES, true) || empty($this->password)) {
            return false;
        }

        try {
            $sql = "INSERT INTO usuarios (nombre, email, password, rol, activo)
                    VALUES (:nombre, :email, :password, :rol, :activo)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':nombre'   => $this->nombre,
                ':email'    => $this->email,
                ':password' => password_hash($this->password, PASSWORD_BCRYPT),
                ':rol'      => $this->rol,
                ':activo'   => (int) $this->activo,
            ]);

            $this->id = (int) $this->pdo->lastInsertId();
            return $this->id;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Carga en el objeto los datos del usuario con el id actual.
     *
     * @return array|false Los datos del usuario (sin la contraseña), o false si no existe
     *                     o falla la consulta
     */
    public function obtener(): array|false {
        try {
            $sql = "SELECT id, nombre, email, rol, activo, fecha_alta
                    FROM usuarios WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $this->id]);
            $datos = $stmt->fetch();

            if ($datos) {
                $this->nombre = $datos['nombre'];
                $this->email  = $datos['email'];
                $this->rol    = $datos['rol'];
                $this->activo = (bool) $datos['activo'];
                return $datos;
            }

            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Devuelve todos los usuarios ordenados por nombre.
     *
     * @return array Lista de usuarios (sin la contraseña), vacía si falla la consulta
     */
    public static function listar(): array {
        try {
            $sql = "SELECT id, nombre, email, rol, activo, fecha_alta
                    FROM usuarios ORDER BY nombre";

            return Conection::getInstance()->getPDO()->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Actualiza el nombre, email, rol y estado del usuario. No modifica la contraseña.
     *
     * @return bool true si el usuario existe y se ha actualizado, false si el rol
     *              no es válido, no existe o falla la consulta
     */
    public function actualizar(): bool {
        if (!in_array($this->rol, self::ROLES, true)) {
            return false;
        }

        try {
            $sql = "UPDATE usuarios
                    SET nombre = :nombre, email = :email, rol = :rol, activo = :activo
                    WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id'     => $this->id,
                ':nombre' => $this->nombre,
                ':email'  => $this->email,
                ':rol'    => $this->rol,
                ':activo' => (int) $this->activo,
            ]);

            // rowCount() sería 0 si los datos no cambian, así que se comprueba
            // con existe() que el usuario está en la tabla
            return $stmt->rowCount() > 0 || $this->existe();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Elimina el usuario con el id actual.
     *
     * @return bool true si se ha eliminado, false si no existía o falla la consulta
     */
    public function eliminar(): bool {
        try {
            $sql = "DELETE FROM usuarios WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $this->id]);

            // Si rowCount() es 0, no existía ningún usuario con ese id
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Comprueba el email y la contraseña de un usuario con password_verify().
     *
     * @param string $email    Email del usuario
     * @param string $password Contraseña en texto plano
     * @return array|false Los datos del usuario (sin la contraseña), o false si no existe,
     *                     está inactivo, la contraseña no coincide o falla la consulta
     */
    public static function login(string $email, string $password): array|false {
        try {
            $sql = "SELECT id, nombre, email, password, rol, activo, fecha_alta
                    FROM usuarios WHERE email = :email";

            $stmt = Conection::getInstance()->getPDO()->prepare($sql);
            $stmt->execute([':email' => $email]);
            $datos = $stmt->fetch();

            if ($datos && $datos['activo'] && password_verify($password, $datos['password'])) {
                // El hash no se devuelve fuera de la clase
                unset($datos['password']);
                return $datos;
            }

            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Comprueba si existe un usuario con el id actual.
     *
     * @return bool true si existe, false si no
     */
    private function existe(): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $this->id]);
        return (bool) $stmt->fetchColumn();
    }
}
