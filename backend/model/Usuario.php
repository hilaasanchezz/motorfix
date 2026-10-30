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
}
