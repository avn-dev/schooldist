<?php

namespace Tc\Gui2\Selection\Exchangerate;

use Illuminate\Support\Arr;

class Sources extends \Ext_Gui2_View_Selection_Filter_Abstract {

    public function getOptions($parentGuiIds, &$gui)
    {
        $table = \Ext_TC_Exchangerate_Table::getInstance(Arr::first($parentGuiIds));
        $sources = collect($table->getSources())
            ->mapWithKeys(fn (\Ext_TC_Exchangerate_Table_Source $source) => [$source->id => $source->name]);

        return \Ext_Gui2_Util::addLabelItem($sources->toArray(), $gui->t('Quelle'));
    }

}