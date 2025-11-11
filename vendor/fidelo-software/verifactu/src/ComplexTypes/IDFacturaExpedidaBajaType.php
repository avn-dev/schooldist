<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

/**
 * Class representing IDFacturaExpedidaBajaType
 *
 * Datos de identificación de factura que se anula para operaciones de baja
 * XSD Type: IDFacturaExpedidaBajaType
 */
class IDFacturaExpedidaBajaType
{
    /**
     * NIF del obligado
     *
     * @var ?string $IDEmisorFacturaAnulada
     */
    public ?string $IDEmisorFacturaAnulada = null;

    /**
     * Nº Serie+Nº Factura de la Factura que se anula.
     *
     * @var ?string $NumSerieFacturaAnulada
     */
    public ?string $NumSerieFacturaAnulada = null;

    /**
     * Fecha de emisión de la factura que se anula
     *
     * @var ?string $FechaExpedicionFacturaAnulada
     */
    public ?string $FechaExpedicionFacturaAnulada = null;
}

