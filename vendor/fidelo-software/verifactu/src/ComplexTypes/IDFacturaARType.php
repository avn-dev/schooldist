<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

/**
 * Class representing IDFacturaARType
 *
 * Datos de identificación de factura sustituida o rectificada. El NIF se cogerá del NIF indicado en el bloque IDFactura
 * XSD Type: IDFacturaARType
 */
class IDFacturaARType
{
    /**
     * NIF del obligado
     *
     * @var ?string $IDEmisorFactura
     */
    public ?string $IDEmisorFactura = null;

    /**
     * Nº Serie+Nº Factura de la factura
     *
     * @var ?string $NumSerieFactura
     */
    public ?string $NumSerieFactura = null;

    /**
     * Fecha de emisión de la factura sustituida o rectificada
     *
     * @var ?string $FechaExpedicionFactura
     */
    public ?string $FechaExpedicionFactura = null;
}

