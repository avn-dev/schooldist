<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

/**
 * Class representing ContraparteConsultaType
 *
 * Datos de una persona física o jurídica Española o Extranjera
 * XSD Type: ContraparteConsultaType
 */
class ContraparteConsultaType
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
