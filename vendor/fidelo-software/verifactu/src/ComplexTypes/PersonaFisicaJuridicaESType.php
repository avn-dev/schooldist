<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

/**
 * Class representing PersonaFisicaJuridicaESType
 *
 * Datos de una persona física o jurídica Española con un NIF asociado
 * XSD Type: PersonaFisicaJuridicaESType
 */
class PersonaFisicaJuridicaESType
{
    /**
     * @var ?string $NombreRazon
     */
    public ?string $NombreRazon = null;

    /**
     * @var ?string $NIF
     */
    public ?string $NIF = null;
}

