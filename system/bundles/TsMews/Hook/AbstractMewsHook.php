<?php

namespace TsMews\Hook;

use Core\Service\Hook\AbstractHook;
use TcExternalApps\Service\AppService;
use TsMews\Api;
use TsMews\Handler\ExternalApp;
use TsMews\Service\Synchronization;

abstract class AbstractMewsHook extends AbstractHook {

    /**
     * Prüft ob die Mews App installiert und vollständig ist
     *
     * @return bool
     */
    protected function hasApp(): bool {
        return (
            AppService::hasApp(ExternalApp::APP_NAME) &&
            !empty(\System::d(ExternalApp::CONFIG_URL)) &&
            !empty(\System::d(ExternalApp::CONFIG_CLIENT_TOKEN)) &&
            !empty(\System::d(ExternalApp::CONFIG_ACCESS_TOKEN))
        );
    }

    /**
     * Baut ein Api-Objekt für Mews auf
     *
     * @return Api
     */
    protected function api(): Api {
        return Api::default();
    }

    /**
     * Prüft ob der Unterkunftsanbieter mit Mews verknüpft ist
     *
     * @param \Ext_Thebing_Accommodation $provider
     * @return bool
     */
    protected function checkProviderSync(\Ext_Thebing_Accommodation $provider): bool {
        return Synchronization::checkProviderSync($provider);
    }

}
