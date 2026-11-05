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
}
