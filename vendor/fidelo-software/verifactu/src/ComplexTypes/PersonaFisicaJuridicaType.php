<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

/**
 * Class representing PersonaFisicaJuridicaType
 *
 * Datos de una persona física o jurídica Española o Extranjera
 * XSD Type: PersonaFisicaJuridicaType
 */
class PersonaFisicaJuridicaType
{
    /**
     * @var ?string $NombreRazon
     */
    public ?string $NombreRazon = null;

    /**
     * @var ?string $NIF
     */
    public ?string $NIF = null;

    /**
     * @var ?IDOtroType $IDOtro
     */
    public ?IDOtroType $IDOtro = null;
}

