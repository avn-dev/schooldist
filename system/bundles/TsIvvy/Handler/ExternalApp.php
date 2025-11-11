<?php

namespace TsIvvy\Handler;

use TsIvvy\Api;
use TsIvvy\Exceptions\AuthenticateException;
use TcExternalApps\Service\AppService;

class ExternalApp extends \TcExternalApps\Interfaces\ExternalApp {

	const APP_NAME = 'ivvy';

	const CONFIG_REGION = 'ivvy_region';
	const CONFIG_ACCESS_KEY = 'ivvy_access_key';
	const CONFIG_ACCESS_SECRET = 'ivvy_access_secret';
	const CONFIG_SETUP_TIME = 'ivvy_setup';
	const CONFIG_SETDOWN_TIME = 'ivvy_setdown';
	const CONFIG_ABSENCE_CATEGORY = 'ivvy_absence_category';
	const CONFIG_ROOM = 'ivvy_room_';
	const CONFIG_ACCOMMODATION_ROOM = 'ivvy_accommodation_room_';
	const CONFIG_DEFAULT_USER = 'ivvy_default_user';
	const CONFIG_USER = 'ivvy_user_';

    public function getIcon() : string {
        return 'fa fa-calendar';
    }

	public function getTitle() : string {
		return \L10N::t('Ivvy');
	}

	public function getDescription() : string {
		return \L10N::t('Ivvy - Description');
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::TUITION;
	}

	public static function isActive(): bool {
		return (
			AppService::hasApp(self::APP_NAME) &&
			!empty(\System::d(self::CONFIG_ACCESS_KEY)) &&
			!empty(\System::d(self::CONFIG_ACCESS_SECRET))
		);
	}

    public function getContent() : ?string {

	    $smarty = new \SmartyWrapper();
	    $smarty->assign('appKey', self::APP_NAME);

	    $apiKey = \System::d(self::CONFIG_ACCESS_KEY, "");
	    $apiSecret = \System::d(self::CONFIG_ACCESS_SECRET, "");

	    if (!empty($apiKey) && !empty($apiSecret)) {

			$api = Api::default();

			$pingSuccess = true;
			$error = "";

			try {
				$api->ping();
			} catch (AuthenticateException $e) {
				$pingSuccess = false;
				$error = $e->getMessage();
			}

			if($pingSuccess) {

				/* @var \Ext_Thebing_School[] $schools */
				$schools = \Ext_Thebing_School::getRepository()->findAll();

				$fideloRooms = [];
				foreach ($schools as $school) {
					$rooms = $school->getClassRooms(true, null, false);

					if (!empty($rooms)) {
						$fideloRooms[] = [
							'box_id' => 'school_' . $school->getId(),
							'box_title' => $school->name,
							'box_class' => 'box-primary',
							'room_prefix' => self::CONFIG_ROOM,
							'rooms' => $rooms
						];
					}
				}

				/* @var \Ext_Thebing_Accommodation[] $accommodations */
				$accommodations = \Ext_Thebing_Accommodation::getRepository()
					->findAll();

				foreach ($accommodations as $accommodation) {

					$defaultCategory = \Ext_Thebing_Accommodation_Category::getInstance($accommodation->default_category_id);
					if (!$defaultCategory->isParking()) {
						continue;
					}

					$rooms = $accommodation->getRoomList(true);

					if (!empty($rooms)) {
						$fideloRooms[] = [
							'box_id' => 'accommodation_' . $accommodation->getId(),
							'box_title' => $accommodation->ext_33,
							'box_class' => 'box-default',
							'room_prefix' => self::CONFIG_ACCOMMODATION_ROOM,
							'rooms' => $rooms
						];
					}

				}

				$ivvyRooms = $api->getRoomList()
					->mapWithKeys(function (Api\Model\Room $room) {
						return [$room->getId() => $room->getName()];
					})
					->toArray();

				$fideloUsers = collect(\User::getRepository()->findAll())
					->mapWithKeys(function (\User $user) {
						return [$user->getId() => $user->getName()];
					})
					->toArray();

				$ivvyUsers = $api->getAccountUserList()
					->mapWithKeys(function (Api\Model\User $user) {
						return [$user->getId() => $user->getFullName()];
					})
					->toArray();

			} else {
				$fideloRooms = $ivvyRooms = $fideloUsers = $ivvyUsers = [];
			}

			$smarty->assign('absenceCategories', $this->getAbsenceCategories());
			$smarty->assign('fideloRooms', $fideloRooms);
			$smarty->assign('ivvyRooms', $ivvyRooms);
			$smarty->assign('fideloUsers', $fideloUsers);
			$smarty->assign('ivvyUsers', $ivvyUsers);
			$smarty->assign('error', $error);
		}

	    return $smarty->fetch('@TsIvvy/external_apps/config.tpl');
    }

    public function saveSettings(\Core\Handler\SessionHandler $session, \MVC_Request $request) {

		$required = collect([self::CONFIG_REGION, self::CONFIG_ACCESS_KEY, self::CONFIG_ACCESS_SECRET]);
		$configs = collect($request->input('config', []));

		$checks = $configs->intersectByKeys($required->flip());

		$missing = $checks->filter(function($value, $key) {
			return empty($value);
		});

		if($missing->isNotEmpty()) {
			$session->getFlashBag()->add('error', \L10N::t('Bitte füllen Sie alle Werte aus'));
			return;
		}

		$success = true;

		if(
			// Haben sich die Zugangsdaten geändert?
			$configs->get(self::CONFIG_REGION) !== \System::d(self::CONFIG_REGION) ||
			$configs->get(self::CONFIG_ACCESS_KEY) !== \System::d(self::CONFIG_ACCESS_KEY) ||
			$configs->get(self::CONFIG_ACCESS_SECRET) !== \System::d(self::CONFIG_ACCESS_SECRET)
		) {
			// Api-Keys prüfen
			$api = new Api($configs->get(self::CONFIG_REGION), $configs->get(self::CONFIG_ACCESS_KEY), $configs->get(self::CONFIG_ACCESS_SECRET));

			try {
				$api->ping();
				Api::clearCache();
			} catch (AuthenticateException $e) {
				$success = false;
			}
		}

		if ($success) {

			$configs->filter(function ($value, $key) {
					$old = \System::d($key);
					// Wenn Raum ausgewählt oder eine alte Einstellung existiert
					return (!empty($value) || $old !== null);
				})
				->each(function($value, $key) {
					\System::s($key, $value);
				});

			$session->getFlashBag()->add('success', \L10N::t('Ihre Einstellungen wurden gespeichert'));
		} else {
			$session->getFlashBag()->add('error', \L10N::t('Es kann keine Verbindung aufgebaut werden!'));
		}

    }

    public static function getAbsenceCategory(): int {
		return (int)\System::d(self::CONFIG_ABSENCE_CATEGORY, 0);
	}

	public static function getSetupTime(): int {
		return (int)\System::d(self::CONFIG_SETUP_TIME, 0);
	}

	public static function getSetdownTime(): int {
		return (int)\System::d(self::CONFIG_SETDOWN_TIME, 0);
	}

	public static function getIvvyDefaultUserId() {
		return \System::d(self::CONFIG_DEFAULT_USER, "");
	}

	public static function getIvvyUserId(int $fideloUserId) {
		$userId = \System::d(self::CONFIG_USER.$fideloUserId, "");

		if (empty($userId)) {
			$userId = self::getIvvyDefaultUserId();
		}

		return $userId;
	}

	public static function getIvvyRoomIdsForFideloRoom(\WDBasic $fideloRoom): ?array {

		if ($fideloRoom instanceof \Ext_Thebing_Tuition_Classroom) {
			$id = \System::d(self::CONFIG_ROOM.$fideloRoom->getId(), "");
		} else if($fideloRoom instanceof \Ext_Thebing_Accommodation_Room) {
			$id = \System::d(self::CONFIG_ACCOMMODATION_ROOM.$fideloRoom->getId(), "");
		} else {
			throw new \InvalidArgumentException('Invalid room object given for ivvy room matching');
		}

		// TODO evtl. auf \TsIvvy\Api\Model\Room umstellen (siehe Api::default()->getRoom());

		if (!empty($id)) {

			[$venueId, $roomId] = explode('_', $id);

			return [
				'venue_id' => $venueId,
				'room_id' => $roomId,
			];
		}

		return null;
	}

	/**
	 * @param $ivvyRoomId
	 * @return \Ext_Thebing_Tuition_Classroom|\Ext_Thebing_Accommodation_Room|null
	 * @throws \Exception
	 */
    public static function getFideloRoomForIvvyRoom($ivvyRoomId): ?\WDBasic {

		$sql = "
			SELECT
				`c_key`
			FROM
				`system_config` 
			WHERE
				`c_value` LIKE :id AND
			    (
			        `c_key` LIKE :key OR 
			        `c_key` LIKE :key2
			    )  
			    
			LIMIT 1    
		";

		$key = \DB::getQueryOne($sql, ['id' => '%_'.$ivvyRoomId, 'key' => self::CONFIG_ROOM.'%', 'key2' => self::CONFIG_ACCOMMODATION_ROOM.'%']);

		if (!is_null($key)) {
			if (strpos($key, self::CONFIG_ROOM) === 0) {
				$roomId = str_replace(self::CONFIG_ROOM, '', $key);
				return \Ext_Thebing_Tuition_Classroom::getInstance($roomId);
			} else if (strpos($key, self::CONFIG_ACCOMMODATION_ROOM) === 0) {
				$roomId = str_replace(self::CONFIG_ACCOMMODATION_ROOM, '', $key);
				return \Ext_Thebing_Accommodation_Room::getInstance($roomId);
			} else {
				throw new \InvalidArgumentException('Invalid room config key given for ivvy room matching.');
			}
		}

		return null;
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
}
