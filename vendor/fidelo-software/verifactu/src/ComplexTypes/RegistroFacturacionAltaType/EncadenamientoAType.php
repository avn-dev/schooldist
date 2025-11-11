<?php

namespace FideloSoftware\Verifactu\ComplexTypes\RegistroFacturacionAltaType;

use FideloSoftware\Verifactu\ComplexTypes\EncadenamientoFacturaAnteriorType;

/**
 * Class representing EncadenamientoAType
 */
class EncadenamientoAType
{
    /**
     * @var ?string $PrimerRegistro
     */
    public ?string $PrimerRegistro = null;

    /**
     * @var ?EncadenamientoFacturaAnteriorType $RegistroAnterior
     */
    public ?EncadenamientoFacturaAnteriorType $RegistroAnterior = null;
}

