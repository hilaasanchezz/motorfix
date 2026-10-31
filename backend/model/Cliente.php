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
}
