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
}
