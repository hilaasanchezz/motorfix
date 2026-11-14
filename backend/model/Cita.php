<?php

require_once __DIR__ . '/conectionBD.php';

/**
 * Modelo de las citas del taller (tabla citas).
 *
 * Gestiona el CRUD de citas. Cada cita pertenece a un vehículo, puede tener
 * un mecánico asignado y se borra si se borra su vehículo.
 *
 * Ejemplo de uso:
 * <code>
 * $cita = new Cita(null, 1, null, '2026-11-20 10:00:00', 'Cambio de aceite');
 * $id = $cita->insertar();
 *
 * $citas = Cita::listar();
 * </code>
 *
 * @author Hilario Sánchez Pérez
 */
class Cita {
    /** @var string[] Estados permitidos (ENUM de la columna estado) */
    public const ESTADOS = ['pendiente', 'confirmada', 'en_reparacion', 'finalizada', 'cancelada'];

    /** @var int|null Identificador de la cita (null si aún no está insertada) */
    private ?int $id;
    /** @var int|null Id del vehículo de la cita */
    private ?int $vehiculoId;
    /** @var int|null Id del usuario mecánico asignado (null si no tiene) */
    private ?int $mecanicoId;
    /** @var string|null Fecha y hora de la cita */
    private ?string $fechaCita;
    /** @var string|null Motivo de la cita */
    private ?string $motivo;
    /** @var string|null Estado de la cita, uno de ESTADOS */
    private ?string $estado;
    /** @var string|null Diagnóstico o notas del mecánico */
    private ?string $observaciones;

    /** @var PDO Conexión compartida obtenida del Singleton Conection */
    private PDO $pdo;

    /**
     * Crea una cita con los datos indicados y obtiene la conexión PDO.
     *
     * @param int|null    $id            Identificador de la cita
     * @param int|null    $vehiculoId    Id del vehículo de la cita
     * @param int|null    $mecanicoId    Id del usuario mecánico asignado
     * @param string|null $fechaCita     Fecha y hora de la cita
     * @param string|null $motivo        Motivo de la cita
     * @param string|null $estado        Estado de la cita, 'pendiente' si no se indica
     * @param string|null $observaciones Diagnóstico o notas del mecánico
     */
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

    /**
     * Getter mágico: devuelve el valor de una propiedad de la cita.
     *
     * No da acceso a la conexión PDO.
     *
     * @param string $propiedad Nombre de la propiedad
     * @return mixed El valor de la propiedad, o null si no existe o no se puede leer
     */
    public function __get(string $propiedad): mixed {
        if ($propiedad === 'pdo' || !property_exists($this, $propiedad)) {
            return null;
        }
        return $this->$propiedad;
    }

    /**
     * Setter mágico: cambia el valor de una propiedad de la cita.
     *
     * No permite cambiar el id ni la conexión PDO.
     *
     * @param string $propiedad Nombre de la propiedad
     * @param mixed  $valor     Nuevo valor
     */
    public function __set(string $propiedad, mixed $valor): void {
        if (in_array($propiedad, ['id', 'pdo'], true) || !property_exists($this, $propiedad)) {
            return;
        }
        $this->$propiedad = $valor;
    }

    /**
     * Inserta la cita en la base de datos.
     *
     * @return int|false El id de la nueva cita, o false si el estado no es válido
     *                   o falla la consulta (por ejemplo, si el vehículo no existe)
     */
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

    /**
     * Carga en el objeto los datos de la cita con el id actual.
     *
     * @return array|false Los datos de la cita, o false si no existe o falla la consulta
     */
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

    /**
     * Devuelve todas las citas ordenadas por fecha.
     *
     * @return array Lista de citas, vacía si falla la consulta
     */
    public static function listar(): array {
        try {
            $sql = "SELECT id, vehiculo_id, mecanico_id, fecha_solicitud, fecha_cita, motivo, estado, observaciones
                    FROM citas ORDER BY fecha_cita";

            return Conection::getInstance()->getPDO()->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Devuelve las citas de los vehículos de un cliente, de la más reciente a la más antigua.
     *
     * Como las citas solo guardan el vehiculo_id, se unen con la tabla vehiculos (JOIN)
     * para filtrar por el cliente y sacar también la matrícula de cada vehículo.
     *
     * @param int $clienteId Id del cliente
     * @return array Lista de citas con id, vehiculo_id, matricula, fecha_solicitud, fecha_cita,
     *               motivo y estado; vacía si no tiene ninguna o falla la consulta
     */
    public static function listarPorCliente(int $clienteId): array {
        try {
            $sql = "SELECT citas.id, citas.vehiculo_id, vehiculos.matricula, citas.fecha_solicitud,
                           citas.fecha_cita, citas.motivo, citas.estado
                    FROM citas JOIN vehiculos ON citas.vehiculo_id = vehiculos.id
                    WHERE vehiculos.cliente_id = :cliente_id
                    ORDER BY citas.fecha_cita DESC";

            $stmt = Conection::getInstance()->getPDO()->prepare($sql);
            $stmt->execute([':cliente_id' => $clienteId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Actualiza todos los datos de la cita con el id actual.
     *
     * @return bool true si la cita existe y se ha actualizado, false si el estado
     *              no es válido, no existe o falla la consulta
     */
    public function actualizar(): bool {
        if (!in_array($this->estado, self::ESTADOS, true)) {
            return false;
        }

        try {
            $sql = "UPDATE citas
                    SET vehiculo_id = :vehiculo_id, mecanico_id = :mecanico_id, fecha_cita = :fecha_cita,
                        motivo = :motivo, estado = :estado, observaciones = :observaciones
                    WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id'            => $this->id,
                ':vehiculo_id'   => $this->vehiculoId,
                ':mecanico_id'   => $this->mecanicoId,
                ':fecha_cita'    => $this->fechaCita,
                ':motivo'        => $this->motivo,
                ':estado'        => $this->estado,
                ':observaciones' => $this->observaciones,
            ]);

            // rowCount() sería 0 si los datos no cambian, así que se comprueba
            // con existe() que la cita está en la tabla
            return $stmt->rowCount() > 0 || $this->existe();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Elimina la cita con el id actual.
     *
     * @return bool true si se ha eliminado, false si no existía o falla la consulta
     *              (por ejemplo, si tiene una factura)
     */
    public function eliminar(): bool {
        try {
            $sql = "DELETE FROM citas WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $this->id]);

            // Si rowCount() es 0, no existía ninguna cita con ese id
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Comprueba si existe una cita con el id actual.
     *
     * @return bool true si existe, false si no
     */
    private function existe(): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM citas WHERE id = :id");
        $stmt->execute([':id' => $this->id]);
        return (bool) $stmt->fetchColumn();
    }
}
