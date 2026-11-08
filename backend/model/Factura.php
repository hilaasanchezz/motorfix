<?php

require_once __DIR__ . '/conectionBD.php';

class Factura {
    public const ESTADOS = ['pendiente', 'pagada', 'anulada'];

    private ?int $id;
    private ?string $numero;
    private ?int $citaId;
    private ?float $manoObra;
    private ?float $repuestos;
    private ?float $subtotal;
    private ?float $ivaPorcentaje;
    private ?float $ivaImporte;
    private ?float $total;
    private ?string $estado;

    private PDO $pdo;

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
}
