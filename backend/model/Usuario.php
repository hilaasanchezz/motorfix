<?php

require_once __DIR__ . '/conectionBD.php';

class Usuario {
    public const ROLES = ['admin', 'oficina', 'mecanico', 'cliente'];

    private ?int $id;
    private ?string $nombre;
    private ?string $email;
    private ?string $password;
    private string $rol;
    private bool $activo;

    private PDO $pdo;

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
                ':password' => $this->password,
                ':rol'      => $this->rol,
                ':activo'   => (int) $this->activo,
            ]);

            $this->id = (int) $this->pdo->lastInsertId();
            return $this->id;
        } catch (PDOException $e) {
            return false;
        }
    }

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

    public static function listar(): array {
        try {
            $sql = "SELECT id, nombre, email, rol, activo, fecha_alta
                    FROM usuarios ORDER BY nombre";

            return Conection::getInstance()->getPDO()->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

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

    private function existe(): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $this->id]);
        return (bool) $stmt->fetchColumn();
    }
}
