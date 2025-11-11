<?php

namespace TsAccommodation\Gui2\Format\Cleaning;

use TsAccommodation\Entity\Cleaning\Status as StatusEntity;

class Status extends \Ext_Gui2_View_Format_Abstract {

    private $html = true;

    public function __construct(bool $html = true) {
        $this->html = true;
    }

    public function format($value, &$column = null, &$resultData = null){

        if($value === StatusEntity::STATUS_CLEAN) {
            $html = sprintf('<div class="badge bg-green">%s</div>', $this->oGui->t('Sauber'));
        } else if($value === StatusEntity::STATUS_NEEDS_REPAIR) {
            $html = sprintf('<div class="badge bg-yellow">%s</div>', $this->oGui->t('Reparatur nötig'));
        } else if($value === StatusEntity::STATUS_CHECKED) {
            $html = sprintf('<div class="badge bg-black">%s</div>', $this->oGui->t('Geprüft'));
        } else {
            $html = sprintf('<div class="badge bg-red">%s</div>', $this->oGui->t('Schmutzig'));
        }

        if(!$this->html) {
            return strip_tags($html);
        }

        return $html;
    }

}
