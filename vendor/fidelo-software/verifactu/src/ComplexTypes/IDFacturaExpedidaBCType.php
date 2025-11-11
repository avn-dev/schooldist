<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

/**
 * Class representing IDFacturaExpedidaBCType
 *
 * Datos de identificación de factura expedida para operaciones de consulta
 * XSD Type: IDFacturaExpedidaBCType
 */
class IDFacturaExpedidaBCType
{
    /**
     * @var ?string $IDEmisorFactura
     */
    public ?string $IDEmisorFactura = null;

    /**
     * Nº Serie+Nº Factura de la Factura del Emisor.
     *
     * @var ?string $NumSerieFactura
     */
    public ?string $NumSerieFactura = null;

    /**
     * Fecha de emisión de la factura
     *
     * @var ?string $FechaExpedicionFactura
     */
    public ?string $FechaExpedicionFactura = null;
}

