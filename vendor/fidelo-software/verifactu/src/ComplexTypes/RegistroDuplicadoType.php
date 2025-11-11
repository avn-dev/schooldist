<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

/**
 * Class representing RegistroDuplicadoType
 *
 * 
 * XSD Type: RegistroDuplicadoType
 */
class RegistroDuplicadoType
{
    /**
     * IdPeticion asociado a la factura registrada previamente en el sistema. Solo se suministra si la factura enviada es rechazada por estar duplicada
     *
     * @var ?string $IdPeticionRegistroDuplicado
     */
    public ?string $IdPeticionRegistroDuplicado = null;

    /**
     * Estado del registro duplicado almacenado en el sistema. Los estados posibles son: Correcta, AceptadaConErrores y Anulada. Solo se suministra si la factura enviada es rechazada por estar duplicada
     *
     * @var ?string $EstadoRegistroDuplicado
     */
    public ?string $EstadoRegistroDuplicado = null;

    /**
     * Código del error de registro duplicado almacenado en el sistema, en su caso.
     *
     * @var ?int $CodigoErrorRegistro
     */
    public ?int $CodigoErrorRegistro = null;

    /**
     * Descripción detallada del error de registro duplicado almacenado en el sistema, en su caso.
     *
     * @var ?string $DescripcionErrorRegistro
     */
    public ?string $DescripcionErrorRegistro = null;
}
