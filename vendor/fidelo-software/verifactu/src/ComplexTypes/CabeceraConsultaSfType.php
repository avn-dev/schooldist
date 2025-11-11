<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

use FideloSoftware\Verifactu\SimpleTypes\VersionType;

/**
 * Class representing CabeceraConsultaSfType
 *
 * Cabecera de la Cobnsulta
 * XSD Type: CabeceraConsultaSf
 */
class CabeceraConsultaSfType
{
    /**
     * @var ?VersionType $IDVersion
     */
    public ?VersionType $IDVersion = null;

    /**
     * Obligado a la emision de los registros de facturacion
     *
     * @var ?ObligadoEmisionConsultaType $ObligadoEmision
     */
    public ?ObligadoEmisionConsultaType $ObligadoEmision = null;

    /**
     * Destinatario (a veces también denominado contraparte, es decir, el cliente) de la operación
     *
     * @var ?PersonaFisicaJuridicaESType $Destinatario
     */
    public ?PersonaFisicaJuridicaESType $Destinatario = null;

    /**
     * Flag opcional que tendrá valor S si quien realiza la cosulta es el representante/asesor del obligado tributario. Permite, a quien realiza la cosulta, obtener los registros de facturación en los que figura como representante. Este flag solo se puede cumplimentar cuando esté informado el obligado tributario en la consulta
     *
     * @var ?string $IndicadorRepresentante
     */
    public ?string $IndicadorRepresentante = null;
}
