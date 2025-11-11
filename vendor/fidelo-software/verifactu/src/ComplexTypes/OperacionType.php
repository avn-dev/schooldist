<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

use FideloSoftware\Verifactu\SimpleTypes\RechazoPrevioType;

/**
 * Class representing OperacionType
 *
 * 
 * XSD Type: OperacionType
 */
class OperacionType
{
    /**
     * @var ?string $TipoOperacion
     */
    private ?string $TipoOperacion = null;

    /**
     * @var ?string $Subsanacion
     */
    private ?string $Subsanacion = null;

    /**
     * @var ?RechazoPrevioType $RechazoPrevio
     */
    private ?RechazoPrevioType $RechazoPrevio = null;

    /**
     * @var ?string $SinRegistroPrevio
     */
    private ?string $SinRegistroPrevio = null;
}
