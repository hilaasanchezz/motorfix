<?php

require_once __DIR__ . '/conectionBD.php';

/**
 * Modelo de las facturas del taller (tabla facturas).
 *
 * Gestiona el CRUD de facturas. Cada factura pertenece a una cita (relación 1:1)
 * y guarda el desglose de mano de obra, repuestos, IVA y total.
 *
 * Ejemplo de uso:
 * <code>
 * $factura = new Factura(null, 'F2026-0002', 2, 45, 30, 75, 21, 15.75, 90.75);
 * $id = $factura->insertar();
 *
 * $facturas = Factura::listar();
 * </code>
 *
 * @author Hilario Sánchez Pérez
 */
class Factura {
    /** @var string[] Estados permitidos (ENUM de la columna estado) */
    public const ESTADOS = ['pendiente', 'pagada', 'anulada'];

    /** @var int|null Identificador de la factura (null si aún no está insertada) */
    private ?int $id;
    /** @var string|null Número de la factura (único), por ejemplo F2026-0001 */
    private ?string $numero;
    /** @var int|null Id de la cita facturada (solo una factura por cita) */
    private ?int $citaId;
    /** @var float|null Importe de la mano de obra */
    private ?float $manoObra;
    /** @var float|null Importe de los repuestos */
    private ?float $repuestos;
    /** @var float|null Mano de obra más repuestos, sin IVA */
    private ?float $subtotal;
    /** @var float|null Porcentaje de IVA aplicado */
    private ?float $ivaPorcentaje;
    /** @var float|null Importe del IVA */
    private ?float $ivaImporte;
    /** @var float|null Total de la factura con IVA */
    private ?float $total;
    /** @var string|null Estado de la factura, uno de ESTADOS */
    private ?string $estado;

    /** @var PDO Conexión compartida obtenida del Singleton Conection */
    private PDO $pdo;

    /**
     * Crea una factura con los datos indicados y obtiene la conexión PDO.
     *
     * @param int|null    $id            Identificador de la factura
     * @param string|null $numero        Número de la factura
     * @param int|null    $citaId        Id de la cita facturada
     * @param float|null  $manoObra      Importe de la mano de obra, 0 si no se indica
     * @param float|null  $repuestos     Importe de los repuestos, 0 si no se indica
     * @param float|null  $subtotal      Mano de obra más repuestos, sin IVA
     * @param float|null  $ivaPorcentaje Porcentaje de IVA, 21 si no se indica
     * @param float|null  $ivaImporte    Importe del IVA
     * @param float|null  $total         Total de la factura con IVA
     * @param string|null $estado        Estado de la factura, 'pendiente' si no se indica
     */
    public function __construct(?int $id = null, ?string $numero = null, ?int $citaId = null,
                                ?float $manoObra = 0, ?float $repuestos = 0, ?float $subtotal = null,
                                ?float $ivaPorcentaje = 21, ?float $ivaImporte = null, ?float $total = null,
                                ?string $estado = 'pendiente') {
        $this->id            = $id;
        $this->numero        = $numero;
        $this->citaId        = $citaId;
        $this->manoObra      = $manoObra;
        $this->repuestos     = $repuestos;
        $this->subtotal      = $subtotal;
        $this->ivaPorcentaje = $ivaPorcentaje;
        $this->ivaImporte    = $ivaImporte;
        $this->total         = $total;
        $this->estado        = $estado;
        $this->pdo           = Conection::getInstance()->getPDO();
    }

    /**
     * Getter mágico: devuelve el valor de una propiedad de la factura.
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
     * Setter mágico: cambia el valor de una propiedad de la factura.
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
     * Inserta la factura en la base de datos.
     *
     * Guarda los importes tal como están en el objeto, sin calcularlos.
     *
     * @return int|false El id de la nueva factura, o false si el estado no es válido
     *                   o falla la consulta (por ejemplo, si el número o la cita ya tienen factura)
     */
    public function insertar(): int|false {
        // El estado tiene que ser uno de los del ENUM
        if (!in_array($this->estado, self::ESTADOS, true)) {
            return false;
        }

        try {
            $sql = "INSERT INTO facturas (numero, cita_id, mano_obra, repuestos, subtotal, iva_porcentaje,
                                          iva_importe, total, estado)
                    VALUES (:numero, :cita_id, :mano_obra, :repuestos, :subtotal, :iva_porcentaje,
                            :iva_importe, :total, :estado)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':numero'         => $this->numero,
                ':cita_id'        => $this->citaId,
                ':mano_obra'      => $this->manoObra,
                ':repuestos'      => $this->repuestos,
                ':subtotal'       => $this->subtotal,
                ':iva_porcentaje' => $this->ivaPorcentaje,
                ':iva_importe'    => $this->ivaImporte,
                ':total'          => $this->total,
                ':estado'         => $this->estado,
            ]);

            $this->id = (int) $this->pdo->lastInsertId();
            return $this->id;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Carga en el objeto los datos de la factura con el id actual.
     *
     * @return array|false Los datos de la factura, o false si no existe o falla la consulta
     */
    public function obtener(): array|false {
        try {
            $sql = "SELECT id, numero, cita_id, fecha, mano_obra, repuestos, subtotal, iva_porcentaje,
                           iva_importe, total, estado
                    FROM facturas WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $this->id]);
            $datos = $stmt->fetch();

            if ($datos) {
                $this->numero        = $datos['numero'];
                $this->citaId        = (int) $datos['cita_id'];
                $this->manoObra      = (float) $datos['mano_obra'];
                $this->repuestos     = (float) $datos['repuestos'];
                $this->subtotal      = (float) $datos['subtotal'];
                $this->ivaPorcentaje = (float) $datos['iva_porcentaje'];
                $this->ivaImporte    = (float) $datos['iva_importe'];
                $this->total         = (float) $datos['total'];
                $this->estado        = $datos['estado'];
                return $datos;
            }

            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Devuelve todas las facturas ordenadas por fecha.
     *
     * @return array Lista de facturas, vacía si falla la consulta
     */
    public static function listar(): array {
        try {
            $sql = "SELECT id, numero, cita_id, fecha, mano_obra, repuestos, subtotal, iva_porcentaje,
                           iva_importe, total, estado
                    FROM facturas ORDER BY fecha";

            return Conection::getInstance()->getPDO()->query($sql)->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Actualiza todos los datos de la factura con el id actual.
     *
     * @return bool true si la factura existe y se ha actualizado, false si el estado
     *              no es válido, no existe o falla la consulta
     */
    public function actualizar(): bool {
        if (!in_array($this->estado, self::ESTADOS, true)) {
            return false;
        }

        try {
            $sql = "UPDATE facturas
                    SET numero = :numero, cita_id = :cita_id, mano_obra = :mano_obra, repuestos = :repuestos,
                        subtotal = :subtotal, iva_porcentaje = :iva_porcentaje, iva_importe = :iva_importe,
                        total = :total, estado = :estado
                    WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id'             => $this->id,
                ':numero'         => $this->numero,
                ':cita_id'        => $this->citaId,
                ':mano_obra'      => $this->manoObra,
                ':repuestos'      => $this->repuestos,
                ':subtotal'       => $this->subtotal,
                ':iva_porcentaje' => $this->ivaPorcentaje,
                ':iva_importe'    => $this->ivaImporte,
                ':total'          => $this->total,
                ':estado'         => $this->estado,
            ]);

            // rowCount() sería 0 si los datos no cambian, así que se comprueba
            // con existe() que la factura está en la tabla
            return $stmt->rowCount() > 0 || $this->existe();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Elimina la factura con el id actual.
     *
     * @return bool true si se ha eliminado, false si no existía o falla la consulta
     */
    public function eliminar(): bool {
        try {
            $sql = "DELETE FROM facturas WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $this->id]);

            // Si rowCount() es 0, no existía ninguna factura con ese id
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Comprueba si existe una factura con el id actual.
     *
     * @return bool true si existe, false si no
     */
    private function existe(): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM facturas WHERE id = :id");
        $stmt->execute([':id' => $this->id]);
        return (bool) $stmt->fetchColumn();
    }
}
