<?php

use Communication\Interfaces\Notifications\NotificationRoute;

/**
 * @property string $id
 * @property string $login_id
 * @property string $app_id
 * @property string $app_version
 * @property string $app_environment
 * @property string $os
 * @property string $os_version
 * @property string $last_action
 * @property string $push_permission
 * @property string $fcm_token
 * @property string $apns_token
 * @property string $additional
 */
class Ext_TS_Inquiry_Contact_Login_Device extends Ext_TC_Basic implements NotificationRoute {

    protected $_sTable = 'ts_inquiries_contacts_logins_devices';

	protected $_bOverwriteCurrentTimestamp = true;

    protected $_aJoinedObjects = array(
        'login_contact' => array(
            'class' => Ext_TS_Inquiry_Contact_Login::class,
            'key' => 'login_id',
            'type' => 'parent'
        )
    );

    public function isAndroid(): bool {
		return str_contains(strtolower($this->os), 'android');
	}

	public function isIOS(): bool {
		return str_contains(strtolower($this->os), 'ios');
	}

	/**
	 * @return Ext_TS_Inquiry_Contact_Login
	 */
    public function getLoginContact() {
        return $this->getJoinedObject('login_contact');
    }

    public function toArray() {
        $columns = ['id', 'os', 'os_version'];
        return array_intersect_key($this->_aData, array_flip($columns));
    }

    public function generateKey() {
        return implode('-', $this->toArray());
    }

	public function getAdditionalData(): array
	{
		if (!empty($this->additional)) {
			return (array)json_decode($this->additional, true);
		}
		return [];
	}

	public function save($bLog = true) {

		$new = !$this->exist();

		$return = parent::save($bLog);

		if ($new) {

			$login = $this->getJoinedObject('login_contact');
			$contact = $login->getContact();
			$traveller = Ext_TS_Inquiry_Contact_Traveller::getInstance($contact->id);
			$inquiries = $traveller->getInquiries(false, true);

			// Sollte fix gehen, daher kein PP
			foreach ($inquiries as $inquiry) {
				\Ext_Gui2_Index_Stack::update('ts_inquiry', $inquiry->id, [
					'has_student_app',
				]);
			}

		}

		return $return;
	}

	public function toNotificationRoute(string $channel)
	{
		$token = $this->isAndroid() ? $this->fcm_token : $this->apns_token;
		return [sprintf('%s:%s', strtolower($this->os), $token), $this->getNotificationName($channel)];
	}

	public function getNotificationName(string $channel): ?string
	{
		return (string)$this->getLoginContact()?->getContact()->getName();

		/*$model = $this->getAdditionalData()['model'] ?? null;

		if ($model) {
			return sprintf('%s (%s - %s)', $this->getLoginContact()?->getContact()->getName(), $this->os, $model);
		}

		return sprintf('%s (%s)', $this->getLoginContact()?->getContact()->getName(), $this->os);*/
	}

}
