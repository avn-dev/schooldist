<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

use FideloSoftware\Verifactu\SimpleTypes\PersonaFisicaJuridicaIDTypeType;

/**
 * Class representing IDOtroType
 *
 * Identificador de persona Física o jurídica distinto del NIF 
 *  (Código pais, Tipo de Identificador, y hasta 15 caractéres)
 *  No se permite CodigoPais=ES e IDType=01-NIFContraparte
 *  para ese caso, debe utilizarse NIF en lugar de IDOtro.
 * XSD Type: IDOtroType
 */
class IDOtroType
{
    /**
     * @var ?string $CodigoPais
     */
    public ?string $CodigoPais = null;

    /**
     * @var ?PersonaFisicaJuridicaIDTypeType $IDType
     */
    public ?PersonaFisicaJuridicaIDTypeType $IDType = null;

    /**
     * @var ?string $ID
     */
    public ?string $ID = null;
}
