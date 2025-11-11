<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

use FideloSoftware\Verifactu\SimpleTypes\ClaveTipoFacturaType;
use FideloSoftware\Verifactu\SimpleTypes\ClaveTipoRectificativaType;
use FideloSoftware\Verifactu\SimpleTypes\RechazoPrevioType;
use FideloSoftware\Verifactu\SimpleTypes\TercerosODestinatarioType;
use FideloSoftware\Verifactu\SimpleTypes\TipoHuellaType;
use FideloSoftware\Verifactu\SimpleTypes\VersionType;
use FideloSoftware\Verifactu\ComplexTypes\RegistroFacturacionAltaType\EncadenamientoAType;
use FideloSoftware\Verifactu\Xmldsig\Signature;
use DateTime;

/**
 * Class representing RegistroFacturacionAltaType
 *
 * Datos correspondientes al registro de facturacion de alta
 * XSD Type: RegistroFacturacionAltaType
 */
class RegistroFacturacionAltaType
{
    /**
     * @var ?VersionType $IDVersion
     */
    public ?VersionType $IDVersion = null;

    /**
     * @var ?IDFacturaExpedidaType $IDFactura
     */
    public ?IDFacturaExpedidaType $IDFactura = null;

    /**
     * @var ?string $RefExterna
     */
    public ?string $RefExterna = null;

    /**
     * @var ?string $NombreRazonEmisor
     */
    public ?string $NombreRazonEmisor = null;

    /**
     * @var ?string $Subsanacion
     */
    public ?string $Subsanacion = null;

    /**
     * @var ?RechazoPrevioType $RechazoPrevio
     */
    public ?RechazoPrevioType $RechazoPrevio = null;

    /**
     * Clave del tipo de factura
     *
     * @var ?ClaveTipoFacturaType $TipoFactura
     */
    public ?ClaveTipoFacturaType $TipoFactura = null;

    /**
     * Identifica si el tipo de factura rectificativa es por sustitución o por diferencia
     *
     * @var ?ClaveTipoRectificativaType $TipoRectificativa
     */
    public ?ClaveTipoRectificativaType $TipoRectificativa = null;

    /**
     * @var ?IDFacturaARType[] $FacturasRectificadas
     */
    public ?array $FacturasRectificadas = null;

    /**
     * @var ?IDFacturaARType[] $FacturasSustituidas
     */
    public ?array $FacturasSustituidas = null;

    /**
     * @var ?DesgloseRectificacionType $ImporteRectificacion
     */
    public ?DesgloseRectificacionType $ImporteRectificacion = null;

    /**
     * @var ?string $FechaOperacion
     */
    public ?string $FechaOperacion = null;

    /**
     * @var ?string $DescripcionOperacion
     */
    public ?string $DescripcionOperacion = null;

    /**
     * @var ?string $FacturaSimplificadaArt7273
     */
    public ?string $FacturaSimplificadaArt7273 = null;

    /**
     * @var ?string $FacturaSinIdentifDestinatarioArt61d
     */
    public ?string $FacturaSinIdentifDestinatarioArt61d = null;

    /**
     * @var ?string $Macrodato
     */
    public ?string $Macrodato = null;

    /**
     * @var ?TercerosODestinatarioType $EmitidaPorTerceroODestinatario
     */
    public ?TercerosODestinatarioType $EmitidaPorTerceroODestinatario = null;

    /**
     * Tercero que expida la factura y/o genera el registro de alta.
     *
     * @var ?PersonaFisicaJuridicaType $Tercero
     */
    public ?PersonaFisicaJuridicaType $Tercero = null;

    /**
     * @var ?PersonaFisicaJuridicaType[] $Destinatarios
     */
    public ?array $Destinatarios = null;

    /**
     * @var ?string $Cupon
     */
    public ?string $Cupon = null;

    /**
     * @var ?DetalleType[] $Desglose
     */
    public ?array $Desglose = null;

    /**
     * @var ?string $CuotaTotal
     */
    public ?string $CuotaTotal = null;

    /**
     * @var ?string $ImporteTotal
     */
    public ?string $ImporteTotal = null;

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
     * @var ?string $NumRegistroAcuerdoFacturacion
     */
    public ?string $NumRegistroAcuerdoFacturacion = null;

    /**
     * @var ?string $IdAcuerdoSistemaInformatico
     */
    public ?string $IdAcuerdoSistemaInformatico = null;

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
