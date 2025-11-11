<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

/**
 * Class representing EncadenamientoFacturaAnteriorType
 *
 * Datos de encadenamiento
 * XSD Type: EncadenamientoFacturaAnteriorType
 */
class EncadenamientoFacturaAnteriorType
{
    /**
     * NIF del obligado a expedir la factura a que se refiere el registro de facturación anterior
     *
     * @var ?string $IDEmisorFactura
     */
    public ?string $IDEmisorFactura = null;

    /**
     * @var ?string $NumSerieFactura
     */
    public ?string $NumSerieFactura = null;

    /**
     * @var ?string $FechaExpedicionFactura
     */
    public ?string $FechaExpedicionFactura = null;

    /**
     * @var ?string $Huella
     */
    public ?string $Huella = null;
}

