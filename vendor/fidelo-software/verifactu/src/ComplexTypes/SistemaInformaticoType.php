<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

use FideloSoftware\Verifactu\SimpleTypes\SiNoType;

/**
 * Class representing SistemaInformaticoType
 *
 * 
 * XSD Type: SistemaInformaticoType
 */
class SistemaInformaticoType
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

    /**
     * @var ?string $NombreSistemaInformatico
     */
    public ?string $NombreSistemaInformatico = null;

    /**
     * @var ?string $IdSistemaInformatico
     */
    public ?string $IdSistemaInformatico = null;

    /**
     * @var ?string $Version
     */
    public ?string $Version = null;

    /**
     * @var ?string $NumeroInstalacion
     */
    public ?string $NumeroInstalacion = null;

    /**
     * @var ?SiNoType $TipoUsoPosibleSoloVerifactu
     */
    public ?SiNoType $TipoUsoPosibleSoloVerifactu = null;

    /**
     * @var ?SiNoType $TipoUsoPosibleMultiOT
     */
    public ?SiNoType $TipoUsoPosibleMultiOT = null;

    /**
     * @var ?SiNoType $IndicadorMultiplesOT
     */
    public ?SiNoType $IndicadorMultiplesOT = null;
}


