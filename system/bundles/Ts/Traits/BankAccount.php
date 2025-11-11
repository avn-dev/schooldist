<?php

namespace Ts\Traits;

trait BankAccount {

    /**
     * Prüft ob die Schule eine Iban hat.
     *
     * @return bool
     */
    public function hasIban() {
        return $this->iban !== '';
    }

    /**
     * Prüft ob die Schule eine Bic hat.
     *
     * @return bool
     */
    public function hasBic() {
        return $this->bic !== '';
    }

    /**
     * Prüft ob die Schule eine Iban und eine Bic hat.
     *
     * @return bool
     */
    public function hasIbanAndBic() {

        if(
            $this->hasIban() &&
            $this->hasBic()
        ) {
            return true;
        }

        return false;

    }

}
