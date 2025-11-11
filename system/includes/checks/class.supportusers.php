<?php

class Checks_SupportUsers extends GlobalChecks {

	private array $internTestInstallations = [
		// SCHOOL
		'test.school',
		// AGENCY
		'test.agency',
	];

	private array $internInstallations = [
		// SCHOOL
		'sms',
		'sms-copy',
		'sms-copy-2',
		'demo-school',
		// AGENCY
		'ams',
		'demo-ams',
		// DEV
		'dev.framework',
		'dev.core',
		'dev.school',
		'dev.agency',
	];

	public function getTitle()
	{
		return 'Fidelo Support';
	}

	public function getDescription()
	{
		return 'Reduces the number of Fidelo support users to one';
	}

	public function executeCheck()
	{
		if (
			// Im Office nichts machen
			\Util::getHost() === 'fidelo.com' ||
			// Auf allen internen Installationen nichts machen (außer $this->internTestInstallations)
			in_array(\Util::getInstallationKey(), $this->internInstallations)
		) {
			return true;
		}

		if (!\Util::backupTable('system_user')) {
			__pout('Backup error');
			return false;
		}

		\DB::begin(__METHOD__);

		try {

			$internTestInstallation = in_array(\Util::getInstallationKey(), $this->internTestInstallations);

			$supportUserId = $this->checkSupportUser($internTestInstallation);

			if (!$internTestInstallation) {
				$this->deleteOtherFideloUsers($supportUserId);
			}

		} catch (\Throwable $e) {
			\DB::rollBack(__METHOD__);
			__pout($e);

			$this->logError('Check Failed', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);

			return false;
		}

		\DB::commit(__METHOD__);

		return true;
	}

	protected function checkSupportUser(bool $internTestInstallation): int
	{
		$sql = "
			SELECT
				`id`
			FROM
			    `system_user`
		";

		$directSupportSql = $sql . " 
			WHERE
				`active` = 1 AND
				(
					LOWER(`username`) = 'support' OR 
					(
						LOWER(`email`) LIKE '%support%' AND
						(
							LOWER(`email`) LIKE '%@fidelo.com' OR
							LOWER(`email`) LIKE '%@thebing.com' OR
							LOWER(`email`) LIKE '%@p32.de'	
						)
					)
			    )
			LIMIT 1
		";

		// Direkten Support-Kontakt suchen
		$userId = (int)\DB::getQueryOne($directSupportSql);

		// Falls keiner existiert und wir auf einer Kundeninstallation sind dann den ersten Fidelo-User nehmen
		if (empty($userId) && !$internTestInstallation) {
			$firstFideloUser = $sql . "
				WHERE
					`active` = 1 AND
					(
						LOWER(`email`) LIKE '%@fidelo.com' OR
						LOWER(`email`) LIKE '%@thebing.com' OR
						LOWER(`email`) LIKE '%@p32.de'	
					)
				LIMIT 1
			";

			$userId = (int)\DB::getQueryOne($firstFideloUser);

			if (empty($userId)) {
				// Falls auf der Kundeninstallation kein Fidelo-User gefunden werden konnte dann den Check abbrechen und
				// man muss manuell schauen was los ist.
				throw new \RuntimeException('Missing support user or any fidelo user.');
			}
		}

		$update = [
			'firstname' => 'Fidelo',
			'lastname' => 'Support',
			'username' => 'support',
			'email' => sprintf('support.%s@p32.de', \Util::getInstallationKey()),
			'authentication' => \User::AUTH_PASSKEYS_EXTERN,
			'multi_login' => 1,
			'status' => 1,
			'blocked_until' => 0,
			'login_failed' => 0,
		];

		if (empty($userId)) {
			$userId = \DB::insertData('system_user', $update);
		} else {
			\DB::updateData('system_user', $update, ['id' => $userId]);
		}

		$this->logInfo('Support user', ['id' => $userId]);

		return $userId;
	}

	protected function deleteOtherFideloUsers(int $supportUserId): void
	{
		$sql = "
			SELECT
				`id`,
				`username`,
				`email`
			FROM
			    `system_user`
			WHERE
				`active` = 1 AND
				`id` != :support_id AND
				(
					LOWER(`email`) LIKE '%@fidelo.com' OR
					LOWER(`email`) LIKE '%@thebing.com' OR
					LOWER(`email`) LIKE '%@p32.de'	
				)
		";

		$fideloUsers = (array)\DB::getQueryRows($sql, ['support_id' => $supportUserId]);

		foreach ($fideloUsers as $user) {
			\DB::updateData('system_user', [
				'username' => Ext_TC_Util::generateRandomString(8).'_'.$user['username'],
				'email' => Ext_TC_Util::generateRandomString(8).'_'.$user['email'],
				'active' => 0
			], ['id' => $user['id']]);

			$this->logInfo('Delete Fidelo user', ['id' => $user['id']]);
		}
	}

}
