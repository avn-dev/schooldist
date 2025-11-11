<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

/**
 * Class representing IDFacturaExpedidaType
 *
 * Datos de identificación de factura
 * XSD Type: IDFacturaExpedidaType
 */
class IDFacturaExpedidaType
{
    /**
     * NIF del obligado
     *
     * @var ?string $IDEmisorFactura
     */
    public ?string $IDEmisorFactura = null;

    /**
     * Nº Serie+Nº Factura de la Factura del Emisor
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

