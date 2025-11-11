<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

/**
 * Class representing DesgloseRectificacionType
 *
 * Desglose de Base y Cuota sustituida en las Facturas Rectificativas sustitutivas
 * XSD Type: DesgloseRectificacionType
 */
class DesgloseRectificacionType
{
    /**
     * @var ?string $BaseRectificada
     */
    public ?string $BaseRectificada = null;

    /**
     * @var ?string $CuotaRectificada
     */
    public ?string $CuotaRectificada = null;

    /**
     * @var ?string $CuotaRecargoRectificado
     */
    public ?string $CuotaRecargoRectificado = null;
}

