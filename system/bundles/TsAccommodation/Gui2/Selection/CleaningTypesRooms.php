<?php

namespace TsAccommodation\Gui2\Selection;

class CleaningTypesRooms extends \Ext_Gui2_View_Selection_Abstract {

    /**
     * {@inheritdoc}
     */
    public function getOptions($selectedIds, $saveField, &$wdbasic) {
        /* @var $wdbasic \TsAccommodation\Entity\CleaningType */
        $rooms = [];

        if(!empty($wdbasic->accommodation_categories)) {
            $providers = \Ext_Thebing_Accommodation::getRepository()
                ->findAll();

            foreach ($providers as $provider) {
                /* @var $provider \Ext_Thebing_Accommodation */

                // Anbieter hat Kategorie nicht
                if(empty(array_intersect($provider->accommodation_categories, $wdbasic->accommodation_categories))) {
                    continue;
                }

                $providerRooms = $provider->getRoomList(true);

                foreach($providerRooms as $roomId => $roomName) {
                    $rooms[$roomId] = $provider->getName().' - '.$roomName;
                }
            }
        }

        return $rooms;
    }

}
