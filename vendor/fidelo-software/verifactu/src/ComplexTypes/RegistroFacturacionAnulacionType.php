<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

use DateTime;
use FideloSoftware\Verifactu\SimpleTypes\GeneradoPorType;
use FideloSoftware\Verifactu\SimpleTypes\TipoHuellaType;
use FideloSoftware\Verifactu\SimpleTypes\VersionType;
use FideloSoftware\Verifactu\ComplexTypes\RegistroFacturacionAnulacionType\EncadenamientoAType;
use FideloSoftware\Verifactu\Xmldsig\Signature;

/**
 * Class representing RegistroFacturacionAnulacionType
 *
 * Datos correspondientes al registro de facturacion de anulacion
 * XSD Type: RegistroFacturacionAnulacionType
 */
class RegistroFacturacionAnulacionType
{
    /**
     * @var ?VersionType $IDVersion
     */
    public ?VersionType $IDVersion = null;

    /**
     * @var ?IDFacturaExpedidaBajaType $IDFactura
     */
    public ?IDFacturaExpedidaBajaType $IDFactura = null;

    /**
     * @var ?string $RefExterna
     */
    public ?string $RefExterna = null;

    /**
     * @var ?string $SinRegistroPrevio
     */
    public ?string $SinRegistroPrevio = null;

    /**
     * @var ?string $RechazoPrevio
     */
    public ?string $RechazoPrevio = null;

    /**
     * @var ?GeneradoPorType $GeneradoPor
     */
    public ?GeneradoPorType $GeneradoPor = null;

    /**
     * @var ?PersonaFisicaJuridicaType $Generador
     */
    public ?PersonaFisicaJuridicaType $Generador = null;

    /**
     * @var ?EncadenamientoAType $Encadenamiento
     */
    public ?EncadenamientoAType $Encadenamiento = null;

    /**
     * @var ?SistemaInformaticoType $SistemaInformatico
     */
    public ?SistemaInformaticoType $SistemaInformatico = null;

    /**
     * @var ?DateTime $FechaHoraHusoGenRegistro
     */
    public ?DateTime $FechaHoraHusoGenRegistro = null;

    /**
     * @var ?TipoHuellaType $TipoHuella
     */
    public ?TipoHuellaType $TipoHuella = null;

    /**
     * @var ?string $Huella
     */
    public ?string $Huella = null;

    /**
     * @var ?Signature $Signature
     */
    public ?Signature $Signature = null;
}

