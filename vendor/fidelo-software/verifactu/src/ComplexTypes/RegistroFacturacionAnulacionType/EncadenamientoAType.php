<?php

namespace FideloSoftware\Verifactu\ComplexTypes\RegistroFacturacionAnulacionType;

use FideloSoftware\Verifactu\ComplexTypes\EncadenamientoFacturaAnteriorType;

/**
 * Class representing EncadenamientoAType
 */
class EncadenamientoAType
{
    /**
     * @var string $PrimerRegistro
     */
    public $PrimerRegistro = null;

    /**
     * @var ?EncadenamientoFacturaAnteriorType $RegistroAnterior
     */
    public ?EncadenamientoFacturaAnteriorType $RegistroAnterior = null;
}

