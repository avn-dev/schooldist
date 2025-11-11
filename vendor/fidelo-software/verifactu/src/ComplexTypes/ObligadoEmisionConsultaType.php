<?php

namespace FideloSoftware\Verifactu\ComplexTypes;

/**
 * Class representing ObligadoEmisionConsultaType
 *
 * Datos de una persona física o jurídica Española con un NIF asociado
 * XSD Type: ObligadoEmisionConsultaType
 */
class ObligadoEmisionConsultaType
{
    /**
     * @var ?string $NombreRazon
     */
    private ?string $NombreRazon = null;

    /**
     * @var ?string $NIF
     */
    private ?string $NIF = null;

    /**
     * Gets as NombreRazon
     *
     * @return ?string
     */
    public function getNombreRazon(): ?string
    {
        return $this->NombreRazon;
    }

    /**
     * Sets a new NombreRazon
     *
     * @param ?string $nombreRazon
     * @return self
     */
    public function setNombreRazon(?string $nombreRazon): self
    {
        $this->NombreRazon = $nombreRazon;
        return $this;
    }

    /**
     * Gets as NIF
     *
     * @return ?string
     */
    public function getNIF(): ?string
    {
        return $this->NIF;
    }

    /**
     * Sets a new NIF
     *
     * @param ?string $nIF
     * @return self
     */
    public function setNIF(?string $nIF): self
    {
        $this->NIF = $nIF;
        return $this;
    }
}

