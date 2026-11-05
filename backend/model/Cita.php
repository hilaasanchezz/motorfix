<?php

require_once __DIR__ . '/conectionBD.php';

class Cita {
    public const ESTADOS = ['pendiente', 'confirmada', 'en_reparacion', 'finalizada', 'cancelada'];

    private ?int $id;
    private ?int $vehiculoId;
    private ?int $mecanicoId;
    private ?string $fechaCita;
    private ?string $motivo;
    private ?string $estado;
    private ?string $observaciones;

    private PDO $pdo;

    public function __construct(?int $id = null, ?int $vehiculoId = null, ?int $mecanicoId = null,
                                ?string $fechaCita = null, ?string $motivo = null,
                                ?string $estado = 'pendiente', ?string $observaciones = null) {
        $this->id            = $id;
        $this->vehiculoId    = $vehiculoId;
        $this->mecanicoId    = $mecanicoId;
        $this->fechaCita     = $fechaCita;
        $this->motivo        = $motivo;
        $this->estado        = $estado;
        $this->observaciones = $observaciones;
        $this->pdo           = Conection::getInstance()->getPDO();
    }

    public function insertar(): int|false {
        // El estado tiene que ser uno de los del ENUM
        if (!in_array($this->estado, self::ESTADOS, true)) {
            return false;
        }

        try {
            $sql = "INSERT INTO citas (vehiculo_id, mecanico_id, fecha_cita, motivo, estado, observaciones)
                    VALUES (:vehiculo_id, :mecanico_id, :fecha_cita, :motivo, :estado, :observaciones)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':vehiculo_id'   => $this->vehiculoId,
                ':mecanico_id'   => $this->mecanicoId,
                ':fecha_cita'    => $this->fechaCita,
                ':motivo'        => $this->motivo,
                ':estado'        => $this->estado,
                ':observaciones' => $this->observaciones,
            ]);

            $this->id = (int) $this->pdo->lastInsertId();
            return $this->id;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function obtener(): array|false {
        try {
            $sql = "SELECT id, vehiculo_id, mecanico_id, fecha_solicitud, fecha_cita, motivo, estado, observaciones
                    FROM citas WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $this->id]);
            $datos = $stmt->fetch();

            if ($datos) {
                $this->vehiculoId    = (int) $datos['vehiculo_id'];
                $this->mecanicoId    = $datos['mecanico_id'] !== null ? (int) $datos['mecanico_id'] : null;
                $this->fechaCita     = $datos['fecha_cita'];
                $this->motivo        = $datos['motivo'];
                $this->estado        = $datos['estado'];
                $this->observaciones = $datos['observaciones'];
                return $datos;
            }

            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function listar(): array {
        try {
            $sql = "SELECT id, vehiculo_id, mecanico_id, fecha_solicitud, fecha_cita, motivo, estado, observaciones
                    FROM citas ORDER BY fecha_cita";

            return Conection::getInstance()->getPDO()->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
