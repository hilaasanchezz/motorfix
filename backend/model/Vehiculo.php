<?php

require_once __DIR__ . '/conectionBD.php';

class Vehiculo {
    public const COMBUSTIBLES = ['gasolina', 'diesel', 'hibrido', 'electrico', 'glp'];

    private ?int $id;
    private ?int $clienteId;
    private ?string $matricula;
    private ?string $marca;
    private ?string $modelo;
    private ?int $anio;
    private ?string $color;
    private ?int $kilometros;
    private ?string $combustible;

    private PDO $pdo;

    public function __construct(?int $id = null, ?int $clienteId = null, ?string $matricula = null,
                                ?string $marca = null, ?string $modelo = null, ?int $anio = null,
                                ?string $color = null, ?int $kilometros = null, ?string $combustible = null) {
        $this->id          = $id;
        $this->clienteId   = $clienteId;
        $this->matricula   = $matricula;
        $this->marca       = $marca;
        $this->modelo      = $modelo;
        $this->anio        = $anio;
        $this->color       = $color;
        $this->kilometros  = $kilometros;
        $this->combustible = $combustible;
        $this->pdo         = Conection::getInstance()->getPDO();
    }

    public function insertar(): int|false {
        // El combustible es opcional, pero si viene tiene que ser uno de los del ENUM
        if ($this->combustible !== null && !in_array($this->combustible, self::COMBUSTIBLES, true)) {
            return false;
        }

        try {
            $sql = "INSERT INTO vehiculos (cliente_id, matricula, marca, modelo, anio, color, kilometros, combustible)
                    VALUES (:cliente_id, :matricula, :marca, :modelo, :anio, :color, :kilometros, :combustible)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':cliente_id'  => $this->clienteId,
                ':matricula'   => $this->matricula,
                ':marca'       => $this->marca,
                ':modelo'      => $this->modelo,
                ':anio'        => $this->anio,
                ':color'       => $this->color,
                ':kilometros'  => $this->kilometros,
                ':combustible' => $this->combustible,
            ]);

            $this->id = (int) $this->pdo->lastInsertId();
            return $this->id;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function obtener(): array|false {
        try {
            $sql = "SELECT id, cliente_id, matricula, marca, modelo, anio, color, kilometros, combustible
                    FROM vehiculos WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $this->id]);
            $datos = $stmt->fetch();

            if ($datos) {
                $this->clienteId   = (int) $datos['cliente_id'];
                $this->matricula   = $datos['matricula'];
                $this->marca       = $datos['marca'];
                $this->modelo      = $datos['modelo'];
                $this->anio        = $datos['anio'] !== null ? (int) $datos['anio'] : null;
                $this->color       = $datos['color'];
                $this->kilometros  = $datos['kilometros'] !== null ? (int) $datos['kilometros'] : null;
                $this->combustible = $datos['combustible'];
                return $datos;
            }

            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function listar(): array {
        try {
            $sql = "SELECT id, cliente_id, matricula, marca, modelo, anio, color, kilometros, combustible
                    FROM vehiculos ORDER BY matricula";

            return Conection::getInstance()->getPDO()->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function actualizar(): bool {
        if ($this->combustible !== null && !in_array($this->combustible, self::COMBUSTIBLES, true)) {
            return false;
        }

        try {
            $sql = "UPDATE vehiculos
                    SET cliente_id = :cliente_id, matricula = :matricula, marca = :marca, modelo = :modelo,
                        anio = :anio, color = :color, kilometros = :kilometros, combustible = :combustible
                    WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id'          => $this->id,
                ':cliente_id'  => $this->clienteId,
                ':matricula'   => $this->matricula,
                ':marca'       => $this->marca,
                ':modelo'      => $this->modelo,
                ':anio'        => $this->anio,
                ':color'       => $this->color,
                ':kilometros'  => $this->kilometros,
                ':combustible' => $this->combustible,
            ]);

            // rowCount() sería 0 si los datos no cambian, así que se comprueba
            // con existe() que el vehículo está en la tabla
            return $stmt->rowCount() > 0 || $this->existe();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function eliminar(): bool {
        try {
            $sql = "DELETE FROM vehiculos WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $this->id]);

            // Si rowCount() es 0, no existía ningún vehículo con ese id
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function existe(): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM vehiculos WHERE id = :id");
        $stmt->execute([':id' => $this->id]);
        return (bool) $stmt->fetchColumn();
    }
}
