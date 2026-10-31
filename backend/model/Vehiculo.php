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
}
