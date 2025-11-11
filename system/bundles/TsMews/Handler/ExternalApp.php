<?php

namespace TsMews\Handler;

use Core\DTO\DateRange;
use TsMews\Api;
use TsMews\Exceptions\AuthenticateException;

class ExternalApp extends \TcExternalApps\Interfaces\ExternalApp {

	const APP_NAME = 'mews';

	const CONFIG_URL = 'mews_url';
	const CONFIG_CLIENT_TOKEN = 'mews_client_token';
	const CONFIG_ACCESS_TOKEN = 'mews_access_token';
	const CONFIG_ABSENCE_CATEGORY = 'mews_absence_category';
	const CONFIG_RATE = 'mews_rate_id';
	const CONFIG_SERVICE = 'mews_service_id';
	const CONFIG_CATEGORY = 'mews_category_';
	const CONFIG_PROVIDER = 'mews_provider_';
	const CONFIG_ROOM_TYPE = 'mews_room_type_';
	const CONFIG_ROOM = 'mews_room_bed_';

	private static $mewsRoomIds = null;

    /**
     * @return string
     */
    public function getIcon() : string {
        return 'fa fa-home';
    }

	/**
	 * @return string
	 */
	public function getTitle() : string {
		return \L10N::t('Mews');
	}

	public function getDescription() : string {
		return \L10N::t('Mews - Description');
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::ACCOMMODATION;
	}

	public function getContent() : ?string {

	    $smarty = new \SmartyWrapper();
	    $smarty->assign('appKey', self::APP_NAME);

	    $smarty->assign('url', \System::d(self::CONFIG_URL, ""));
	    $smarty->assign('clientToken', \System::d(self::CONFIG_CLIENT_TOKEN, ""));
	    $smarty->assign('accessToken', \System::d(self::CONFIG_ACCESS_TOKEN, ""));

	    $absenceCategories = $this->getAbsenceCategories();

	    $smarty->assign('absence_category_id', \System::d(self::CONFIG_ABSENCE_CATEGORY, 0));
	    $smarty->assign('absence_categories', $absenceCategories);
	    $smarty->assign('service_id', \System::d(self::CONFIG_SERVICE, ""));
	    $smarty->assign('rate_id', \System::d(self::CONFIG_RATE, ""));

	    $categories = $this->getCategories();
	    $providers = self::getProviders();
        $roomTypes = $this->getRoomTypes();

	    $smarty->assign('categories', $categories);
	    $smarty->assign('providers', $providers);
	    $smarty->assign('room_types', $roomTypes);

	    $smarty->assign('url_prefix', self::CONFIG_URL);
	    $smarty->assign('client_token_prefix', self::CONFIG_CLIENT_TOKEN);
	    $smarty->assign('access_token_prefix', self::CONFIG_ACCESS_TOKEN);
	    $smarty->assign('absence_category_prefix', self::CONFIG_ABSENCE_CATEGORY);
	    $smarty->assign('service_id_prefix', self::CONFIG_SERVICE);
	    $smarty->assign('rate_id_prefix', self::CONFIG_RATE);
	    $smarty->assign('category_prefix', self::CONFIG_CATEGORY);
	    $smarty->assign('provider_prefix', self::CONFIG_PROVIDER);
	    $smarty->assign('room_type_prefix', self::CONFIG_ROOM_TYPE);
	    $smarty->assign('room_prefix', self::CONFIG_ROOM);

	    return $smarty->fetch('@TsMews/external_apps/config.tpl');
    }

    public function saveSettings(\Core\Handler\SessionHandler $session, \MVC_Request $request) {

	    $required = collect([self::CONFIG_URL, self::CONFIG_CLIENT_TOKEN, self::CONFIG_ACCESS_TOKEN, self::CONFIG_ABSENCE_CATEGORY, self::CONFIG_SERVICE, self::CONFIG_RATE]);
	    $configs = $request->input('config', []);

        $success = false;

        if (!empty($configs)) {

            $checks = collect($configs)
                ->intersectByKeys($required->flip());

            $missing = $checks->filter(function($value, $key) {
                return empty($value);
            });

            if ($missing->isEmpty()) {

                collect($configs)->each(function($value, $config) {
                    $existing = \System::d($config);

                    if(
                        !empty($value) ||
                        $existing !== false
                    ) {
                        \System::s($config, trim($value));
                    }
                });

                $api = new Api(
                    \System::d(self::CONFIG_URL),
                    \System::d(self::CONFIG_CLIENT_TOKEN),
                    \System::d(self::CONFIG_ACCESS_TOKEN)
                );

                try {
                    $dateRange = new DateRange(new \DateTime(), new \DateTime());
                    $api->searchReservations($dateRange);
                    $success = true;
                } catch(AuthenticateException $e) {
                    $session->getFlashBag()->add('error', \L10N::t('Es konnte keine Verbindung aufgebaut werden!'));
                }

            } else {
                $session->getFlashBag()->add('error', \L10N::t('Bitte füllen Sie alle Werte aus'));
            }
        }

	    if($success) {
            $session->getFlashBag()->add('success', \L10N::t('Ihre Einstellungen wurden gespeichert'));
        }
    }

    private function getCategories() {
	    return collect(\Ext_Thebing_Accommodation_Category::getSelectOptions(false))
            ->map(function($name, $id) {
                return [
                    'id' => $id,
                    'name' => $name,
                    'value' => (int)\System::d(self::CONFIG_CATEGORY.$id, 0),
                ];
            })
            ->values()
            ->toArray();
    }

    private function getAbsenceCategories() {
        $sql = "
            SELECT
                `id`,
                `name`
            FROM
                `kolumbus_absence_categories`
            WHERE
                `active` = 1
            ORDER BY
                `name`
        ";

        return \DB::getQueryPairs($sql);

    }

    private static function getProviders() {

	    $providers = \Ext_Thebing_Accommodation::getRepository()
            ->findAll();

	    return collect($providers)
            ->mapToGroups(function(\Ext_Thebing_Accommodation $provider) {
                $data = [
                    'id' => $provider->getId(),
                    'name' => $provider->ext_33,
                    'value' => \System::d(self::CONFIG_PROVIDER.$provider->getId(), 0)
                ];

                $rooms = $provider->getRoomList();
                foreach($rooms as $room) {
                    $data['rooms'][] = [
                        'id' => $room['id'],
                        'name' => $room['name'],
                        'value' => \System::d(self::CONFIG_ROOM.$provider->getId().'_'.$room['id'], "")
                    ];
                }

                return [$provider->default_category_id => $data];
            })
            ->toArray();
    }

    private function getRoomTypes() {
        return collect(\Ext_Thebing_Accommodation_Roomtype::getRepository()->findAll())
            ->map(function($roomType, $id) {
                return [
                    'id' => $roomType->getId(),
                    'name' => $roomType->getName(),
                    'value' => \System::d(self::CONFIG_ROOM_TYPE.$roomType->getId(), ""),
                ];
            })
            ->values()
            ->toArray();
    }

    public static function getBlockCategory() {
	    return \System::d(self::CONFIG_ABSENCE_CATEGORY, 0);
    }

    public static function getServiceId() {
        return \System::d(self::CONFIG_SERVICE, "");
    }

    public static function getRateId() {
        return \System::d(self::CONFIG_RATE, "");
    }

    public static function getRoomTypeId(\Ext_Thebing_Accommodation_Roomtype $roomType) {
        return \System::d(self::CONFIG_ROOM_TYPE.$roomType->getId(), "");
    }

    public static function getRoomId(\Ext_Thebing_Accommodation_Room $room) {
	    $provider = $room->getProvider();
	    return \System::d(self::CONFIG_ROOM.$provider->getId().'_'.$room->getId(), "");
    }

	/**
	 * Liefert alle eingtragenen Mews-IDs
	 *
	 * @return Collection
	 */
    public static function getMewsRoomIds() {

		if (self::$mewsRoomIds === null) {

			$providerData = self::getProviders();

			$roomIds = collect([]);

			foreach ($providerData as $categoryId => $providers) {
				foreach ($providers as $provider) {

					$ids = collect($provider['rooms'])
						->filter(function($room) {
							return !empty($room['value']);
						})
						->mapWithKeys(function($room) {
							return [$room['id'] => $room['value']];
						});

					$roomIds = $roomIds->union($ids);
				}
			}

			self::$mewsRoomIds = $roomIds;
		}

		return self::$mewsRoomIds;
	}
}
