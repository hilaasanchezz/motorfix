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
}
