<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

use FideloSoftware\Verifactu\ComplexTypes\CabeceraType\RemisionRequerimientoAType;
use FideloSoftware\Verifactu\ComplexTypes\CabeceraType\RemisionVoluntariaAType;

/**
 * Class representing CabeceraType
 *
 * Datos de cabecera
 * XSD Type: CabeceraType
 */
class CabeceraType
{
    /**
     * Obligado a expedir la factura.
     *
     * @var ?PersonaFisicaJuridicaESType $ObligadoEmision
     */
    public ?PersonaFisicaJuridicaESType $ObligadoEmision = null;

    /**
     * Representante del obligado tributario. A rellenar solo en caso de que los registros de facturación remitdos hayan sido generados por un representante/asesor del obligado tributario.
     *
     * @var ?PersonaFisicaJuridicaESType $Representante
     */
    public ?PersonaFisicaJuridicaESType $Representante = null;

    /**
     * @var ?RemisionVoluntariaAType $RemisionVoluntaria
     */
    public ?RemisionVoluntariaAType $RemisionVoluntaria = null;

    /**
     * @var ?RemisionRequerimientoAType $RemisionRequerimiento
     */
    public ?RemisionRequerimientoAType $RemisionRequerimiento = null;

}
