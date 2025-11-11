<?
class Ext_Thebing_System_Checks_Releasesystemuserdata extends GlobalChecks {

	public function executeCheck(){

		Ext_Thebing_Util::backupTable('system_user');

		$sSql = " UPDATE
						`system_user`
					SET
						`username` = CONCAT(substring(MD5(RAND()), 1, 8), '_', `username`),
						`email` = CONCAT(substring(MD5(RAND()), 1, 8), '_', `email`),
						`changed` = `changed`
					WHERE
						`active` = 0 ";
		DB::executeQuery($sSql);


		return true;
	}

	public function getTitle() {
		$sTitle = 'Release old Userdata';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Release E-Mail & Username from old User';
		return $sDescription;
	}

}