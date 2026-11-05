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
}
