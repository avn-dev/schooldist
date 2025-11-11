<?php

namespace TsAccounting\Service\Placeholder\Company;

use Exception;
use Ext_Gui2;
use Ext_Thebing_Inquiry_Document_Version_Item;

class TemplateReceiptText extends \Ext_Thebing_Placeholder
{

	/**
	 * @var Ext_Gui2
	 */
	protected $_oGui;

	/**
	 * @var Ext_Thebing_Inquiry_Document_Version_Item
	 */
	protected $_oEntryObject;

	/**
	 * @var array
	 */
	protected $_aEntry;

	/**
	 *
	 * @var \Ext_TS_Inquiry
	 */
	protected $inquiry;

	/**
	 * @param Ext_Gui2 $oGui
	 */
	public function __construct(Ext_Gui2 $oGui = null)
	{
		parent::__construct();
		$this->_oGui = $oGui;
	}

	/**
	 * @param string $sType
	 * @return array
	 * @throws Exception
	 */
	public function getPlaceholders($sType = '')
	{

		if (!$this->_oGui instanceof Ext_Gui2) {
			throw new Exception('pls define a gui object if u want to display the table!');
		}

		return array(
			array(
				'placeholders' => array(
					'service_name' => $this->_oGui->t('Zeigt den Service an welcher für diese Position verbucht wird.'),
					'service_nickname' => $this->_oGui->t('Zeigt die Kurzform des Services an, wenn verfügbar.'),
					'service_type' => $this->_oGui->t('Zeigt den Typen der Position an.'),
					'duration' => $this->_oGui->t('Zeigt die Wochenanzahl an.'),
					'lessons' => $this->_oGui->t('Zeigt die Anzahl der Lektionen an.'),
					'nights' => $this->_oGui->t('Zeigt die Anzahl der Nächte an (Wichtig für Extranächte).'),
					'start_date' => $this->_oGui->t('Zeigt das Startdatum der Leistung an.'),
					'end_date' => $this->_oGui->t('Zeigt das Enddatum der Leistung an.'),
					'agency' => $this->_oGui->t('Zeigt die Agentur an.'),
					'agency_number' => $this->_oGui->t('Zeigt die Nummer der Agentur an.'),
					'sponsor' => $this->_oGui->t('Zeigt den Sponsor an.'),
					'sponsor_number' => $this->_oGui->t('Zeigt die Nummer des Sponsors an.'),
					'firstname' => $this->_oGui->t('Zeigt den Vornamen des Kunden an.'),
					'surname' => $this->_oGui->t('Zeigt den Nachnamen des Kunden an.'),
					'customernumber' => $this->_oGui->t('Zeigt die Nummer des Kunden an.'),
					'document_type' => $this->_oGui->t('Zeigt den Typ des Dokumentes an.'),
					'document_is_credit' => $this->_oGui->t('Zeigt an, ob Dokument eine Gutschrift ist.'),
					'document_number' => $this->_oGui->t('Zeigt die Nummer des Dokumentes an.'),
					'main_document_number' => $this->_oGui->t('Zeigt die Nummer des Ursprungsdokumentes an.'),
					'document_date' => $this->_oGui->t('Zeigt das Datum des Ursprungsdokumentes an.'),
					'original_position' => $this->_oGui->t('Zeigt die komplette Beschreibung der Position an, wie sie auf der Rechnung dargestellt wurde.'),
					'amount' => $this->_oGui->t('Zeigt den Betrag an.'),
					'currency' => $this->_oGui->t('Währung des Betrags (ISO-Kürzel)'),
					'address_type' => $this->_oGui->t('Typ der Adresse (address, agency, billing, group, sponsor)'),
					'address_company' => $this->_oGui->t('Firma der Rechnungsadresse, falls vorhanden'),
					'address_firstname' => $this->_oGui->t('Vorname der Rechnungsadresse, falls vorhanden'),
					'address_surname' => $this->_oGui->t('Nachname der Rechnungsadresse, falls vorhanden'),
					'account_number_income' => $this->_oGui->t('Habenkonto'),
					'account_number_expense' => $this->_oGui->t('Sollkonto'),
				),
			)
		);

	}

	public function setInquiry(\Ext_TS_Inquiry $inquiry)
	{
		$this->inquiry = $inquiry;
	}

	/**
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 */
	public function setObject(Ext_Thebing_Inquiry_Document_Version_Item $oItem)
	{
		$this->_oEntryObject = $oItem;
		if ($oItem->getSchool() instanceof \Ext_Thebing_School) {
			$this->_iSchoolId = $oItem->getSchool()->id;
		}
		$this->inquiry = $this->_oEntryObject->getInquiry();
	}

	public function setEntry(array $aEntry)
	{
		$this->_aEntry = $aEntry;
	}

	protected function _getReplaceValue($sPlaceholder, array $aPlaceholder)
	{

		$mValue = null;

		switch ($sPlaceholder) {
			case 'service_name':
			case 'service_nickname':
				$bShort = $sPlaceholder !== 'service_name';
				$mValue = $this->_oEntryObject->getServiceName($bShort);
				break;
			case 'service_type':
				$mValue = $this->_oEntryObject->getTypeName();
				break;
			case 'amount':
				$mValue = \Ext_Thebing_Format::Number($this->_aEntry['amount'], $this->inquiry->currency_id);
				break;
			case 'currency':
				$currency = \Ext_Thebing_Currency::getInstance($this->inquiry->currency_id);
				$mValue = $currency->iso4217;
				break;
			case 'duration':
				$mValue = '';
				$oService = $this->_oEntryObject->getJourneyService();
				if ($oService) {
					$mValue = $oService->getWeeks();
				}
				break;
			case 'lessons':
				$mValue = '';
				$oService = $this->_oEntryObject->getJourneyService();
				if ($oService instanceof \Ext_TS_Inquiry_Journey_Course) {
					$mValue = $oService->getUnits();
				}
				break;
			case 'nights':
				$mValue = $this->_oEntryObject->nights;
				break;
			case 'start_date':
				$mValue = $this->_oEntryObject->index_from;
				if ($mValue && $mValue != '0000-00-00') {
					$oFormat = new \Ext_Thebing_Gui2_Format_Date();
					$mValue = $oFormat->formatByValue($mValue);
				} else {
					$mValue = '';
				}
				break;
			case 'end_date':
				$mValue = $this->_oEntryObject->index_until;
				if ($mValue && $mValue != '0000-00-00') {
					$oFormat = new \Ext_Thebing_Gui2_Format_Date();
					$mValue = $oFormat->formatByValue($mValue);
				} else {
					$mValue = '';
				}
				break;
			case 'agency':
				$mValue = '';
				if ($this->inquiry) {
					$oAgency = $this->inquiry->getAgency();
					if ($oAgency) {
						$mValue = $oAgency->getName(true);
					}
				}
				break;
			case 'agency_number':
				$mValue = $this->inquiry?->getAgency()?->getNumber() ?? '';
				break;
			case 'sponsor':
				$mValue = '';
				if ($this->inquiry) {
					$sponsor = $this->inquiry->getSponsor();
					if ($sponsor) {
						$mValue = $sponsor->name;
					}
				}
				break;
			case 'sponsor_number':
				$mValue = '';
				if ($this->inquiry) {
					$sponsor = $this->inquiry->getSponsor();
					if ($sponsor) {
						$mValue = $sponsor->getNumber();
					}
				}
				break;
			case 'firstname':
				$mValue = '';
				if ($this->inquiry) {
					$oTraveller = $this->inquiry->getFirstTraveller();
					if ($oTraveller) {
						$mValue = $oTraveller->firstname;
					}
				}
				break;
			case 'surname':
				$mValue = '';
				if ($this->inquiry) {
					$oTraveller = $this->inquiry->getFirstTraveller();
					if ($oTraveller) {
						$mValue = $oTraveller->lastname;
					}
				}
				break;
			case 'customernumber':
				$mValue = '';
				if ($this->inquiry) {
					$oTraveller = $this->inquiry->getFirstTraveller();
					if ($oTraveller) {
						$mValue = $oTraveller->getCustomerNumber();
					}
				}
				break;
			case 'document_number':
				$mValue = '';
				$oDocument = $this->_oEntryObject->getDocument();
				if ($oDocument) {
					$mValue = $oDocument->document_number;
				}
				break;
			case 'document_type':
				$mValue = '';
				$oDocument = $this->_oEntryObject->getDocument();
				if ($oDocument) {
					$mValue = $oDocument->type;
				}
				break;
			case 'document_is_credit':
				$mValue = '';
				$oDocument = $this->_oEntryObject->getDocument();
				if ($oDocument) {
					$mValue = $oDocument->is_credit;
				}
				break;
			case 'main_document_number':
				$mValue = '';
				$oDocument = $this->_oEntryObject->getDocument();
				if ($oDocument) {
					$oParent = $oDocument->getParentDocument();
					if ($oParent) {
						$mValue = $oParent->document_number;
					}
				}
				break;
			case 'document_date':
				$mValue = '';
				$oDocument = $this->_oEntryObject->getDocument();
				if ($oDocument) {
					$mValue = $oDocument->created;
					$oFormat = new \Ext_Thebing_Gui2_Format_Date();
					$mValue = $oFormat->formatByValue($mValue);
				}
				break;
			case 'original_position':
				$mValue = $this->_oEntryObject->description;
				break;
			case 'address_type':
				$mValue = $this->_aEntry['address_type'];
				break;
			case 'address_firstname':
				$aAddressData = $this->_oEntryObject->getVersion()->getAddressNameData();
				$mValue = $aAddressData['firstname'] ?? '';
				break;
			case 'address_surname':
				$aAddressData = $this->_oEntryObject->getVersion()->getAddressNameData();
				$mValue = $aAddressData['lastname'] ?? '';
				break;
			case 'account_number_expense':
				$mValue = $this->_aEntry['account_number_expense'] ?? '';
				break;
			case 'account_number_income':
				$mValue = $this->_aEntry['account_number_income'] ?? '';
				break;
			case 'address_company':
				$aAddressData = $this->_oEntryObject->getVersion()->getAddressNameData();
				$mValue = $aAddressData['object_name'] ?? '';
				break;
			default:
				$mValue = parent::_getReplaceValue($sPlaceholder, $aPlaceholder);
				break;
		}

		return $mValue;
	}

}