<?php

require_once __DIR__ . '/conectionBD.php';

/**
 * Modelo de los vehículos de los clientes (tabla vehiculos).
 *
 * Gestiona el CRUD de vehículos. Cada vehículo pertenece a un cliente
 * (relación 1:N) y se borra si se borra su cliente.
 *
 * Ejemplo de uso:
 * <code>
 * $vehiculo = new Vehiculo(null, 1, '1234ABC', 'Seat', 'Ibiza', 2018, 'rojo', 85000, 'gasolina');
 * $id = $vehiculo->insertar();
 *
 * $vehiculos = Vehiculo::listar();
 * </code>
 *
 * @author Hilario Sánchez Pérez
 */
class Vehiculo {
    /** @var string[] Tipos de combustible permitidos (ENUM de la columna combustible) */
    public const COMBUSTIBLES = ['gasolina', 'diesel', 'hibrido', 'electrico', 'glp'];

    /** @var int|null Identificador del vehículo (null si aún no está insertado) */
    private ?int $id;
    /** @var int|null Id del cliente al que pertenece */
    private ?int $clienteId;
    /** @var string|null Matrícula del vehículo (única) */
    private ?string $matricula;
    /** @var string|null Marca del vehículo */
    private ?string $marca;
    /** @var string|null Modelo del vehículo */
    private ?string $modelo;
    /** @var int|null Año de fabricación */
    private ?int $anio;
    /** @var string|null Color del vehículo */
    private ?string $color;
    /** @var int|null Kilómetros recorridos */
    private ?int $kilometros;
    /** @var string|null Tipo de combustible, uno de COMBUSTIBLES */
    private ?string $combustible;

    /** @var PDO Conexión compartida obtenida del Singleton Conection */
    private PDO $pdo;

    /**
     * Crea un vehículo con los datos indicados y obtiene la conexión PDO.
     *
     * @param int|null    $id          Identificador del vehículo
     * @param int|null    $clienteId   Id del cliente al que pertenece
     * @param string|null $matricula   Matrícula del vehículo
     * @param string|null $marca       Marca del vehículo
     * @param string|null $modelo      Modelo del vehículo
     * @param int|null    $anio        Año de fabricación
     * @param string|null $color       Color del vehículo
     * @param int|null    $kilometros  Kilómetros recorridos
     * @param string|null $combustible Tipo de combustible
     */
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

    /**
     * Inserta el vehículo en la base de datos.
     *
     * @return int|false El id del nuevo vehículo, o false si el combustible no es válido
     *                   o falla la consulta (por ejemplo, si la matrícula ya existe)
     */
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

    /**
     * Carga en el objeto los datos del vehículo con el id actual.
     *
     * @return array|false Los datos del vehículo, o false si no existe o falla la consulta
     */
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

    /**
     * Devuelve todos los vehículos ordenados por matrícula.
     *
     * @return array Lista de vehículos, vacía si falla la consulta
     */
    public static function listar(): array {
        try {
            $sql = "SELECT id, cliente_id, matricula, marca, modelo, anio, color, kilometros, combustible
                    FROM vehiculos ORDER BY matricula";

            return Conection::getInstance()->getPDO()->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Actualiza todos los datos del vehículo con el id actual.
     *
     * @return bool true si el vehículo existe y se ha actualizado, false si el combustible
     *              no es válido, no existe o falla la consulta
     */
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

    /**
     * Elimina el vehículo con el id actual.
     *
     * @return bool true si se ha eliminado, false si no existía o falla la consulta
     */
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

    /**
     * Comprueba si existe un vehículo con el id actual.
     *
     * @return bool true si existe, false si no
     */
    private function existe(): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM vehiculos WHERE id = :id");
        $stmt->execute([':id' => $this->id]);
        return (bool) $stmt->fetchColumn();
    }
}
