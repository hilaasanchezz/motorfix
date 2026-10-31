<?php

require_once __DIR__ . '/conectionBD.php';

class Cliente {
    private ?int $id;
    private ?int $usuarioId;
    private ?string $dni;
    private ?string $nombre;
    private ?string $apellidos;
    private ?string $telefono;
    private ?string $email;
    private ?string $direccion;

    private PDO $pdo;

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

    public static function listar(): array {
        try {
            $sql = "SELECT id, usuario_id, dni, nombre, apellidos, telefono, email, direccion, fecha_alta
                    FROM clientes ORDER BY apellidos, nombre";

            return Conection::getInstance()->getPDO()->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

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

    private function existe(): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM clientes WHERE id = :id");
        $stmt->execute([':id' => $this->id]);
        return (bool) $stmt->fetchColumn();
    }
}
