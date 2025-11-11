<?

class Ext_Thebing_System_Checks_CleanFiles extends Ext_Thebing_System_ThebingCheck {

	public function getTitle() {
		$sTitle = 'Clean files';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Removes unneeded files and directories.';
		return $sDescription;
	}

	public function isNeeded() {
		if(
			Ext_Thebing_Util::isDevSystem() ||
			Ext_Thebing_Util::isTestSystem() ||
			Ext_Thebing_Util::isLive2System()
		) {
			return false;
		}
		return true;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '512M');

		$aFiles = array(
			'/css',
			'/dbModels',
			'/english',
			'/german',
			'/img',
			'/nbproject',
			'/picture_library',
			'/plesk-stat',
			'/var',
			'/zend',
			'/phpinfo.php',
			'/system/includes/__classes',
			'/system/includes/classes',
			'/system/extensions/zend',
			'/admin/extensions/ac',
			'/media/thebing.com',
			'/media/templates',
			'/media/image',
			'/media/file',
			'/media/secure/temp',
			'/media/patrick.jpg',
			'/media/smithsig1.jpg',
			'/media/smithsig.jpg',
			'/media/umfrage_header.gif',
			'/media/temp',
			'/media/original',
			'/media/Beispiel.jpg',
			'/media/header.gif',
			'/media/firma_paloma.jpg',
			'/media/ac_rechnung.pdf',
			'/media/ac_rechnung_deutsch_international_logo.pdf',
			'/media/ac_rechnungsvorlage_ac_usa_mit_logo.pdf',
			'/media/ac_rechnungsvorlage_deutsch_mit_logo.pdf',
			'/media/ac_rechnungsvorlage_international_mit_logo.pdf',
			'/media/ac_rechnungsvorlage_international_mit_logo_auf_englisch.pdf',
			'/media/ac_rechnungsvorlage_international_ohne_logo.pdf',
			'/media/ac-ec_invoice_temp_w_logo.pdf',
			'/media/ac-rechnungsvorlage_deutsch_mit_logo.pdf',
			'/media/ac-rechnungsvorlage_international_mit_logo.pdf',
			'/media/ac-rechnungsvorlage_international_mit_logo_auf_englisch.pdf'
		);

		foreach((array)$aFiles as $sFile) {
			$sFile = \Util::getDocumentRoot().$sFile;
			Ext_Thebing_Util::recursiveDelete($sFile);
		}

		return true;

	}

}
