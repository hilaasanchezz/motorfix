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
}
