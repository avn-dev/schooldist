<?php

namespace TsStatistic\Helper;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class QuicExcel {

	/**
	 * Mapping von ISO-Code (tc_c.nationality) zu Row im Quic Excel (eine andere Referenz gibt es nicht)
	 *
	 * Für die übrigen Länder (Other *) hat English UK / Quic eine sehr merkwürdige Vorstellung von geopgraphischen
	 * Bezeichnungen, da nicht alle Länder der Welt zweifelsfrei den Kategorien von English UK zugeordnet werden können
	 *
	 * @TODO Hier sind noch nicht alle Länder gemappt, aber die sind auf relevanten Installationen auch nicht in Verwendung
	 * Sollte eine Nationalität fehlen, wird ein Fehler angezeigt, sodass die Datenintegrität gewährleistet ist
	 */
	const MAPPING = [
		'AD' => 'Other Westen Europe',
		'AE' => 'United Arab Emirates',
		'AF' => 'Other Middle East',
		'AG' => 'Other Central America',
		'AI' => 'Other Central America',
		'AL' => 'Albania',
		'AM' => 'Armenia',
		'AN' => 'Other South America', // Gibt es nicht mehr
		'AO' => 'Angola',
		'AQ' => 'Other South America', // Schwachsinn
		'AR' => 'Argentina',
		'AS' => 'USA',
		'AT' => 'Austria',
		'AU' => 'Australia',
		'AW' => 'Other South America',
		'AX' => 'Other Westen Europe',
		'AZ' => 'Azerbaijan',
		'BA' => 'Bosnia and Herzegovina',
		'BB' => 'Other Central America',
		'BD' => 'Other Asia / Far East',
		'BE' => 'Belgium',
		'BF' => 'Other Africa',
		'BG' => 'Bulgaria',
		'BH' => 'Bahrain',
		'BI' => 'Other Africa',
		'BJ' => 'Other Africa',
		'BL' => 'Other Central America',
		'BM' => 'Other North America',
		'BN' => 'Other Asia / Far East',
		'BO' => 'Bolivia',
		'BR' => 'Brazil',
		'BS' => 'Other Central America',
		'BT' => 'Other Asia / Far East',
		'BV' => null,
		'BW' => 'Other Africa',
		'BY' => 'Belarus',
		'BZ' => 'Other Central America',
		'CA' => 'Canada',
		'CC' => 'Other Asia / Far East',
		'CD' => 'Other Africa',
		'CF' => 'Central African Republic',
		'CG' => 'Other Africa',
		'CH' => 'Switzerland',
		'CI' => 'Other Africa',
		'CK' => 'Other Australasia',
		'CL' => 'Chile',
		'CM' => 'Other Africa',
		'CN' => 'China',
		'CO' => 'Colombia',
		'CR' => 'Costa Rica',
		'CS' => 'Other Eastern Europe', // Gibt es nicht mehr
		'CU' => 'Other Central America',
		'CV' => 'Other Africa',
		'CX' => null,
		'CY' => 'Cyprus',
		'CZ' => 'Czech Republic',
		'DE' => 'Germany',
		'DJ' => 'Other Africa',
		'DK' => 'Denmark',
		'DM' => 'Other Central America',
		'DO' => 'Other Central America',
		'DZ' => 'Algeria',
		'EC' => 'Ecuador',
		'EE' => 'Estonia',
		'EG' => 'Egypt',
		'EH' => 'Other Africa',
		'ER' => 'Other Africa',
		'ES' => 'Spain',
		'ET' => 'Other Africa',
		'FI' => 'Finland',
		'FJ' => 'Other Australasia',
		'FK' => 'Other South America',
		'FM' => 'Other Australasia & Oceania',
		'FO' => null,
		'FR' => 'France',
		'GA' => 'Other Africa',
		'GB' => 'United Kingdom',
		'GD' => 'Other Central America',
		'GE' => 'Georgia',
		'GF' => 'Other South America',
		'GG' => null,
		'GH' => 'Other Africa',
		'GI' => 'Other Westen Europe',
		'GL' => 'Other North America',
		'GM' => 'Other Africa',
		'GN' => 'Other Africa',
		'GP' => 'Other Central America',
		'GQ' => 'Other Africa',
		'GR' => 'Greece',
		'GS' => null,
		'GT' => 'Other Central America',
		'GU' => 'Other Australasia & Oceania',
		'GW' => 'Other Africa',
		'GY' => 'Other South America',
		'HK' => 'Hong Kong',
		'HM' => null,
		'HN' => 'Other Central America',
		'HR' => 'Croatia',
		'HT' => 'Other Central America',
		'HU' => 'Hungary',
		'ID' => 'Indonesia',
		'IE' => 'Ireland',
		'IL' => 'Israel',
		'IM' => 'Other Western Europe',
		'IN' => 'India',
		'IO' => 'Other Asia / Far East',
		'IQ' => 'Iraq',
		'IR' => 'Iran',
		'IS' => 'Iceland',
		'IT' => 'Italy',
		'JE' => null,
		'JM' => 'Other Central America',
		'JO' => 'Jordan',
		'JP' => 'Japan',
		'KE' => 'Other Africa',
		'KG' => 'Kyrgyzstan',
		'KH' => 'Cambodia',
		'KI' => 'Other Asia / Far East',
		'KM' => 'Other Africa',
		'KN' => 'Other Central America',
		'KP' => 'Other Asia / Far East',
		'KR' => 'Korea',
		'KW' => 'Kuwait',
		'KY' => 'Other Central America',
		'KZ' => 'Kazakhstan',
		'LA' => 'Other Asia / Far East',
		'LB' => 'Lebanon',
		'LC' => null,
		'LI' => 'Liechtenstein',
		'LK' => 'Other Asia / Far East',
		'LR' => null,
		'LS' => null,
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'LV' => 'Latvia',
		'LY' => 'Libya',
		'MA' => 'Morocco',
		'MC' => 'Monaco',
		'MD' => 'Moldova',
		'ME' => 'Montenegro',
		'MF' => null,
		'MG' => 'Other Africa',
		'MH' => null,
		'MK' => 'Macedonia',
		'ML' => 'Other Africa',
		'MM' => 'Other Asia / Far East',
		'MN' => 'Mongolia',
		'MO' => 'Macao',
		'MP' => null,
		'MQ' => 'Other Central America',
		'MR' => 'Other Africa',
		'MS' => null,
		'MT' => 'Other Westen Europe',
		'MU' => 'Other Africa',
		'MV' => 'Other Asia / Far East',
		'MW' => null,
		'MX' => 'Mexico',
		'MY' => 'Malaysia',
		'MZ' => 'Other Africa',
		'NA' => 'Other Africa',
		'NC' => 'Other Australasia',
		'NE' => 'Other Africa',
		'NF' => null,
		'NG' => 'Nigeria',
		'NI' => 'Other Central America',
		'NL' => 'Netherlands',
		'NO' => 'Norway',
		'NP' => 'Nepal',
		'NR' => 'Other Australasia',
		'NU' => 'Other Australasia & Oceania',
		'NZ' => 'New Zealand',
		'OM' => 'Oman',
		'PA' => 'Panama',
		'PE' => 'Peru',
		'PF' => 'Other Asia / Far East',
		'PG' => 'Other Australasia',
		'PH' => 'Philippines',
		'PK' => 'Pakistan',
		'PL' => 'Poland',
		'PM' => null,
		'PN' => null,
		'PR' => 'Other Central America',
		'PS' => 'Palestine',
		'PT' => 'Portugal',
		'PW' => null,
		'PY' => 'Paraguay',
		'QA' => 'Qatar',
		'QO' => null,
		'RE' => null,
		'RO' => 'Romania',
		'RS' => 'Serbia',
		'RU' => 'Russia',
		'RW' => 'Other Africa',
		'SA' => 'Saudi Arabia',
		'SB' => 'Other Australasia',
		'SC' => 'Other Africa',
		'SD' => 'Other Africa',
		'SE' => 'Sweden',
		'SG' => 'Singapore',
		'SH' => null,
		'SI' => 'Slovenia',
		'SJ' => null,
		'SK' => 'Slovakia',
		'SL' => 'Other Africa',
		'SM' => 'Other Western Europe',
		'SN' => 'Other Africa',
		'SO' => 'Other Africa',
		'SR' => null,
		'ST' => null,
		'SV' => 'Other Central America',
		'SY' => 'Syria',
		'SZ' => 'Other Africa',
		'TC' => null,
		'TD' => 'Other Africa',
		'TF' => null,
		'TG' => 'Other Africa',
		'TH' => 'Thailand',
		'TJ' => 'Tajikistan',
		'TK' => null,
		'TL' => 'Other Asia / Far East',
		'TM' => 'Turkmenistan',
		'TN' => 'Tunisia',
		'TO' => null,
		'TR' => 'Turkey',
		'TT' => 'Other South America',
		'TV' => null,
		'TW' => 'Taiwan',
		'TZ' => 'Other Africa',
		'UA' => 'Ukraine',
		'UG' => 'Other Africa',
		'UM' => null,
		'US' => 'USA',
		'UY' => 'Uruguay',
		'UZ' => 'Uzbekistan',
		'VA' => null,
		'VC' => null,
		'VE' => 'Venezuela',
		'VG' => null,
		'VI' => null,
		'VN' => 'Vietnam',
		'VU' => 'Other Australasia',
		'WF' => null,
		'WS' => null,
		'XK' => 'Other Eastern Europe',
		'YE' => 'Yemen',
		'YT' => null,
		'ZA' => 'Other Africa',
		'ZM' => 'Other Africa',
		'ZW' => 'Other Africa'
	];

	/**
	 * @var Spreadsheet
	 */
	private $oSpreadsheet;

	/**
	 * @var array
	 */
	private $aExcelNationalities = [];

	/**
	 * @return string
	 */
	public function getQuicExcelFile() {

		$sFile = \Util::getDocumentRoot().'system/bundles/TsStatistic/Resources/files/QUIC_statistics_submission_form_2018.01.xls';

		if(!is_file($sFile)) {
			throw new \RuntimeException('Quic excel file does not exist! '.$sFile);
		}

		return $sFile;

	}

	/**
	 * Excel von Quic in PHPExcel einlesen
	 *
	 * @return Spreadsheet
	 */
	public function getQuicExcel() {

		if($this->oSpreadsheet !== null) {
			return $this->oSpreadsheet;
		}

		$oReader = IOFactory::createReaderForFile($this->getQuicExcelFile());
		$this->oSpreadsheet = $oReader->load($this->getQuicExcelFile());

		return $this->oSpreadsheet;

	}

	/**
	 * Nationalitäten aus dem Excel auslesen, Key entspricht Row im Excel
	 *
	 * @return array
	 */
	public function getExcelNationalities() {

		if(!empty($this->aExcelNationalities)) {
			return $this->aExcelNationalities;
		}

		$oSheet = $this->getQuicExcel()->getSheet(0);

		$aRows = $oSheet->rangeToArray('A1:A'.$oSheet->getHighestRow());

		$bFound = false;
		foreach($aRows as $iKey => $aRow) {
			if(
				$bFound &&
				!empty($aRow[0])
			) {
				$this->aExcelNationalities[$iKey + 1] = trim($aRow[0]);
			}

			if($aRow[0] === 'Nationality of student') {
				$bFound = true;
			}
		}

		if(!$bFound) {
			throw new \RuntimeException('Could not find begin of nationalities in Quic excel file');
		}

		$this->checkExcelNationalityMapping();

		return $this->aExcelNationalities;

	}

	/**
	 * Prüfen, ob alle Nationalitäten aus dem Excel in dieser Klasse gemappt sind
	 *
	 * Prüfung dient der Sicherheit, sollte das Excel von Quic einmal ausgetauscht werden
	 */
	private function checkExcelNationalityMapping() {

		foreach($this->aExcelNationalities as $sNationality) {
			if(array_search($sNationality, self::MAPPING) === false) {
				throw new \RuntimeException('Quic excel nationality "'.$sNationality.'" not mapped');
			}
		}

	}

	/**
	 * @param $sIso
	 * @return string|null
	 */
	public function getNationalityByKey($sIso) {

		if(isset(self::MAPPING[$sIso])) {
			return self::MAPPING[$sIso];
		}

		return null;

	}

}
