<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

use FideloSoftware\Verifactu\SimpleTypes\CalificacionOperacionType;
use FideloSoftware\Verifactu\SimpleTypes\IdOperacionesTrascendenciaTributariaType;
use FideloSoftware\Verifactu\SimpleTypes\ImpuestoType;
use FideloSoftware\Verifactu\SimpleTypes\OperacionExentaType;

/**
 * Class representing DetalleType
 *
 * 
 * XSD Type: DetalleType
 */
class DetalleType
{
    /**
     * @var ?ImpuestoType $Impuesto
     */
    public ?ImpuestoType $Impuesto = null;

    /**
     * @var ?IdOperacionesTrascendenciaTributariaType $ClaveRegimen
     */
    public ?IdOperacionesTrascendenciaTributariaType $ClaveRegimen = null;

    /**
     * @var ?CalificacionOperacionType $CalificacionOperacion
     */
    public ?CalificacionOperacionType $CalificacionOperacion = null;

    /**
     * @var ?OperacionExentaType $OperacionExenta
     */
    public ?OperacionExentaType $OperacionExenta = null;

    /**
     * @var ?string $TipoImpositivo
     */
    public ?string $TipoImpositivo = null;

    /**
     * @var ?string $BaseImponibleOimporteNoSujeto
     */
    public ?string $BaseImponibleOimporteNoSujeto = null;

    /**
     * @var ?string $BaseImponibleACoste
     */
    public ?string $BaseImponibleACoste = null;

    /**
     * @var ?string $CuotaRepercutida
     */
    public ?string $CuotaRepercutida = null;

    /**
     * @var ?string $TipoRecargoEquivalencia
     */
    public ?string $TipoRecargoEquivalencia = null;

    /**
     * @var ?string $CuotaRecargoEquivalencia
     */
    public ?string $CuotaRecargoEquivalencia = null;
}
