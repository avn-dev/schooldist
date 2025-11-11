<?php

use \Carbon\Carbon;
use Carbon\CarbonPeriod;
use Ts\Dto\Amount;

/**
 * @property int $id
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property int $active
 * @property int $creator_id
 * @property int $document_id
 * @property int $version
 * @property int $template_id
 * @property string $template_language
 * @property int $payment_condition_id
 * @property int $invoice_select_id Ausgewähltes Dokument im Select
 * @property string $date (DATE)
 * @property string $txt_address
 * @property string $txt_subject
 * @property string $txt_intro
 * @property string $txt_outro
 * @property string $txt_enclosures
 * @property string $txt_pdf
 * @property int $signature_user_id
 * @property string $txt_signature
 * @property string $signature
 * @property string $path
 * @property string $comment
 * @property int $user_id
 * @property string $sent (TIMESTAMP)
 * @property int $tax
 * @property array $layout_fields
 * @property array $addresses
 * @property int $has_commissionable_items
 * @property int $status (BIT)
 * @property ?int $company_id
 */
class Ext_Thebing_Inquiry_Document_Version extends Ext_Thebing_Basic {
	use \Core\Traits\BitwiseFlag;

	const STATUS_PDF_CREATION_FAILED = 1;

	protected $_sTable = 'kolumbus_inquiries_documents_versions';

	protected $_aFormat = array(
		'changed' => array(
			'format' => 'TIMESTAMP'
			),
		'created' => array(
			'format' => 'TIMESTAMP'
			),
		'document_id' => array(
			'required'=>true,
			'validate'=>'INT_POSITIVE'
		)
	);

	protected $_aJoinTables = array(
		'layout_fields' => array(
			'table' => 'kolumbus_inquiries_documents_versions_fields',
			'foreign_key_field' => '',
			'primary_key_field' => 'version_id',
			'readonly' => true
		),
		'addresses' => array(
			'table' => 'ts_inquiries_documents_versions_addresses',
			'foreign_key_field' => array('type', 'type_id'),
			'primary_key_field' => 'version_id'
		),
	);
	
	protected $_aJoinedObjects = array(
		'document' => [
			'class' => 'Ext_Thebing_Inquiry_Document',
			'key' => 'document_id',
			'type' => 'parent',
			'bidirectional' => true
		],
		'items' => [
			'class' => 'Ext_Thebing_Inquiry_Document_Version_Item',
			'key' => 'version_id',
			'check_active' => true,
			'type' => 'child',
			'bidirectional' => true,
			'on_delete' => 'cascade'
		],
        'paymentterms' => [
        	'class' => 'Ext_TS_Document_Version_PaymentTerm',
			'key' => 'version_id',
			'check_active' => true,
			'type' => 'child',
			'on_delete' => 'cascade',
			'bidirectional' => true,
			'orderby' => 'date' // Natürlich funktioniert ein Array nicht überall, obwohl das laut WDBasic gehen sollte
		],
        'print' => array(
			'class' => 'Ext_Thebing_Inquiry_Document_Version_Print',
			'key' => 'version_id',
			'check_active' => true,
			'type' => 'child',
			'orderby' => 'printed',
			'orderby_type' => 'DESC'
        ),
		'priceindex' => [
			'class' => 'Ext_Thebing_Inquiry_Document_Version_Price',
			'key' => 'version_id',
			'check_active' => true,
			'type' => 'child',
			'on_delete' => 'cascade'
		],
		'company' => [
			'class' => \TsAccounting\Entity\Company::class,
			'key' => 'company_id',
			'type' => 'parent'
		]
    );

	public $bCalculateProvisionNew = false; 

	// Sagt das definitiv keine ITEMS erzeugt werden falls es in der DB keine gibt!
	public $bDoNotBuildNewItems = false;

	// Definiert das keine Pakete mehr generiert werden!
	public $bNoNewPacketPrices = false;

	public $bCalculateTax = true;

	public $bDiffEdit = true;

	public $sLanguage = false; // false => inquiry sprache

	// Need for PDF Preview Dummy
	public $bDummy = false;

	// Fehler
	public $aErrors	 = array();
	public $aSeasonErrors = array();
	public $aWeekErrors = array();

	// Specialpositionen
	public $aSpecialPositions = array();

	public $bUseAmountCache = true;

	public $sAction;

    protected $_aGetItemObjectCache = array();

	/**
	 * Zusatzkosten die pro Kurs berechnet werden sollen (charge == 1) sollen bei gesplitteten Kursen nicht nochmal auftauen
	 * @var SplObjectStorage[]
	 */
	private static $aAdditionalPerCourse = array();
	// Zusatzkosten die nur einmal pro Rechnung auftauchen sollen (charge == 0)
	private static $aAdditionalCoursesSingle = array();
	
	// Zusatzkosten die nur einmal pro Rechnung auftauchen sollen (charge == 0)
	private static $aAdditionalAccommodationSingle = array();
	// Zusatzkosten die pro Unterkunft berechnet werden sollen (charge == 1) sollen bei gesplitteten Unterkünften nicht nochmal auftauen
	private static $aAdditionalPerAccommodation = array();

	/**
	 * Tooltips für die Tabelle
	 *
	 * @var mixed[]
	 */
	public $aItemTooltips;

	/**
	 * @var Ext_Thebing_Gui2
	 */
	protected $_oGui;

	// Amount Cache für Version
	protected static $aAmountCache = array();

    protected static $_aGetItemsCache = array();

    // Items Cache
	protected $_aItems = null;

	/**
	 *
	 * @var Ext_TS_Inquiry_Abstract
	 */
	protected $_oInquiry = null;

    public $bGetLastVersionFromCache = true;

	/**
	 * @var string
	 */
	protected $_sPlaceholderClass = 'Ext_Thebing_Inquiry_Document_VersionPlaceholder';

	public function setGui(&$oGui) {
		$this->_oGui = $oGui;
	}

	static public function setChange($iInquiryId, $iTypeId, $sType, $sStatus = 'edit', $iParentId = 0){

		$aSql = array(
			'inquiry_id' => (int)$iInquiryId,
			'type_id' => (int)$iTypeId,
			'type' => $sType,
			'status' => $sStatus,
			'parent_id' => (int)$iParentId,
			'write_change' => true
		);

		System::wd()->executeHook('ts_inquiry_document_version_set_change', $aSql);

		if(!$aSql['write_change']) {
			return;
		}

		$sSql = " SELECT 
						* 
					FROM 
						`kolumbus_inquiries_documents_versions_items_changes` 
					WHERE
						`inquiry_id` = :inquiry_id AND
						`type_id` = :type_id AND
						`type` = :type AND
						`parent_id` = :parent_id
					";
		$aTemp = DB::getPreparedQueryData($sSql, $aSql);

		if(empty($aTemp)){
			$sSql = " INSERT INTO
						`kolumbus_inquiries_documents_versions_items_changes`
					SET
						`inquiry_id` = :inquiry_id,
						`type_id` = :type_id,
						`type` = :type,
						`status` = :status,
						`parent_id` = :parent_id
					";
		} else {
			$sSql = " UPDATE
						`kolumbus_inquiries_documents_versions_items_changes`
						SET
							`status` = :status,
							`active` = 1
						WHERE
							`inquiry_id` = :inquiry_id AND
							`type_id` = :type_id AND
							`type` = :type AND
							`parent_id` = :parent_id
					";
		}

		DB::executePreparedQuery($sSql, $aSql);

		// Special Positionen müssen mit geändert werden wenn sie schon benutzt wurde
		$sSql = "SELECT
						`special_block_id`
					FROM
						`kolumbus_inquiries_positions_specials` `kips` INNER JOIN
						`ts_inquiries_to_special_positions` `ts_i_to_sp` ON
							`ts_i_to_sp`.`special_position_id` = `kips`.`id`
					WHERE
						`kips`.`used` = 1 AND
						`ts_i_to_sp`.`inquiry_id` = :inquiry_id AND
						`kips`.`type` = :type AND
						`kips`.`type_id` = :type_id
					LIMIT 1
				";
				
		$iSpecialBlockId = (int)DB::getQueryOne($sSql, $aSql);

		if($iSpecialBlockId > 0){
			$sSql = " SELECT 
						* 
					FROM 
						`kolumbus_inquiries_documents_versions_items_changes` 
					WHERE
						`inquiry_id` = :inquiry_id AND
						`type_id` = :special_block_id AND
						`type` = 'special' AND
						`parent_id` = 0
					";
					
			$aSql['special_block_id'] = (int)$iSpecialBlockId;
			
			$aTemp = DB::getPreparedQueryData($sSql, $aSql);
			
			if(empty($aTemp)){
				$sSql = " INSERT INTO
						`kolumbus_inquiries_documents_versions_items_changes`
					SET
						`inquiry_id` = :inquiry_id,
						`type_id` = :special_block_id,
						`type` = 'special',
						`status` = :status,
						`parent_id` = 0
					";
			}else{
				$sSql = " UPDATE
						`kolumbus_inquiries_documents_versions_items_changes`
						SET
							`status` = :status,
							`active` = 1
						WHERE
							`inquiry_id` = :inquiry_id AND
							`type_id` = :special_block_id AND
							`type` = 'special' AND
							`parent_id` = 0
					";
			}
			
			DB::executePreparedQuery($sSql, $aSql);
			
		}
	}

	static public function clearChanges($iInquiryId, $iDocumentId = 0, $bClearVisible = false){

		if($bClearVisible){
			$sSetAddon = " `visible`		= 0  ";
		}else{
			$sSetAddon = "	`active`		= 0 ,
							`document_id`	= :document_id
						";
		}

		$sSql = " UPDATE
						`kolumbus_inquiries_documents_versions_items_changes`
						SET
							" . $sSetAddon . "
					WHERE
						`inquiry_id` = :inquiry_id";

		$aSql = array();
		$aSql['inquiry_id'] = (int)$iInquiryId;
		$aSql['document_id'] = (int)$iDocumentId;

		DB::executePreparedQuery($sSql, $aSql);

	}

	static public function getChanges($iInquiryId, $sStatus = '', $iDocumentId = 0, $bWithInactive = false) {

		$sSql = "
					SELECT
						*
					FROM
						`kolumbus_inquiries_documents_versions_items_changes`
					WHERE
						`inquiry_id` = :inquiry_id";

		if($sStatus != "") {
			$sSql .= ' AND `status` = :status ';
		}
		
		if($iDocumentId > 0) {
			$sSql .= ' AND ( `document_id` = :document_id OR  `document_id` = 0 ) ';
		}

		if(!$bWithInactive) {
			$sSql .= ' AND `active` = 1 ';
		}

		$aSql = array();
		$aSql['inquiry_id']		= (int)$iInquiryId;
		$aSql['status']			= $sStatus;
		$aSql['document_id']	= (int)$iDocumentId;
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		// Paket Pos. als Ändeurngen ergänzen
		$aPaket = array();
		$aPaket['inquiry_id']	= (int)$iInquiryId;
		$aPaket['type']			= 'paket';
		$aPaket['type_id']		= 0;
		$aPaket['status']		= 'edit';
		$aResult[] = $aPaket;

		return (array)$aResult;

	}

	public function getGroupAmount($bBeforArrival = true, $bAtSchool = true, $sType = null){

		$fAmount = 0;
		$oDocument	= $this->getDocument(false);
		$aDocuments = collect($oDocument->getDocumentsOfSameNumber());
		$oInquiry	= $oDocument->getInquiry();
		if($oInquiry){
			$oGroup		= $oInquiry->getGroup();
			
			if($oGroup)
			{
				$aInquirys = $oGroup->getInquiries(false, false);

				foreach($aInquirys as $oInquiry) {

					$aInquiryDocuments = $aDocuments->filter(function (Ext_Thebing_Inquiry_Document $oDocument) use ($oInquiry) {
						return $oDocument->entity === Ext_TS_Inquiry::class && $oDocument->entity_id == $oInquiry->id;
					});

					if ($aInquiryDocuments->count() > 1) {
						// Die alte Methode searchByNumber hat immer nach created sortiert mit LIMIT 1, aber eigentlich darf es nur ein einziges Dokument geben
						throw new LogicException(sprintf('More than one document for inquiry group member %d and document number %s', $oInquiry->id, $oDocument->document_number));
					}

					$oDocumentTemp = $aInquiryDocuments->first();

//					$iDocumentId = Ext_Thebing_Inquiry_Document_Search::searchByNumber($oInquiry->id, $oDocument->document_number);
//
					if ($oDocumentTemp !== null) {
//					if($iDocumentId > 0) {
//						$oDocumentTemp = Ext_Thebing_Inquiry_Document::getInstance($iDocumentId);

						$oVersion = $oDocumentTemp->getVersion($this->version);

						if($oVersion !== false) {

							$fTempAmount = $oVersion->getAmount($bBeforArrival, $bAtSchool, $sType, true);
							$fAmount += (float)$fTempAmount;
						}
					}
				}
			}
		}

		return $fAmount;

	}

	/**
	 * Liefert die Summen aller gewünschter Items
	 * @param string $sType
	 * @param array $aConfig
	 * @return int 
	 */
	public function getItemAmount($sType, $aConfig = []) {
		
		$aItems = $this->getItemObjects(true);

		$fAmount = 0;

		// Brutto oder Netto
		if(!isset($aConfig['amount_type'])){
			$aConfig['amount_type'] = 'brutto'; #netto
		}

		$aTypes = array($sType);
		if(
			isset($aConfig['special']) &&
			$aConfig['special'] === true
		) {
			$aTypes[] = 'special';
		}
		
		foreach((array) $aItems as $oItem) {

			if(in_array($oItem->type, $aTypes)) {

				// TODO Wenn ein Special matcht, wird das Item einfach komplett nicht berechnet?
				// Specials mit einberechnen, falls gewünscht
				if($oItem->type == 'special') {
					$aSpecialPositions = $oItem->specials;
					foreach($aSpecialPositions as $iSpecialPosition) {
						$oSpecialPosition = Ext_Thebing_Inquiry_Special_Position::getInstance($iSpecialPosition);
						
						if(
							$oSpecialPosition->type != '' &&
							!in_array($oSpecialPosition->type, $aTypes)
						) {
							continue 2;
						}
					}
				}
				
				// Falls nur Items einer bestimmten Buchung geholt werden sollen ( Gruppen)
				if(isset($aConfig['inquiry_id'])) {
					$oInquiry = $oItem->getInquiry();
					if($oInquiry->id != $aConfig['inquiry_id']){
						continue;
					}
				}

				$fAmount += $oItem->getAmount($aConfig['amount_type'], true, $this->bCalculateTax);

			}
		}

		return $fAmount;

	}

	/**
	 * Methode holt Betrag über PriceIndex, externe Steuern dürften nicht funktionieren
	 * @TODO Wofür gibt es $bCalculateWithDiscount?
	 * @TODO Welchen Sinn hat hier $this->bCalculateTax, wenn das überhaupt nicht verwendet wird?
	 * @TODO Redundant mit calculateAmount()
	 *
	 * @param bool $bBeforeArrival
	 * @param bool $bAtSchool
	 * @param string $sType
	 * @param bool $bCurrentAmount
	 * @param bool $bCalculateWithDiscount
	 * @return float|int
	 */
	public function getAmount($bBeforeArrival = true, $bAtSchool = true, $sType = null, $bCurrentAmount = true, $bCalculateWithDiscount = true) {

		$oDocument	= $this->getDocument();

		if($sType == null) {
			$sType = $oDocument->type;
		}

		//$oInquiry	= Ext_TS_Inquiry::getInstance($oDocument->inquiry_id);

		// Schauen ob im Cache schon etwas da ist
		$aCache = null;
		if(isset(self::$aAmountCache[$this->id][$sType][(int)$bBeforeArrival][(int)$bAtSchool][(int)$bCurrentAmount][(int)$bCalculateWithDiscount][(int)$this->bCalculateTax])) {
			$aCache = self::$aAmountCache[$this->id][$sType][(int)$bBeforeArrival][(int)$bAtSchool][(int)$bCurrentAmount][(int)$bCalculateWithDiscount][(int)$this->bCalculateTax];
		}

		if(
			!empty ($aCache) && 
			$this->bUseAmountCache
		) {
            if(!is_float($aCache)){
                throw new Exception('Amount Cache beinhaltet einen nicht Float Wert!');
            }
			return $aCache;
		}

		//@todo: statt Document mit null zu überprüfen, version austauschen!
		if($bCurrentAmount) {
			// Betrag DIESER Version
			$oDocument	= null;
			$iVersion	= (int)$this->id;

		} else {

			// TODO Was hat das hier zu suchen, wenn man sich auf einer KONKRETEN Version befindet?
			// Betrag der NEUESTEN Version
			// Standard
			$oLastVersion = $oDocument->getLastVersion();
			if(
				is_object($oLastVersion) &&
				$oLastVersion instanceof Ext_Thebing_Inquiry_Document_Version
			) {
				$iVersion	= (int)$oLastVersion->id;
			} else {
				$iVersion	= 0;
			}

		}

		$aAmount = Ext_Thebing_Inquiry_Document_Version_Price::getVersionAmountArray($iVersion, $bBeforeArrival, $bAtSchool);

		if(empty($aAmount)) {
			return 0;
		}

		$sField = 'amount';

		if(
			strpos($sType, 'netto') !== false ||
			strpos($sType, 'storno') !== false // Immer netto, da bei brutto dasselbe drin steht
		) {
			$sField = 'amount_net';
//		} elseif(strpos($sType, 'storno') !== false) {
////			$oParentDoc = $this->getDocument()->getParentDocument();
////			if(strpos($oParentDoc->type, 'netto') !== false) {
////				$sField = 'amount_net';
////			}
//			// Immer nach Buchung gehen, da sich der Wert nicht ohne Gutschrift ändern kann
//			// Ansonsten kommt hier ein falscher Typ raus, wenn eine Bruttodiff auf Netto erstellt wird
//			$oInquiry = $this->getDocument()->getInquiry();
//			if(
//				$oInquiry->payment_method == 0 ||
//				$oInquiry->payment_method == 2
//			) {
//				$sField = 'amount_net';
//			}
		} elseif(strpos($sType, 'creditnote') !== false) {
			$sField = 'amount_provision';
		}

		if($this->bUseAmountCache){
			self::$aAmountCache[$this->id][$sType][(int)$bBeforeArrival][(int)$bAtSchool][(int)$bCurrentAmount][(int)$bCalculateWithDiscount][(int)$this->bCalculateTax] = $aAmount[$sField];
		}

		return (float)$aAmount[$sField];

	}

	/**
	 * Liefert NUR den Steuerbetrag dieser Version
	 *
	 * @return float
	 */
	public function getOnlyTaxAmount() {

		$oDocument = $this->getDocument();
		$fAmount = 0;

		if($oDocument->type === 'creditnote') {
			$sField = 'amount_vat_provision';
		} elseif(strpos($oDocument->type, 'netto') !== false) {
			$sField = 'amount_vat_net';
		} else {
			$sField = 'amount_vat_gross';
		}

		$aPriceIndexList = Ext_Thebing_Inquiry_Document_Version_Price::getPriceIndexList($this, true, true);

		foreach($aPriceIndexList as $oPriceIndex) {
			$fAmount += $oPriceIndex->$sField;
		}

		return $fAmount;

	}

	/**
	 * Methode holt Betrag über Items, Steuer dürfte funktionieren
	 * @TODO Quasi redundant mit Ext_Thebing_Inquiry_Document_Version_Price::getVersionAmountArray(), hier aber direkt mit Items
	 * @TODO Redundant mit getAmount(), wird aber nur einmal in der ganzen Software benutzt
	 * @TOOD Kann nach kurzem Refactoring entfernt werden
	 *
	 * @param Ext_Thebing_Inquiry_Document_Version $oDocument
	 * @param $bTax
	 * @param bool $bCalculateWithDiscount
	 * @param bool $bBeforArrival
	 * @param bool $bAtSchool
	 * @return array
	 */
	public function calculateAmount($oDocument, $bTax, $bCalculateWithDiscount, $bBeforArrival = true, $bAtSchool = true) {

		$bTax = $this->bCalculateTax;

		//$oDocument nicht überschreiben, weil das für getItems benutzt wird,
		//temp document merken um die schule zu holen
		if(
			$oDocument === null
		) {
			$oDocumentTemp = $this->getDocument();
		} else {
			$oDocumentTemp = $oDocument;
		}

		if($this->id > 0) {
			//nicht wundern, damit ist tatsächlich der Modus gemeint (exkl,inkl)
			$iTax		= (int)$this->tax;
		} else {
			$oInquiry = $this->getInquiry($oDocumentTemp);
			$oSchool = $oInquiry->getSchool();
			$iTax		= (int)$oSchool->tax;
		}

		// Items NICHT neu erzeugen falls keine Items vorhanden
		#$bTemp = $this->bDoNotBuildNewItems;
		#$this->bDoNotBuildNewItems = true;

		// 3ter Parameter aktiviert die Steuern!
		// 4ter Parameter aktiviert den Discount!

		$aItems	= $this->getItems($oDocument, false, $bTax, $bCalculateWithDiscount);

		#$this->bDoNotBuildNewItems = $bTemp;

		$aAmount = array();
		$aAmount['amount'] = (float)0;
		$aAmount['amount_net'] = (float)0;
		$aAmount['amount_provision'] = (float)0;

		foreach($aItems as $aItem){

			if(
				(
					(
						$bBeforArrival &&
						$aItem['initalcost'] == 0
					) ||
					(
						$bAtSchool &&
						$aItem['initalcost'] == 1
					)
				) &&
				$aItem['calculate'] == 1 &&
				$aItem['onPdf'] == 1
			) {

				$aAmount['amount']		+= (float)$aItem['amount'];
				$aAmount['amount_net']	+= (float)$aItem['amount_net'];
				$aAmount['amount_provision']	+= (float)$aItem['amount_provision'];

				if($iTax == 2) {
					// zzgl. VAT hinzuaddieren
					$aAmount['amount']			+= (float)$aItem['amount_vat'];
					$aAmount['amount_net']		+= (float)$aItem['amount_net_vat'];
					$aAmount['amount_provision']	+= (float)$aItem['amount_commission_vat'];
				}

			}

		}

		return $aAmount;

	}

	/**
	 * Gibt die Position mit der angegeben ID zurück.
	 *
	 * @TODO Die Position muss nicht zu dieser Version gehören. Macht das Sinn?
	 * @param int $iItem
	 * @return Ext_Thebing_Inquiry_Document_Version_Item
	 */
	public function getItem($iItem){
		return new Ext_Thebing_Inquiry_Document_Version_Item($iItem);
	}

	/**
	 * @return \Carbon\Carbon
	 */
	public function getEarliestServiceFrom() {
		
		$aItems = $this->getJoinedObjectChilds('items');
		
		$dMinServiceFrom = null;
		foreach($aItems as $oItem) {
			
			if($oItem->onPdf != 1) {
				continue;
			}
			
			$dItemServiceFrom = new Carbon($oItem->index_from);
			if($dMinServiceFrom === null) {
				$dMinServiceFrom = $dItemServiceFrom;
			} else {
				$dMinServiceFrom = min($dMinServiceFrom, $dItemServiceFrom);
			}

		}
		
		return $dMinServiceFrom;
	}
	
	/**
	 * Erstellt eine neue Positionen die dieswer Version zugeordnet ist.
	 *
	 * @return Ext_Thebing_Inquiry_Document_Version_Item
	 */
	public function newItem() {
		return $this->getJoinedObjectChild('items');
	}

	/**
	 * Get All Items as Objects
	 *
	 * Diese Methode basiert auf der Datenbank UND benutzt einen Instanz-Cache!
	 * @internal
	 *
	 * TODO
	 * Die Methode wirkt schwer veraltet, das sollte mit dem QueryBuilder schneller gehen, auch weil hier pro Item
	 * getInstance aufgerufen wird und nicht getObjectFromArray benutzt wird. Außerdem sollte $bSortDesc standardmäßig
	 * false sein oder am besten komplett abgeschafft werden
	 *
	 * @param bool $bOnlyOnPdf Nur angezeigte Items
	 * @param bool $bSortDesc
	 * @return Ext_Thebing_Inquiry_Document_Version_Item[]
	 */
	public function getItemObjects($bOnlyOnPdf = false, $bSortDesc = true) {

		if($this->id == 0){
			return [];
		}

		$cacheKey = implode('_', [(int)$bOnlyOnPdf, (int)$bSortDesc]);

		if(!isset($this->_aGetItemObjectCache[$cacheKey])) {

			$this->_aGetItemObjectCache[$cacheKey] = [];

			$sWhere = "";
			if($bOnlyOnPdf) {
				$sWhere = " AND `onPdf` = 1 ";
			}

			$sSql = "
				SELECT
					*
				FROM
					`kolumbus_inquiries_documents_versions_items`
				WHERE
					`active` = 1 AND
					`version_id` = :version_id
					{$sWhere}
				ORDER BY
					`position`
			";

			if ($bSortDesc) {
				// TODO das sollte entfernt werden, die Methode sollte die Items immer in korrekter Reihenfolge zurückliefern
				$sSql .= " DESC";
			}

			$aResult = DB::getPreparedQueryData($sSql, ['version_id' => $this->id]);

			foreach($aResult as $aData){
				// getInstance eingebaut, bei Problem Verwendung prüfen!
				$this->_aGetItemObjectCache[$cacheKey][$aData['id']] = Ext_Thebing_Inquiry_Document_Version_Item::getInstance((int)$aData['id']);
			}

		}

		return $this->_aGetItemObjectCache[$cacheKey];

	}

	/**
	 * Speichert zu Items eventuelle Angebotsrabatte
	 */
	public function updateSpecialIndexFields() {
		
		$aItems = $this->getItemObjects();
		
		$aSpecialSums = array();
		
		foreach($aItems as $oItem) {

			if(
				$oItem->onPdf &&
				$oItem->parent_type === 'item_id'
			) {

				foreach(array('gross', 'net') as $sTmpType) {

					$sKey = 'amount';
					if($sTmpType === 'net') {
						$sKey = 'amount_net';
					}

					$fAmountWithoutDiscount = $oItem->$sKey - ($oItem->$sKey / 100 * $oItem->amount_discount);

					// Steuer-Nettobetrag in Spalte speichern, daher bei Steuern inklusive die Steuer abziehen
					// => In amount/amount_net steht bei Steuer inklusive die Steuer mit in der Spalte!
					if($this->tax == 1) {
						// Von 100% + Steuer-% (119%) rechnen
						$fAmountWithoutDiscount -= $fAmountWithoutDiscount - ($fAmountWithoutDiscount / ($oItem->tax / 100 + 1));
					}

					$aSpecialSums[$oItem->parent_id]['amount_'.$sTmpType] = $fAmountWithoutDiscount;
					$aSpecialSums[$oItem->parent_id]['amount_'.$sTmpType.'_vat'] = $fAmountWithoutDiscount * ($oItem->tax / 100);

				}
			}
		}
		
		foreach($aSpecialSums as $iItemId=>$aSpecialSum) {
			if(isset($aItems[$iItemId])) {
				$aItems[$iItemId]->index_special_amount_gross = $aSpecialSum['amount_gross'];
				$aItems[$iItemId]->index_special_amount_net = $aSpecialSum['amount_net'];
				$aItems[$iItemId]->index_special_amount_gross_vat = $aSpecialSum['amount_gross_vat'];
				$aItems[$iItemId]->index_special_amount_net_vat = $aSpecialSum['amount_net_vat'];
				$aItems[$iItemId]->save();
			}
		}

	}
	
    
    protected $_aItemsFromDB = array();
    
    /**
	 * Von einer getItems() sollte zu erwarten sein, dass diese einfach getJoinedObjects() aufruft
	 * @deprecated
	 *
	 * Return all Positions of the Version
	 * @todo caching verwenden
	 * @return Ext_Thebing_Inquiry_Document_Version_Item[]
	 */
	public function getItems($oDocument = null, $bBuildNew = false, $bCalculateWithTax = false, $bCalculateWithDiscount = false, $bWithInactive = false, $sType=null) {

        $sCacheKey = $this->id;
   
        if($oDocument){
            $sCacheKey .= '_'.$oDocument->id;
        } else {
            $sCacheKey .= '_0';
        }
        $sCacheKey .= '_'.(int)$bCalculateWithTax;
        $sCacheKey .= '_'.(int)$bCalculateWithDiscount;
		$sCacheKey .= '_'.(int)$bWithInactive;
     
		if(
			$oDocument == null ||
			$oDocument->id <= 0
		) {
			$sType = '';
//			$iInquiry = 0;
			if($oDocument){
				$sType = $oDocument->type;
//				$iInquiry = $oDocument->inquiry_id;
			}
			$oDocument	= $this->getDocument(false);
			$oDocument->type = $sType;
//			$oDocument->inquiry_id = $iInquiry;
			$iVersion	= $this->id;
		} else {
			$oLastVersion = $oDocument->getLastVersion($this->bGetLastVersionFromCache);
			if($oLastVersion) {
				$iVersion = $oLastVersion->id;
			} else {
				$iVersion = 0;
			}
		}	

        if($iVersion > 0) {
			
			if(!isset($this->_aItemsFromDB[$sCacheKey])){

				$sWhere = "";
				if(!$bWithInactive) {
					$sWhere = " AND `active` = 1 ";
				}

				$sSql = "
					SELECT
						`id`,
						`position`
					FROM
						`kolumbus_inquiries_documents_versions_items`
					WHERE
						`version_id` = :version_id
						{$sWhere}
					ORDER BY
						`position`
				";
				$aSql['version_id'] = (int)$iVersion;

				$aResult = DB::getPreparedQueryData($sSql, $aSql);

				$this->_aItemsFromDB[$sCacheKey] = $aResult;
                
           } else {
               $aResult = $this->_aItemsFromDB[$sCacheKey];
           }

        } else {
            $aResult = null;
            $bBuildNew = true; // damit der cache geschrieben wird
        }

		if(
            $bBuildNew ||
            !isset(self::$_aGetItemsCache[$sCacheKey])
        ){
         
            $aBack = array();

            $bSort = true;

			if(
                $aResult !== null &&
                $bBuildNew !== true
            ) {

                foreach($aResult as $aData) {

                    if($aData['position'] > 0) {
                        $bSort = false;
                    }
#__out(Util::getBacktrace());
                    // getInstance eingebaut, bei Problem Verwendung prüfen!
                    $oItem = Ext_Thebing_Inquiry_Document_Version_Item::getInstance($aData['id']);
                    $aTemp = $this->createItemData($oItem, $sType);

                    if($aTemp['description'] == ''){
                        continue;
                    }

                    if(!empty($aTemp)){
                        $aBack[] = $aTemp;
                    }

                }

            } else if(
                $this->bDoNotBuildNewItems == false ||
                $oDocument->type == 'receipt_customer' ||
                $oDocument->type == 'receipt_agency'
            ) {

                if(
					$oDocument->type == 'receipt_customer' ||
					$oDocument->type == 'receipt_agency'
                ) {
                    // Bei den oberen Payment Reciepts müssen die Rechnungspositionen ALLER
                    // Bisherigen Rechnungen aufgeführt werden
                    $aBack = $this->buildPaymentReceiptItems($oDocument);
                } elseif($oDocument->type == 'storno') {
                    $aBack = $this->buildStornoItems($oDocument);
                } else {
                    $aBack = $this->buildItems($oDocument);
                }

            }
            ## ENDE
#__out($aBack);
            if(
				$bSort && 
				$aBack !== false
			) {
                $aBack = $this->sortPositions((array)$aBack);
            }

            ## START Discount
            if($bCalculateWithDiscount) {
                $aBackTemp = array();
                foreach((array)$aBack as $aItem) {
                    $aBackTemp[] = $aItem;
                    if(abs($aItem['amount_discount']) > 0) {

                        $oItemDiscount = Ext_Thebing_Inquiry_Document_Version_Item::getInstance($aItem['position_id']);

                        $aItemDiscount = $this->createItemData($oItemDiscount);

                        // Discount Eintrag anpassen
                        $aItemDiscount['description'] = $aItemDiscount['description_discount'];
                        $aItemDiscount['amount'] =  (-1) * round(((float)$aItemDiscount['amount'] / 100) * (float)$aItemDiscount['amount_discount'], 5);
                        $aItemDiscount['amount_net'] =  (-1) * round(((float)$aItemDiscount['amount_net'] / 100) * (float)$aItemDiscount['amount_discount'], 5);
                        $aItemDiscount['amount_provision'] =  (-1) * round(((float)$aItemDiscount['amount_provision'] / 100) * (float)$aItemDiscount['amount_discount'], 5);

                        // Position anpassen, damit Discount unter der eigentlichen Zeile ist
                        $aItemDiscount['position'] += 0.1; // Wird in Hook abgefragt

                        $aBackTemp[] = $aItemDiscount;
                    }
                }
                $aBack = $aBackTemp;
            }
            ## ENDE

            ## START Steuern
            if($bCalculateWithTax) {
                $oInquiry = $this->getInquiry($oDocument);
                if($oInquiry) {
					$sLanguage = $this->template_language;
					if(empty($sLanguage)) {
						$sLanguage = $oInquiry->getCustomer()->getLanguage();
					}
					$aDataTax = Ext_TS_Vat::addTaxRows($aBack, $oInquiry, $sLanguage, $this);
					$aBack = $aDataTax['items'];
                }
            }
            ## ENDE

			if($bBuildNew)
			{
				return $aBack;
			}
			
            self::$_aGetItemsCache[$sCacheKey] = $aBack;
        }

		return self::$_aGetItemsCache[$sCacheKey];
	}

	/**
	 * Create an Array with all Data for an Item
	 * 
	 * @global Ext_Thebing_School $oSchool
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @return array
	 */
	public function createItemData($oItem, $sType=null){
		global $oSchool;

		if($this->bCalculateProvisionNew){
			$oItem->amount_provision		= (float)$oItem->getNewProvisionAmount(0, $sType);
			$oItem->amount_net				= (float)$oItem->amount - $oItem->amount_provision;
		}

		$aBack['id']						= (int)$oItem->id;
		$aBack['parent_type']				= $oItem->parent_type;
		$aBack['parent_id']					= (int)$oItem->parent_id;
		$aBack['description']				= $oItem->description;
		$aBack['old_description']			= $oItem->old_description;
		$aBack['description_discount']		= $oItem->description_discount;
		$aBack['count']						= (int)$oItem->count;
		$aBack['nights']					= (int)$oItem->nights;
		$aBack['amount']					= (float)$oItem->amount;
		$aBack['amount_net']				= (float)$oItem->amount_net;
		$aBack['amount_provision']			= (float)$oItem->amount_provision;
		$aBack['amount_discount']			= (float)$oItem->amount_discount;
		$aBack['tax_category']				= (int)$oItem->tax_category;
		$aBack['tax']						= (float)$oItem->tax;
		$aBack['type']						= (string)$oItem->type;
		$aBack['type_id']					= (int)$oItem->type_id;
		$aBack['initalcost']				= (int)$oItem->initalcost;
		$aBack['onPdf']						= (int)$oItem->onPdf;
		$aBack['calculate']					= (int)$oItem->calculate;
		$aBack['position']					= (int)$oItem->position;
		$aBack['position_id']				= (int)$oItem->id;
		$aBack['parent_id']					= (int)$oItem->parent_id;
		$aBack['parent_type']				= (string)$oItem->parent_type;
		$aBack['parent_booking_id']			= (int)$oItem->parent_booking_id;
		$aBack['version_id']				= (int)$oItem->version_id;
		$aBack['additional_info']			= (array)$oItem->additional_info;
		$aBack['index_from']                = (string)$oItem->index_from;
		$aBack['index_until']               = (string)$oItem->index_until;
		$aBack['type_object_id']			= (int)$oItem->type_object_id;
		$aBack['type_parent_object_id']		= (int)$oItem->type_parent_object_id;
		$aBack['contact_id']				= (int)$oItem->contact_id;
		$aBack['tags']						= (array)$oItem->getServiceTags();

		if(
			($oSchool->additional_costs_are_initial ?? 0) == 1 && 
			$oItem->initalcost == 1
		) {
			$aBack['onPdf']		= 0;
		}

		return $aBack;

	}

	public function buildStornoItems(&$oDocument = null){

		global $_VARS;
		
		// Document holen
		if($oDocument == null){
			$oDocument					= $this->getDocument();
		}

		// Inquiry Object holen
		$oInquiry						= $this->getInquiry();

		// Kunde
		$oCustomer						= $oInquiry->getCustomer();

		// Schule holen
		$oSchool						= $oInquiry->getSchool();
				
		#$iCancelAmount					= $oInquiry->getCancellationAmount();
        $oTypeSearch = new Ext_Thebing_Inquiry_Document_Type_Search();
        $oTypeSearch->addSection('invoice_without_proforma');
        $oTypeSearch->remove('brutto_diff_special');

		$aDocuments	= Ext_Thebing_Inquiry_Document_Search::search($oInquiry->id, $oTypeSearch, true, true);

		// anzeigesprache für den dialog 
		$sLang = $oSchool->fetchInterfaceLanguage();
		if(
			isset($_VARS['save']['language']) &&
			!empty($_VARS['save']['language'])
		){
			$sLang = $_VARS['save']['language'];	
		}

		$aItems							= array();

		$aDocuments						= Ext_Thebing_Cancellation_Amount::prepareCancellationDocs($aDocuments);

		foreach($aDocuments as $iKey => $oTempDocument){

			$oVersion			= $oTempDocument->getLastVersion();
			$aDocumentItems		= (array)$oVersion->getItems();

			foreach($aDocumentItems as $aItem)
			{
				$aItem['amount']		= (float)$aItem['amount']*-1;
				$aItem['amount_net']	= (float)$aItem['amount_net']*-1;
				$aItem['amount_provision'] = (float)$aItem['amount_provision']*-1;
				$aItems[] = $aItem;
			}
		}

		$oCancellationAmount	= new Ext_Thebing_Cancellation_Amount($oInquiry, $aDocuments, $sLang);
		$oCancellationAmount->initItems();
		$aCancellationItems		= (array)$oCancellationAmount->getCancellationItems();

		foreach($aCancellationItems as $aCancellationItem){

			$aCancellationData = [];
			$aCancellationData['description'] = $aCancellationItem['description'];
			$aCancellationData['old_description'] = '';
			$aCancellationData['description_discount'] = '';
			$aCancellationData['count'] = 0;
			$aCancellationData['nights'] = 0;
			$aCancellationData['amount'] = (float)$aCancellationItem['amount'];
			$aCancellationData['amount_net'] = (float) $aCancellationItem['amount_net'];
			$aCancellationData['amount_provision'] = (float)$aCancellationItem['amount_provision'];
			$aCancellationData['amount_discount'] = (float)$aCancellationItem['amount_discount'];
			$aCancellationData['tax_category'] = (int)$aCancellationItem['tax_category'];
			$aCancellationData['type'] = $aCancellationItem['item_type'];//wird benötigt für die Buchhaltung
			$aCancellationData['type_id'] = (int)$aCancellationItem['type_id'];//wird benötigt für die Buchhaltung
			$aCancellationData['initalcost'] = 0;
			$aCancellationData['onPdf'] = 1;
			$aCancellationData['calculate'] = 1;
			$aCancellationData['position_id'] = 0;
			$aCancellationData['status'] = 'new';
			$aCancellationData['parent_id'] = 0;
			$aCancellationData['parent_type'] = 'cancellation';
			$aCancellationData['parent_booking_id'] = $aCancellationItem['parent_booking_id'];
			$aCancellationData['type_object_id'] = $aCancellationItem['type_object_id'];
			$aCancellationData['type_parent_object_id'] = $aCancellationItem['type_parent_object_id'];
			$aCancellationData['index_from'] = $aCancellationItem['index_from'];
			$aCancellationData['index_until'] = $aCancellationItem['index_until'];
			$aCancellationData['additional_info'] = $aCancellationItem['additional_info'];

			$aItems[] = $aCancellationData;

		}

		return $aItems;
	}

	public function buildPaymentReceiptItems(Ext_Thebing_Inquiry_Document $oDocument) {

		$oInquiry = $oDocument->getInquiry();

		// Alle Rechnungsdokumente
		$aDocuments		= $oInquiry->getDocuments('invoice_without_proforma', true, true);
		$aItems			= array();
		foreach((array)$aDocuments as $oTempDocument){
			$oLastVersion				= $oTempDocument->getLastVersion();
			// Discount darf hier NICHT berechnet werden, da die getItems() diese Funktion aufruft
			// und hier wiederum die getItems aufgerufen wird, sonst sind Rabatt positionen mehrfach!
			$aTempItems					= $oLastVersion->getItems($oTempDocument, false, true, false);

			// Items hinzufügen
			foreach((array)$aTempItems as $aTempItem){
				$aItems[] = $aTempItem;
			}
		}

		return $aItems;
	}

	/**
	 * Create all Positions
	 */
	public function buildItems($oDocument = null, array $aServices = null) {

		$oWDDate = new WDDate();

		// Document holen
		if($oDocument == null) {
			$oDocument					= $this->getDocument();
		}

		// Inquiry/Enquiry Objekt holen
		$oInquiry = $this->getInquiry($oDocument);

		if(!$oInquiry instanceof Ext_TS_Inquiry_Abstract) {
			return [];
		}

		// Cach für Zusatzkosten löschen damtit diese korrekt berechnet werden können  
		self::clearAdditionalCostCache(); 

		// Kunde
		$oCustomer = $oInquiry->getCustomer();

		// Sprache
		$sDisplayLanguage = $oCustomer->getLanguage();

		if(
			$this->sLanguage !== false &&
			$this->sLanguage != ""
		) {
			$sDisplayLanguage = $this->sLanguage;
		}

        if(empty($sDisplayLanguage)) {
            $sDisplayLanguage = System::getInterfaceLanguage();
        }

		$oDisplayLanguage = new \Tc\Service\Language\Frontend($sDisplayLanguage);

		// Schule holen		
		$oSchool					= $oInquiry->getSchool();
		$iSchoolId					= (int)$oSchool->id;

		// Saison daten
		$oSaison					= new Ext_Thebing_Saison($oSchool->id);
		$oSaisonSearch				= new Ext_Thebing_Saison_Search();

		// Amount Object
		$oAmount					= new Ext_Thebing_Inquiry_Amount($oInquiry);
		$bNett						= false;

		if(strpos($oDocument->type, 'netto') !== false) {
			$bNett = true;
		}

		## Paketpreise?
		$bPacketPrices = false;

		// TODO Das hier muss dringend überprüft werden, da $oSchool->price_structure im Dialog nicht mehr existiert
		// TODO Alles mit packet/paket entfernen
		if(
			$oSchool->price_structure != 1 && 
			$this->bNoNewPacketPrices == false
		) {
			//$bPacketPrices = true;
		}

		$bPaymentMethodLocal			= $oInquiry->getPaymentMethodLocal();

		// Währung
		$oCurrency						= new Ext_Thebing_Currency_Util($oSchool);
		$iCurrencyIdId					= $oInquiry->getCurrency();
		$oCurrency->setCurrencyById($iCurrencyIdId);

		// Kurse und Unterkünfte holen
		if (empty($aServices)) {
			$aCourses = $oInquiry->getCourses();
			$aAccommodations = $oInquiry->getAccommodations();
			$aTransfers = $oInquiry->getTransfers();
			$aInsurances = $oInquiry->getInsurances();
			$aActivities = $oInquiry->getActivities();
		} else {
			$aCourses = $aServices['courses'] ?? [];
			$aAccommodations = $aServices['accommodations'] ?? [];
			$aTransfers = $aServices['transfers'] ?? [];
			$aInsurances = $aServices['insurances'] ?? [];
			$aActivities = $aServices['activities'] ?? [];
		}

		// Ersten Kurs/Unterkunft
		$oFirstCourse					= reset($aCourses);
		$oFirstAccommodation			= reset($aAccommodations);

		// Arrays definieren
		$aBack							= array();
		$aCourseData					= array();
		$aAccommodationData				= array();
		$aAdditionalAccommodationData	= array();
		$aAdditionalCourseData			= array();
		$aAdditionalTransferData		= array();
		$aAdditionalData				= array();
		$aSpecialData					= array();
		$aTransferData					= array();
		$aExtraNight					= array();

		$aItemsByKey = [];
		
		## START Kurs Positionen ##
		if(!empty($aCourses)) {

			$iCourseWeeksTotal = 0;
			$iCourseUnitsTotal = 0;

			$aCourseOffsets = array();
			
			// Berechnen wieviele Wochen bzw. wieviele Einheiten die Kurse insgesammt haben
			// MK: Das ist doch total falsch was hier läuft. Die Wochen werden über alle Kurse einfach addiert und nicht nach der Reihenfolge und dem Kurs.
			foreach($aCourses as $iKey => $oInquiryCourse) {

				$iCourseId = $oInquiryCourse->course_id;

				if($iCourseId > 0) {

					// Kursdaten holen
					$oCourse = Ext_Thebing_Tuition_Course::getInstance($iCourseId);

					// Wenn der Kurs ausgeschlossen wird, dürfen seine Wochen auch nicht eingerechnet werden
					if($oCourse->skip_ongoing_price_calculation == 1) {
						continue;
					}
					
					if ( $oCourse->per_unit != 1 ) {
						$iCourseWeeksTotal += (int)$oInquiryCourse->weeks;
					} else {
						$iCourseUnitsTotal += (int)$oInquiryCourse->units;
						// folgewochen sollen auch aus lektionskursen entstehen
						// ( wenn z.b zuerst lektions und dann wochenkurs gebucht wurde )
						$iCourseWeeksTotal += (int)$oInquiryCourse->weeks;
					}

				}

			}

			// Wochen mitzählen für "fortlaufende" berechnung
			$iLastWeekOfLastCourse = 0;
			$iLastUnitOfLastCourse = 0;

			$i = 0;
			$a = 0; 

			$aCourseCurrentOffset = array();
			$splitFixedCourseFees = false;
			
			foreach((array) $aCourses as $iKey => $oInquiryCourse){
				
				/* @var $oInquiryCourse Ext_TS_Inquiry_Journey_Course */

				$iInquiryCourseId = $oInquiryCourse->id;

				$iCourseId = (int) $oInquiryCourse->course_id;
				$oWDDate->set($oInquiryCourse->from, WDDate::DB_DATE);
				$iFrom = (int) $oWDDate->get(WDDate::TIMESTAMP);
				$oWDDate->set($oInquiryCourse->until, WDDate::DB_DATE);
				$iUntil = (int) $oWDDate->get(WDDate::TIMESTAMP);
				$iWeeks = (int) $oInquiryCourse->weeks;
				$iUnits = (int) $oInquiryCourse->units;

				// Kursdaten holen
				$oCourse = Ext_Thebing_Tuition_Course::getInstance($iCourseId);

				//Name Generieren
				$sCourseName = $oInquiryCourse->getLineItemDescription($oDisplayLanguage);

				$sTmpItemKey = Ext_Thebing_Util::generateRandomString(16);

				$aAdditionalInfo = [];
				
				// Wochenkurse und Prüfungen
				if($oCourse->per_unit != 1) {
					
					$iCourseWeekOffset = 0;

					/*
					 * TODO Sollte das nicht in der Preisberechnung passieren? Achtung, Redundanz!
					 * Preise "fortlaufend" berechnen ( vorgänger wochen hinzuaddieren )
					 * Bzgl. Ferien: Das Suchen der korrekten Preiswoche findet in Ext_Thebing_Course_Amount::calculateWeek() statt
					 * 
					 * Fortlaufende Preisberechnung geht nur bei Wochenkursen
					 */
					if(
						$oSchool->price_calculation == 1 &&
						$oCourse->getType() !== 'exam' &&
						$oCourse->skip_ongoing_price_calculation == 0
					) {

						/*
						 * Bei normaler Preisberechnung, oder einmaligem Preis
						 */
						if(
							$oSchool->checkNormalPriceCalculationMode() ||
							$oCourse->hasFixedPrice()
						) {
							
							$iCourseWeekOffset = (int)$aCourseCurrentOffset['ongoing'];
							$aCourseCurrentOffset['ongoing'] += $oInquiryCourse->weeks;
							
						} else {
							// Totalwochen - aktuelle wochen
							$iCourseWeekOffset = $iCourseWeeksTotal - $oInquiryCourse->weeks;
						}

					}

					if(
						$iCourseWeekOffset > 0 &&
						$oCourse->hasFixedPrice()
					) {
						$splitFixedCourseFees = true;
					}
					
					// Betrag errechen
					$oAmount->setTimeData($iFrom, $iUntil, $iWeeks, 0);
					$iAmount = $oAmount->calculateCourseAmount($oInquiryCourse, false, $iCourseWeekOffset, $sTmpItemKey);

					$aPriceCalculation = $oAmount->getCalculationDescription();

					$iAmountNet = $iAmount;
					$iAmountProv = 0;

					if($oInquiry->hasAgency()) {
						$iAmountNet = $oAmount->calculateCourseAmount($oInquiryCourse, true, $iCourseWeekOffset, $sTmpItemKey);
						$iAmountProv = $iAmount - $iAmountNet;
					}

					$aAdditionalInfo['course_week_offset'] = $iCourseWeekOffset;

				} else {

					$iCourseUnitOffset = 0;

					// TODO Sollte das nicht in der Preisberechnung passieren? Achtung, Redundanz!
					// Preise "fortlaufend" berechnen ( vorgänger wochen hinzuaddieren )
					if(
						$oSchool->price_calculation == 1 &&
						$oCourse->skip_ongoing_price_calculation == 0
					) {

						if($oSchool->checkNormalPriceCalculationMode()) {
							// bei lektionen dürfen nur lektions units benutzt werden und nicht die wochen! ( falls n wochenkurs vorher war ) #2998
							// wurde vorerst deaktiviert da die Preisklasse bei "normaler Preisstruktur" in Kombination mit Startlektionen eine Fehlerhafte berechnung liefert
							// wie in #2998 wird daher das fortlaufende Berechnnen bei Lektiionskursen erstmal rausgenommen
							$iCourseUnitOffset = 0; //(int)$aCourseCurrentOffset['ongoing_units'];
							// ongoing muss in wochen und lektionen getrennt werden  #2998
							$aCourseCurrentOffset['ongoing'] += $oInquiryCourse->weeks;
							$aCourseCurrentOffset['ongoing_units'] += $oInquiryCourse->units;

						} else {
							// Totalwochen - aktuelle wochen
							$iCourseUnitOffset = $iCourseUnitsTotal - $oInquiryCourse->units;
						}

					}

					// Betrag errechen
					$oAmount->setTimeData($iFrom, $iUntil, 0, $iUnits);
					$iAmount = $oAmount->calculateCourseAmount($oInquiryCourse, false, $iCourseUnitOffset, $sTmpItemKey);

					$aPriceCalculation = $oAmount->getCalculationDescription();
					
					// Netto und Provi falls nötig
					$iAmountNet = $oAmount->calculateCourseAmount($oInquiryCourse, true, $iCourseUnitOffset, $sTmpItemKey);
					$iAmountProv = $iAmount - $iAmountNet;

					$aAdditionalInfo['course_unit_offset'] = $iCourseUnitOffset;
					
				}

				// Gruppen Guide checken und Amount löschen
				if(
					$oInquiry->hasGroup() &&
					$oInquiry->isGuide() &&
					(
						$oInquiry->getJourneyTravellerOption('free_course') ||
						$oInquiry->getJourneyTravellerOption('free_all')
					)
				){
					$iAmount = $iAmountNet = $iAmountProv = 0;
				}

				$aCourseData[$i]['item_key'] = $sTmpItemKey;
				$aCourseData[$i]['description'] = $sCourseName;
				$aCourseData[$i]['amount'] = $iAmount;
				$aCourseData[$i]['amount_net'] = $iAmountNet;
				$aCourseData[$i]['amount_provision'] = $iAmountProv;
				$aCourseData[$i]['amount_discount'] = 0;
//				$aCourseData[$i]['id'] = $iInquiryCourseId; // Inquiry Course Id
				$aCourseData[$i]['type'] = 'course';
				$aCourseData[$i]['type_id'] = $iInquiryCourseId; // Inquiry Course Id
				$aCourseData[$i]['type_object_id'] = $iCourseId;
				$aCourseData[$i]['type_parent_object_id'] = 0;
				$aCourseData[$i]['parent_id'] = (int) 0;
				$aCourseData[$i]['parent_booking_id'] = (int) 0;

				// Wenn die Agentur eingestellt hat Bezahlungsart "Vorort"
				// oder wenn bei dem Kunden "Vorort" eingestellt ist
				if($bPaymentMethodLocal){
					$aCourseData[$i]['initalcost'] = 1;
				} else{
					$aCourseData[$i]['initalcost'] = 0;
				}

				// Checkboxen
				$aCourseData[$i]['onPdf'] = 1;
				$aCourseData[$i]['calculate'] = 1;
				$aCourseData[$i]['from'] = $oInquiryCourse->from;
				$aCourseData[$i]['until'] = $oInquiryCourse->until;
				$aCourseData[$i]['index_from'] = $aCourseData[$i]['from'];
				$aCourseData[$i]['index_until'] = $aCourseData[$i]['until'];

				$dVatDate = Ext_TS_Vat::getVATReferenceDateByService($oSchool, $oInquiryCourse);
				
				// Steuerkategorie
				$aCourseData[$i]['tax_category'] = Ext_TS_Vat::getDefaultCombination('Ext_Thebing_Tuition_Course', $iCourseId, $oSchool, $oInquiry, $dVatDate, $oDocument->type);

				// Tooltip
				$aCourseData[$i]['tooltip'] = $aPriceCalculation;

				$iBillingUnits = $oInquiryCourse->weeks;
				if($oCourse->price_calculation === 'month') {
					$iBillingUnits = Ext_TS_Inquiry_Journey_Service::getMonthCount($oInquiryCourse);
				}

				$aAdditionalInfo = array(
					'item_key' => $sTmpItemKey,
					'tooltip' => $aPriceCalculation,
					'tuition_course_id' => $iCourseId,
					'course_weeks' => $oInquiryCourse->weeks,
					'weeks' => $oInquiryCourse->weeks,
					'course_units' => $oInquiryCourse->units,
					'from' => $oInquiryCourse->from,
					'until' => $oInquiryCourse->until,
					'courselanguage_id' => $oInquiryCourse->courselanguage_id,
					'billing_type' => $oCourse->price_calculation,
					'billing_units' => $iBillingUnits,
					'category_id' => $oCourse->category_id,
				);

				if(!empty($oAmount->aPeriods)) {
					$aAdditionalInfo['periods'] = $oAmount->aPeriods;
				}
				
				$aCourseData[$i]['additional_info'] = $aAdditionalInfo;

				$aItemsByKey[$sTmpItemKey] = $aCourseData[$i];
				
				$i++;

				//Zusatzkosten
				$this->buildAdditionalCourseCostItems($aAdditionalCourseData, $oInquiryCourse, $oDisplayLanguage, $oAmount);

			}

			if($splitFixedCourseFees) {
				$distributeService = new Ts\Service\Invoice\DistributeFixedPrice($oInquiry);
				$distributeService->run($aCourseData);
			}
			// Zusatzkosten items in die itemsByKey eintragen, damit die Daten des Parent von Zusatzkosten Specials weiter unten verfügbar sind
			foreach ($aAdditionalCourseData as $additionalCourseItem) {
				$aItemsByKey[$additionalCourseItem['item_key']] = $additionalCourseItem;
			}
			
			//Keys neuschreiben
			$aAdditionalCourseData = array_values($aAdditionalCourseData);

		}

		####################################################
		if(!empty($aAccommodations)){

			$iAccoWeeksTotal = 0;
			$iLastWeeksOfLastAcco	= 0;

			foreach($aAccommodations as $iKey => $oInquiryAccommodation){
				$iAccoWeeksTotal += $oInquiryAccommodation->weeks;
			}

			$iAccoWeeks	= $iAccoWeeksTotal;
			$i			= 0;
			$a			= 0;
			
			foreach($aAccommodations as $iKey => $oInquiryAccommodation) {

				// Temporäre ID zur späteren Identifizierung
				$sTmpItemKey = Ext_Thebing_Util::generateRandomString(16);

				$aAdditionalInfo = array(
					'item_key' => $sTmpItemKey,
					'accommodation_class' => get_class($oInquiryAccommodation),
//					'accommodation_id' => $oInquiryAccommodation->id, // TODO Wofür ist das? Das steht doch in type_id
					'accommodation_weeks' => $oInquiryAccommodation->weeks,
					'accommodation_category_id' => $oInquiryAccommodation->accommodation_id,
					'accommodation_roomtype_id' => $oInquiryAccommodation->roomtype_id,
					'accommodation_meal_id' => $oInquiryAccommodation->meal_id,
					'weeks' => $oInquiryAccommodation->weeks,
					'from' => $oInquiryAccommodation->from,
					'until' => $oInquiryAccommodation->until,
					'billing_type' => 'week', // TODO Sollten monatliche Unterkunftspreise ergänzt werden, muss das hier angepasst werden
					'billing_units' => $oInquiryAccommodation->weeks
				);

				$iInquiryAccommodationId	= (int)$oInquiryAccommodation->id;
				$iAccommodationId	= (int)$oInquiryAccommodation->accommodation_id;
				$oWDDate->set($oInquiryAccommodation->from, WDDate::DB_DATE);
				$iFrom				= (int)$oWDDate->get(WDDate::TIMESTAMP);
				$oWDDate->set($oInquiryAccommodation->until, WDDate::DB_DATE);
				$iUntil				= (int)$oWDDate->get(WDDate::TIMESTAMP);
				$iWeeks				= (int)$oInquiryAccommodation->weeks;

				$iAmount			= 0;
				$iAmountProv		= 0;
				$iAmountNet			= 0;

				// Wochentotal - aktuelle wochen
				if($oSchool->price_calculation == 1){
					// TODO Wird nicht mehr benutzt
					$iAccoWeeks -= $iWeeks;
				} else {
					// wenn nicht fortlaufend dann resetzte diese Variable jedesmal
					$iLastWeeksOfLastAcco = 0;
				}

				$aExtraNightsCurrent	= (array) $oInquiry->getExtraNights('forCalculate', $oInquiryAccommodation);
				$aExtraWeeks			= (array) $oInquiry->getExtraWeeks('forCalculate', $oInquiryAccommodation);
				
				// Helper-Klasse für Extranächte
				$oHelper = new Ext_TS_Service_Accommodation_Helper_Extranights($oInquiryAccommodation);
				$oHelper->aExtraNights = $aExtraNightsCurrent;
				$oHelper->aExtraWeeks = $aExtraWeeks;
				
				$oInquiryAccommodation->setExtranightHelper($oHelper);
				
				// Name generieren
				$sAccommodationName = $oInquiryAccommodation->getLineItemDescription($oDisplayLanguage);

				$oAccommodation					= Ext_Thebing_Accommodation_Category::getInstance($iAccommodationId);
				$sAccommodationDescription		= $oAccommodation->getName($sDisplayLanguage);

				// Betrag errechen
				$oAmount->setTimeData($iFrom, $iUntil, $iWeeks);
				$iAmount = $oAmount->calculateAccommodationAmount($oInquiryAccommodation, false, $iLastWeeksOfLastAcco, $sTmpItemKey);

				$aPriceCalculation = $oAmount->getCalculationDescription();

				$iAmountNet = $iAmount;
				$iAmountProv = 0;
				
				if($oInquiry->hasAgency()) {
					$iAmountNet = $oAmount->calculateAccommodationAmount($oInquiryAccommodation, true, $iLastWeeksOfLastAcco, $sTmpItemKey);
					$iAmountProv = $iAmount-$iAmountNet;
				}
				
				// Gruppen Guide checken und Amount löschen
				if(
					$oInquiry->hasGroup() &&
					$oInquiry->isGuide() &&
					(
						$oInquiry->getJourneyTravellerOption('free_accommodation') ||
						$oInquiry->getJourneyTravellerOption('free_all')
					)
				){
					$iAmount = $iAmountNet = $iAmountProv = 0;
				}

				$aAccommodationData[$i]['item_key']					= $sTmpItemKey;
				$aAccommodationData[$i]['description']				= $sAccommodationName;
				$aAccommodationData[$i]['amount']					= $iAmount;
				$aAccommodationData[$i]['amount_net']				= $iAmountNet;
				$aAccommodationData[$i]['amount_provision']			= $iAmountProv;
				$aAccommodationData[$i]['amount_discount']			= 0;
//				$aAccommodationData[$i]['id']						= $iInquiryAccommodationId;// Inquiry Accommodation Id
				$aAccommodationData[$i]['type']						= 'accommodation';
				$aAccommodationData[$i]['type_id']					= $iInquiryAccommodationId;// Inquiry Accommodation Id
				$aAccommodationData[$i]['type_object_id']			= $iAccommodationId;
				$aAccommodationData[$i]['type_parent_object_id']	= 0;
				$aAccommodationData[$i]['parent_id']				= (int)0;
				$aAccommodationData[$i]['parent_booking_id']		= (int)0;
				$aAccommodationData[$i]['from'] = $oInquiryAccommodation->from;
				$aAccommodationData[$i]['until'] = $oInquiryAccommodation->until;
				$aAccommodationData[$i]['index_from'] = $aAccommodationData[$i]['from'];
				$aAccommodationData[$i]['index_until'] = $aAccommodationData[$i]['until'];

				if(
					$bPaymentMethodLocal
				){
					$aAccommodationData[$i]['initalcost']		= 1;
				} else {
					$aAccommodationData[$i]['initalcost']		= 0;
				}

				$sFrom = $oHelper->getRealFrom();
				$sUntil = $oHelper->getRealUntil();
				
				$aAccommodationData[$i]['onPdf']				= 1;
				$aAccommodationData[$i]['calculate']			= 1;
				$aAccommodationData[$i]['from']					= $sFrom;
				$aAccommodationData[$i]['until']				= $sUntil;
				$aAccommodationData[$i]['index_from'] = $aAccommodationData[$i]['from'];
				$aAccommodationData[$i]['index_until'] = $aAccommodationData[$i]['until'];
				
				$dVatDate = Ext_TS_Vat::getVATReferenceDateByService($oSchool, $oInquiryAccommodation);				
				// Das ist die falsche Klasse, muss mal korrigiert werden. Die ID ist von der Kategorie, nicht von der Unterkunft an sich.
				$aAccommodationData[$i]['tax_category']			= Ext_TS_Vat::getDefaultCombination('Ext_Thebing_Accommodation', $iAccommodationId, $oSchool, $oInquiry, $dVatDate, $oDocument->type);

				$aAccommodationData[$i]['additional_info'] = array_merge($aAdditionalInfo, ['tooltip' => $aPriceCalculation, 'from' => $sFrom, 'until' => $sUntil]);

				// Tooltip
				$aAccommodationData[$i]['tooltip'] = $aPriceCalculation;

				$iLastWeeksOfLastAcco += $iWeeks;

				//Zusatzkosten
				$this->buildAdditionalAccommodationCostItems($aAdditionalAccommodationData, $oInquiryAccommodation, $oDisplayLanguage, $oAmount, $oDocument);
				
				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Extra nights

				if(!empty($aExtraNightsCurrent)) {

					foreach($aExtraNightsCurrent as $aData) {
						
						$sName = $oInquiryAccommodation->getExtraNightInfo($aData['nights'], $oDisplayLanguage, $aData['type']);
						
						$oAmount->aCurrencExtraNight = $aData;
						
						$iExtraNightsAmount = $oAmount->getExtraNightAmount(false, false, $oInquiryAccommodation);
						$iExtraNightsAmountNet = $oAmount->getExtraNightAmount(true, false, $oInquiryAccommodation);

						$iExtraNightsAmountProv = $iExtraNightsAmount - $iExtraNightsAmountNet;

						// Gruppen Guide checken und Amount löschen
						if(
							$oInquiry->hasGroup() &&
							$oInquiry->isGuide() &&
							$oInquiry->getJourneyTravellerOption('free_all')
						){
							$iExtraNightsAmount = $iExtraNightsAmountNet = $iExtraNightsAmountProv = 0;

						}

						$sFrom = $oHelper->getRealFrom($aData['type']);
						$sUntil = $oHelper->getRealUntil($aData['type']);

						$aExtraNightsAdditionalInfo = $aAdditionalInfo;
						$aExtraNightsAdditionalInfo['nights'] = $aData['nights'];
						$aExtraNightsAdditionalInfo['nights_type'] = $aData['type'];
						$aExtraNightsAdditionalInfo['from'] = $sFrom;
						$aExtraNightsAdditionalInfo['until'] = $sUntil;
						$aExtraNightsAdditionalInfo['index_from'] = $aExtraNightsAdditionalInfo['from'];
						$aExtraNightsAdditionalInfo['index_until'] = $aExtraNightsAdditionalInfo['until'];

						$aTempExtra = array(
							'nights' =>								$aData['nights'], // TODO: Spalte entfernen
							'description' =>						$sName,
							'amount' =>								$iExtraNightsAmount,
							'amount_net' =>							$iExtraNightsAmountNet,
							'amount_provision' =>					$iExtraNightsAmountProv,
							'amount_discount' =>					0,
//							'id' =>									(int) $iInquiryAccommodationId,
							'type' =>								'extra_nights',
							'type_id' =>							$iInquiryAccommodationId,
							'type_object_id' =>						$iAccommodationId,
							'type_parent_object_id' =>				0,
							'onPdf' =>								1,
							'calculate' =>							1,
							'tax_category' =>						$aAccommodationData[$i]['tax_category'],
							'parent_id' =>							0,
							'parent_booking_id' =>					0,
							'initalcost' =>							(int) $bPaymentMethodLocal,
							'additional_info' =>					$aExtraNightsAdditionalInfo,
							'from' =>								$sFrom,
							'until' =>								$sUntil
						);

						$aExtraNight[] = $aTempExtra;
					}
				}

				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Extra weeks

				if(!empty($aExtraWeeks)) {

					foreach($aExtraWeeks as $aData) {						
						$iAmount = $oAmount->getExtraWeekAmount(false, $oFirstAccommodation);
						$iAmountNet = $oAmount->getExtraWeekAmount(true, $oFirstAccommodation);
						$iAmountProv = $iAmount - $iAmountNet;

						$aData['type'] = str_replace('nights', 'weeks', $aData['type']);
						
						$sName = $oInquiryAccommodation->getExtraWeekInfo($aData['nights'], $oDisplayLanguage, $aData['type']);

						// Gruppen Guide checken und Amount löschen
						if(
							$oInquiry->hasGroup() &&
							$oInquiry->isGuide() &&
							$oInquiry->getJourneyTravellerOption('free_all')
						) {
							$iAmount = $iAmountNet = $iAmountProv = 0;
						}

						$sFrom = $oHelper->getRealFrom($aData['type']);
						$sUntil = $oHelper->getRealUntil($aData['type']);
						
						$aExtraWeekAdditionalInfo = $aAdditionalInfo;
						$aExtraWeekAdditionalInfo['extra_weeks'] = (int)$aData['nights'];
						$aExtraWeekAdditionalInfo['extra_weeks_type'] = $aData['type'];
						$aExtraWeekAdditionalInfo['from'] = $sFrom;
						$aExtraWeekAdditionalInfo['until'] = $sUntil;
						$aExtraWeekAdditionalInfo['index_from'] = $aExtraWeekAdditionalInfo['from'];
						$aExtraWeekAdditionalInfo['index_until'] = $aExtraWeekAdditionalInfo['until'];

						$aTempExtra = array(
							'description'		=> $sName,
							'amount'			=> $iAmount,
							'amount_net'		=> $iAmountNet,
							'amount_provision'	=> $iAmountProv,
							'amount_discount'	=> 0,
//							'id'				=> (int)$iInquiryAccommodationId,
							'type'				=> 'extra_weeks',
							'type_id'			=> $iInquiryAccommodationId,
							'type_object_id'	=> $iAccommodationId,
							'type_parent_object_id' => 0,
							'onPdf'				=> 1,
							'calculate'			=> 1,
							'tax_category'		=> $aAccommodationData[$i]['tax_category'],
							'parent_id'			=> 0,
							'parent_booking_id'	=> 0,
							'initalcost'		=> (int)$bPaymentMethodLocal,
							'additional_info'	=> $aExtraWeekAdditionalInfo,
							'from'				=> $sFrom,
							'until'				=> $sUntil
						);

						$aExtraNight[] = $aTempExtra;
					}
				}

				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
				$aItemsByKey[$sTmpItemKey] = $aAccommodationData[$i];

				$i++;

				$aHookData = array(
					'items' => &$aAccommodationData,
					'current_item' => &$aAccommodationData[$i-1],
					'item_count' => &$i,
					'object' => $oInquiryAccommodation,
					'inquiry' => $oInquiry
				);

				\System::wd()->executeHook('ts_inquiry_document_build_items_item', $aHookData);

			}

			// Reset keys on additional accommodation data
			$aAdditionalAccommodationData = array_values($aAdditionalAccommodationData);
		}

		if(!empty($oFirstCourse)) {
			$oWDDate->set($oFirstCourse->from, WDDate::DB_DATE);
			$iFirstFrom	 = (int)$oWDDate->get(WDDate::TIMESTAMP);
			$oWDDate->set($oFirstCourse->until, WDDate::DB_DATE);
			$iFirstUntil  = (int)$oWDDate->get(WDDate::TIMESTAMP);
			$oAmount->setTimeData($iFirstFrom, $iFirstUntil, $oFirstCourse->weeks);
		} else 	if(!empty($oFirstAccommodation)){
			$oWDDate->set($oFirstAccommodation->from, WDDate::DB_DATE);
			$iFirstFrom	 = (int)$oWDDate->get(WDDate::TIMESTAMP);
			$oWDDate->set($oFirstAccommodation->until, WDDate::DB_DATE);
			$iFirstUntil  = (int)$oWDDate->get(WDDate::TIMESTAMP);
			$oAmount->setTimeData($iFirstFrom, $iFirstUntil, $oFirstAccommodation->weeks);
		} else {
			$oAmount->setTimeData(0,0,0);
		}

		// Transfer Array füllen
		$aTransfersTwoWay = array();
		$i = 0;

		foreach($aTransfers as $iKey => $oTransfer) {

			// Leere Objekte (also ohne Datum) dürfen nicht beachtet werden
			if(!Core\Helper\DateTime::isDate($oTransfer->transfer_date, 'Y-m-d')) {
				continue;
			}

			$aTemp = array();

			$sTmpItemKey = Ext_Thebing_Util::generateRandomString(16);
			
			$oTransferPackage = Ext_Thebing_Transfer_Package::searchPackageByTransfer($oTransfer);

			$iAmount		= 0;
			$iAmountNet		= 0;
			$iAmountProv	= 0;

			$sName = $oTransfer->getName(null, 1, $oDisplayLanguage);

			$sTransferType = 'additional';
			if($oTransfer->transfer_type == 1) {
				$sTransferType = 'arrival';
			} elseif($oTransfer->transfer_type == 2) {
				$sTransferType = 'departure';
			}

			$aAdditionalInfo = array(
				'item_key' => $sTmpItemKey,
				'transfer_type' => $sTransferType,
				'billing_type' => 'once',
				'billing_units' => 1
			);
			
			// Daran werden nach dem speichern die Zugehörichkeiten zu evtl. vorhandenenn special positionen ermittelt
			$aSpecialAdditionalInfo = array(
				'parent_item_key' => $sTmpItemKey,
				'type' => 'transfer',
				'type_id' => (int)$oTransfer->id,
				'transfer_type' => $sTransferType, 
				'transfer_id' => (int)$oTransfer->id
			);

			if(is_object($oTransferPackage)) {

				$aAdditionalInfo['transfer_package_id'] = $oTransferPackage->id;

				$iAmount = (float)$oTransferPackage->amount_price;
				$iAmountNet = $iAmount;

				// Ist für die Provisionsberechnung und Season bestimmung relevant!
				$oAmount->setTimeData($oTransfer->transfer_date, 0);
				
				$oProvision = $oAmount->getTransferProvision($oTransfer);
				if ($oProvision) {
					$iAmountNet = $iAmountNet - $oProvision->calculate((float)$iAmount);
				}

				// Auf Special prüfen und berechnen
				$oSpecialAmount = new Ext_Thebing_Inquiry_Special_Amount($iAmount, $oTransferPackage);
				$oSpecialAmount->setCalculationTime(Carbon::parse($oTransfer->transfer_date)->getTimestamp());
				$oSpecialAmount->setInquiry($oInquiry);

				$oSpecialAmountNet = new Ext_Thebing_Inquiry_Special_Amount($iAmountNet, $oTransferPackage);
				$oSpecialAmountNet->setCalculationTime(Carbon::parse($oTransfer->transfer_date)->getTimestamp());
				$oSpecialAmountNet->setInquiry($oInquiry);

				// Transfer-special manuell setzen
				$oAmount->setSpecialAmount('transfer', $oTransferPackage->id, $oTransferPackage, $oSpecialAmount, false, $aSpecialAdditionalInfo);
				$oAmount->setSpecialAmount('transfer', $oTransferPackage->id, $oTransferPackage, $oSpecialAmountNet, true, $aSpecialAdditionalInfo);

			}

			// Gruppen Guide checken und Amount löschen
			if(
				$oInquiry->hasGroup() &&
				$oInquiry->isGuide() &&
				(
					$oInquiry->getJourneyTravellerOption('free_transfer') ||
					$oInquiry->getJourneyTravellerOption('free_all')
				)
			){
				$iAmount = $iAmountNet = $iAmountProv = 0;
				// Gratis-Gruppen-Guides extra aufführen in Maske
				$sName .= ' (' .$oDisplayLanguage->translate('gratis') . ')';
			}

			$aTemp['item_key']				= $sTmpItemKey;
			$aTemp['description']			= $sName;
			$aTemp['amount']				= $iAmount;
			$aTemp['amount_net']			= $iAmountNet;
			$aTemp['amount_provision']		= $iAmountProv;
			$aTemp['amount_discount']		= 0;
			$aTemp['type']					= 'transfer';
			$aTemp['type_id']				= (int)$oTransfer->id;
			$aTemp['type_object_id']		= 0;
			$aTemp['type_parent_object_id']	= 0;
			$aTemp['onPdf']					= 1;
			$aTemp['calculate']				= 1;
			
			$dVatDate = Ext_TS_Vat::getVATReferenceDateByDate($oSchool, new Carbon($oTransfer->transfer_date), new Carbon($oTransfer->transfer_date));
			$aTemp['tax_category'] = Ext_TS_Vat::getDefaultCombination(Ext_TS_Vat_Combination::KEY_OTHER, Ext_TS_Vat_Combination::OTHERS_TRANSFER, $oSchool, $oInquiry, $dVatDate, $oDocument->type);
			$aTemp['initalcost']			= 0;
			$aTemp['parent_id']				= (int)0;
			$aTemp['parent_booking_id']		= (int)0;
			$aTemp['additional_info']		= $aAdditionalInfo;
			$aTemp['type_object_id']		= 0;
			$aTemp['type_parent_object_id']	= 0;
			$aTemp['from'] = $oTransfer->transfer_date;
			$aTemp['until'] = $oTransfer->transfer_date;
			$aTemp['index_from'] = $aTemp['from'];
			$aTemp['index_until'] = $aTemp['until'];

			if($bPaymentMethodLocal){
				$aTemp['initalcost']		= 1;
			}

			$aTransferData[$i] = $aTemp;
			if($oTransfer->transfer_type != 0) {
				$aTemp['object'] = $oTransfer;
				$aTransfersTwoWay[$i] = $aTemp;
			}
			
			$i++;
		}

		// Hin & Abreisen
		if(count($aTransfersTwoWay) == 2) {

			$sTmpItemKey = Ext_Thebing_Util::generateRandomString(16);

			$aTemp = array_keys($aTransfersTwoWay);
			$iKey1 = (int)$aTemp[0];
			$iKey2 = (int)$aTemp[1];

			$oTransferArr = $aTransfersTwoWay[$iKey1]['object'];
			$oTransferDep = $aTransfersTwoWay[$iKey2]['object'];

			// TODO Vielleicht könnte man das mal optimieren, da hier bei 2 Transfers insgesamt 4x \Ext_Thebing_Transfer_Package::searchPackageByTransfer() aufgerufen wird
			$oTransferPackage = Ext_Thebing_Transfer_Package::searchPackageByTwoWayTransfer($oTransferArr, $oTransferDep);

			if(is_object($oTransferPackage)) {

				$sName = $oDisplayLanguage->translate('Anreise und Abreise');

				$sName .= ' ('.Ext_Thebing_Format::LocalDate($oTransferArr->transfer_date, $iSchoolId).', '.Ext_Thebing_Format::LocalDate($oTransferDep->transfer_date, $iSchoolId).')';

				$aAdditionalInfo = array(
					'item_key' => $sTmpItemKey,
					'transfer_arrival_id' => $oTransferArr->id,
					'transfer_departure_id' => $oTransferDep->id,
					'transfer_package_id' => $oTransferPackage->id,
					'billing_type' => 'once',
					'billing_units' => 1
				);
				
				$aSpecialAdditionalInfo = array(
					'parent_item_key' => $sTmpItemKey,
					'type' => 'transfer',
					'transfer_type' => 'app_dep',
					'transfer_arrival_id' => $oTransferArr->id,
					'transfer_departure_id' => $oTransferDep->id
				);

				$iAmount = (float)$oTransferPackage->amount_price_two_way;
				$iAmountNet = $iAmount;

				$oProvision = $oAmount->getTransferProvision($oTransfer, true);
				if ($oProvision) {
					$iAmountNet = $iAmountNet - $oProvision->calculate((float)$iAmount);
				}

				// Auf Special prüfen und berechnen
				$oSpecialAmount = new Ext_Thebing_Inquiry_Special_Amount($iAmount, $oTransferPackage);
				$oSpecialAmount->setCalculationTime(Carbon::parse($oTransferArr->transfer_date)->getTimestamp());
				$oSpecialAmount->setInquiry($oInquiry);

				$oSpecialAmountNet = new Ext_Thebing_Inquiry_Special_Amount($iAmountNet, $oTransferPackage);
				$oSpecialAmountNet->setCalculationTime(Carbon::parse($oTransferArr->transfer_date)->getTimestamp());
				$oSpecialAmountNet->setInquiry($oInquiry);

				// Transfer-special manuell setzen
				$oAmount->setSpecialAmount('transfer', $oTransferPackage->id, $oTransferPackage, $oSpecialAmount, false, $aSpecialAdditionalInfo);
				$oAmount->setSpecialAmount('transfer', $oTransferPackage->id, $oTransferPackage, $oSpecialAmountNet, true, $aSpecialAdditionalInfo);

				// Gruppen Guide checken und Amount löschen
				if(
					$oInquiry->hasGroup() &&
					$oInquiry->isGuide() &&
					(
						$oInquiry->getJourneyTravellerOption('free_transfer') ||
						$oInquiry->getJourneyTravellerOption('free_all')
					)
				) {
					$iAmount = $iAmountNet = $iAmountProv = 0;
					// Gratis-Gruppen-Guides extra aufführen in Maske
					$sName .= ' (' . $oDisplayLanguage->translate('gratis') . ')';
				}

				$aTemp = reset($aTransfersTwoWay);
				$aTemp['item_key']				= $sTmpItemKey;
				$aTemp['description']			= $sName;
				$aTemp['amount']				= $iAmount;
				$aTemp['amount_net']			= $iAmountNet;
				$aTemp['amount_provision']		= $iAmountProv;
				$aTemp['amount_discount']		= 0;
				$aTemp['type']					= 'transfer';
				
				$dVatDate = Ext_TS_Vat::getVATReferenceDateByDate($oSchool, new Carbon($oTransferArr->transfer_date), new Carbon($oTransferDep->transfer_date));
				$aTemp['tax_category'] = Ext_TS_Vat::getDefaultCombination(Ext_TS_Vat_Combination::KEY_OTHER, Ext_TS_Vat_Combination::OTHERS_TRANSFER, $oSchool, $oInquiry, $dVatDate, $oDocument->type);
				$aTemp['type_id']				= 0;
				$aTemp['additional_info']		= $aAdditionalInfo;
				$aTemp['from'] = $oTransferArr->transfer_date;
				$aTemp['until'] = $oTransferDep->transfer_date;
				$aTemp['index_from'] = $aTemp['from'];
				$aTemp['index_until'] = $aTemp['until'];

				unset($aTemp['object']);

				unset($aTransferData[$iKey1]);
				unset($aTransferData[$iKey2]);
				$aTransferData[] = $aTemp;
				$aTransferData = array_values($aTransferData);
				// Transfer items in die itemsByKey eintragen, damit die Daten des Parent von Specials weiter unten verfügbar sind
				foreach ($aTransferData as $transferItem) {
					$aItemsByKey[$transferItem['item_key']] = $transferItem;
				}
			}

		}


		// Insurances
		$aTemp = $aInsurances;
		$aInsurances = array();
		$i = 0;

		foreach((array)$aTemp as $oJourneyInsurance) {
			/** @var Ext_TS_Inquiry_Journey_Insurance|Ext_TS_Enquiry_Combination_Insurance $oJourneyInsurance */

			$sTmpItemKey = Ext_Thebing_Util::generateRandomString(16);
			
			$oInsurance = $oJourneyInsurance->getInsurance();
			if($oInsurance->payment == 3) {
				$sBillingType = 'week';
				$iBillingUnits = $oJourneyInsurance->weeks;
			} else {
				$sBillingType = 'once';
				$iBillingUnits = 1;
			}

			$fAmount = $oAmount->calculateInsuranceAmount($oJourneyInsurance);
			$sName = $oJourneyInsurance->getLineItemDescription($oDisplayLanguage);
			    
			$aPriceCalculation = $oAmount->getCalculationDescription();
				
			// Gruppen Guide checken und Amount löschen
			if(
				$oInquiry->hasGroup() &&
				$oInquiry->isGuide() &&
				$oInquiry->getJourneyTravellerOption('free_all')
			) {
				$fAmount = $iAmountNet = $iAmountProv = 0;
			}

			$aInsurances[$i]['item_key'] = $sTmpItemKey;
			$aInsurances[$i]['description']			= $sName;
			$aInsurances[$i]['amount']				= $fAmount;
			$aInsurances[$i]['amount_net']			= $fAmount;
			$aInsurances[$i]['amount_provision']	= 0;
			$aInsurances[$i]['amount_discount']		= 0;
//			$aInsurances[$i]['id']					= (int)$oJourneyInsurance->id;
			$aInsurances[$i]['type']				= "insurance";
			$aInsurances[$i]['type_id']				= (int)$oJourneyInsurance->id;
			$aInsurances[$i]['type_object_id']		= (int)$oJourneyInsurance->insurance_id;
			$aInsurances[$i]['type_parent_object_id']	= 0;
			$aInsurances[$i]['onPdf']				= 1;
			$aInsurances[$i]['calculate']			= 1;
			$aInsurances[$i]['parent_id']			= (int)0;
			$aInsurances[$i]['parent_booking_id']	= (int)0;
			$aInsurances[$i]['initalcost']			= (int)$bPaymentMethodLocal;
			
			$dVatDate = Ext_TS_Vat::getVATReferenceDateByService($oSchool, $oJourneyInsurance);
			$aInsurances[$i]['tax_category'] = Ext_TS_Vat::getDefaultCombination('Ext_Thebing_Insurances', (int)$oJourneyInsurance->insurance_id, $oSchool, $oInquiry, $dVatDate, $oDocument->type);
			$aInsurances[$i]['from'] = $oJourneyInsurance->from;
			$aInsurances[$i]['until'] = $oJourneyInsurance->getUntil();
			$aInsurances[$i]['index_from'] = $aInsurances[$i]['from'];
			$aInsurances[$i]['index_until'] = $aInsurances[$i]['until'];
			$aInsurances[$i]['additional_info'] = [
				'insurance_id' => (int)$oJourneyInsurance->insurance_id,
				'insurance_from' => $oJourneyInsurance->from,
				'insurance_until' => $oJourneyInsurance->getUntil(),
				'insurance_weeks' => $oJourneyInsurance->weeks,
				'billing_type' => $sBillingType,
				'billing_units' => $iBillingUnits,
				'tooltip' => $aPriceCalculation
			];

			$aInsurances[$i]['tooltip'] = $aPriceCalculation;
			
			$i++;

		}

		// Aktivitäten
		$aTemp = (array)$aActivities;
		$aActivities = [];
		$i = 0;

		foreach($aTemp as $oJourneyActivity) {
			/** @var Ext_TS_Inquiry_Journey_Activity $oJourneyActivity */

			$sTmpItemKey = Ext_Thebing_Util::generateRandomString(16);
			
			$oPrice = $oAmount->calculateActivityAmount($oJourneyActivity);
			$sName = $oJourneyActivity->getLineItemDescription($oDisplayLanguage);

			$fPrice = $fPriceNet = $fCommission = 0;
			if ($oPrice !== null) {
				$fPriceNet = $oPrice->getPrice();
				$fPrice = $oPrice->getPrice();
			}

			$oActivity = $oJourneyActivity->getActivity();

			if(
				$fPrice == 0 &&
				!$oActivity->showWithoutPrice()
			) {
				// Bei der Aktivität ist "Anzeigen wenn Preis 0 ist?" nicht ausgewählt, daher soll sie nicht auf der Rechnung erscheinen
				continue;
			}

			// Provision ausrechnen
			if($oInquiry->hasAgency() && $oPrice !== null) {

				$agencySchoolCommissions = new Ext_Thebing_Agency_Provision($oInquiry->getAgency()->id, $oSchool, $oPrice->getSeason()->id);

				$oCommission = $agencySchoolCommissions->getActivityCommission($oActivity);

				if ($oCommission) {
					$oPrice->setCommission($oCommission);
				}

				$fCommission = $oPrice->getCommission();
				$fPriceNet = $fPrice - $fCommission;
				
			}

			$aActivities[$i]['item_key'] = $sTmpItemKey;
			$aActivities[$i]['description'] = $sName;
			$aActivities[$i]['amount'] = $fPrice;
			$aActivities[$i]['amount_net'] = $fPriceNet;
			$aActivities[$i]['amount_provision'] = $fCommission;
			$aActivities[$i]['amount_discount'] = 0;
//			$aActivities[$i]['id'] = (int)$oJourneyActivity->id;
			$aActivities[$i]['type'] = 'activity';
			$aActivities[$i]['type_id'] = (int)$oJourneyActivity->id;
			$aActivities[$i]['type_object_id'] = (int)$oJourneyActivity->activity_id;
			$aActivities[$i]['type_parent_object_id'] = 0;
			$aActivities[$i]['onPdf'] = 1;
			$aActivities[$i]['calculate'] = 1;
			$aActivities[$i]['parent_id'] = 0;
			$aActivities[$i]['parent_booking_id'] = 0;
			$aActivities[$i]['initalcost'] = (int)$bPaymentMethodLocal;
			$dVatDate = Ext_TS_Vat::getVATReferenceDateByService($oSchool, $oJourneyActivity);
			$aActivities[$i]['tax_category'] = Ext_TS_Vat::getDefaultCombination(Ext_TS_Vat_Combination::KEY_OTHER, Ext_TS_Vat_Combination::OTHERS_ACTIVITY, $oSchool, $oInquiry, $dVatDate, $oDocument->type);
			$aActivities[$i]['from'] = $oJourneyActivity->from;
			$aActivities[$i]['until'] = $oJourneyActivity->until;
			$aActivities[$i]['index_from'] = $aActivities[$i]['from'];
			$aActivities[$i]['index_until'] = $aActivities[$i]['until'];
			//$dVatDate = Ext_TS_Vat::getVATReferenceDateByService($oSchool, $oJourneyActivity);
			//$aActivities[$i]['tax_category'] = Ext_TS_Vat::getDefaultCombination('Ext_Thebing_Insurances', (int)$oJourneyActivity->activity_id, $oSchool, $oInquiry, $dVatDate, $oDocument->type);
			$aActivities[$i]['additional_info'] = [
				'from' => $oJourneyActivity->from,
				'until' => $oJourneyActivity->until,
				'activity_id' => $oJourneyActivity->activity_id,
				'activity_weeks' => $oJourneyActivity->weeks,
				'activity_blocks' => $oJourneyActivity->blocks,
				'billing_type' => 'week',
				'billing_units' => $oJourneyActivity->weeks
			];

			$i++;

		}

		// Aufschlag/Rabatt für Zahlungsbedingung
		$aPaymentSurchargeData = $aExtraPositionData = [];

		$this->addPaymentConditionFee($aPaymentSurchargeData, $oInquiry, $aCourseData);

		## Special Positionen, kann erst hier angelegt werden, da nun alle Preise der einzelnen
		## Positionen errechnet wurden
		$this->aSpecialPositions = $oAmount->getSpecialAmounts();
		$i = 0;

		foreach((array)$this->aSpecialPositions as $sKey => $aSpecialPositionData){

			if(count($aSpecialPositionData['block']) > 0){

				$iAmountProv			= $aSpecialPositionData['gross'] - $aSpecialPositionData['net'];
				$aPositionData			= explode('_', $sKey);
				
				// Zusätzliche IDs mitschicken um später ausfindig zu machen zu welcher item_id 
				// die Special Position gehört.
				$aAdditionalInfo = '';
				if(!empty($aSpecialPositionData['additional_info'])){
					$aAdditionalInfo = $aSpecialPositionData['additional_info'];
				}

				if($aPositionData[0] == 'additional'){
					$sParentType		= $aPositionData[0] . '_' . $aPositionData[1];
					$iParentId			= (int)$aPositionData[2];
				}else{
					$sParentType		= $aPositionData[0];
					$iParentId			= (int)$aPositionData[1];
				}
				
				// ERSTER Special Block der gefunden wurde für diese Position
				$iFirstSpecialBlockId	= reset($aSpecialPositionData['block']);
				$oFirstSpecialBlock		= Ext_Thebing_Special_Block_Block::getInstance($iFirstSpecialBlockId);

				$iObjectId = 0;
				
				$oObject = null;
				switch($sParentType){
					case 'course':
						$oObject = $aSpecialPositionData['object'] ?? Ext_TS_Inquiry_Journey_Course::getInstance($iParentId);
						$iObjectId = $oObject->course_id;
						break;
					case 'accommodation':
						$oObject = $aSpecialPositionData['object'] ?? Ext_TS_Inquiry_Journey_Accommodation::getInstance($iParentId);
						$iObjectId = $oObject->accommodation_id;
						break;
					case 'transfer':
						$oObject = $aSpecialPositionData['object'] ?? Ext_Thebing_Transfer_Package::getInstance($iParentId);
						break;
					case 'additional_course':
					case 'additional_accommodation':
						$oObject = $aSpecialPositionData['object'] ?? Ext_Thebing_School_Additionalcost::getInstance($iParentId);
						$iObjectId = $iParentId;
						break;
				}

				if(is_object($oObject)) {

					$iTaxCategory = null;
					if(!empty($aSpecialPositionData['additional_info']['parent_item_key'])) {
						$aParentItem = $aItemsByKey[$aSpecialPositionData['additional_info']['parent_item_key']];
						$iTaxCategory = $aParentItem['tax_category'];
					}

					#$aAdditionalInfo['special']['code'] = 'ABC';
					
					$aSpecialData[$i]['object'] = $oObject;
					$aSpecialData[$i]['description']		= $oObject->getSpecialInfo($iSchoolId, $sDisplayLanguage);
					$aSpecialData[$i]['amount']				= (float)$aSpecialPositionData['gross'] * (-1);
					$aSpecialData[$i]['amount_net']			= (float)$aSpecialPositionData['net'] * (-1);
					$aSpecialData[$i]['amount_provision']	= (float)$iAmountProv * (-1);
					$aSpecialData[$i]['amount_discount']	= 0;
					
					$aSpecialData[$i]['tax_category'] = $iTaxCategory;
					
//					$aSpecialData[$i]['id']					= (int)$iFirstSpecialBlockId;
					$aSpecialData[$i]['type']				= 'special';
					$aSpecialData[$i]['type_id']			= (int)$iFirstSpecialBlockId;
					$aSpecialData[$i]['onPdf']				= 1;
					$aSpecialData[$i]['calculate']			= 1;
					/*
					 * Die Type-ID des Original-Items ist hier nicht korrekt, hier 
					 * muss nach dem Speichern die ID des Original-Items gespeichert werden
					 */
					$aSpecialData[$i]['parent_id']			= 0;// (int)$iParentId;
					$aSpecialData[$i]['parent_type']		= 'item_id'; //(string)$sParentType;
					$aSpecialData[$i]['type_parent_object_id']	= $iObjectId;
					$aSpecialData[$i]['parent_booking_id']	= (int)0; // Wird nach dem speichern erst befüllt.
					$aSpecialData[$i]['additional_info']	= $aAdditionalInfo;
					
					if(!empty($aParentItem['additional_info']['billing_type'])) {
						$aSpecialData[$i]['additional_info']['billing_type'] = $aParentItem['additional_info']['billing_type'];
					}

					// Der Code mit $aAdditionalInfo macht wenig Sinn, weil from und until immer in updateItemCache überschrieben wurden
//					if(
//						isset($aAdditionalInfo['from']) &&
//						isset($aAdditionalInfo['until'])
//					) {
//						$aSpecialData[$i]['from']	= $aAdditionalInfo['from'];
//						$aSpecialData[$i]['until']	= $aAdditionalInfo['until'];
//						$aSpecialData[$i]['index_from'] = $aSpecialData[$i]['from'];
//						$aSpecialData[$i]['index_until'] = $aSpecialData[$i]['until'];
//
//						if($aSpecialData[$i]['additional_info']['billing_type'] === 'month') {
//							$specialPeriod = new \Core\DTO\DateRange(new Carbon($aAdditionalInfo['from']), new Carbon($aAdditionalInfo['until']));
//							$aSpecialData[$i]['additional_info']['billing_units'] = Ext_TS_Inquiry_Journey_Service::getMonthCount($specialPeriod);
//						}
//
//					}

					$aSpecialData[$i]['from'] = $aParentItem['index_from'] ?? $aParentItem['from'] ?? $aAdditionalInfo['from'];
					$aSpecialData[$i]['until'] = $aParentItem['index_until'] ?? $aParentItem['until'] ?? $aAdditionalInfo['until'];
					$aSpecialData[$i]['index_from'] = $aSpecialData[$i]['from'];
					$aSpecialData[$i]['index_until'] = $aSpecialData[$i]['until'];

					if (
						!empty($aSpecialData[$i]['from']) &&
						!empty($aSpecialData[$i]['until']) &&
						$aSpecialData[$i]['additional_info']['billing_type'] === 'month'
					) {
						$specialPeriod = new \Core\DTO\DateRange(new Carbon($aAdditionalInfo['from']), new Carbon($aAdditionalInfo['until']));
						$aSpecialData[$i]['additional_info']['billing_units'] = Ext_TS_Inquiry_Journey_Service::getMonthCount($specialPeriod);
					}
					
					if(
						System::d('debugmode') > 0 &&
						!empty($aSpecialPositionData['additional_info']['calculation'])
					) {
						$aSpecialData[$i]['tooltip'] = $aSpecialPositionData['additional_info']['calculation'];
						unset($aSpecialData[$i]['additional_info']['calculation']);
					}
					
					$i++;
				}
			}
		}

		## Wenn keine Paketpreise
		if(!$bPacketPrices) {

			// Sortieren der Positionen!
			// Das ist keine Sortierung, sondern das Array wird neu aufgebaut.
			// TODO Gegen echte Sortierfunktion ersetzen (und variable Variable entfernen), da fehlende Typen einfach ganz verschwinden
			$aPositionOrder = $oSchool->getPositionOrder();

			foreach($aPositionOrder as $sPosition){
				$sDataArray = 'aTemp';
				switch($sPosition){
					case 'course':
						$sDataArray = 'aCourseData';
						break;
					case 'additional_course':
						$sDataArray = 'aAdditionalCourseData';
						break;
					case 'accommodation':
						$sDataArray = 'aAccommodationData';
						break;
					case 'additional_accommodation':
						$sDataArray = 'aAdditionalAccommodationData';
						break;
					case 'additional_general':
						$sDataArray = 'aAdditionalData';
						break;
					case 'extra_night':
					case 'extra_week':
						$sDataArray = 'aExtraNight';
						break;
					case 'transfer':
						$sDataArray = 'aTransferData';
						break;
					case 'extra_position':
						$sDataArray = 'aExtraPositionData';
						break;
					case 'payment_surcharge':
						$sDataArray = 'aPaymentSurchargeData';
						break;
					case 'special':
						$sDataArray = 'aSpecialData';
						break;
					case 'insurance':
						$sDataArray = 'aInsurances';
						break;
					case 'activity':
						$sDataArray = 'aActivities';
						break;

				}

				foreach((array)$$sDataArray as $aData){

					if($sDataArray == 'aTemp'){
						continue;
					}
					
					$aData['old_description'] = $aData['description'];

					if($sDataArray == 'aExtraNight')
					{
						if($aData['type'] == 'extra_nights' && $sPosition == 'extra_night')
						{
							$aBack[] = $aData;
						}
						else if($aData['type'] == 'extra_weeks' && $sPosition == 'extra_week')
						{
							$aBack[] = $aData;
						}
					}
					else
					{
						$aBack[] = $aData;
					}
				}

			}

		} 

		// Falls Firma angegeben, relevante Leistungstypen filtern
		$serviceTypes = [];
		if(!empty($this->company_id)) {

			$invoiceItemService = new Ts\Service\Invoice\Items($oSchool);
			$companyItems = $invoiceItemService->splitItemsByCompany($oInquiry, $aBack);
			
			$aBack = $companyItems[$this->company_id]??[];

		}
		
		// Generelle Fehler
		$this->aErrors = array_merge_recursive($this->aErrors, $oAmount->aErrors);
		$this->aErrors = array_merge($this->aErrors, Ext_TS_Inquiry_Journey_Insurance::$aInsuranceErrors);

		// Für jede Position noch ein optionales Label anhängen
		self::addPositionLabelByRef($aBack, $oInquiry);

		$aHookData = [
			'items' => &$aBack,
			'version' => $this
		];
		\System::wd()->executeHook('ts_inquiry_document_build_items', $aHookData);

		return $aBack;
	}

	protected function addPaymentConditionFee(array &$aPaymentSurchargeData, Ext_TS_Inquiry_Abstract $oInquiry, array $aCourseData) {
		
		$iPaymentConditionId = null;
		if($this->payment_condition_id > 0) {
			$iPaymentConditionId = $this->payment_condition_id;
		} elseif(
			$oInquiry instanceof Ext_TS_Inquiry && 
			$oInquiry->partial_invoices_terms > 0
		) {
			$iPaymentConditionId = $oInquiry->partial_invoices_terms;
		}
		
		if($iPaymentConditionId !== null) {
			$oPaymentTerms = Ext_TS_Payment_Condition::getInstance($iPaymentConditionId);

			if($oPaymentTerms->surcharge_amount != 0) {
				
				$from = $oInquiry->getFirstCourseStart();
				$until = $oInquiry->getLastCourseEnd();
				
				$periods = [];
				$billingUnits = 1;
				$billingType = 'once';
				
				if($oPaymentTerms->surcharge_type === 'amount') {
					$fAmount = $oPaymentTerms->surcharge_amount;
				} else {
					$fAmount = 0;
					foreach($aCourseData as $aCourseItem) {
						$fAmount += $aCourseItem['amount'];
					}
					$fAmount = $fAmount * $oPaymentTerms->surcharge_amount / 100;
				}					

				if($oPaymentTerms->surcharge_calculation === 'per_month') {
					
					$dummyCourse = new \Ext_TS_Inquiry_Journey_Course();
					$dummyCourse->from = $oInquiry->getFirstCourseStart();
					$dummyCourse->until = $oInquiry->getLastCourseEnd();

					$aCalculateMonths = \Core\Helper\DateTime::getMonthPeriods(new Carbon($dummyCourse->from), new Carbon($dummyCourse->until), false);
					
					$billingUnits = $totalAmount = 0;
					foreach($aCalculateMonths as $oCalculateMonth) {
						$fCourseMonth = \Ext_TS_Inquiry_Journey_Service::getMonthCount($oCalculateMonth);
						$totalAmount += $fCourseMonth * $fAmount;
						$billingUnits += $fCourseMonth;
						
						$periods[$oCalculateMonth->from->format('Y-m-d')] = $fCourseMonth * $fAmount;
						
					}
					
					$fAmount = $totalAmount;
					
					$billingType = 'month';
					
				} else {
					$until = $from;
				}
				
				$additionalInfo = [
					'payment_condition_id'=>$oPaymentTerms->id,
					'billing_units' => $billingUnits,
					'billing_type' => $billingType
				];
				
				if(!empty($periods)) {
					$additionalInfo['periods'] = $periods;
				}
				
				$aPaymentSurchargeData[] = [
					'type' => 'payment_surcharge',
					'description' => $oPaymentTerms->surcharge_description,
					'amount' => $fAmount,
					'amount_net' => $fAmount,
					'amount_provision' => 0,
					'amount_discount' => 0,
					'onPdf' => 1,
					'calculate' => 1,
					'from' => $from,
					'until' => $until,
					'index_from' => $from,
					'index_until' => $until,
					'additional_info' => $additionalInfo
				];

			}
		}
		
	}

	public static function getPositionLabel($aData) {

		$oFormat = new Ext_Thebing_Gui2_Format_Name();
		
		$aTemp = array();
		$aTemp['firstname'] = $aData['firstname'];
		$aTemp['lastname'] = $aData['lastname'];
		$sLabel = $oFormat->format($aTemp, $aTemp, $aTemp);

		if($aData['guide'] == 1){
			$sLabel .= ' (' . L10N::t('Guide', 'Thebing » PDF') . ')';
		}

		return $sLabel;

	}

	// Funktion fügt zu jeder Rechnungsposition ein Label hinzu
	public static function addPositionLabelByRef($aBack, Ext_TS_Inquiry_Abstract $oInquiry, $sType = 'customer'){
		
		switch($sType){

			default:
				foreach($aBack as $iKey => $aPosition){
					$aBack[$iKey]['label'] = '';#$oCustomer->name;

					if(
						$oInquiry->hasGroup() &&
						$oInquiry->isGuide()
					){
						$aBack[$iKey]['label'] .= ' (' . L10N::t('Guide', 'Thebing » PDF') . ')';
					}
				}	
		}
	}

	/**
	 * @param null $oDocument
	 * @param bool $bBuildNew
	 * @param bool $bCalculateWithTax
	 * @param bool $bCalculateWithDiscount
	 * @param bool $bForPdf
	 * @param bool $bForHistory Damit die aktuelle Version benutz wird bei getItems (vorallem bei Gruppen)
	 * @return array
	 */
	public function getGroupItems($oDocument = null, $bBuildNew = false, $bCalculateWithTax = false, $bCalculateWithDiscount = false, $bForPdf = false, $bForHistory = false) {
		
		if($oDocument == null) {
			$oDocument = $this->getDocument();
		}

		$sInquiryDocType = $oDocument->type;

		// Bei LOAs Muss beim Bearbeiten IMMER die letzte Invoice Rechnung geholt werden!!!!!!! WICHTIG!!!!
		if($sInquiryDocType == 'additional_document'){
			$sInquiryDocType = 'invoice';
		}
		
		$oInquiry = $this->getInquiry();
			
		$iInquiryId = (int)$oInquiry->id;
		
		$oSchool = $oInquiry->getSchool();

		/**
		 * @todo Sauber umsetzen
		 * Es ist total doof hier mit den Buchungen zu arbeiten, da sich diese ja
		 * ändern können. Man muss zu den Dokumenten weitere Informationen 
		 * speichern, um korrekt zuordnen zu können für wen die Rechnung ist.
		 */
//		if($oInquiry->getKey() == 'inquiry') {
		if ($oInquiry->type & Ext_TS_Inquiry::TYPE_BOOKING) {
			$aInquirys = Ext_Thebing_Inquiry_Group::getInquiriesOfGroup($oInquiry->group_id);
		} else {
			//Mehrere Anfragen nötig, es existiert nur 1 Dokument!
			$aInquirys = array($oInquiry);
		}

		$aTempItems = array();

		// alle Positionen aller Mitgleider holen
		foreach($aInquirys as $oInquiry) {

//			if($oInquiry->getKey() == 'inquiry') {
			if ($oInquiry->type & Ext_TS_Inquiry::TYPE_BOOKING) {
				// Rechnung des Mitglieds suchen, damit alle Positionen zusammen gemerged werden können
				// TODO Das könnte man eigentlich auf $oDocument->getDocumentsOfSameNumber() umstellen, wie an anderen Stellen
				$oSearch = new Ext_Thebing_Inquiry_Document_Search($oInquiry->id);
				$oSearch->setType($sInquiryDocType);
				$oSearch->setCredit($oDocument->is_credit);
				$oSearch->setDocumentNumber($oDocument->document_number);
				$iDocumentId = (int)$oSearch->searchDocument(false, false);

				if ($oInquiry->type & Ext_TS_Inquiry::TYPE_ENQUIRY) {
					// Das darf nicht ausgeführt werden, da die Dokumente anders zugewiesen sind.
					throw new \LogicException('Document search used for enquiry which is not possible');
				}
			} else {
				//Dokumentsuchen für einzelne Mitglieder nicht nötig bei Anfragen, es existiert nur 1 Dokument!
				$iDocumentId = (int)$oDocument->id;
			}

			if($iDocumentId > 0) {
				$oDocumentTemp = new Ext_Thebing_Inquiry_Document($iDocumentId);
			} else {
				$oDocumentTemp = $oInquiry->newDocument($sInquiryDocType, false);
			}

			$oVersion = $oDocumentTemp->getVersion($this->version);

			/*
			 * Wenn hier generell ein Dokument existiert, für dieses Gruppenmitglied aber nicht, dann wurde das Mitglied
			 * nachträglich ergänzt und muss daher hier überprungen werden.
			 */
			if(
				$bBuildNew !== true &&
				$oVersion === false &&
				$oDocument->exist()
			) {
				continue;
			}

			if($bForHistory == true){
				$oDocumentTemp = null; // löschen damit aktuelle Versionsitems geholt werden nicht die neueste
			}

			$aItems = array();
			if($oVersion !== false){

				$oVersion->bGetLastVersionFromCache = $this->bGetLastVersionFromCache;
				$aItems = $oVersion->getItems($oDocumentTemp, $bBuildNew, $bCalculateWithTax, $bCalculateWithDiscount, $bForHistory);

			} else {
				if($bForHistory !== true){ 
					//Bei der Historie dürfen wirklich NUR die Positionen geholt werden, zu der gerade gesuchten Version
					$aItems = $this->getItems($oDocumentTemp, $bBuildNew, $bCalculateWithTax, $bCalculateWithDiscount, $bForHistory);
				}
			}

			foreach((array)$aItems as $aItem) {
				// Inquiry Id je Item
				$aItem['inquiry_id'] = $oInquiry->id;

				// Key für Gruppierung der Items
				$sKey = $this->buildGroupItemKey($aItem);


				// Gleiche Items zusammenfügen
				$aTempItems[$sKey][] = $aItem;
			}
		}

		$aFinalItems = $this->mergeItemsForGroup($aTempItems, $bForPdf);

		if(
			!$bForHistory && 
			$oDocument->id <= 0
		) {
			// Sort by school settings
			$aBack = $this->sortPositions($aFinalItems);
		} else {
			$aBack = $aFinalItems;

			// Sort by position key
			usort($aBack, array($this, 'sortItems'));
		}

		return $aBack;

	}

	private function buildGroupItemKey(array $item) {

		$key = implode('_', [
			$item['description'], # Das muss weg
			(int)$item['amount_discount'], # Das muss weg
			$item['type'],
			$item['type_object_id'], # Bei Gruppen nicht die type_id nehmen, weil dieselbe Leistung bei Gruppenmitgliedern eine andere ID hat.
			$item['from'] ?? $item['index_from'],
			$item['until'] ?? $item['index_until']
		]);
		
		return $key;
	}
	
	/**
	 * Erzeugt einen String der eine Rechnungsposition eindeutig beschreibt, damit man identische Positionen identifizieren kann.
	 * Identisch insofern, dass die berechnete Leistung gleich sein muss.
	 * 
	 * @param array $item
	 * @return string
	 */
	private function buildItemKey(array $item) {

		$keyElements = [
			(string)$item['type'],
			(int)$item['type_id']
		];

		if($item['type'] === 'special') {
			$keyElements[] = (string)$item['additional_info']['type'];
			$keyElements[] = (int)$item['additional_info']['type_id'];
		} elseif($item['type'] === 'extra_nights') {
			$keyElements[] = $item['additional_info']['nights_type'];
		} elseif($item['type'] === 'extra_weeks') {
			$keyElements[] = $item['additional_info']['extra_weeks_type'];
		}
		
		if(!empty($item['index_from'])) {
			$keyElements[] = $item['index_from'];
		}
		if(!empty($item['index_until'])) {
			$keyElements[] = $item['index_until'];
		}

		$key = implode('_', $keyElements);
		
		return $key;
	}
	
	/*
	 * Funktion merged die Gruppenitems für die PDFs ODER die Rechnungsmaske
	 */
	public function mergeItemsForGroup($aTempItems, $bForPdf = false, $bSubItems=true, $bWriteSession=false) {

		$aFinalItems = array();
		$i = 1;

		if($bWriteSession) {
			$this->_oGui->resetDocumentPositions();
		}

		foreach((array)$aTempItems as $aItems) {

			$aTemp = reset($aItems);
			$aTemp['count']				= 0;
			$aTemp['count_all']			= 0;
			$aTemp['amount']			= 0;
			$aTemp['amount_provision']	= 0;
			$aTemp['amount_net']		= 0;

			$sStatus = '';
			$iOnPdf = 0;

			$iPositionKey = null;

			// alle Items durchegehen und status gegenbenfalls auf "edit" setzten
			foreach((array)$aItems as $iKey => $aItem) {

				if($iPositionKey === null) {
					if(isset($aItem['position_key'])) {
						$iPositionKey = $aItem['position_key'];
					} else {
						$iPositionKey = $i;
					}
				}
				
				$aItem['position_key'] = $iPositionKey;

				if($bWriteSession) {
					$this->_oGui->addDocumentPositionItem($i, $aItem);
				}

				// Gesamtanzahl festhalten
				$aTemp['count_all']++;
				
				// Für PDF muss counter den aktiven Items entsprechen
				if(
					$aItem['onPdf'] == 1
				){
					$aTemp['count']++;
				}
				
				if($aItem['onPdf'] == 1) {
					$iOnPdf = 1;
					// Nur aktive Items auf PDF
					$aTemp['amount']			+= $aItem['amount'];
					$aTemp['amount_provision']	+= $aItem['amount_provision'];
					$aTemp['amount_net']		+= $aItem['amount_net'];
				}

				if($aTemp['amount_discount'] != $aItem['amount_discount']){
					$aTemp['amount_discount'] = '';
				}

				if($aTemp['tax_category'] != $aItem['tax_category']){
					$aTemp['tax_category'] = 0;
				}

				if(
					$aItem['status'] == 'new' &&
					(
						$sStatus == '' ||
						$sStatus == 'new'
					)
				) {
					$sStatus = 'new';
				}else if($aItem['status'] == 'edit') {
					$sStatus = 'edit';
				} else {
					$sStatus = 'old';
				}

				$aItems[$iKey]['count'] = '&nbsp;';

			}

			// Leere Items überspringen
			if(
				(int)$aTemp['count'] < 1 &&
				$bForPdf //nicht im Dialog ausblenden!
			){
				continue;
			}

			// Summendaten setzten
			$aFinalItems[$i] = $aTemp;
			// Status für überpunkt setzten
			$aFinalItems[$i]['status'] = $sStatus;
			// OnPdf für den überpunkt setzen
			$aFinalItems[$i]['onPdf'] = $aFinalItems[$i]['calculate'] = (int)$iOnPdf;

			if($bSubItems) {
				// Items Setzten
				$aFinalItems[$i]['items'] = $aItems;
			}

			$aFinalItems[$i]['position_key'] = $iPositionKey;

			$i++;

		}

		$aFinalItems = array_values($aFinalItems);

		// die Finalen Items nochmals durchgehen um die Description an zu passen
		// Gruppen Guide Positionen
		if($bForPdf) {

			foreach((array)$aFinalItems as $iKey => $aMainItem){

				$bOnlyGuides = true;
				foreach((array)$aMainItem['items'] as $iSubKey => $aSubItem){

					if($aSubItem['inquiry_id'] > 0){
						$oInquiry = Ext_TS_Inquiry::getInstance($aSubItem['inquiry_id']);

						if(!$oInquiry->isGuide()){
							$bOnlyGuides = false;
							break;
						}
					}
				}

				if($bOnlyGuides){
					$aFinalItems[$iKey]['description'] .= ' (' . Ext_Thebing_L10N::t('Guide', $this->sLanguage, 'Thebing » PDF') . ')';
				}

			}

		}

		return $aFinalItems;

	}

	public function getGroupItemsForDialog(
		&$sourceDocument,
		$sType,
		$bForDiff,
		$bForCredit,
		$bCheckChanges
	) {

		if(!$this->_oGui instanceof Ext_Thebing_Gui2) {
			throw new Exception('GUI instance not found!');
		}

		$noSourceDocument = false;
		if($sourceDocument == null){
			$noSourceDocument = true;
		}
		
		$oDocument = $this->getDocument();

		$oInquiry = $this->getInquiry($oDocument);

		if(!$oInquiry instanceof Ext_TS_Inquiry_Abstract) {
			return array();
		}

		$iInquiryId = $oInquiry->id; 

		// keine Inquiry -> keine Positionen!
		if($iInquiryId <= 0) {
			return array();
		}

		/** @var Ext_Thebing_Inquiry_Group|Ext_TS_Enquiry_Group $oGroup */
		$oGroup = $oInquiry->getGroup();

		// Array mit allen Inquiries, die für die Berechnung der Positionen benötigt werden
		$aCalculateInquiries = array();

		// Beim ersten Aufruf schauen, ob alles für alle gleich ist.
		if(
			$this->id == 0 &&
			$oInquiry->type & Ext_TS_Inquiry::TYPE_BOOKING &&
			$oInquiry->hasSameData('course') &&
			$oInquiry->hasSameData('accommodation') &&
			$oInquiry->hasSameData('transfer')
		) {
			$aGuides = $oGroup->getGuides();
			$aOthers = $oGroup->getNotGuideMembers();

			$oMainInquiry = null;
			foreach($aOthers as $iKey => $oMember) {

				$oTempInquiry = $oInquiry->createMemberInquiry($oMember);

				// Bei einer Gutschrift dürfen auch nur die Gruppenmitglieder auftauchen, die bereits eine Rechnung haben
				if(
					$bForCredit &&
					$oTempInquiry->has_invoice == 0
				) {
					unset($aOthers[$iKey]);
				}
				else {
					$oMainInquiry = $oTempInquiry;
				}
			}

			if($oMainInquiry) {
				$oInquiry = $oMainInquiry;
			}

			// Alle Guides raussuchen
			foreach($aGuides as $oMember) {

				$oTempInquiry = $oInquiry->createMemberInquiry($oMember);

				// Bei einer Gutschrift dürfen auch nur die Gruppenmitglieder auftauchen, die bereits eine Rechnung haben
				if(
					(
						$bForCredit &&
						$oTempInquiry->has_invoice == 1
					) || (
						!$bForCredit # Wenn KEINE Gutschrift, dann alle anzeigen
					)
				) {
					$bIsInquiryBelongingToDocument = true;
					if(
//						$oGroup instanceof Ext_Thebing_Inquiry_Group &&
						$oDocument->type !== 'additional_document' // Bei LOA usw. alles immer aktuell anzeigen
					) {
						$bIsInquiryBelongingToDocument = $oGroup->isInquiryBelongingToDocument($oTempInquiry, $oDocument);
					}
					$aCalculateInquiries[] = array(
						'inquiry' => $oTempInquiry,
						'inquiry_belongs_to_document' => $bIsInquiryBelongingToDocument,
						'guide' => true
					);
				}
			}

			foreach($aOthers as $oMember) {
				$bIsInquiryBelongingToDocument = true;
				$oInquiry2 = $oInquiry->createMemberInquiry($oMember);
				if(
//					$oGroup instanceof Ext_Thebing_Inquiry_Group &&
					$oDocument->type !== 'additional_document' // Bei LOA usw. alles immer aktuell anzeigen
				) {
					$bIsInquiryBelongingToDocument = $oGroup->isInquiryBelongingToDocument($oInquiry2, $oDocument);
				}
				$aCalculateInquiries[] = array(
					'inquiry' => $oInquiry2,
					'inquiry_belongs_to_document' => $bIsInquiryBelongingToDocument,
					'guide' => false
				);
			}

		} else {
			$aMembers = $oInquiry->getAllGroupMembersForDocument($oDocument);

			// In der \Ext_TS_Enquiry::getAllGroupMembersForDocument passierte hier ein merkwürdiger Workaround: reset() auf $aMembers, da alle Mitglieder die gleichen Items haben
			if (
				$this->exist() &&
				$oGroup instanceof Ext_TS_Enquiry_Group
			) {
				$aMembers = [reset($aMembers)];
			}

			foreach($aMembers as $oMember) {
				// Standard bei Anfragegruppen muss true sein.
				$bIsInquiryBelongingToDocument = true;
				$oInquiry2 = $oInquiry->createMemberInquiry($oMember);
				if(
//					$oGroup instanceof Ext_Thebing_Inquiry_Group &&
					$oDocument->type !== 'additional_document' // Bei LOA usw. alles immer aktuell anzeigen
				) {
					$bIsInquiryBelongingToDocument = $oGroup->isInquiryBelongingToDocument($oInquiry2, $oDocument);
				}
				$aCalculateInquiries[] = array(
					'inquiry' => $oInquiry2,
					'inquiry_belongs_to_document' => $bIsInquiryBelongingToDocument,
				);
			}
		}

		// Wenn nicht auf Änderungen geprüft werden soll: Inquiries rauswerfen, die nicht zur Rechnung gehören, sonst wäre das trotzdem Aktualisierung/Diff #9443
		if(
			!$bCheckChanges &&
			!$bForCredit // Bei Gutschrift sollen vermutlich alle angezeigt werden
		) {
			foreach($aCalculateInquiries as $iKey => $aInquiry) {
				if(!$aInquiry['inquiry_belongs_to_document']) {
					unset($aCalculateInquiries[$iKey]);
				}
			}
		}

		// Doc Type ohne "group_"
		$sInquiryDocType = str_replace('group_', '', $oDocument->type);

		// Bei LOAs Muss beim Bearbeiten IMMER die letzte Invoice Rechnung geholt werden!!!!!!! WICHTIG!!!!
		if($sInquiryDocType == 'additional_document'){
			$sInquiryDocType = 'invoice';
		}

		$aTempItems = array();
		$iCountInquiries = count($aCalculateInquiries);

		unset($oInquiry);

		// Dadurch das $oDocument immer überschrieben wird muss hier der Wert der ausgewählten Rechnung vorgemerkt werden
		$iIsCredit = $oDocument->is_credit;

		// alle Positionen aller Mitgleider holen
		foreach($aCalculateInquiries as $aInquiry) {

			/** @var Ext_TS_Inquiry_Abstract $oInquiry */
			$oInquiry = $aInquiry['inquiry'];

			#$this->setInquiry($oInquiry);

//			$oInquiry->manipulateInstance();
//			if ($oInquiry->type == Ext_TS_Inquiry::TYPE_ENQUIRY) {
//				Ext_TS_Inquiry::setInstance($oInquiry);
//			}

			// Achtung: Es gibt das Problem, wenn eine Rechnung + Gutschrift existiert
			// Wenn man nun WIEDER eine NEUE Rechnung erstellen mag, darf NICHT die ALTE gefunden werden!
			// Deswegen suchen wir erst ob eine ALTE vorhanden ist, dann ob eine Gutschrift vorhanden ist

//			if($oInquiry->getKey() == 'inquiry') {
			
			$groupMemberDocumentId = $groupMemberSourceDocumentId = null;
			
			if ($oInquiry->type & Ext_TS_Inquiry::TYPE_BOOKING) {

				$oSearch = new Ext_Thebing_Inquiry_Document_Search($oInquiry->id);
				
				// Entsprechendes Dokument dieses Gruppenmitglieds raussuchen
				if(
					$sourceDocument &&
					$sourceDocument->exist()
				) {

					$sourceDocumentType = str_replace('group_', '', $sourceDocument->type);
					
					$oSearch->setType($sourceDocumentType);
					$oSearch->setCredit($sourceDocument->is_credit);
					$oSearch->setDocumentNumber($sourceDocument->document_number);

					$groupMemberSourceDocumentId = (int)$oSearch->searchDocument(false, false);

				}

				if(
					$oDocument &&
					$oDocument->exist()
				) {

					// Suche nach Dokument
					$oSearch->setType($sInquiryDocType);
					$oSearch->setCredit($iIsCredit);
					$oSearch->setDocumentNumber($oDocument->document_number);

					$groupMemberDocumentId = (int)$oSearch->searchDocument(false, false);

					// Verstehe ich nicht (MK)
					if ($oInquiry->type & Ext_TS_Inquiry::TYPE_ENQUIRY) {
						// Das darf nicht ausgeführt werden, da die Dokumente anders zugewiesen sind.
						throw new \LogicException('Document search used for enquiry which is not supported');
					}
					
				}
				
			} else {
				$groupMemberDocument = $this->getDocument();
				$groupMemberDocumentId = $groupMemberDocument->id;
			}

			if($groupMemberDocumentId > 0) {
				$groupMemberDocument = Ext_Thebing_Inquiry_Document::getInstance($groupMemberDocumentId);
				// Prüfen ob creditflag stimmt sonst neues doc. siehe kommentar weiter oben!
				if(
					$bForCredit === false &&
					(int)$groupMemberDocument->is_credit != (int)$bForCredit
				) {
					$groupMemberDocument = $oInquiry->newDocument($sInquiryDocType);
				}
			} else {
				$groupMemberDocument = $oInquiry->newDocument($sInquiryDocType);
			}

			/*
			 * Hier haben wir wieder ein spezielles Schmuckstück für Gruppen (Rechnungsaktualisierung): #9443
			 * Bei einem Schüler, der neu ist und nicht zur Rechnung gehört, muss buildItems() IMMER ausgeführt werden.
			 * Da wir uns hier auf der Version der ausgewählten Schülers befinden, würden sonst die Items dieser
			 * Version geholt und mit buildItems() des Schülers verglichen werden. Das geht aber nicht gut,
			 * da dann irgendwelche Daten mit irgendwelchen Daten verglichen werden.
			 */
			$bBuildNewAnyWay = $aInquiry['inquiry_belongs_to_document'] === false;

//			// Wenn kein Dokument übergeben wurde, soll ein neues Dokument angelegt werden. Daher wird $oCalculateInquiryDocument hier auf NULL gesetzt für diesen Fall
//			if($noSourceDocument) {
//				$oGroupMemberDocument = null;
//			}

			$groupMemberSourceDocument = null;
			
			/*
			* Sonderfall Stornierung
			* Das hier muss ein leeres Objekt mit type=storno sein, damit die Items korrekt generiert werden.
			*/
			if(
				$sourceDocument &&
				!$sourceDocument->exist() &&
				$sourceDocument->type === 'storno'
			) {
				$groupMemberSourceDocument = $oInquiry->newDocument('storno');
			} elseif($groupMemberSourceDocumentId) {
				$groupMemberSourceDocument = Ext_Thebing_Inquiry_Document::getInstance($groupMemberSourceDocumentId);
			}

			$groupMemberDocumentVersion = $groupMemberDocument->getLatestVersionOrNew();
			$groupMemberDocumentVersion->setInquiry($oInquiry);
			$groupMemberDocumentVersion->bCalculateProvisionNew = $this->bCalculateProvisionNew;

			$aItems = $groupMemberDocumentVersion->getItemsForDialog($groupMemberSourceDocument, $sType, $bForDiff, $bForCredit, $bCheckChanges, false, $bBuildNewAnyWay);

			if($bCheckChanges) {
				// manipuliert die Generellen Kosten anhand der Einstellung für Gruppen
				foreach((array)$aItems as $iKey => $aItem) {
					if($aItem['type'] == 'additional_general') {
						$oGeneralCost = Ext_Thebing_School_Additionalcost::getInstance((int)$aItem['type_id']);

						switch($oGeneralCost->group_option) {
							case 1:
								// pro Gruppenmitglied inkl. Leader (normal anzeigen)
								break;
							case 2:
								// pro Gruppenmitglied exkl. Leader (Leader zahlen nix)
								if($oInquiry->isGuide()) {
									unset($aItems[$iKey]);
								}
								break;
							case 3:
								// Wird nur einmal pro Gruppe berechnet (wird dem 1. Mitglied zugewiesen das kein Guide ist)
								if($groupMemberDocumentId == 0) {
									$aItems[$iKey]['amount'] = $aItem['amount'] / $iCountInquiries;
									$aItems[$iKey]['amount_net'] = $aItem['amount_net'] / $iCountInquiries;
									$aItems[$iKey]['amount_provision'] = $aItem['amount_provision'] / $iCountInquiries;
									$aItems[$iKey]['amount_discount'] = $aItem['amount_discount'] / $iCountInquiries;
								}
								break;
							case 4:
								// Wird nie berechnet bei Gruppen
								unset($aItems[$iKey]);
								break;
							default:
						}
					}
				}
			}

			// Bei einer Buchung steht hier zu jedem Item der gleiche
			// Kontakt - dies wird in der unteren foreach
			// Schleife korrigiert! Ticket #6232
			$oCustomer = $oInquiry->getTraveller();
			$aInquiry['data'] = array(
				'id' => $oInquiry->id,
				'lastname' => $oCustomer->lastname,
				'firstname' => $oCustomer->firstname,
				'guide' => $oInquiry->isGuide($oCustomer),
				'customer_id' => $oCustomer->getId()
			);

			foreach($aItems as $aItem) {

				/*
				 * @todo Das ist natürlich so suboptimal und wahrscheinlich nicht eindeutig
				 */
				$sKey = $this->buildGroupItemKey($aItem);
				
				// Abweichender Positions-Kontakt:
				// Sollte dies der Fall sein,
				// wird der richtige Kontakt geladen! Ticket: #6232
				if(
					!empty($aItem['contact_id']) &&
					$aItem['contact_id'] != $aInquiry['data']['customer_id']
				) {
					$oGroupContact = Ext_TS_Group_Contact::getInstance($aItem['contact_id']);
					$aItem['data'] = array(
						'id' => $oInquiry->id,
						'lastname' => $oGroupContact->lastname,
						'firstname' => $oGroupContact->firstname,
						'guide' => $oInquiry->isGuide($oGroupContact),
						'customer_id' => $oGroupContact->getId()
					);
					$aItem['label'] = $this->getPositionLabel($aItem['data']);
				}
				else {
					$aItem['label'] = $this->getPositionLabel($aInquiry['data']);
					$aItem['data'] = $aInquiry['data'];
				}
				$aItem['inquiry_id'] = (int)$aInquiry['data']['id'];
				$aTempItems[$sKey][] = $aItem;
			}

		}

		$aFinalItems = $aTempItems;

		// Items sortieren
		if($oDocument->id <= 0)	{
			// Sort by school settings
			$aFinalItems = $this->sortPositions($aFinalItems);
		} else {
			// Sort by position key
			usort($aFinalItems, array($this, 'sortItems'));
		}

		$aFinalItems = $this->mergeItemsForGroup($aFinalItems, false, false, true);

		return $aFinalItems;
	}
	
	/**
	 * Sortiert die Items nach Schuleinstellungen
	 */
	public function sortPositions(array $aItems) {

		$aBack		= array();

		$oDocument	= $this->getDocument();
		$oInquiry	= $oDocument->getInquiry();

		if($oInquiry) {
			$oSchool	= $oInquiry->getSchool();
		} else {
			$oSchool	= Ext_Thebing_Client::getFirstSchool();
		}

		$aPositionOrder	= $oSchool->getPositionOrder();

		foreach((array)$aPositionOrder as $sOrder) {

			$sNewOrder = $sNewOrder2 = 'course';

			switch($sOrder){
				case 'extra_night':
					$sNewOrder = $sNewOrder2 = 'extra_nights';
					break;
				case 'extra_week':
					$sNewOrder = $sNewOrder2 = 'extra_weeks';
					break;
				case 'extra_position':
					$sNewOrder = 'extra_position';
					$sNewOrder2 = 'extraPosition';
					break;
				default:
					$sNewOrder = $sNewOrder2 = $sOrder;
					break;
			}

			foreach((array)$aItems as $iKey => $aItem){
				
				if(isset($aItem['type'])) {
					$sType = $aItem['type'];
				} else {
					$sType = $aItem[0]['type'];
				}
				
				if(
					$sType == $sNewOrder ||
					$sType == $sNewOrder2
				){
					$aBack[] = $aItem;
					unset($aItems[$iKey]);
				}
			}

		}
		 

		// fehlende Positionen ergänzen (sollte nie vorkommen)
		$aBack += $aItems;

		return $aBack;
	}

	/**
	 * @TODO Diese Methode (Diff + Refresh) muss dringend ausgelagert werden
	 *
	 * Parameter 1 is Optional, we need this if the document isnt saved
	 *
	 * @param $oDocument
	 * @param null $sType
	 * @param $bForDiff
	 * @param $bForCredit
	 * @param bool $bCheckChanges
	 * @param bool $bCheckInstanceCache
	 * @param bool $bBuildNewAnyWay
	 * @param bool $iPartialInvoice
	 * @param Ext_TS_Document_PaymentCondition|null $oPaymentConditionService
	 * @return array
	 * @throws Ext_TC_Exception
	 */
	public function getItemsForDialog(
		&$oDocument,
		$sType,
		$bForDiff,
		$bForCredit,
		$bCheckChanges = true,
		$bCheckInstanceCache = true,
		$bBuildNewAnyWay = false,
		$iPartialInvoice = null,
		$oPaymentConditionService = null
	) {

		// Wenn Credit müssen keine Änderungen berücksichtigt werden da alles neu ist!
		if($bForCredit){
			$bCheckChanges = false;
			$bForDiff = true;
		}

		$aDocumentPositions = array();

		if($oDocument == null){
			$oDocument = $this->getDocument();
		}

		if($oDocument->type == 'storno'){
			$bCheckChanges = false;
		}

		$iDeletedPositionIdCount = 0;

		$oInquiry = $this->getInquiry($oDocument); 
		$oSchool = $oInquiry->getSchool();
		
		if(!$oInquiry){
			throw new Ext_TC_Exception('Document Placeholder need an invoice', 'placeholder_document_needed');
		}

		$iInquiryId = (int)$oInquiry->id;
		
		$oCustomer = $oInquiry->getTraveller();

		$sDisplayLanguage = $oCustomer->getLanguage();
		
		if(
			$this->sLanguage !== false &&
			$this->sLanguage != ""
		) {
			$sDisplayLanguage = $this->sLanguage;
		}
		
		$oLanguage = new Tc\Service\Language\Frontend($sDisplayLanguage);
		
		if(
			$bCheckInstanceCache &&
			$this->_oGui
		) {
			$aDocumentPositions = $this->_oGui->getDocumentPositions();
		}

		if(
			$bCheckInstanceCache &&    //// Wenn die Positionen bereits im GUI Objekt vorhanden sind
			!empty($aDocumentPositions)
		) {

			$aItems = array();

			foreach($aDocumentPositions as $aDocumentSubpositions) {
				foreach($aDocumentSubpositions as $aDocumentSubposition) {
					$aItems[] = $aDocumentSubposition;
				}
			}

		} else {

			$aChanges = self::getChanges($iInquiryId);

			if($bForDiff) {

				if(!$this->bDiffEdit && !$bForCredit){
					// Bei Diff. muss IMMER auf änderung geprüft werden ( ausnahme beim editieren-> this->bDiffEdit )
					$bCheckChanges = true;
				}

				// Wenn das document neu aufgemacht wird -> nur DB Items ausgeben
				// da dann nur editiert werden soll
				if(
					(
						$oDocument->id > 0 &&
						$this->bDiffEdit &&
						!$bCheckChanges
					) ||
					$bForCredit
				) {

					// Gesucht werden darf nur, wenn eine neue Gutschrift erstellt wird!
					// Ansonsten wird die Gutschrift beim Editieren mit voller Wahrscheinlichkeit überschrieben,
					//	wenn nach der Gutschrift eine neue Rechnung erstellt wird. #4841
					// Außerdem darf bei Gutschriften an die Agentur auch nicht gesucht werden. #5076
					if(
						$bForCredit &&
						$this->sAction !== 'edit_invoice' &&
						$sType !== 'creditnote'
					) {
						// Ansonsten Items  des Letzten Documents ermitteln
//						$oSearch = new Ext_Thebing_Inquiry_Document_Search($iInquiryId);
//						$oSearch->setType('invoice');
						// Nur überschreiben wenn auch etwas gefunden wurde
						// Vorher konnte $oDocument = false sein!
						#$oDocument = $oSearch->searchDocument(true, false) ?? $oDocument;
						#__out($oDocument->aData);
					}

					$this->bDoNotBuildNewItems = true;
					//// Hole alle Items zum Document/Version
					$aItems = $this->getItems($oDocument);

				} else {

					// Ansonsten Items des Letzten Documents ermitteln
					$oSearch = new Ext_Thebing_Inquiry_Document_Search($iInquiryId);
					
					if(strpos($sType, 'proforma_') !== false) {
						$oSearch->setType('proforma');
					} else {
						$oSearch->setType('invoice_without_proforma');
					}
					
					if(!empty($this->company_id)) {
						$oSearch->setCompanyId($this->company_id);
					}
					$aLastDocument = $oSearch->searchDocument();
					
					$oLastDocument = reset($aLastDocument);
					$bBreak = true;

					if(
						$oLastDocument->id == $oDocument->id &&
						$this->bDiffEdit
					) {
						$oLastDocument	= next($aLastDocument);
					}

					if($oLastDocument instanceof Ext_Thebing_Inquiry_Document) {

						$oLastVersion	= $oLastDocument->getLastVersion();
						// Wenn ID da ist -> aktualisieren -> eigene pos. "ignorieren"
						if($oDocument->id > 0) {
							$aItems = array();
						} else {
							$aItems = $oLastVersion->getItems($oLastDocument);
						}

						if(!$this->bDiffEdit){
							// Beim aktualisieren einer Diff Rechnung müssen anhand der vorherigen Diff
							// Rechnung die Positionen nochmal geholt werden, deswegen werden hier auch die
							// inaktiven Change-Flags geholt.
							$aChanges = self::getChanges($iInquiryId, '', $oDocument->id, true);
						}

						$aTempChanges = $aChanges;
						array_pop($aTempChanges); // paketpreis rauslöschen

						// Empty-Check bei $aTempChanges auskommentiert (sowie if else unten) Ticket #5892
						// Bei einer Diff auf einer Diff würden inaktive Positionen ansonsten nicht mehr angezeigt werden
						if(
							strpos($oLastDocument->type, 'diff') !== false
//							&& !empty($aTempChanges)
						){

							// Wenn das letzte Dokument eine Differenzrechnung ist erstelle ein Virtuelles Document welches
							// alle bisherigen Änderungen vereint
							// alle docs holen
							reset($aLastDocument);
							$aAllDocuments  = $aLastDocument;

							//rest durchgehen solange diif oder die LETZTE rechnung
							$bNormalInvoice = false;

							if(
								strpos($oLastDocument->type, 'diff') !== false ||
								$oLastDocument->is_credit != 1
							){

								foreach((array)$aAllDocuments as $oCurrentDoc){

									if(
										(
											strpos($oCurrentDoc->type, 'diff') !== false ||
											$bNormalInvoice == false
										) &&
										$oCurrentDoc->id != $oLastDocument->id
									) {

										// Sobald eine Normale rechnung gefunden wird ist es
										// der letzte durchlauf da alle rechnungen davor (falls vorhanden)
										// gutgeschrieben sein müssen
										if(strpos($oCurrentDoc->type, 'diff') === false){
											$bNormalInvoice = true;
										}

										$oCurrentVersion = $oCurrentDoc->getLastVersion();
										$aCurrentItems = $oCurrentVersion->getItems($oCurrentDoc);

										$aItems = $this->mergeVersionsItemsForDiff($aCurrentItems, $aItems);


									}

								}

							}						

						}
//						else if(strpos($oLastDocument->type, 'diff') !== false) {
//							return array();
//						}

					}

				}

			} else {

				// Hole alle Items zum Document/Version
				$aItems = $this->getItems($oDocument, $bBuildNewAnyWay, false, false, false, $sType);

				if($sType === 'creditnote') {
					$this->updateItemVat($oDocument, $sType, $aItems);
				}

			}

			// Nur bei neuen Dokumenten splitten, bei vorhandenen Dokumenten wurde ja schon gesplittet und die Items sollen nicht verändert werden.
			if(
				!$this->exist() &&
				!empty($this->company_id) &&
				!empty($aItems)
			) {

				$invoiceItemService = new Ts\Service\Invoice\Items($oSchool);
				$companyItems = $invoiceItemService->splitItemsByCompany($oInquiry, $aItems);

				$aItems = $companyItems[$this->company_id];
				
			}

			// Transferänderungen checken da Transfer Packete (arrival&Departure) keine eindeutige ID haben
			// und sonst nie neu berechnet werden würden
			if($oInquiry->getJourney()->transfer_mode & Ext_TS_Inquiry_Journey::TRANSFER_MODE_BOTH) {

				foreach((array)$aChanges as $iKey => $aChange){
					if(
						$aChange['type'] == 'transfer'
					) {
						// Anreise
						$oTransferArrival = $oInquiry->getTransfers('arrival');
						// Abreise
						$oTransferDeparture = $oInquiry->getTransfers('departure');
						break;
					}
				}

				// Transfer ID merken
				$iTransferPacketId = 0;
				foreach((array)$aChanges as $iKey => $aChange){
					if(
						$aChange['type'] == 'transfer' &&
						(
							$oTransferArrival->id == $aChange['type_id'] ||
							$oTransferDeparture->id == $aChange['type_id']
						)
					) {

						if($iTransferPacketId == 0) {
							$aChanges[$iKey]['type_id'] = 0;
							$iTransferPacketId = (int)$aChange['type_id'];
						} elseif($iTransferPacketId > 0) {
							unset($aChanges[$iKey]);
							continue;
						}
					}
				}

			}

			// Um Additional Costen zu prüfen bei der Diff Rechnung müssen wir feststellen wozu sie gehören (parent_id)
			$aTempAdditionalCostParent = $this->getAdditionalParentCombination($aItems);

			## Änderungen prüfen und gegenfalls positionen neuanlegen/löschen
			// Wenn es änderungen gibt baue dir die Positionen neu zusammen
			if($bCheckChanges) {

				$aFinalItems = array();
				$aNewItems	= $this->getItems($oDocument, true);

				// =========================================================================================
				// Alle Ferien Splittung Infos
				$aHolidaySplittings = []; /** @var Ext_TS_Inquiry_Holiday_Splitting[] $aHolidaySplittings */
				// Zusatzkosten die durch Feriensplittung übernommen werden müssen
				$aAdditionalCostsFromSchoolholiday = array();

				// Gucken ob Kurs/Unterk durch Schülerferien gesplittet wurde, Falls ja die
				// alte Position nicht anzeigen
				foreach((array)$aItems as $iDKey => $aItem){

					$bDelete = false;
					$aTmpHolidaySplittings = [];

					if($aItem['type'] == 'course') {
						$aTmpHolidaySplittings = Ext_TS_Inquiry_Holiday_Splitting::getRepository()->findBy([
							'journey_course_id' => $aItem['type_id']
						]);

					} elseif($aItem['type'] == 'accommodation') {
						$aTmpHolidaySplittings = Ext_TS_Inquiry_Holiday_Splitting::getRepository()->findBy([
							'journey_accommodation_id' => $aItem['type_id']
						]);
					}

					// Wenn Kurs/Unterk. in irgendweiner Form gesplittet wurde, muss er gelöscht werden, da beim
					// aktualisieren immer die neuen Kurse geholt werden müssen
					if(!empty($aTmpHolidaySplittings)) {
						$aHolidaySplittings = array_merge($aHolidaySplittings, $aTmpHolidaySplittings);
						$bDelete = true;
					}

					if($bDelete){
						#unset($aItems[$iDKey]); 
					}		
				}

				// Die neuen Kurszusatzkosten sollen nicht angezeigt werden, nur die des alten Kurses/Unterkunft (neue löschen)
				// @TODO Die Zusatzkosten werden pauschal immer rausgelöscht, selbst wenn es vorher gar keine gab (falsches Diff)
				foreach($aHolidaySplittings as $oHolidaySplitting) {

					$iInquiryCourseId = (int)$oHolidaySplitting->journey_course_id;
					$iInquirySplitCourseId = (int)$oHolidaySplitting->journey_split_course_id;
					$iInquiryAccommodationId = (int)$oHolidaySplitting->journey_accommodation_id;
					$iInquirySplitAccommodationId = (int)$oHolidaySplitting->journey_split_accommodation_id;

					foreach((array)$aNewItems as $iEKey => $aItemTemp){
						if(
							(
								$aItemTemp['parent_booking_id'] == $iInquirySplitCourseId ||
								$aItemTemp['parent_booking_id'] == $iInquiryCourseId ||
								$aItemTemp['parent_booking_id'] == $iInquirySplitAccommodationId ||
								$aItemTemp['parent_booking_id'] == $iInquiryAccommodationId
							) &&
							strpos($aItemTemp['type'], 'additional') !== false
						){
							unset($aNewItems[$iEKey]);
						}
					}

					// Refresh
					if($oDocument->id > 0) {
						// Alte Zusatzposition ergänzen
						foreach((array)$aItems as $iEKey => $aItemTemp){
							if(
								(
									$aItemTemp['parent_booking_id'] == $iInquirySplitCourseId ||
									$aItemTemp['parent_booking_id'] == $iInquiryCourseId ||
									$aItemTemp['parent_booking_id'] == $iInquirySplitAccommodationId ||
									$aItemTemp['parent_booking_id'] == $iInquiryAccommodationId
								) &&
								(
									$aItemTemp['type'] == 'additional_course' || 
									$aItemTemp['type'] == 'additional_accommodation'
								)
							){
								$aAdditionalCostsFromSchoolholiday[] = $aItemTemp;
								unset($aItems[$iEKey]);
							}
						}
					}

					// Diff
					else {

						// Es gibt noch keine alte Rechnung die Zusatzkosten müssen also irgendwie hergezaubert werden :)
						foreach((array)$aNewItems as $iEKey => $aItemTemp) {

							$oObject = null;
							$oInquiryCourse = null;
							$oInquiryAccommodation = null;
							$oInquiryItem = null;
							
							if(
								$aItemTemp['type'] == 'course' &&
								$iInquiryCourseId == $aItemTemp['type_id']
							) {
								$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance((int)$aItemTemp['type_id']);
								$oInquiryItem = $oInquiryCourse;
								$oObject = $oInquiryCourse->getCourse();
							} elseif (
								$aItemTemp['type'] == 'accommodation' &&
								$iInquiryAccommodationId == $aItemTemp['type_id']
							) {
								$oInquiryAccommodation = Ext_TS_Inquiry_Journey_Accommodation::getInstance((int)$aItemTemp['type_id']);
								$oInquiryItem = $oInquiryAccommodation;
								$oObject = $oInquiryAccommodation->getCategory();
							}
		
							if(is_object($oObject)) {

								if(is_object($oInquiryItem)) {

									// Fallback für alte Datensätze, wo der Check nichts gefunden hat
									if(!$oHolidaySplitting->hasOriginalData()) {

										$oOldService = $oHolidaySplitting->getJoinedObject('old_'.$oHolidaySplitting->getType());
										$oNewService = $oHolidaySplitting->getJoinedObject('new_'.$oHolidaySplitting->getType());

										$oHolidaySplitting->original_weeks = $oOldService->weeks + $oNewService->weeks;

										$dUntil = (new DateTime($oOldService->from))->add(new DateInterval('P'.$oHolidaySplitting->original_weeks.'W'));
										$oHolidaySplitting->original_from = $oOldService->from;
										$oHolidaySplitting->original_until = $dUntil->format('Y-m-d');

									}

									// Ursprungsposition wiederherstellen
									// !!WICHTIG!!! Objekt NICHT speichern, da es nur temporär benötigt wird um die Zusatzpositionen zu holen
									$oInquiryItem->weeks = $oHolidaySplitting->original_weeks;
									
									$aAdditionalCosts = array();
									
									// Cach für Zusatzkosten löschen damti diese korrekt berechnet werden können
									self::clearAdditionalCostCache();
									
									if($oInquiryCourse instanceof Ext_TS_Inquiry_Journey_Course) {
										
										$oInquiryItem->from = $oHolidaySplitting->original_from;
										$oInquiryItem->until = $oHolidaySplitting->original_until;
									
										$this->buildAdditionalCourseCostItems($aAdditionalCosts, $oInquiryCourse, $oLanguage);

									} elseif($oInquiryAccommodation instanceof Ext_TS_Inquiry_Journey_Accommodation) {
										
										$oInquiryItem->from = $oHolidaySplitting->original_from;
										$oInquiryItem->until = $oHolidaySplitting->original_from; // Hier stand vorher schon from drin
										
										$oAmount = new Ext_Thebing_Inquiry_Amount($oInquiry);
										$this->buildAdditionalAccommodationCostItems($aAdditionalCosts, $oInquiryAccommodation, $oLanguage, $oAmount, $oDocument);

									}
									
									$aAdditionalCostsFromSchoolholiday = array_merge($aAdditionalCostsFromSchoolholiday, $aAdditionalCosts);

								}
							}
						}
					}
				}

				$aNewItems = array_merge($aNewItems, $aAdditionalCostsFromSchoolholiday);

                //--------------------------------------------------------------

				$aTempItemsForCheck = $aItems;
				$aMatchedItemsItemKeys = []; // #12077 (Neuer Key => Alter Key)
				foreach((array)$aNewItems as $aNewItem){

					$bFound = false;

					// prüfen ob eine Änderung vorhanden ist
					foreach((array)$aChanges as $iCKey => $aChange) {

						if($aChange['status'] != 'edit') {
							continue;
						}

						// Wenn eine gefunden wird
						if(
							$aChange['type'] == $aNewItem['type'] &&
							$aChange['type_id'] == $aNewItem['type_id'] &&
							$aChange['parent_id'] == $aNewItem['parent_booking_id'] 
						) {

							// Schreibe die Postion NEU
							if($bForDiff){
								$aNewItem['status'] = 'new';
							} else {
								$aNewItem['status'] = 'edit';
							}

							// bisherige pos holen um an die Pos. Id zu kommen
							foreach((array)$aTempItemsForCheck as $iTCKey => $aItem) {
								if(
									$aItem['type'] == $aNewItem['type'] &&
									$aItem['type_id'] == $aNewItem['type_id'] &&
									$aItem['parent_booking_id'] == $aNewItem['parent_booking_id'] &&
									(
										$aItem['type'] == 'special' ||
										$aItem['amount'] >= 0 // Betrag muss positiv sein da es sonst gutschriften sind
									)									
								) {
									$aNewItem['position_id']	= $aItem['position_id'];
									//Position setzen für die Sortierung
									$aNewItem['position']		= $aItem['position'];
									// Eintrag entfernen damit z.b extra kosten nicht nochmal rausfliegen
									// da es möglich ist mehrere gleiche zu haben
									//unset($aTempItemsForCheck[$iTCKey]);
								}
							}

							if($aNewItem['position_id'] <= 0){
								$aNewItem['position_id'] = $iDeletedPositionIdCount;
								$iDeletedPositionIdCount--;
							}

							$aFinalItems[] = $aNewItem;
							
							$bFound = true;

							// Eintrag entfernen damit z.b extra kosten nicht nochmal rausfliegen
							// da es möglich ist mehrere gleiche zu haben
							/*
							 * Specials müssen drin bleiben, da ein Special (gleiche type_id) als Position mehrfach vorkommen kann
							 * Mit unset() würden ansonsten benötigte Änderungen des Specials nicht erkannt werden #6333
							 *
							 * Wurde nun ganz rausgenommen (auch ein paar Zeilen höher):
							 * 	Früher gab es parent_id/parent_booking_id nicht, aber das sollte jetzt damit funktionieren #7050
							 */
							//if($aNewItem['type'] !== 'special') {
							//	unset($aChanges[$iCKey]);
							//}

							break;

						}
					}

					// Wenn keine Änderung gefunden
					if($bFound == false) {

						// schaue ob es die Position bisher schon gab
						foreach((array)$aItems as $iDKey => $aItem) {

							$bFound = false;
							// Zusatzkosten prüfen ob diese aufgrund von Schülerferien neu berechnet werden müssen
							if(
								$aItem['type'] == 'additional_course' ||
								$aItem['type'] == 'additional_accommodation'
							){
								foreach($aHolidaySplittings as $oHolidaySplitting) {

									// #5068 - Fehler bei Schülerferien
									// Wenn ein Kurs gesplittet wurde, darf die alte Position nur übernommen werden, 
									// wenn der Zeitraum noch übereinstimmt
									if(
										$bForDiff &&
										$aItem['type'] == 'additional_course' &&
										(
											$aItem['index_from'] != $aNewItem['from'] ||
											$aItem['index_until'] != $aNewItem['until']	
										)										
									) {
										continue;
									}
									
									if(
										(int)$oHolidaySplitting->journey_course_id == (int)$aItem['parent_booking_id'] ||
										(int)$oHolidaySplitting->journey_accommodation_id == (int)$aItem['parent_booking_id']
									){
										$bFound = true;
										break;
									}
								}
							}
							#__out($this->buildItemKey($aItem).'#'.$this->buildItemKey($aNewItem));
							if(
								$this->buildItemKey($aItem) == $this->buildItemKey($aNewItem) &&
								$bFound == false
							) {
								
								// Wenn gefunden und keine Diff
								// ODER
								// Wenn gefunden und inaktiv
								if(
									!$bForDiff ||
									(
										$bForDiff &&
										$aItem['onPdf'] == 0
									)
								){

									if(!$bForDiff) {
										// #5068 - Fehler bei Schülerferien
										// Wenn ein Kurs gesplittet wurde, darf die alte Position nur übernommen werden, 
										// wenn der Zeitraum noch übereinstimmt
										$bFound = $this->_checkItemsTimeFrame($aItem, $aNewItem);
										if(!$bFound) {
											continue;
										}
									}
									
									// ÜBERNEHME die Position
									// wenn neues doc -> als neu makieren
									if($oDocument->id <= 0){
										$aItem['status'] = 'new';
																		
									} else {
										$aItem['status'] = 'old'; 
									}

									// Additionalinfo übernehmen
									foreach((array)$aNewItem['additional_info'] as $sAdditionalInfoKey=>$mAdditionalInfoValue) {
										if(!isset($aItem['additional_info'][$sAdditionalInfoKey])) {
											$aItem['additional_info'][$sAdditionalInfoKey] = $aNewItem['additional_info'][$sAdditionalInfoKey];
										}
									}

									// #12077: Gematchte Item-Keys sammeln für Child-Verknüpfung
									$aMatchedItemsItemKeys[$aNewItem['additional_info']['item_key']] = $aItem['additional_info']['item_key'];

									$aFinalItems[] = $aItem;
									$bFound = true;

									// Item aus der aktuellen Schleife löschen damit es möglich
									// ist 2x dieselbe position zu buchen!
									unset($aItems[$iDKey]);

								} else if($bForDiff) {

									// Zusatzkosten prüfen ob diese nochmal aufgeführt werden sollen
									if(
										$aNewItem['type'] == 'additional_course' ||
										$aNewItem['type'] == 'additional_accommodation'
									){
										$bTempFound = false;
										// Kosten die noch nicht aufgeführt werden nochmal auflisten
										if(in_array($aNewItem['parent_booking_id'], (array)$aTempAdditionalCostParent[$aNewItem['type']])){
											// .. aber nur nochmal aufführen wenn der Parent Course noch angezeigt wird
											// continue erstmal auskommentiert! Falls es wieder rein soll muss geprüft werden,
											// dass die Kosten NUR aufgeführt werden, wenn der dazugehörige Kurs auch angezeit wird
											// Fehler trat auf bei einer diff Rechnung!
											
											//continue;
											
										}

									}

									// #5068 - Fehler bei Schülerferien
									// Wenn ein Kurs gesplittet wurde, darf die alte Position nur übernommen werden, 
									// wenn der Zeitraum noch übereinstimmt
									$bFound = $this->_checkItemsTimeFrame($aItem, $aNewItem);
									
								}
								break;
							} else {
								// #5068 - Fehler bei Schülerferien
								// Das Problem war, dass wenn ein Kurs gesplittet wurde dessen Positionen nicht angezeigt wurden
								// Beispiel:
								//		Kurs wurde gesplittet in 1 Woche und 5 Wochen
								//		Differenzrechnung hat nur die Position mit der einen Woche angezeigt
								if($bForDiff) {
									$bFound = false;
								}
							}
						}

						// Wenn es die Position bisher auch nicht gab
						// und es KEINE DIFF ist
						if($bFound == false){
							
							// Übernehme die NEUE Position
							$aNewItem['status'] = 'new';

							// #12077
							// parent_item_key ersetzen, wenn Parent-Item oben gemacht wurde
							// Ansonsten hätte ein neues Special-Item kein Parent (Exception)
							if(
								!empty($aNewItem['additional_info']['parent_item_key']) &&
								!empty($aMatchedItemsItemKeys[$aNewItem['additional_info']['parent_item_key']])
							) {
								$aNewItem['additional_info']['parent_item_key'] = $aMatchedItemsItemKeys[$aNewItem['additional_info']['parent_item_key']];
							}

							// wurde die Position aber durch das system im Rahmen der Schulferiensplittung hinzugefügt
							// wird sie als "old" gekennzeichnet beim aktualisieren
							if($oDocument->id > 0){
								foreach((array)$aAdditionalCostsFromSchoolholiday as $aAdditionalCost){
									if($aAdditionalCost['type_id'] == $aNewItem['type_id']){
										$aNewItem['status'] = 'old';
										break;
									}
								}
							}
							
							$aFinalItems[] = $aNewItem;
						}
			
					// Wenn diff alter kurs als minusbetrag
					} else if($bForDiff){

						// TODO: Dieses ganze Code-Konstrukt ist kein Diff, sondern nur irgendein Frickelwerk
						// Hier müsste zudem pro Typ viel mehr überprüft werden, da diese ID-Geschichten nicht reichen!
						foreach((array)$aItems as $aItem){
							if(
								$this->buildItemKey($aItem) == $this->buildItemKey($aNewItem) &&
								(
									(
										$aItem['type'] == 'special' &&
										$aItem['amount'] < 0
									) ||
									(
										$aItem['type'] != 'special' &&
										$aItem['amount'] > 0
									)
								)
							){
								$aItem['status']			= 'delete';
								$aItem['amount']			= $aItem['amount'] * -1;
								$aItem['amount_net']		= $aItem['amount_net'] * -1;
								$aItem['amount_provision']	= $aItem['amount_provision'] * -1;
								$aItem['position_id']		= $iDeletedPositionIdCount;
								$aItem['editable']			= 1; // gibt an das die Position nicht verändert werden darf. Wurde rausgenommen da man nun alles editieren können soll!
								$aFinalItems[] = $aItem;
								$iDeletedPositionIdCount--;
								break;
							}
						}
					}
				}

				// Nur für diff, gelöschte suchen
				foreach((array)$aItems as $aItem) {

					$bFound = false;
					foreach((array)$aNewItems as $aNewItem){
						if(
							$this->buildItemKey($aItem) == $this->buildItemKey($aNewItem) &&(
								// Lieber separat abfragen, da niemand weiß, was mit einem abs() hier passieren würde
								$aItem['amount'] >= 0 || (
									$aItem['type'] === 'special' &&
									$aItem['amount'] <= 0
								)
							)
						) {
							$bFound = true;
							break;
						}
					}

					// Wenn die alte Pos. nicht gefunden wird => delete
					if($bFound == false) {

						// DIFF :: alles auser extra positioen udn gutschriften als gelöscht aufführen
						if(
							$aItem['type'] != 'extraPosition' &&
							$aItem['type'] != 'additional_general' &&	// Zeile eingefügt 29.07.11 T789
							$aItem['type'] != 'credit' &&
							$bForDiff && (
								$aItem['amount'] >= 0 || (
									// Specials müssen auch als gelöscht angezeigt werden können, haben aber negativen Betrag #6333
									$aItem['type'] === 'special' &&
									$aItem['amount'] <= 0
								)
							)
						) {
							$aItem['status']			= 'delete';
							$aItem['amount']			= $aItem['amount'] * -1;
							$aItem['amount_net']		= $aItem['amount_net'] * -1;
							$aItem['amount_provision']	= $aItem['amount_provision'] * -1;
							if($aItem['type'] != 'paket') {
								$aItem['editable']			= 1; // gibt an das die Position nicht verändert werden darf // EDIT: Diff positionen sollen IMMER editierbar sein
							} else {
								$aItem['editable']			= 1; // das ist eine ausnahme! ist ein Paket
							}							
							$aFinalItems[] = $aItem;
						} else if($bForDiff && $aItem['amount'] < 0) {
							$aItem['status']	= 'old';
							//$aFinalItems[]	= $aItem;
						} else if($bForDiff) {
							// DIFF:: Extra Positionen NICHT auffürhen
						}  else if(
							!$bForDiff && 
							(
								$aItem['type'] == 'extraPosition' ||
								$aItem['type'] == 'additional_general'
							)
							
						) {
							// Wenn KEINE Diff müssen Extra Positionen wieder dazugeholt werden
							$aItem['status']	= 'old';
							$aFinalItems[]	= $aItem;
						}
					}

				}

				// #17906, Ergänzung zu #12077: Bei Specials, die $aChanges edit haben, aber die Ursprungspos. nicht edit ist, muss parent_item_key überschrieben werden
				foreach ($aFinalItems as $iKey => $aItem) {
					if (
						!empty($aItem['additional_info']['parent_item_key']) &&
						isset($aMatchedItemsItemKeys[$aItem['additional_info']['parent_item_key']])
					) {
						$aItem['additional_info']['parent_item_key'] = $aMatchedItemsItemKeys[$aItem['additional_info']['parent_item_key']];
						$aFinalItems[$iKey] = $aItem;
					}
				}

				$aItems = $aFinalItems;
				// Sortieren nach dem Key "position"
				if($oDocument->id > 0) {
					usort($aItems, array($this, 'sortItems'));
				}

			}

			// Specials ohne Ursprungsposition entfernen
			$specialItems = [];
			$otherItems = [];
			foreach($aItems as $itemIndex=>$aItem) {
				if($aItem['type'] == 'special') {
					$key = $aItem['additional_info']['type'].'_'.(int)$aItem['additional_info']['type_id'];
					$specialItems[$key][] = $itemIndex;
				} else {
					$otherItems[] = $aItem['type'].'_'.(int)$aItem['type_id'];
				}
			}
			foreach($specialItems as $itemKey=>$specialItemIndexes) {
				if(!in_array($itemKey, $otherItems)) {
					foreach($specialItemIndexes as $specialItemIndex) {
						unset($aItems[$specialItemIndex]);
					}
				}
			}

			// Teilrechnung
			if(
				$iPartialInvoice &&
				$oPaymentConditionService !== null
			) {

				$oSearch = new Ext_Thebing_Inquiry_Document_Search($oInquiry->id);
				$oSearch->setType(['brutto', 'brutto_diff']);
				$oSearch->setPartialInvoice(true);
				$aDocuments = $oSearch->searchDocument();

				$aVersions = array_map(function(Ext_Thebing_Inquiry_Document $oInquiryDocument) {
					return $oInquiryDocument->getLastVersion();
				}, $aDocuments);

				$oDiff = new Ext_TS_Document_PartialInvoiceDiff($oInquiry, $aItems, $aVersions, $this);
				$oDiff->setPaymentConditionService($oPaymentConditionService);

				// Kompletten Restzeitraum abrechnen
				if($iPartialInvoice === 2) {
					$oDiff->setInvoicingCompleteRemainder();
				}

				$nextPartialInvoice = Ts\Entity\Inquiry\PartialInvoice::getRepository()->getNext($oInquiry);

				if(empty($nextPartialInvoice)) {
					throw new Ext_TC_Exception('No partial invoice left!');
				}
				
				$oSetting = $nextPartialInvoice->getSetting();
				
				// Zeitraum anzeigen und Felder sperren
				$oBillingPeriod = $oDiff->getBillingPeriod($oSetting, $nextPartialInvoice);

				$this->aItemTooltips['billing_period'] = vsprintf('%s: %s – %s', [
					L10N::t('Abrechnungszeitraum', Ext_Thebing_Document::$sL10NDescription),
					Ext_Thebing_Format::LocalDate($oBillingPeriod->from),
					Ext_Thebing_Format::LocalDate($oBillingPeriod->until)
				]);

				$aItems = $oDiff->diffItems();

			}

			// Alle Credits nicht editierbar machen!
			foreach($aItems as $iKey => $aItem) {
				if($aItem['type'] == 'credit') {
					$aItems[$iKey]['editable'] = 0;
				}
			}
			
			System::wd()->executeHook('ts_inquiry_document_get_dialog_items_return', $aItems, $oDocument, $sType);
		
			if(
				$bCheckInstanceCache &&
				is_object($this->_oGui)
			) {
				
				//ganz wichtig, nicht mit 0 anfangen!
				$iCounter = 1;

				$dBillingFrom = null;
				$dBillingUntil = null;
				
				foreach((array)$aItems as $iKey => $aItem) {
					$aItem['inquiry_id'] = $oInquiry->id;
					$aItem['position_key'] = $iCounter;
					
					$aItems[$iKey] = $aItem;

					$aTooltip = [];
					if(!empty($aItem['tooltip'])) {
						$aTooltip = $aItem['tooltip'];
					} elseif(!empty($aItem['additional_info']['tooltip'])) {
						$aTooltip = $aItem['additional_info']['tooltip'];
					}

					if(!empty($aTooltip)) {
						$this->aItemTooltips['amount_'.$iCounter] = self::getItemCalculation($aTooltip);
					}

					$aItem['data']['customer_id'] = $oCustomer->getId();

					$this->_oGui->addDocumentPositionItem($iCounter, $aItem);
					
					$iCounter++;
				}

				// Beim Editieren einer Teilrechnung den Abrechnungszeitraum anzeigen
				if(
					$sType !== 'creditnote' && // Dok ist bei Edit eigentliches Dok, bei CN aber Ursprungsdok
					$oDocument->partial_invoice &&
					empty($this->aItemTooltips['billing_period']) 
				) {
					
					// Abrechnungszeitraum bei Teilrechnung ermitteln
					$partialInvoice = Ts\Entity\Inquiry\PartialInvoice::query()
						->where('document_id', $this->document_id)
						->where('inquiry_id', $oInquiry->id)
						->first();
					
					$this->aItemTooltips['billing_period'] = vsprintf('%s: %s – %s', [
						L10N::t('Abrechnungszeitraum', Ext_Thebing_Document::$sL10NDescription),
						Ext_Thebing_Format::LocalDate($partialInvoice->from),
						Ext_Thebing_Format::LocalDate($partialInvoice->until)
					]);
				}
			
			}

		}

		// Fehler löschen die bei der Preisberechhnung aufgetreten sind aber irrelevant sind
		// da die Fehler-Position nicht angezeigt wird
		$this->cleanPriceErrors($aItems);

		return $aItems;
	}

	public function updateItemVat(Ext_Thebing_Inquiry_Document $oDocument, string $sType, array &$aItems) {
		
		$oSchool = $oDocument->getSchool();
		$oInquiry = $oDocument->getInquiry();
		
		foreach($aItems as &$aItem) {
			
			$iClassId = $aItem['type_object_id'];

			// TODO Kann man das nicht anders lösen?
			switch($aItem['type']) {
				case 'course':
					$sClass = 'Ext_Thebing_Tuition_Course';
					break;
				case 'accommodation':
					// Das ist die falsche Klasse, muss mal korrigiert werden. Die ID ist von der Kategorie.
					$sClass = 'Ext_Thebing_Accommodation';
					break;
				case 'insurance':
					$sClass = 'Ext_Thebing_Insurances';
					break;
				case 'additional_accommodation':
				case 'additional_course':
				case 'additional_general':
					$sClass = 'Ext_Thebing_School_Cost';
					$iClassId = $aItem['type_id']; // Nicht type_object_id
					break;
				case 'activity':
//					$sClass = '\TsActivities\Entity\Activity';
					$sClass = Ext_TS_Vat_Combination::KEY_OTHER;
					$iClassId = Ext_TS_Vat_Combination::OTHERS_ACTIVITY;
					break;
				case 'transfer':
//					$sClass = 'TRANSFER';
					$sClass = Ext_TS_Vat_Combination::KEY_OTHER;
					$iClassId = Ext_TS_Vat_Combination::OTHERS_TRANSFER;
					break;
				case 'extraPosition':
//					$sClass = 'NOT_ALLOCATED_EXTRAPOSITIONS';
					$sClass = Ext_TS_Vat_Combination::KEY_OTHER;
					$iClassId = Ext_TS_Vat_Combination::OTHERS_EXRAPOSITION;
					break;
				default:
					// Exception bei unbekanntem Typ?
					break;
			}

			$oService = new stdClass();
			$oService->from = $aItem['index_from'];
			$oService->until = $aItem['index_until'];

			$dVatDate = Ext_TS_Vat::getVATReferenceDateByService($oSchool, $oService);
			$aItem['tax_category'] = Ext_TS_Vat::getDefaultCombination($sClass, $iClassId, $oSchool, $oInquiry, $dVatDate, $sType);
	
		}
		
	}
	
	protected function _checkItemsTimeFrame($aItem, $aNewItem) {
		if(
			$aItem['type'] == 'course' ||
			$aItem['type'] == 'accommodation' ||
			$aItem['type'] == 'insurance'										
		) {
			$sItemFrom = $aItem['from'] || $aItem['index_from'];
			$sItemUntil = $aItem['until'] || $aItem['index_until'];
			$sNewItemFrom = $aNewItem['from'] || $aNewItem['index_from'];
			$sNewItemUntil = $aNewItem['until'] || $aNewItem['index_until'];

			$bFound = false;

			if(
				$sItemFrom == $sItemUntil &&
				$sNewItemFrom == $sNewItemUntil
			) {
				// ITEM NICHT Übernehmen
				// keine Änderung => muss nicht in der DIFF aufgeführt werden
				// Found => true damit es nicht als "neue" pos erkannt wird
				$bFound = true;
			}
		} else {

			$bFound = true;
		}
		
		return $bFound;
	}
	
	/**
	 * Erzeugt aus dem Kalkulationsarray eine Ausgabe
	 */
	static public function getItemCalculation(array $aCalculation) {
		
		$sCalculation = '';

		foreach($aCalculation as $mEntry) {
			if(is_numeric($mEntry)) {
				$mEntry = Ext_Thebing_Format::Number($mEntry);
			}
			if($mEntry == 'line') {
				$sCalculation .= '<hr/>';
			} else {
				$sCalculation .= $mEntry.'<br/>';
			}
		}

		return $sCalculation;
		
	}
	
	/*
	 * Löscht die Fehler aus dem Fehler array, die bei der Preisberechnung entstanden sind und
	 * nicht zu den finalItems gehören
	 */
	public function cleanPriceErrors($aItems){

		// Fehlertypen
		foreach((array)$this->aErrors as $sError => $aData){
			// Fehler IDs
			foreach((array)$aData as $jKey => $iId){
				// Positionen
				$bFound = false;
									
				foreach((array)$aItems as $iKey =>  $aItem){
					switch($sError){
						case 'wrong_unit_number':
						case 'wrong_week_number':
						case 'wrong_unit_season':
						case 'extraunit_start_gt_position':
						case 'extraweek_start_gt_position':
						case 'missing_unit_season':
							if($aItem['type'] == 'course'){
								$oInquiry		= $this->getInquiry();
								$oInquiryCourse = $oInquiry->getServiceObject($aItem['type'], $aItem['type_id']);
								//$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance((int)$aItem['type_id']);
								if($oInquiryCourse->course_id == $iId){
									$bFound = true;
									break;
								}
							}	
							break;
						case 'course_season_not_found':
						case 'course_season_found':
							if(
								$aItem['type_id'] == $iId &&
								$aItem['type'] == 'course'
							){
								$bFound = true;
								break;
							}
							break;
						case 'insurance_season_not_found':
						case 'insurance_wrong_week_number':
							if(
								$aItem['type_id'] == $jKey &&
								$aItem['type'] == 'insurance'
							){
								$bFound = true;
								break;
							}
							break;
						case 'accommodation_season_not_found':
						case 'accommodation_season_found':
						case 'accommodation_week_not_found':
							if(
								$aItem['type_id'] == $iId &&
								$aItem['type'] == 'accommodation'
							){
								$bFound = true;
								break;
							}
							break;
						case 'activity_season_not_found':
							if(
								$aItem['type_id'] == $iId &&
								$aItem['type'] == 'activity'
							){
								$bFound = true;
								break;
							}
							break;
					}
				}

				// Nicht gefundene Positionen löschen aus Fehlern
				if(!$bFound){
					unset($this->aErrors[$sError][$jKey]);
				}
			} 
			
			if(empty($this->aErrors[$sError])){
				unset($this->aErrors[$sError]);
			}
		}

	}

	public function sortItems($aItem1, $aItem2) {

		//Neue Positionen immer nach ganz unten
		$iState1 = 0;
		if($aItem1['status']=='new'){
			$iState1 = 1;
		}
		$iState2 = 0;
		if($aItem2['status']=='new'){
			$iState2 = 1;
		}

		if($iState1 < $iState2) {
			return 0;
		} elseif($iState1 == $iState2) {
			//Nur wenn Status gleich ist, position vergleichen
			if($aItem1['position'] == $aItem2['position']) {
				return 0;
			} elseif($aItem1['position'] < $aItem2['position']) {
				return -1;
			} else {
				return 1;
			}
		} else {
			return 1;
		}

	}

	public function mergeVersionsItemsForDiff($aItems1, $aItems2){

		$aNewItems = array();

		// Schauen ob in der aktuelleren version (2)
		// positionen sind die auch in der alten version sind(1)
		// wenn ja müssen die neuen(2) genommen werden da sie aktueller sind
		foreach($aItems2 as $iKey2 => $aItem2) {
			
			$bFound = false;
			
			$itemKey2 = $this->buildItemKey($aItem2);
			
			foreach($aItems1 as $iKey1 => $aItem1){

				$itemKey1 = $this->buildItemKey($aItem1);

				if($itemKey2 == $itemKey1) {

					// Item hebt sich auf
					if(
						($aItem2['amount'] < 0 && $aItem1['amount'] > 0) ||
						($aItem2['amount'] > 0 && $aItem1['amount'] < 0)
					) {
						
						unset($aNewItems[$itemKey1]);
						unset($aItems1[$iKey1]);
						unset($aItems2[$iKey1]);
												
					} else {
					
						$aNewItems[] = $aItem2;
						unset($aItems1[$iKey1]);
						
					}

					$bFound = true;
					
					break;
					
				}

			}

			if(!$bFound){
				$aNewItems[$itemKey2] = $aItem2;
			}

		}

		// jetzt noch die alten durchgehen um zu schauen ob dort positionen sind
		// welche in den neuen nicht waren, diese müssen dann ebenfall übernommen werden
		// da sie in der diff nur nicht da waren weil keine änderung vorlag
		foreach($aItems1 as $iKey1 => $aItem1) {
			$itemKey1 = $this->buildItemKey($aItem1);
			$aNewItems[$itemKey1] = $aItem1;
		}

		return $aNewItems;
	}

	/**
	 * @param Ext_Thebing_Inquiry_Document|null $oDocument
	 * @param bool $bGroup
	 * @param Ext_Thebing_Pdf_Template|null $oTemplate
	 * @return array
	 */
	public function getItemsForPdf(&$oDocument=null, $bGroup=false, $oTemplate=null) {

		if($oDocument == null) {
			$oDocument = $this->getDocument();
		}

		$oInquiry = $this->getInquiry($oDocument);

		$sLanguage = $this->template_language;
		if(empty($sLanguage)) {
			// Kundenobject holen
			$oCustomer = $oInquiry->getCustomer();
			// Korrespondenzsprache
			$sLanguage = $oCustomer->getLanguage();
		}

		// Schule holen
		$oSchool		= $oInquiry->getSchool();
		$iSchoolId		= (int)$oSchool->id;
		$iCurrencyId	= (int)$oInquiry->getCurrency();

		
		// Temporär speichern wird weiter unten überschreiben
		$iTax			= (int)$oSchool->tax;

		$bNetto			= false;
		$sType = 'gross';
		if(strpos($oDocument->type, 'netto') !== false){
			$sType = 'net';
			$bNetto = true;
		} elseif($oDocument->type == 'storno') {
			// Bezahlmethode muss über das Inquiry bestimmt werden und NICHT über den Doc typ #1854
			if($oInquiry->hasNettoPaymentMethod()) {
				$sType = 'net';
				$bNetto = true;
			}
			
		} elseif(strpos($oDocument->type, 'creditnote') !== false) {
			$sType = 'net_creditnote';
			$bNetto = true;
		}
			
		// Wenn Art der Tabelle überschrieben werden soll durch Template Einstellung
		if($oTemplate->inquirypositions_view == 3) {
			$bNetto = true;
		} elseif($oTemplate->inquirypositions_view == 2) {
			$bNetto	= false;
		}

		if($oDocument->type == 'additional_document') {
			if(
				(
					$oTemplate->inquirypositions_view == 1 || 
					$oTemplate->inquirypositions_view == 3
				) &&
				$oInquiry->hasNettoPaymentMethod()
			) {
				$bNetto = true;
				$sType = 'net';
			} else if($oTemplate->inquirypositions_view == 1) {
				$bNetto = false;
			}
		}

		// Wenn Template nicht übergeben wurde
		if(!$oTemplate instanceof Ext_Thebing_Pdf_Template) {
			$oTemplate = $this->getTemplate();
		}

		$aExclusive = $oSchool->getTaxExclusive();

		$bGetLastVersionFromCache = $this->bGetLastVersionFromCache;
		$this->bGetLastVersionFromCache = false;
		
		if($bGroup){
			$aItems	= (array)$this->getGroupItems($oDocument, false, false, true, true);	
			$aDataTax = Ext_TS_Vat::addGroupTaxRows($aItems, $oInquiry, $sLanguage);
		} else {
			$aItems	= (array)$this->getItems($oDocument, false, false, true);
			// Steuerzeilen zu den Rechnungspositionen //////////////
			$aDataTax = Ext_TS_Vat::addTaxRows($aItems, $oInquiry, $sLanguage);
		}
		
		$this->bGetLastVersionFromCache = $bGetLastVersionFromCache;

		$aItems = (array)$aDataTax['items'];
		$aGeneral = (array)$aDataTax['general'];

		$aTableData = array();
		$aTableData2 = array();
		$aAmountTotal = array();
		$aAmountTotal2 = array();
		$i = 0; 

		$aHookData = array('items' => &$aItems);
		System::wd()->executeHook('ts_inquiry_document_get_pdf_items', $aHookData);

		foreach((array)$aItems as $iKey => $aValue) {

			// Steuereinstellung holen (wird nun zusätzlich in der Version gespeichert
			if(
				$iKey == 0 &&
				$aValue['version_id'] > 0
			) {
				$oVersion = self::getInstance($aValue['version_id']);
				// Steuersatz aus Version
				$iTax = $oVersion->tax;
			}
			
			$bData2 = false;

			if(
				$aValue['onPdf'] == 1 &&
				$aValue['initalcost'] == 1 &&
				$aValue['calculate'] == 1
			){
				$bData2 = true;
			} else if(
				$aValue['onPdf'] == 1 &&
				$aValue['initalcost'] == 0 &&
				$aValue['calculate'] == 1
			){
				$bData2 = false;
			} else {

				continue;
			}

			$aTempVariable			= array();
			$aTempVariableAmount	= array();

			if(in_array(Ext_Thebing_Inquiry_Document::PDF_LINEITEM_NUMBERING, $aExclusive)) {
				$aTemp = [];
				$aTemp['orginal'] = $i+1;
				$aTemp['original'] = $i+1;
				$aTemp['text'] = $i+1;
				$aTemp['align'] = 'R';
				$aTemp['column'] = 'number';
				$aTempVariable[] = $aTemp;
			}
			
			if($bGroup){
				$aTemp 				= array();
				$aTemp['orginal'] 	= $aValue['count'];
				$aTemp['original'] 	= $aValue['count'];
				$aTemp['text'] 		= $aValue['count'];
				$aTemp['align'] 	= 'L';
				$aTemp['column'] 	= 'quantity';
				$aTempVariable[]	= $aTemp;
			}

			$aTemp 					= array();
			$aTemp['text'] 			= $aValue['description'];
			// Steuersatz wird ebenfalls als Schlüssel bei Gruppenverwendet
			// um gleiche positionen heraus zu finden
			$aTemp['tax_category'] 	= $aValue['tax_category'];
			// Discount wird ebenfalls als Schlüssel bei Gruppenverwendet
			// um gleiche positionen heraus zu finden
			$aTemp['amount_discount'] 	= $aValue['amount_discount'];
			$aTemp['column']		= 'description';

			array_push($aTempVariable, $aTemp);

			$aTempVariableAmount['brutto'] += Ext_Thebing_Format::convertFloat($aValue['amount']);

			if($sType == 'net') {

				$aTemp 				= array();
				$aTemp['orginal'] 	= $aValue['amount'];
				$aTemp['original'] 	= $aValue['amount'];
				$aTemp['text'] 		= Ext_Thebing_Format::Number($aValue['amount'], $iCurrencyId, $iSchoolId);
				$aTemp['align'] 	= 'R';
				$aTemp['column']	= 'amount';
				$aTemp['item_id'] = $aValue['id'];
				$aTemp['additional_info'] = $aValue['additional_info'];
				$aTempVariable[]	= $aTemp;

				//wurde eingebaut #1736
				$fAmountProvision	= round($aValue['amount'],2) - round($aValue['amount_net'],2);
				
				$aTemp 				= array();
				$aTemp['orginal'] 	= $aValue['amount_provision'];
				$aTemp['original'] 	= $aValue['amount_provision'];
				$aTemp['text'] 		= Ext_Thebing_Format::Number($fAmountProvision, $iCurrencyId, $iSchoolId);
				$aTemp['align'] 	= 'R';
				$aTemp['column']	= 'amount_provision';
				$aTempVariable[]	= $aTemp;

				$aTempVariableAmount['provision'] += Ext_Thebing_Format::convertFloat($aValue['amount_provision']);

				$aTemp 				= array();
				$aTemp['orginal'] 	= $aValue['amount_net'];
				$aTemp['original'] 	= $aValue['amount_net'];
				$aTemp['text'] 		= '<b>' . Ext_Thebing_Format::Number($aValue['amount_net'], $iCurrencyId, $iSchoolId) . '</b>';
				$aTemp['align'] 	= 'R';
				$aTemp['column']	= 'amount_net';
				$aTempVariable[]	= $aTemp;

				$aTempVariableAmount['netto'] += Ext_Thebing_Format::convertFloat($aValue['amount_net']);

				$fVatAmount = $aValue['amount_net_vat'];

			} elseif($sType == 'net_creditnote') {	
				
				$aTemp 				= array();
				$aTemp['orginal'] 	= $aValue['amount'];
				$aTemp['original'] 	= $aValue['amount'];
				$aTemp['text'] 		= Ext_Thebing_Format::Number($aValue['amount'], $iCurrencyId, $iSchoolId);
				$aTemp['align'] 	= 'R';
				$aTemp['column']	= 'amount';
				$aTemp['item_id'] = $aValue['id'];
				$aTemp['additional_info'] = $aValue['additional_info'];
				$aTempVariable[]	= $aTemp;

				$aTemp 				= array();
				$aTemp['orginal'] 	= $aValue['amount_net'];
				$aTemp['original'] 	= $aValue['amount_net'];
				$aTemp['text'] 		= Ext_Thebing_Format::Number($aValue['amount_net'], $iCurrencyId, $iSchoolId);
				$aTemp['align'] 	= 'R';
				$aTemp['column']	= 'amount_net';
				$aTempVariable[]	= $aTemp;

				$aTempVariableAmount['netto'] += Ext_Thebing_Format::convertFloat($aValue['amount_net']);
 
				//wurde eingebaut wegen #1736
				$fAmountProvision	= round($aValue['amount'],2) - round($aValue['amount_net'],2);
				
				$aTemp 				= array();
				$aTemp['orginal'] 	= $aValue['amount_provision'];
				$aTemp['original'] 	= $aValue['amount_provision'];
				$aTemp['text'] 		= '<b>' . Ext_Thebing_Format::Number($fAmountProvision, $iCurrencyId, $iSchoolId). '</b>';
				$aTemp['align'] 	= 'R';
				$aTemp['column']	= 'amount_provision';
				$aTempVariable[]	= $aTemp;

				$aTempVariableAmount['provision'] += Ext_Thebing_Format::convertFloat($aValue['amount_provision']);

				$fVatAmount = $aValue['amount_commission_vat'];
				
			} else {
				
				$aTemp 				= array();
				$aTemp['orginal'] 	= $aValue['amount'];
				$aTemp['original'] 	= $aValue['amount'];
				$aTemp['text'] 		= '<b>' . Ext_Thebing_Format::Number($aValue['amount'], $iCurrencyId, $iSchoolId) . '</b>';
				$aTemp['align'] 	= 'R';
				$aTemp['column']	= 'amount';
				$aTemp['item_id'] = $aValue['id'];
				$aTemp['additional_info'] = $aValue['additional_info'];
				$aTempVariable[]	= $aTemp;

				$fVatAmount = $aValue['amount_vat'];
			}

			// Steuern ////////////////////////////////
			if($iTax > 0) {

				$sVat = '';
				if($aValue['tax_category'] > 0) {
					if(in_array(0, $aExclusive)) {
						// % Anzeige
						$sVat = Ext_Thebing_Format::Number($aValue['tax'], null, null, false, 3).' %';
					}
					if(in_array(1, $aExclusive)) {
						// Währung Anzeige
						$sTmp = Ext_Thebing_Format::Number($fVatAmount, $iCurrencyId, $iSchoolId);
						if(!empty($sVat)) {
							$sTmp .= ' ('.$sVat.')';
						}
						$sVat = $sTmp;
					}
				}

				$aTemp 				= array();
				$aTemp['text'] 		= $sVat;
				$aTemp['orginal']	= $fVatAmount;
				$aTemp['original']	= $fVatAmount;
				$aTemp['vat'] 		= $aValue['tax'];
				$aTemp['align'] 	= 'R';
				$aTemp['column']	= 'amount_vat';
				$aTempVariable[]	= $aTemp;

				$aTempVariableAmount['vat'] += Ext_Thebing_Format::convertFloat($fVatAmount);
			}
			///////////////////////////////////////////////

			$i++;

			if(!$bData2){

				$aTableData[$i] = $aTempVariable;
				$aAmountTotal['brutto']				+= $aTempVariableAmount['brutto'];
				$aAmountTotal['netto']				+= $aTempVariableAmount['netto'];
				$aAmountTotal['provision']			+= $aTempVariableAmount['provision'];
				$aAmountTotal['vat']				+= $aTempVariableAmount['vat']	;

			} else {
				$aTableData2[$i] = $aTempVariable;
				$aAmountTotal2['brutto']			+= $aTempVariableAmount['brutto'];
				$aAmountTotal2['netto']				+= $aTempVariableAmount['netto'];
				$aAmountTotal2['provision']			+= $aTempVariableAmount['provision'];
				$aAmountTotal2['vat']				+= $aTempVariableAmount['vat']	;
			}

		}

		//keys neu setzten
		array_values($aTableData);
		array_values($aTableData2);

		// Wenn es Vor-Ort Positionen gibt, dann die Summen Labels anpassen
		$aSumLabels = array();
		if(!empty($aTableData2)) {
			$aSumLabels['table1'] = \Ext_TC_Placeholder_Abstract::translateFrontend('Summe Vor-Anreise', $sLanguage);
			$aSumLabels['table2'] = \Ext_TC_Placeholder_Abstract::translateFrontend('Summe Vor-Ort', $sLanguage);		
		} else {
			$aSumLabels['table1'] = \Ext_TC_Placeholder_Abstract::translateFrontend('Summe', $sLanguage);
			$aSumLabels['table2'] = \Ext_TC_Placeholder_Abstract::translateFrontend('Summe', $sLanguage);				
		}

		// Gesammtzeile1
		if(!empty($aTableData)){

			$aTableData[] = 'line';
			$aTotal = array();
			$i = 0;

			if(in_array(Ext_Thebing_Inquiry_Document::PDF_LINEITEM_NUMBERING, $aExclusive)) {
				$aTotal[$i]['text'] = '';
				$aTotal[$i]['column'] = 'number';
				$i++;
			}
			
			if($bGroup){
				$aTotal[$i]['text'] = '';
				$aTotal[$i]['column'] = 'quantity';
				$i++;
			}

			$aTotal[$i]['text'] = $aSumLabels['table1'];
			$aTotal[$i]['type'] = 'sum1';
			$aTotal[$i]['column'] = 'description';
			$i++;
			
			if($sType == 'net') {

				$aTotal[$i]['orginal'] 	= $aAmountTotal['brutto'];
				$aTotal[$i]['original'] 	= $aAmountTotal['brutto'];
				$aTotal[$i]['text'] = Ext_Thebing_Format::Number($aAmountTotal['brutto'], $iCurrencyId, $iSchoolId);
				$aTotal[$i]['align'] = 'R';
				$aTotal[$i]['column'] = 'amount';
				$i++;

				$aTotal[$i]['orginal'] 	= $aAmountTotal['provision'];
				$aTotal[$i]['original'] 	= $aAmountTotal['provision'];
				$aTotal[$i]['text'] = Ext_Thebing_Format::Number($aAmountTotal['provision'], $iCurrencyId, $iSchoolId);
				$aTotal[$i]['align'] = 'R';
				$aTotal[$i]['column'] = 'amount_provision';
				$i++;

				$aTotal[$i]['orginal'] 	= $aAmountTotal['netto'];
				$aTotal[$i]['original'] 	= $aAmountTotal['netto'];
				$aTotal[$i]['text'] = '<b>' . Ext_Thebing_Format::Number($aAmountTotal['netto'], $iCurrencyId, $iSchoolId) . '</b>';
				$aTotal[$i]['align'] = 'R';
				$aTotal[$i]['column'] = 'amount_net';
				$i++;
				
			} elseif($sType == 'net_creditnote') {

				$aTotal[$i]['orginal'] 	= $aAmountTotal['brutto'];
				$aTotal[$i]['original'] 	= $aAmountTotal['brutto'];
				$aTotal[$i]['text'] = Ext_Thebing_Format::Number($aAmountTotal['brutto'], $iCurrencyId, $iSchoolId);
				$aTotal[$i]['align'] = 'R';
				$aTotal[$i]['column'] = 'amount';
				$i++;

				$aTotal[$i]['orginal'] 	= $aAmountTotal['netto'];
				$aTotal[$i]['original'] 	= $aAmountTotal['netto'];
				$aTotal[$i]['text'] = Ext_Thebing_Format::Number($aAmountTotal['netto'], $iCurrencyId, $iSchoolId);
				$aTotal[$i]['align'] = 'R';
				$aTotal[$i]['column'] = 'amount_net';
				$i++;

				$aTotal[$i]['orginal'] 	= $aAmountTotal['provision'];
				$aTotal[$i]['original'] 	= $aAmountTotal['provision'];
				$aTotal[$i]['text'] = '<b>' . Ext_Thebing_Format::Number($aAmountTotal['provision'], $iCurrencyId, $iSchoolId).'</b>';
				$aTotal[$i]['align'] = 'R';
				$aTotal[$i]['column'] = 'amount_provision';
				$i++;

			} else {

				$aTotal[$i]['orginal'] 	= $aAmountTotal['brutto'];
				$aTotal[$i]['original'] 	= $aAmountTotal['brutto'];
				$aTotal[$i]['text'] = '<b>' . Ext_Thebing_Format::Number($aAmountTotal['brutto'], $iCurrencyId, $iSchoolId) . '</b>';
				$aTotal[$i]['align'] = 'R';
				$aTotal[$i]['column'] = 'amount';
				$i++;

			}

			// Steuern
			if($iTax > 0){
				$aTotal[$i]['text'] = '';
				$aTotal[$i]['column'] = 'amount_vat';
				$i++;
			}

			$aTableData[] = $aTotal;

		}

		// Gesammtzeile2
		if(!empty($aTableData2)){

			$aTableData2[] = 'line';
			$aTotal = array();
			$i = 0;

			if(in_array(Ext_Thebing_Inquiry_Document::PDF_LINEITEM_NUMBERING, $aExclusive)) {
				$aTotal[$i]['text'] = '';
				$aTotal[$i]['column'] = 'quantity';
				$i++;
			}
			
			if($bGroup){
				$aTotal[$i]['text'] = '';
				$aTotal[$i]['column'] = 'quantity';
				$i++;
			}

			$aTotal[$i]['text'] = $aSumLabels['table2'];
			$aTotal[$i]['type'] = 'sum2';
			$aTotal[$i]['column'] = 'description';
			$i++;
			
			if($sType == 'net') {

				$aTotal[$i]['orginal'] 	= $aAmountTotal2['brutto'];
				$aTotal[$i]['original'] 	= $aAmountTotal2['brutto'];
				$aTotal[$i]['text'] = Ext_Thebing_Format::Number($aAmountTotal2['brutto'], $iCurrencyId, $iSchoolId);
				$aTotal[$i]['align'] = 'R';
				$aTotal[$i]['column'] = 'amount';
				$i++;

				$aTotal[$i]['orginal'] 	= $aAmountTotal2['provision'];
				$aTotal[$i]['original'] 	= $aAmountTotal2['provision'];
				$aTotal[$i]['text'] = Ext_Thebing_Format::Number($aAmountTotal2['provision'], $iCurrencyId, $iSchoolId);
				$aTotal[$i]['align'] = 'R';
				$aTotal[$i]['column'] = 'amount_provision';
				$i++;

				$aTotal[$i]['orginal'] 	= $aAmountTotal2['netto'];
				$aTotal[$i]['original'] 	= $aAmountTotal2['netto'];
				$aTotal[$i]['text'] = '<b>' . Ext_Thebing_Format::Number($aAmountTotal2['netto'], $iCurrencyId, $iSchoolId) . '</b>';
				$aTotal[$i]['align'] = 'R';
				$aTotal[$i]['column'] = 'amount_net';
				$i++;
				
			} elseif($sType == 'net_creditnote') {

				$aTotal[$i]['orginal'] 	= $aAmountTotal2['brutto'];
				$aTotal[$i]['original'] 	= $aAmountTotal2['brutto'];
				$aTotal[$i]['text'] = Ext_Thebing_Format::Number($aAmountTotal2['brutto'], $iCurrencyId, $iSchoolId);
				$aTotal[$i]['align'] = 'R';
				$aTotal[$i]['column'] = 'amount';
				$i++;

				$aTotal[$i]['orginal'] 	= $aAmountTotal2['netto'];
				$aTotal[$i]['original'] 	= $aAmountTotal2['netto'];
				$aTotal[$i]['text'] = Ext_Thebing_Format::Number($aAmountTotal2['netto'], $iCurrencyId, $iSchoolId);
				$aTotal[$i]['align'] = 'R';
				$aTotal[$i]['column'] = 'amount_net';
				$i++;
				
				$aTotal[$i]['orginal'] 	= $aAmountTotal2['provision'];
				$aTotal[$i]['original'] 	= $aAmountTotal2['provision'];
				$aTotal[$i]['text'] = '<b>' . Ext_Thebing_Format::Number($aAmountTotal2['provision'], $iCurrencyId, $iSchoolId). '</b>';
				$aTotal[$i]['align'] = 'R';
				$aTotal[$i]['column'] = 'amount_provision';
				$i++;

			} else {

				$aTotal[$i]['orginal'] 	= $aAmountTotal2['brutto'];
				$aTotal[$i]['original'] 	= $aAmountTotal2['brutto'];
				$aTotal[$i]['text'] = '<b>' . Ext_Thebing_Format::Number($aAmountTotal2['brutto'], $iCurrencyId, $iSchoolId) . '</b>';
				$aTotal[$i]['align'] = 'R';
				$aTotal[$i]['column'] = 'amount';
				$i++;

			}

			// Steuern
			if($iTax > 0){
				$aTotal[$i]['text'] = '';
				$aTotal[$i]['column'] = 'amount_vat';
				$i++;
			}

			$aTableData2[] = $aTotal;
		}

		// 3. Tabelle Gesammt generell ////////////////////////////////////////////////////////////////////
		$aTableDataTotal = array();
		if(
			$iTax > 0 &&
			!empty($aGeneral) &&
			(
				in_array(Ext_Thebing_Inquiry_Document::PDF_VAT_LINES_SIMPLE, $aExclusive) ||
				in_array(Ext_Thebing_Inquiry_Document::PDF_VAT_LINES_EXTENDED, $aExclusive)
			)
		) {

			$aTableDataTotal[] = 'line';

			// Summen der Vorrort + Vor Anreise Tabellen Nur anzeigen wenn es 2 Tabellen gibt
			if(
				!empty($aTableData) &&
				!empty($aTableData2)
			) {

				$aTableDataTotal[] = end($aTableData);
				$aTableDataTotal[] = end($aTableData2);

				$aTableDataTotal[] = 'line';

				// Zwischensumme der beiden ogg. Tabellen

				$aTaxCol = array();
				$i = 0;

				if(in_array(Ext_Thebing_Inquiry_Document::PDF_LINEITEM_NUMBERING, $aExclusive)) {
					$aTaxCol[$i]['text'] = '';
					$i++;
				}
				
				if($bGroup){
					$aTaxCol[$i]['text'] = '';
					$i++;
				}

				$aTaxCol[$i]['text'] = \Ext_TC_Placeholder_Abstract::translateFrontend('Zwischensumme', $sLanguage);
				$i++;

				if($sType == 'net') {

					$aTaxCol[$i]['text'] = Ext_Thebing_Format::Number($aAmountTotal['brutto'] + $aAmountTotal2['brutto'], $iCurrencyId, $iSchoolId);
					$aTaxCol[$i]['align'] = 'R';
					$aTaxCol[$i]['column'] = 'amount';
					$i++;

					$aTaxCol[$i]['text'] = Ext_Thebing_Format::Number($aAmountTotal['provision'] + $aAmountTotal2['provision'], $iCurrencyId, $iSchoolId);
					$aTaxCol[$i]['align'] = 'R';
					$aTaxCol[$i]['column'] = 'amount_provision';
					$i++;

					$aTaxCol[$i]['text'] = '<b>' . Ext_Thebing_Format::Number($aAmountTotal['netto'] + $aAmountTotal2['netto'], $iCurrencyId, $iSchoolId) . '</b>';
					$aTaxCol[$i]['align'] = 'R';
					$aTaxCol[$i]['column'] = 'amount_net';
					$i++;

				} elseif($sType == 'net_creditnote') {
					
					$aTaxCol[$i]['text'] = Ext_Thebing_Format::Number($aAmountTotal['brutto'] + $aAmountTotal2['brutto'], $iCurrencyId, $iSchoolId);
					$aTaxCol[$i]['align'] = 'R';
					$aTaxCol[$i]['column'] = 'amount';
					$i++;

					$aTaxCol[$i]['text'] = Ext_Thebing_Format::Number($aAmountTotal['netto'] + $aAmountTotal2['netto'], $iCurrencyId, $iSchoolId);
					$aTaxCol[$i]['align'] = 'R';
					$aTaxCol[$i]['column'] = 'amount_net';
					$i++;

					$aTaxCol[$i]['text'] = '<b>' . Ext_Thebing_Format::Number($aAmountTotal['provision'] + $aAmountTotal2['provision'], $iCurrencyId, $iSchoolId). '</b>';
					$aTaxCol[$i]['align'] = 'R';
					$aTaxCol[$i]['column'] = 'amount_provision';
					$i++;

				} else {

					$aTaxCol[$i]['text'] = '<b>' . Ext_Thebing_Format::Number($aAmountTotal['brutto'] + $aAmountTotal2['brutto'], $iCurrencyId, $iSchoolId) . '</b>';
					$aTaxCol[$i]['align'] = 'R';
					$aTaxCol[$i]['column'] = 'amount';
					$i++;

				}

				$aTaxCol[$i]['text'] = '';
				$aTaxCol[$i]['align'] = 'R';
				$aTaxCol[$i]['column'] = 'amount_vat';
				$i++;

				$aTableDataTotal[] = $aTaxCol;

				$aTableDataTotal[] = 'line';

			}

			// Steuersätze in Prozent
			$aGeneralTaxAmounts = array('amount' => 0, 'amount_net' => 0, 'amount_prov' => 0);
			foreach($aGeneral as $fCurrentTax => $aGeneralTax) {
				
				$aTaxCol = array();
				$i = 0;
				$sPositionPrefix = '';
				$sPositionNote = '';

				
				if(in_array(Ext_Thebing_Inquiry_Document::PDF_LINEITEM_NUMBERING, $aExclusive)) {
					$aTaxCol[$i]['text'] = '';
					$i++;
				}

				if(in_array(Ext_Thebing_Inquiry_Document::PDF_VAT_LINES_EXTENDED, $aExclusive)) {
					
					if(!empty($aGeneralTax['lines'])) {
						if(count($aGeneralTax['lines']) > 1) {
							$sPositionPrefix = \Ext_TC_Placeholder_Abstract::translateFrontend('Positionen', $sLanguage).' '.implode(', ', $aGeneralTax['lines']).' ';
						} else {
							$sPositionPrefix = \Ext_TC_Placeholder_Abstract::translateFrontend('Position', $sLanguage).' '.implode(', ', $aGeneralTax['lines']).' ';
						}
					}

					if(!empty($aGeneralTax['note'])) {
						$sPositionNote .= ', '.$aGeneralTax['note'];
					}

				}

				if($bGroup == true){
					$aTaxCol[$i]['text'] = '';
					$i++;
				}
				
				if($sType == 'net') {
					
					// Bei Netto die Prozentsätze Nettobeträge angeben					
					$aTaxCol[$i]['text'] = $sPositionPrefix.$aGeneralTax['description'] . ' (' . Ext_Thebing_Format::Number($aGeneralTax['amount_net_vat'], $iCurrencyId, $iSchoolId).$sPositionNote.')';
					$i++;

					$aTaxCol[$i]['text'] = '';
					$aTaxCol[$i]['align'] = 'R';
					$aTaxCol[$i]['column'] = 'amount';
					$i++;

					$aTaxCol[$i]['text'] = '';
					
					$aTaxCol[$i]['align'] = 'R';
					$aTaxCol[$i]['column'] = 'amount_provision';
					$i++;

					$aTaxCol[$i]['text'] = Ext_Thebing_Format::Number($aGeneralTax['amountNet'], $iCurrencyId, $iSchoolId);
					$aTaxCol[$i]['align'] = 'R';
					$aTaxCol[$i]['column'] = 'amount_net';
					$aGeneralTaxAmounts['amount_net'] += $aGeneralTax['amountNet'];
					$i++;
	
				} elseif($sType == 'net_creditnote') {
					// Bei Netto die Prozentsätze Nettobeträge angeben

					
					$aTaxCol[$i]['text'] = $sPositionPrefix.$aGeneralTax['description'] . ' (' . Ext_Thebing_Format::Number($aGeneralTax['amount_commission_vat'], $iCurrencyId, $iSchoolId).$sPositionNote.')';
					$i++;

					$aTaxCol[$i]['text'] = '';
					$aTaxCol[$i]['align'] = 'R';
					$aTaxCol[$i]['column'] = 'amount';
					$i++;

					$aTaxCol[$i]['text'] = '';
					$aTaxCol[$i]['align'] = 'R';
					$aTaxCol[$i]['column'] = 'amount_net';
					$i++;

					$aTaxCol[$i]['text'] = Ext_Thebing_Format::Number($aGeneralTax['amountProv'], $iCurrencyId, $iSchoolId);
					$aTaxCol[$i]['align'] = 'R';
					$aTaxCol[$i]['column'] = 'amount_provision';
					$i++;
					$aGeneralTaxAmounts['amount_prov'] += $aGeneralTax['amountProv'];

				} else {

					
					$aTaxCol[$i]['text'] = $sPositionPrefix.$aGeneralTax['description'] . ' (' . Ext_Thebing_Format::Number($aGeneralTax['amount_vat'], $iCurrencyId, $iSchoolId).$sPositionNote.')';
					$i++;

					$aTaxCol[$i]['text'] = Ext_Thebing_Format::Number($aGeneralTax['amount'], $iCurrencyId, $iSchoolId);
					$aTaxCol[$i]['align'] = 'R';
					$aTaxCol[$i]['column'] = 'amount';
					$aGeneralTaxAmounts['amount'] += $aGeneralTax['amount'];
					$i++;

				}

				$aTaxCol[$i]['text'] = '';
				$aTaxCol[$i]['align'] = 'R';
				$aTaxCol[$i]['column'] = 'amount_vat';
				$i++;

				$aTableDataTotal[] = $aTaxCol;

			}

			if(end($aTableDataTotal) != 'line'){
				$aTableDataTotal[] = 'line';
			}

			// Gesamtzeile
			$aTaxCol = array();
			$i = 0;
			
			if(in_array(Ext_Thebing_Inquiry_Document::PDF_LINEITEM_NUMBERING, $aExclusive)) {
				$aTaxCol[$i]['text'] = '';
				$i++;
			}
			
			if($bGroup == true) {
				$aTaxCol[$i]['text'] = '';
				$i++;
			}
			$aTaxCol[$i]['text'] = \Ext_TC_Placeholder_Abstract::translateFrontend('Gesamtbetrag', $sLanguage);
			$i++;

			if($sType == 'net') {

				$aTaxCol[$i]['text'] = '';
				$aTaxCol[$i]['align'] = 'R';
				$aTaxCol[$i]['column'] = 'amount';
				$i++;

				$aTaxCol[$i]['text'] = '';
				$aTaxCol[$i]['align'] = 'R';
				$aTaxCol[$i]['column'] = 'amount_provision';
				$i++;

				// bei inklusivsteuern die Steuern nicht mehr extra hinzuaddieren
				if($iTax == 1) {
					//inklusive
					$fTotalAmount = $aAmountTotal['netto'] + $aAmountTotal2['netto'];
				} else {
					// exklusive
					$fTotalAmount = $aGeneralTaxAmounts['amount_net'] + $aAmountTotal['netto'] + $aAmountTotal2['netto'];
				}
				$aTaxCol[$i]['original'] = $fTotalAmount;
				$aTaxCol[$i]['text'] = '<b>' . Ext_Thebing_Format::Number($fTotalAmount, $iCurrencyId, $iSchoolId) . '</b>';
				$aTaxCol[$i]['align'] = 'R';
				$aTaxCol[$i]['column'] = 'amount_net';
				$i++;
				
			} elseif($sType == 'net_creditnote') {
				
				$aTaxCol[$i]['text'] = '';
				$aTaxCol[$i]['align'] = 'R';
				$aTaxCol[$i]['column'] = 'amount';
				$i++;

				$aTaxCol[$i]['text'] = '';
				$aTaxCol[$i]['align'] = 'R';
				$aTaxCol[$i]['column'] = 'amount_net';
				$i++;

				$fTotalAmountProv = $aAmountTotal['provision'] + $aAmountTotal2['provision'];
				if($iTax == 2) {
					// Bei Steuer exklusive die Steuer draufrechnen
					$fTotalAmountProv += $aGeneralTaxAmounts['amount_prov'];
				}

				$aTaxCol[$i]['original'] = $fTotalAmountProv;
				$aTaxCol[$i]['text'] = '<b>' . Ext_Thebing_Format::Number($fTotalAmountProv, $iCurrencyId, $iSchoolId) . '</b>';
				$aTaxCol[$i]['align'] = 'R';
				$aTaxCol[$i]['column'] = 'amount_provision';
				$i++;
				
			} else {

				// bei inklusivsteuern die Steuern nicht mehr extra hinzuaddieren
				if($iTax == 1) {
					//inklusive
					$fTotalAmountNet = $aAmountTotal['brutto'] + $aAmountTotal2['brutto'];
				}else{
					// exklusive
					$fTotalAmountNet = $aGeneralTaxAmounts['amount'] + $aAmountTotal['brutto'] + $aAmountTotal2['brutto'];
				}

				$aTaxCol[$i]['original'] = $fTotalAmountNet;
				$aTaxCol[$i]['text'] = '<b>' . Ext_Thebing_Format::Number($fTotalAmountNet, $iCurrencyId, $iSchoolId) . '</b>';
				$aTaxCol[$i]['align'] = 'R';
				$aTaxCol[$i]['column'] = 'amount';
				$i++;

			}

			$aTaxCol[$i]['text'] = '';
			$aTaxCol[$i]['align'] = 'R';
			$aTaxCol[$i]['column'] = 'amount_vat';
			$i++;

			$aTableDataTotal[] = $aTaxCol;

		}

		///////////////////////////////////////////////////////////////////////////////////////////////////

		// Daten zusammenführen
		$aTableData = array(
								'body'	=>	$aTableData,
								'type' 	=> 	'invoice_document'
							);

		$aTableData2 = array(
								'body'	=>	$aTableData2,
								'type' 	=> 	'invoice_document'
							);
		$aTableDataTotal = array(
								'body'	=>	$aTableDataTotal,
								'type' 	=> 	'invoice_document_without_header'
							);

		// Wenn Schuleinstellung: Bei creditnotes nur commissionsspalte
		if(
			$sType == 'net_creditnote' &&
			$oSchool->commission_column == 1 &&
			$oSchool->netto_column != 1	// Sicherheit damit sich Schuleinstellungen nicth überschneiden
		) {

			self::deletePositionsColumn('amount', $aTableData['body']);
			self::deletePositionsColumn('amount', $aTableData2['body']);
			self::deletePositionsColumn('amount', $aTableDataTotal['body']);
			
			self::deletePositionsColumn('amount_net', $aTableData['body']);
			self::deletePositionsColumn('amount_net', $aTableData2['body']);
			self::deletePositionsColumn('amount_net', $aTableDataTotal['body']);

		} elseif(
			$sType == 'net' &&
			$oSchool->netto_column == 1
		) {

			self::deletePositionsColumn('amount', $aTableData['body']);
			self::deletePositionsColumn('amount', $aTableData2['body']);
			self::deletePositionsColumn('amount', $aTableDataTotal['body']);
			
			self::deletePositionsColumn('amount_provision', $aTableData['body']);
			self::deletePositionsColumn('amount_provision', $aTableData2['body']);
			self::deletePositionsColumn('amount_provision', $aTableDataTotal['body']);

		}

		$aBack = array();

		// Wenn es nur eine Tabelle und keine weiteren Total-Zeilen gibt, dann Total-Zeile der ersten Tabelle entfernen
		if(
			!empty($aTableData['body']) && 
			empty($aTableData2['body']) && 
			count($aTableDataTotal['body']) === 2
		) {
			$aTableData['body'] = array_slice($aTableData['body'], 0, -2);
		}

		$aBack[] = $aTableData;
		$aBack[] = $aTableData2;
		$aBack[] = $aTableDataTotal;

		$aHookData = ['document' => $oDocument, 'version' => $this, 'group' => $bGroup, 'data' => &$aBack];
		System::wd()->executeHook('ts_inquiry_document_get_pdf_items_return', $aHookData);

		return $aBack;

	}

	/**
	 * Löscht Spalten aus dem Tabellen Array
	 * @param type $sColumn
	 * @param array $aTable 
	 */
	public static function deletePositionsColumn($sColumn, array &$aTable, $bResetIndexes=false) {
		
		foreach($aTable as $iRowKey=>$aRow) {

			if(
				is_array($aRow)
			)
			{
			foreach($aRow as $iColumnKey=>$aColumn) {
				if($aColumn['column'] == $sColumn) {
					unset($aTable[$iRowKey][$iColumnKey]);
				}
			}

			if($bResetIndexes) {
				$aTable[$iRowKey] = array_values($aTable[$iRowKey]);
			}
		}
		}
		
	}

	/**
	 * @param bool $bUseCache
	 * @return Ext_Thebing_Inquiry_Document
	 */
	public function getDocument($bUseCache = true) {

		if($bUseCache) {
			return $this->getJoinedObject('document');
			//return Ext_Thebing_Inquiry_Document::getInstance($this->document_id);
		}

		/*
		 * TODO Entfernen
		 * Wenn man das auf getInstance umstellt, klappt komischerweise getHistoryHTML nicht mehr :-(
		 */
		return new Ext_Thebing_Inquiry_Document($this->document_id);

	}

	/**
	 * {@inheritdoc}
	 */
	public function save($bLog = true) {
		global $user_data;

		$this->user_id = $user_data['id'];
		
		// Defauleinstellung für App-Freigabe setzen, falls im Template definiert
		// TODO Irgendeine Lösung hierfür finden, da bei Nummergenerierung in save() + Transaktion die Lock-Exception geschmissen wird
		if(
			$this->id == 0 &&
			$this->version == 1
		) {
			$oTemplate = $this->getTemplate();
			if($oTemplate->app_release == 1) {
				$oDocument = $this->getDocument();
				if(
					$oDocument->id > 0 &&
					$oDocument->released_student_login != 1
				) {
					$oDocument->released_student_login = 1;
					$oDocument->save();
				}
			}
		}
		
		$mSave = parent::save();

		// Letzte Version speichern
		$oDocument = $this->getDocument();
		$oDocument->saveLatestVersion();
		
		$this->updateUsedSpecialCodes();
		
		$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
		$oStackRepository->writeToStack('ts/post-document-save', ['version_id' => $this->id], 1);
		
		return $mSave;
	}

	protected function updateUsedSpecialCodes() {

		// Eventuell verwendete Gutscheincodes sperren
		$specialCodeIds = [];
		foreach($this->getJoinedObjectChilds('items') as $item) {
			$additionalInfo = $item->additional_info;

			if(
				!empty($additionalInfo['special']) &&
				!empty($additionalInfo['special']['code_ids'])
			) {
				$specialCodeIds = array_merge($specialCodeIds, $additionalInfo['special']['code_ids']);
			}
		}

		if(!empty($specialCodeIds)) {
			foreach($specialCodeIds as $specialCodeId) {
				$specialCode = Ts\Entity\Special\Code::getInstance($specialCodeId);
				// Code wurde gelöscht
				if($specialCode->exist()) {
					$specialCode->saveUsage($this->getInquiry());
				}
			}
		}
		
	}


	/**
	 * @inheritdoc
	 */
	public function delete() {

		$bSuccess = parent::delete();

		if(
			$bSuccess &&
			$this->bPurgeDelete
		) {
			// Da active = 0 im Dialog angezeigt wird, darf das nicht einfach so gelöscht werden
			$sPath = $this->getPath(true);
			if(is_file($sPath)) {
				@unlink($sPath);
			}
		}

		return $bSuccess;

	}

	/**
	 * Gibt das Objekt des zugehörigen Templates zurück
	 * @return Ext_Thebing_Pdf_Template
	 */
	public function getTemplate() {
		$oTemplate = Ext_Thebing_Pdf_Template::getInstance($this->template_id);
		return $oTemplate;
	}

	/**
	 * Erzeugt einen Titel für diese Version
	 * @return string
	 */
	public function getLabel() {

		$sLabel = '';

		$oDocument = $this->getDocument();

		if($oDocument->document_number) {
			$sLabel .= $oDocument->document_number.' - ';
		}

		// Wenn Sonstige Dokumente
		if(
			$oDocument->type == 'additional_document' ||
			$oDocument->type == 'examination'
		) {
			$oTemplate = $this->getTemplate();
			$sLabel .= $oTemplate->name;
		} else {
			$sLabel .= $oDocument->getLabel();
		}

		$oInquiry = $oDocument->getEntity();
		$oSchool	= $oInquiry->getSchool();
		$iSchool	= $oSchool->id;

		// #4976
		// Bei Rechnungen und ähnlichen Dokumenten den Betrag anhängen		
		$oDocumentSearch = new Ext_Thebing_Inquiry_Document_Type_Search();
		$aInvoiceTypes = $oDocumentSearch->getSectionTypes('invoice_creditnote_manual_creditnote_offer');
		if(in_array($oDocument->type, $aInvoiceTypes)) {

			if($oInquiry === null || !$oInquiry->hasGroup()) {
				$fAmount = $oDocument->getAmount();
			} else {
				$fAmount = $oDocument->getGroupAmount();
			}

			$iCurrency = (int)$oDocument->getCurrencyId();
			$sLabel .= ' '.Ext_Thebing_Format::Number($fAmount, $iCurrency, $oSchool->id);

		} elseif(
			$oDocument->type == 'receipt_customer' ||
			$oDocument->type == 'receipt_agency'
		) {
			$fAmount 	= $oDocument->getFormatetPaymentAmount();
			$sLabel 	.= ' '.$fAmount;
		}
		
		// Seltsamerweise ist es hier passiert das eine falsche id übergeben wurde!
		$bCheck = Ext_Thebing_Client::checkSchool($iSchool);
		if(!$bCheck){
			$oSchool = Ext_Thebing_Client::getFirstSchool();
			$iSchool = $oSchool->id;
		}

		$sLabel .= ': '.Ext_Thebing_Format::LocalDateTime($this->created, $iSchool);

		if($oDocument->type == 'examination') {
			$oExamination = Ext_Thebing_Examination::getExaminiationByDocumentId($oDocument);
			$oExaminationVersion = $oExamination->getLastVersion();

			$sLabel .= ' ('.Ext_Thebing_Format::LocalDate($oExaminationVersion->examination_date, $iSchool).')';
		}

		// Bei Bezahlbelegen den Templatenamen anhängen
		// #5031
		if(
			$oDocument->type == 'receipt_customer' ||
			$oDocument->type == 'receipt_agency'
		) {
			$oTemplate = $this->getTemplate();
			$sLabel .= ' - '. $oTemplate->getName();
		}
		
		return $sLabel;

	}

	public function getPath($bFullPath=false) {

		if (empty($this->path)) {
			return '';
		}

		$sPath = '';

		if($bFullPath) {
			$sPath .= \Util::getDocumentRoot().'storage';
		}

		$sPath .= $this->path;

		return $sPath;

	}

	// Liefert ein neues Versionsfield object zurück
	// Für editierbare Layout Felder
	public function getNewLayoutField(){
		if($this->id > 0){
			$oNewField = new Ext_Thebing_Inquiry_Document_Version_Field(0);
			$oNewField->version_id = (int)$this->id;
		}else{
			$oNewField = NULL;
		}
		return $oNewField;
	}

	// Liefert alle gespeicherten editierbaren Layoutfelder
	public function getLayoutFields(){
		return Ext_Thebing_Util::convertDataIntoObject($this->layout_fields, 'Ext_Thebing_Inquiry_Document_Version_Field');
	}

	/*
	 * Validate Funktion für Versionen
	 */
	public function validate($bThrowExceptions = false) {

		$aErrors = parent::validate($bThrowExceptions);

		// TODO Das muss korrekt in der WDBasic eingebaut werden, dass kein validate() beim Löschen stattfindet
		// Gelöschte Versionen nicht weiter prüfen
		if($this->active == 0) {
			return $aErrors;
		}

		$oDocument = $this->getDocument();

		if(
			$aErrors === true &&
			in_array($oDocument->type, Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_with_creditnote'))
		) {

			$aErrorsNew = [];
			$dLastDate = null;
			$dVersionDate = new DateTime($this->date);
			$aPaymentTerms = $this->getPaymentTerms();
			$iFinalRows = 0;

			foreach($aPaymentTerms as $iKey => $oPaymentTerm) {

				if($oPaymentTerm->type === 'final') {
					$iFinalRows++;
				}

				// Datum vorhanden?
				if(empty($oPaymentTerm->date)) {
					$aErrorsNew['paymentterms.date.'.$iKey] = 'VERSION_PAYMENTTERM_DATE_EMPTY';
					continue;
				}

				// Datum gültig?
				if(!Core\Helper\DateTime::isDate($oPaymentTerm->date, 'Y-m-d')) {
					$aErrorsNew['paymentterms.date.'.$iKey] = 'VERSION_PAYMENTTERM_DATE_FORMAT';
					continue;
				}

				$dDate = new DateTime($oPaymentTerm->date);

				// Datum kleiner als Versionsdatum
				if(
					Core\Helper\DateTime::isDate($this->date, 'Y-m-d') &&
					$dDate < $dVersionDate
				) {
					$aErrorsNew['paymentterms.date.'.$iKey] = 'VERSION_PAYMENTTERM_DATE_BEFORE_VERSION_DATE';
					continue;
				}

				// Chronologische Reihenfolge
				if(
					$dLastDate !== null &&
					$dLastDate > $dDate
				) {
					$aErrorsNew['paymentterms.date.'.($iKey + 1)] = 'VERSION_PAYMENTTERM_DATE_CHRONOLOGICAL';
					continue;
				}

				// Betrag >= 0
				if(abs(round($oPaymentTerm->amount, 2)) < 0) {
					$aErrorsNew['paymentterms.date.'.$iKey] = 'VERSION_PAYMENTTERM_AMOUNT_NEGATIVE';
					continue;
				}

				$dLastDate = $dDate;

			}

			if(
				$this->active &&
				$iFinalRows !== 1
			) {
				throw new RuntimeException('No or more than one final payment row! Count: '.$iFinalRows);
			}

			if(!empty($aErrorsNew)) {
				$aErrors = $aErrorsNew;
			}

		}

		$aErrors1	= array();

		// Betrag darf nicht 0 sein
		$inquiry = $oDocument->getInquiry();
		if ($inquiry) {
			$school	= $inquiry->getSchool();
			if (
				$this->active &&
				$school &&
				($school->invoice_amount_null_forbidden ?? 0) == 1 &&
				in_array($oDocument->type, Ext_Thebing_Inquiry_Document_Search::getTypeData(['invoice_with_creditnotes_and_without_proforma', 'offer']))
			) {
				$paymentTerms = $this->getPaymentTerms();
				$finalAmount = 0;
				foreach($paymentTerms as $paymentTerm) {
					if ($paymentTerm->type === 'final') {
						$finalAmount = $paymentTerm->amount;
					}
				}
				if (
					!$inquiry->hasGroup() &&
					(float)$finalAmount == 0
				) {
					$aErrors1[''][] = 'VERSION_AMOUNT_ZERO_FORBIDDEN';
				}
			}
			// Es darf nur einen Entwurf geben. Wenn es für eine Firma ist,
			// dann kann es einen pro Firma geben.
			if (
				$inquiry instanceof Ext_Ts_Inquiry &&
				$oDocument->shouldCreateAsDraft() &&
				$inquiry->hasDraft(
					[
						$oDocument->id
					],
					$this->company_id ? \TsAccounting\Entity\Company::getInstance($this->company_id) : null,
					$oDocument->is_credit
				)
			) {
				$aErrors1[''][] = 'ONLY_ONE_DRAFT';
			}
		}

		if (
			!$oDocument->isMutable() &&
			$this->isNew() &&
			$this->version !== 1
		) {
			$aErrors1[''][] = 'INVOICE_IS_IMMUTABLE';
		}

		// Documenttypen die ein Restzahlungsdatum benötigen
		// Ein Anzahlungsdatum ebenfalls falls ein Betrag eingegeben wurde
		$aTypes = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_with_creditnote');

		// Versionen die zu Dokumenten mit Nummern gehören brauch immer ein Datum
		if(
			in_array($oDocument->type, $aTypes) && 
			!WDDate::isDate($this->date, WDDate::DB_DATE)
		){
			$aErrors1['date'][] = 'WRONG_VERSION_DATE';
		}

		if(empty($aErrors1) && $aErrors === true) {
			return true;
		}elseif(is_array($aErrors)){
			return array_merge($aErrors, $aErrors1);
		}elseif(!empty($aErrors1)){
			return $aErrors1;
		}

		return $aErrors;

	}

	/**
	 * Muss nach validate() passieren wegen nicht gespeicherten Items und soll auch nicht generell passieren
	 *
	 * @see handlePaymentTermsRoundingDifference
	 * @return array
	 */
	public function validatePaymentTermsAmount() {

		$aErrors = array();
		$oDocument = $this->getDocument();
		$aTypes = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_with_creditnote');

		if(!in_array($oDocument->type, $aTypes)) {
			return $aErrors;
		}

		$fTotalAmount = 0;
		$fPrepayAmount = 0;
		$fFinalAmount = 0;

		$aPaymentTerms = $this->getPaymentTerms();

		if(!empty($aPaymentTerms)) {
			$fTotalAmount = (float)$this->getAmount();
		}

		foreach($aPaymentTerms as $oPaymentTerm) {
			if($oPaymentTerm->type !== 'final') {
				$fFinalAmount += (float)$oPaymentTerm->amount;
			} else {
				$fPrepayAmount += (float)$oPaymentTerm->amount;
			}
		}

		$fTotalAmount = abs(round($fTotalAmount, 2));

		if($fTotalAmount < abs(round($fPrepayAmount, 2))) {
			$aErrors['paymentterms.amount.'.(count($aPaymentTerms) - 1)] = 'VERSION_PAYMENTTERM_AMOUNT_EQUAL_OR_HIGHER_THAN_TOTAL';
		}

		if($fTotalAmount != abs(round($fPrepayAmount + $fFinalAmount, 2))) {
			$aErrors['paymentterms.amount.'.(count($aPaymentTerms) - 1)] = 'VERSION_PAYMENTTERM_AMOUNT_NOT_EQUAL_TO_TOTAL';
		}

		return $aErrors;

	}

	/**
	 * Rundungsdifferenz nach validatePaymentTermsAmount() korrigieren (kann bei Ratenzahlungen auftreten)
	 *
	 * Vor der Umstellung war das kein Problem, da der Restzahlungsbetrag einfach nicht gespeichert wurde.
	 * Es ist allerdings praktischer, den Restzahlungsbetrag doch im Term zu speichern, da dieser Betrag
	 * an mehreren Stellen verwendet wird.
	 *
	 * @see validatePaymentTermsAmount()
	 */
	public function handlePaymentTermsRoundingDifference() {

		$fAmount = $this->getAmount();
		foreach($this->getPaymentTerms() as $oPaymentTerm) {
			if($oPaymentTerm->type !== 'final') {
				$fAmount -= $oPaymentTerm->amount;
			} else {
				$oPaymentTerm->amount = $fAmount;
				// Version wird eigentlich nochmal von createPdf() gespeichert
				$oPaymentTerm->save();
			}
		}

	}

	private function getAdditionalParentCombination($aItems){
		$aCombination = array();
		
		foreach((array)$aItems as $aItemTemp){
			if(
				$aItemTemp['type'] == 'additional_course' ||
				$aItemTemp['type'] == 'additional_accommodation'
			){
				$aCombination[$aItemTemp['type']][] = (int)$aItemTemp['parent_booking_id'];
				$aCombination[$aItemTemp['type']] = array_unique($aCombination[$aItemTemp['type']]);
			}
		}
			
		
		return $aCombination;
	}

	public function canShowInquiryPositions($oTemplate=false,$oDocument=false){

		if(!$oTemplate){
			$oTemplate = $this->getTemplate();
		}

		$iInquiryPositionsView = (int)$oTemplate->canShowInquiryPositions();

		if(!$oDocument){
			$oDocument	= $this->getDocument();
		}

		$oInquiry	= $this->getInquiry($oDocument);
		$sType		= $oDocument->type;

		$aInvoiceTypes = (array)Ext_Thebing_Inquiry_Document_Search::getTypeData(['invoice', 'offer']);

		// Zusatzdokumente
		if(
			$oInquiry && 
			!in_array($sType, $aInvoiceTypes)
		) {
			$aInvoiceTypes[] = 'offer'; // Explizit wieder offer für if in Methode
			$aDocuments = $oInquiry->getDocuments($aInvoiceTypes);

			if(empty($aDocuments)){
				$iInquiryPositionsView = 0;
			}
		}else if(!$oInquiry){
			$iInquiryPositionsView = 0;
		}

		return $iInquiryPositionsView;
	}

	/**
	 * Funktion erstellt die Zusatzkosten für Kurse
	 *
	 * @param Ext_TS_Inquiry_Journey_Course $oInquiryCourse
	 * @param Tc\Service\LanguageAbstract $oLanguage
	 * @param Ext_Thebing_Inquiry_Amount $oAmount
	 * @return mixed[]
	 * @throws Exception
	 */
	public function buildAdditionalCourseCostItems(
		array &$aAdditionalCourseData,
		Ext_TS_Service_Interface_Course $oInquiryCourse, Tc\Service\LanguageAbstract $oLanguage, Ext_Thebing_Inquiry_Amount $oAmount = null
	) {

		$oCourse = $oInquiryCourse->getCourse();
		$aCourseCosts = $oInquiryCourse->getAdditionalCosts();
		$oInquiry = $oInquiryCourse->getInquiry();
		$oAgency = $oInquiry->getAgency();
		$oSchool = $oInquiry->getSchool();
		$oDocument = $this->getDocument();

		if(!($oAmount instanceof Ext_Thebing_Inquiry_Amount)) {
			$oAmount = new Ext_Thebing_Inquiry_Amount($oInquiry);
		}

		$bPaymentMethodLocal = $oInquiry->getPaymentMethodLocal();

		$oDate = new WDDate();
		$oDate->set($oInquiryCourse->from, WDDate::DB_DATE);
		$iFrom = (int)$oDate->get(WDDate::TIMESTAMP);
		$oDate->set($oInquiryCourse->until, WDDate::DB_DATE);
		$iUntil = (int)$oDate->get(WDDate::TIMESTAMP);

		$dFrom = new Carbon($iFrom);
		$dUntil = new Carbon($iUntil);
		
		$iWeeks = (int)$oInquiryCourse->weeks;
		$iUnits = (int)$oInquiryCourse->units;
		$sType = 'additional_course';

		if($oCourse->getType() === 'week') {
			// Wochenkurse
			$oAmount->setTimeData($iFrom, $iUntil, $iWeeks, 0);
		} else {
			// Lektionskurs
			$oAmount->setTimeData($iFrom, $iUntil, 0, $iUnits);
		}

		// Anzahl der Kurse insgesamt
		if(
			$oInquiry->hasGroup() &&
//			$oInquiry->getKey() == 'inquiry'
			$oInquiry->type & Ext_TS_Inquiry::TYPE_BOOKING
		) {
			$oGroup = $oInquiry->getGroup();
			$aGroupCourses = $oGroup->getCourses();
			$iCourseCount = count($aGroupCourses);
		} else {
			$aCourses = $oInquiry->getCourses();
			$iCourseCount = count($aCourses);
		}

		foreach($aCourseCosts as $oAdditionalCost) {

			$aInquiryCourseIds = [(int)$oInquiryCourse->id];
			
			$aAdditionalCourseItem = [];
			
            if(
				!$oAdditionalCost->checkWeeksDependency($iWeeks) ||
				!$oAdditionalCost->checkAgeDependency($oInquiry)
			) {
                continue;
            }
			
			if($oAdditionalCost->limited_availability) {

				$validity = Ts\Entity\Additionalcost\Validity::getValidEntry($oAdditionalCost, $dFrom, $dUntil);
				if($validity === null) {
					continue;
				}
				
				// valid_until kann leer sein
				if(
					!empty($validity->valid_until) && 
					$validity->valid_until !== '0000-00-00'
				) {
					$validityUntil = new Carbon($validity->valid_until);
				} else {
					$validityUntil = Carbon::now()->addYears(10);
				}

				$overlap = Core\Helper\DateTime::getDateRangeIntersection($dFrom, $dUntil, new Carbon($validity->valid_from), $validityUntil);

				$dCurrentFrom = $overlap['start'];
				$dCurrentUntil = $overlap['end'];

			} else {
				$dCurrentFrom = clone $dFrom;
				$dCurrentUntil = clone $dUntil;
			}
			
			$iFrom = $dCurrentFrom->getTimestamp();
			$iUntil = $dCurrentUntil->getTimestamp();

			$iWeeks = ceil($dCurrentUntil->diff($dCurrentFrom)->days / 7);
			
			$sTmpItemKey = Ext_Thebing_Util::generateRandomString(16);

			$iAmount = 0;
			$iAmountNet = 0;
			$iAmountProv = 0;

			// Alle Pro Kurs Kosten für diesen Kurs
			if(!isset(self::$aAdditionalPerCourse[$oAdditionalCost->id])) {
				self::$aAdditionalPerCourse[$oAdditionalCost->id] = new SplObjectStorage();
			}
			self::$aAdditionalPerCourse[$oAdditionalCost->id]->attach($oInquiryCourse);

			// Wenn es "pro Kurs" Kosten sind, die Kurse aber zusammengehören z.B. Schulferien, Schülerferien
			// dann sollen die Kosten nur einmal berechnet werden
			$bContinue = false;
			if((int)$oAdditionalCost->calculate == 1) {
				if(self::$aAdditionalPerCourse[$oAdditionalCost->id]->count() > 1) {
					// Mehrere Kurse haben diese "pro Kurs" Gebüren, prüfen ob Kurse zusammengehören
					$aRelatedCourses = [];
					if($oInquiryCourse instanceof Ext_TS_Inquiry_Journey_Course) {
						// Es gibt keine Kursstruktur/Ferien bei Anfragen
						$aRelatedCourses = $oInquiryCourse->getRelatedServices();
					}
					foreach(self::$aAdditionalPerCourse[$oAdditionalCost->id] as $oTmpJourneyCourse) {
						if(
							$oInquiryCourse !== $oTmpJourneyCourse &&
							in_array($oTmpJourneyCourse, $aRelatedCourses, true)
						) {
							// Diese Gebür wurde bereits bei einem Kurs verbucht und kann übersprungen werden
							$bContinue = true;
							break;
						}
					}
				}
			}

			if($bContinue) {
				continue;
			}

			// Betrag errechen
			if((int)$oAdditionalCost->calculate == 2) {
				// Wochenweise berechnung der Kosten
				for($iWeekcount = 0; $iWeekcount < $iWeeks; $iWeekcount++) {

					$oDate = new WDDate($iFrom);
					$oDate->add($iWeekcount * 7, WDDate::DAY);
					$iFromTemp = $oDate->get(WDDate::TIMESTAMP);

					// Sicherheitsabfrage um nicht zu viel zu zählen
					if($iFromTemp > $iUntil){
						$iFromTemp = $iUntil;
					}

					// Für jede Woche den Preis berechnen
					$oAmount->setTimeData($iFromTemp, $iUntil, $iWeeks);
					$iAmount += $oAmount->calculateAdditionalCost($oAdditionalCost, false, 'course', $oCourse->id);
					$iAmountNet += $oAmount->calculateAdditionalCost($oAdditionalCost, true, 'course', $oCourse->id);

				}
			} else {
				$iAmount = $oAmount->calculateAdditionalCost($oAdditionalCost, false, 'course', $oCourse->id);
				$iAmountNet = $oAmount->calculateAdditionalCost($oAdditionalCost, true, 'course', $oCourse->id);
			}
			$iAmountProv = $iAmount - $iAmountNet;

			// Einmalige Kurskosten
			if((int)$oAdditionalCost->calculate == 0) {
				if(array_key_exists($oAdditionalCost->id, self::$aAdditionalCoursesSingle)) {
					// auf teuersten Betrag prüfen
					if(self::$aAdditionalCoursesSingle[$oAdditionalCost->id] < $iAmount) {
						// alten billigen kurs löschen
						foreach($aAdditionalCourseData as $iKey => $aData) {
							if($aData['id'] == $oAdditionalCost->id) {
								
								// Bei einmaligen Kurskosten brauche ich für die Kostenstelle alle InquiryCoures-IDs, auf die sich die Zusatzgebühr bezieht
								$aInquiryCourseIds = array_merge($aInquiryCourseIds, $aAdditionalCourseData[$iKey]['additional_info']['inquiry_journey_course_ids']);
								
								unset($aAdditionalCourseData[$iKey]);
								break;
							}
						}
					} else {
						// Bei einmaligen Kurskosten brauche ich für die Kostenstelle alle InquiryCoures-IDs, auf die sich die Zusatzgebühr bezieht
						foreach($aAdditionalCourseData as $iKey => $aData) {
							if($aData['id'] == $oAdditionalCost->id) {
								$aAdditionalCourseData[$iKey]['additional_info']['inquiry_journey_course_ids'][] = (int)$oInquiryCourse->id;
							}
						}
						continue;
					}
				} else {
					self::$aAdditionalCoursesSingle[$oAdditionalCost->id] = (float)$iAmount;
				}
			}

			// Gruppen Guide checken und Amount löschen
			if(
				$oInquiry->hasGroup() &&
				$oInquiry->isGuide() &&
				(
					$oInquiry->getJourneyTravellerOption('free_course_fee') ||
					$oInquiry->getJourneyTravellerOption('free_all')
				)
			) {
				$iAmount = $iAmountNet = $iAmountProv = 0;
			}

            if(
               $iAmount == 0 &&
               !$oAdditionalCost->showZeroAmount()
            ) {
                continue;
            }

			// Auf Special prüfen und berechnen
			$oSpecialAmount = new Ext_Thebing_Inquiry_Special_Amount($iAmount, $oAdditionalCost);
			$oSpecialAmount->setCalculationTime($iFrom);
			$oSpecialAmount->setInquiry($oInquiry);
			
			$oSpecialAmountNet = new Ext_Thebing_Inquiry_Special_Amount($iAmountNet, $oAdditionalCost);
			$oSpecialAmountNet->setCalculationTime($iFrom);
			$oSpecialAmountNet->setInquiry($oInquiry);
			
			// Hash erstellen damit nach dem speichern eine Zusammengehörichkeit ermittelt werden kann.
			#$sSpecialHash = md5($sType.$oAdditionalCost->id.$oCourse->id.$oInquiryCourse->id);

			$aSpecialAdditionalInfo = array(
				'parent_item_key' => $sTmpItemKey,
				'type' => $sType,
				'type_id' => (int)$oAdditionalCost->id,
				'additional_cost_id' => (int)$oAdditionalCost->id,
				'course_id' => $oCourse->id,
				'inquiry_journey_course_id' => (int)$oInquiryCourse->id,
			);

			// Transfer-special manuell setzen
			$oAmount->setSpecialAmount($sType, $oAdditionalCost->id, $oAdditionalCost, $oSpecialAmount, false, $aSpecialAdditionalInfo);
			$oAmount->setSpecialAmount($sType, $oAdditionalCost->id, $oAdditionalCost, $oSpecialAmountNet, true, $aSpecialAdditionalInfo);
			
			$sCostName = $oInquiryCourse->getAdditionalCostInfo($oAdditionalCost->id, $iWeeks, $iCourseCount, $oLanguage);

			$aAdditionalCourseItem['item_key'] = $sTmpItemKey;
			$aAdditionalCourseItem['description'] = $sCostName;
			$aAdditionalCourseItem['amount'] = $iAmount;
			$aAdditionalCourseItem['amount_net'] = $iAmountNet;
			$aAdditionalCourseItem['amount_provision'] = $iAmountProv;
			$aAdditionalCourseItem['amount_discount'] = 0;
			$aAdditionalCourseItem['id'] = (int)$oAdditionalCost->id;
			$aAdditionalCourseItem['type'] = $sType;
			$aAdditionalCourseItem['type_id'] = (int)$oAdditionalCost->id;
			$aAdditionalCourseItem['parent_id'] = (int)$oCourse->id;
			$aAdditionalCourseItem['parent_booking_id'] = (int)$oInquiryCourse->id;
			$aAdditionalCourseItem['type_object_id'] = 0;
			$aAdditionalCourseItem['type_parent_object_id'] = (int)$oInquiryCourse->course_id;
			$aAdditionalCourseItem['from'] = $oInquiryCourse->from;
			$aAdditionalCourseItem['until'] = $oInquiryCourse->until;
			$aAdditionalCourseItem['index_from'] = $aAdditionalCourseItem['from'];
			$aAdditionalCourseItem['index_until'] = $aAdditionalCourseItem['until'];

			// Wenn "Vorort"
			if(
				$bPaymentMethodLocal ||
				// Wenn bei der Buchung Vorort eingestellt ist
				// und bei der Agentur die Kosten nicht sezial als Vorort definiert sidn
				(
					!$bPaymentMethodLocal &&
					is_object($oAgency) &&
					$oAgency->checkInitalCost($oAdditionalCost->id)
				)
			) {
				$aAdditionalCourseItem['initalcost'] = 1;
			} else{
				$aAdditionalCourseItem['initalcost'] = 0;
			}

			$aAdditionalCourseItem['onPdf'] = 1;
			$aAdditionalCourseItem['calculate'] = 1;
			$aAdditionalCourseItem['cost_calculate_setting'] = (int)$oAdditionalCost->calculate;
			
			$dVatDate = Ext_TS_Vat::getVATReferenceDateByService($oSchool, $oInquiryCourse);
			$aAdditionalCourseItem['tax_category'] = Ext_TS_Vat::getDefaultCombination('Ext_Thebing_School_Cost', $oAdditionalCost->id, $oSchool, $oInquiry, $dVatDate, $oDocument->type);
			//$aAdditionalCourseItem['orginal_course'] = Ext_Thebing_Inquiry_Holidays::getOriginalCourseId($oInquiryCourse->id);
			$aAdditionalCourseItem['additional_info'] = array(
				'item_key' => $sTmpItemKey,
				'weeks' => $iWeeks,
				'count_courses' => $iCourseCount,
				'billing_type' => $oAdditionalCost->calculate == Ext_Thebing_School_Additionalcost::CALCULATION_PER_WEEK ? 'week' : 'once',
				'billing_units' => $oAdditionalCost->calculate == Ext_Thebing_School_Additionalcost::CALCULATION_PER_WEEK ? $iWeeks : 1,
				'inquiry_journey_course_ids' => $aInquiryCourseIds
			);

			$aAdditionalCourseData[] = $aAdditionalCourseItem;

		}

	}

	/**
	 * Funktion erstellt die Zusatzkosten für Unterkünfte
	 *
	 * @param Ext_TS_Inquiry_Journey_Accommodation $oInquiryAccommodation
	 * @param \Tc\Service\LanguageAbstract $oLanguage
	 * @param Ext_Thebing_Inquiry_Amount $oAmount
	 * @return array
	 * @throws Exception
	 */
	public function buildAdditionalAccommodationCostItems(
		array &$aAdditionalAccommodationData,
		$oInquiryAccommodation, 
		Tc\Service\LanguageAbstract $oLanguage, &$oAmount, Ext_Thebing_Inquiry_Document $oDocument
	) {

		$oCategory						= $oInquiryAccommodation->getCategory();
		
		$aAccommodationCosts			= $oInquiryAccommodation->getAdditionalCosts();
		
		$oInquiry						= $this->getInquiry($oDocument);

		if(!$oInquiry instanceof Ext_TS_Inquiry_Abstract) {
			throw new Exception('Inquiry object missing!');
		}

		$oAgency						= $oInquiry->getAgency();

		$oSchool						= $oInquiry->getSchool();

		if(!($oAmount instanceof Ext_Thebing_Inquiry_Amount)){
			$oAmount					= new Ext_Thebing_Inquiry_Amount($oInquiry);
		}

		$bPaymentMethodLocal			= $oInquiry->getPaymentMethodLocal();
		
		$oDate							= new WDDate();

		$oDate->set($oInquiryAccommodation->from, WDDate::DB_DATE);
		$iFrom							= (int) $oDate->get(WDDate::TIMESTAMP);
			
		$oDate->set($oInquiryAccommodation->until, WDDate::DB_DATE);
		$iUntil							= (int) $oDate->get(WDDate::TIMESTAMP);

		$dFrom = new DateTime($oInquiryAccommodation->from);
		$dUntil = new DateTime($oInquiryAccommodation->until);
		$iWeeks = (int) $oInquiryAccommodation->weeks;
		
		$sType = 'additional_accommodation';
		
		$oAmount->setTimeData($iFrom, $iUntil, $iWeeks);
		
		// Anzahl der Unterkünfte insgesamt
		if(
			$oInquiry->hasGroup() &&
//			$oInquiry->getKey() == 'inquiry'
			$oInquiry->type & Ext_TS_Inquiry::TYPE_BOOKING
		) {
			$oGroup = $oInquiry->getGroup();
			$aGroupAccommodations = $oGroup->getAccommodations();
			$iAccommodationCount = count($aGroupAccommodations);
		} else {
			$aAccommodations = $oInquiry->getAccommodations();
			$iAccommodationCount = count($aAccommodations);
		}
		
		foreach($aAccommodationCosts as $oAdditionalCost) {

			$aInquiryAccommodationIds = [(int)$oInquiryAccommodation->id];
			
			$aAdditionalAccommodationItem = [];
			
            if(
				!$oAdditionalCost->checkWeeksDependency($iWeeks) ||
				!$oAdditionalCost->checkAgeDependency($oInquiry)
			) {
                continue;
            }

			// Alle pro Unterkunft Kosten für diese Unterkunft
			if(!isset(self::$aAdditionalPerAccommodation[$oAdditionalCost->id])) {
				self::$aAdditionalPerAccommodation[$oAdditionalCost->id] = new SplObjectStorage();
			}
			self::$aAdditionalPerAccommodation[$oAdditionalCost->id]->attach($oInquiryAccommodation);

			// Wenn es "pro Unterkunft" Kosten sind, die Unterkünfte aber zusammengehören z.B. Schülerferien
			// dann sollen die Kosten nur einmal berechnet werden
			$bContinue = false;
			if((int)$oAdditionalCost->calculate == 1){
				if(count(self::$aAdditionalPerAccommodation[$oAdditionalCost->id]) > 1){
					// Mehrere Unterkünfte haben diese "pro Unterkunft" Gebüren, prüfen ob Kurse zusammengehören
					$aRelatedAccommodations = [];
					if($oInquiryAccommodation instanceof Ext_TS_Inquiry_Journey_Accommodation) {
						$aRelatedAccommodations = $oInquiryAccommodation->getRelatedServices();
					} 
					foreach(self::$aAdditionalPerAccommodation[$oAdditionalCost->id] as $oTmpJourneyAccommodation) {
						if(
							$oTmpJourneyAccommodation !== $oInquiryAccommodation &&
							in_array($oTmpJourneyAccommodation, $aRelatedAccommodations, true)
						){
							// Diese Gebür wurde bereits bei einer Unterkunft verbucht und kann übersprungen werden
							$bContinue = true;
							break;
						}
					}
				}
			}

			if($bContinue){
				continue;
			}

			$sTmpItemKey = Ext_Thebing_Util::generateRandomString(16);

			if($oAdditionalCost->limited_availability) {

				$validity = Ts\Entity\Additionalcost\Validity::getValidEntry($oAdditionalCost, $dFrom, $dUntil);
				if($validity === null) {
					continue;
				}

				// valid_until kann leer sein
				if(
					!empty($validity->valid_until) && 
					$validity->valid_until !== '0000-00-00'
				) {
					$validityUntil = new Carbon($validity->valid_until);
				} else {
					$validityUntil = Carbon::now()->addYears(10);
				}

				$overlap = Core\Helper\DateTime::getDateRangeIntersection($dFrom, $dUntil, new Carbon($validity->valid_from), $validityUntil);

				$dCurrentFrom = $overlap['start'];
				$dCurrentUntil = $overlap['end'];

			} else {
				$dCurrentFrom = clone $dFrom;
				$dCurrentUntil = clone $dUntil;
			}

			$iFrom = $dCurrentFrom->getTimestamp();
			$iUntil = $dCurrentUntil->getTimestamp();

			$iAmount = $oAmount->calculateAdditionalAccommodationCost($oAdditionalCost, $dCurrentFrom, $dCurrentUntil, false, $oCategory->id);

			$iAmountNet = $iAmount;
			if($oInquiry->hasAgency()) {
				$iAmountNet = $oAmount->calculateAdditionalAccommodationCost($oAdditionalCost, $dCurrentFrom, $dCurrentUntil, true, $oCategory->id);
			}
			
			$iAmountProv = $iAmount - $iAmountNet;

			// Unterkunftskosen sollen nur einmal berechnet werden wenn keine checkbox gewählt
			if((int)$oAdditionalCost->calculate == 0){

				if(array_key_exists($oAdditionalCost->id, self::$aAdditionalAccommodationSingle)){
					// auf teuersten Betrag prüfen
					if(self::$aAdditionalAccommodationSingle[$oAdditionalCost->id] < $iAmount){
						// alten billige Unterkunft löschen
						foreach($aAdditionalAccommodationData as $iKey => $aData){
							if($aData['id'] == $oAdditionalCost->id) {
								
								// Bei einmaligen Kurskosten brauche ich für die Kostenstelle alle InquiryCoures-IDs, auf die sich die Zusatzgebühr bezieht
								$aInquiryAccommodationIds = array_merge($aInquiryAccommodationIds, $aAdditionalAccommodationData[$iKey]['additional_info']['inquiry_journey_accommodation_ids']);
								
								unset($aAdditionalAccommodationData[$iKey]);
								break;
							}
						}
					} else {
						// Bei einmaligen Kurskosten brauche ich für die Kostenstelle alle InquiryCoures-IDs, auf die sich die Zusatzgebühr bezieht
						foreach($aAdditionalAccommodationData as $iKey => $aData) {
							if($aData['id'] == $oAdditionalCost->id) {
								$aAdditionalAccommodationData[$iKey]['additional_info']['inquiry_journey_accommodation_ids'][] = (int)$oInquiryAccommodation->id;
							}
						}
						continue;
					}

				}else{
					self::$aAdditionalAccommodationSingle[$oAdditionalCost->id] = (float)$iAmount;
				}
				
			}

			// Gruppen Guide checken und Amount löschen
			if(
				$oInquiry->hasGroup() &&
				$oInquiry->isGuide() &&
				(
					$oInquiry->getJourneyTravellerOption('free_accommodation_fee') ||
					$oInquiry->getJourneyTravellerOption('free_all')
				)
			) {
				$iAmount = $iAmountNet = $iAmountProv = 0;
			}

			if(
				$iAmount == 0 &&
				!$oAdditionalCost->showZeroAmount()
			) {
				continue;
			}

			// Auf Special prüfen und berechnen
			$oSpecialAmount = new Ext_Thebing_Inquiry_Special_Amount($iAmount, $oAdditionalCost);
			$oSpecialAmount->setCalculationTime($iFrom);
			$oSpecialAmount->setInquiry($oInquiry);
			
			$oSpecialAmountNet = new Ext_Thebing_Inquiry_Special_Amount($iAmountNet, $oAdditionalCost);
			$oSpecialAmountNet->setCalculationTime($iFrom);
			$oSpecialAmountNet->setInquiry($oInquiry);
			
			// Hash erstellen damit nach dem speichern eine Zusammengehörichkeit ermittelt werden kann.
			#$sSpecialHash = md5($sType.$oAdditionalCost->id.$oCategory->id.$oInquiryAccommodation->id);
			
			$aSpecialAdditionalInfo = array(
				'parent_item_key' => $sTmpItemKey,
				'type' => $sType,
				'type_id' => (int)$oAdditionalCost->id,
				'additional_cost_id' => (int)$oAdditionalCost->id,
				'accommodation_category_id' => (int)$oCategory->id,
				'inquiry_journey_accommodation_id' => (int)$oInquiryAccommodation->id
			);
			
			// Transfer-special manuell setzen
			$oAmount->setSpecialAmount($sType, $oAdditionalCost->id, $oAdditionalCost, $oSpecialAmount, false, $aSpecialAdditionalInfo);
			$oAmount->setSpecialAmount($sType, $oAdditionalCost->id, $oAdditionalCost, $oSpecialAmountNet, true, $aSpecialAdditionalInfo);

			if($oAdditionalCost->calculate == 3) {
				$iInfoCount = $dCurrentUntil->diff($dCurrentFrom)->days;
			} else {
				$iInfoCount = ceil($dCurrentUntil->diff($dCurrentFrom)->days / 7);
			}

			$sCostName = $oInquiryAccommodation->getAdditionalCostInfo($oAdditionalCost->id, $iInfoCount, $iAccommodationCount, $oLanguage);
			
			$aAdditionalAccommodationItem['item_key']				= $sTmpItemKey;
			$aAdditionalAccommodationItem['description']			= $sCostName;
			$aAdditionalAccommodationItem['amount']					= $iAmount;
			$aAdditionalAccommodationItem['amount_net']				= $iAmountNet;
			$aAdditionalAccommodationItem['amount_provision']		= $iAmountProv;
			$aAdditionalAccommodationItem['amount_discount']		= 0;
			$aAdditionalAccommodationItem['id']						= (int)$oAdditionalCost->id;
			$aAdditionalAccommodationItem['type']					= $sType;
			$aAdditionalAccommodationItem['type_id']				= (int)$oAdditionalCost->id;
			$aAdditionalAccommodationItem['parent_id']				= (int)$oCategory->id;
			$aAdditionalAccommodationItem['parent_booking_id']		= (int)$oInquiryAccommodation->id;
			$aAdditionalAccommodationItem['type_object_id']			= 0;
			$aAdditionalAccommodationItem['type_parent_object_id']  = (int)$oInquiryAccommodation->accommodation_id;
			$aAdditionalAccommodationItem['from'] = $oInquiryAccommodation->from;
			$aAdditionalAccommodationItem['until'] = $oInquiryAccommodation->until;
			$aAdditionalAccommodationItem['index_from'] = $aAdditionalAccommodationItem['from'];
			$aAdditionalAccommodationItem['index_until'] = $aAdditionalAccommodationItem['until'];
			
			// Wenn "Vorort"
			if(
				$bPaymentMethodLocal ||
				(
					is_object($oAgency) &&
					$oAgency->checkInitalCost($oAdditionalCost->id)
				)
			){
				$aAdditionalAccommodationItem['initalcost']			= 1;
			} else {
				$aAdditionalAccommodationItem['initalcost']			= 0; 
			}
			$aAdditionalAccommodationItem['onPdf']			= 1;
			$aAdditionalAccommodationItem['calculate']		= 1;
			$aAdditionalAccommodationItem['cost_calculate_setting'] = (int)$oAdditionalCost->calculate;
			
			$dVatDate = Ext_TS_Vat::getVATReferenceDateByService($oSchool, $oInquiryAccommodation);
			$aAdditionalAccommodationItem['tax_category']	= Ext_TS_Vat::getDefaultCombination('Ext_Thebing_School_Cost', $oAdditionalCost->id, $oSchool, $oInquiry, $dVatDate, $oDocument->type);

			$aAdditionalAccommodationItem['additional_info'] = array(
				'item_key' => $sTmpItemKey,
				'weeks' => $iWeeks,
				'nights' => $oAdditionalCost->calculate == 3 ? $iInfoCount : null,
				'count_accommodations' => $iAccommodationCount,
				'roomtype_id' => (int)$oInquiryAccommodation->roomtype_id,
				'meal_id' => (int)$oInquiryAccommodation->meal_id,
				'billing_type' => $oAdditionalCost->calculate == Ext_Thebing_School_Additionalcost::CALCULATION_PER_WEEK ? 'week' : 'once',
				'billing_units' => $oAdditionalCost->calculate == Ext_Thebing_School_Additionalcost::CALCULATION_PER_WEEK ? $iWeeks : 1,
				'inquiry_journey_accommodation_ids' => $aInquiryAccommodationIds
			);

			$aAdditionalAccommodationData[] = $aAdditionalAccommodationItem;
		}
		
	}

	public static function clearAdditionalCostCache() {
		self::$aAdditionalAccommodationSingle = array();
		self::$aAdditionalCoursesSingle = array();
		self::$aAdditionalPerAccommodation = array();
		self::$aAdditionalPerCourse = array();
	}

	/**
	 * Löscht Zusatzkosten die zu einem Kurs/Unterkunft gehören
	 */
	public function deleteAdditionalCostsFromItems(&$aItems, $aItem){
		
		foreach((array)$aItems as $iKey => $aItemTemp){
			if(
				$aItemTemp['type'] == 'additional_' . $aItem['type'] &&
				$aItemTemp['parent_booking_id'] == $aItem['type_id']
			){
				unset($aItems[$iKey]);
			}
		}							
	}
	
	/**
	 * Speichert, das die Version gedruckt worden ist
	 */
	public function savePrintstatus($iUserId, $bSuccess){
		
		$oPrint = new Ext_Thebing_Inquiry_Document_Version_Print(0);
	
		$oPrint->printed		= time();
		$oPrint->user_id		= (int)$iUserId;
		$oPrint->version_id		= (int)$this->id;
		if($bSuccess){
			$oPrint->print_success	= time();
		}
	
		$oPrint->save();
	}
	
	/**
	 * Da nach dem speichern der Positionen einer Version, diese evtl. noch umgeschreiben werden müssen
	 * (IDs ans anderen Positionen ausgelesen werden) wird dies hier passieren
	 */
	public function updateItemIds($aItemIdAllocations){
		
		$aItems = $this->getItemObjects();

		foreach($aItems as $oItem) {

			if($oItem->parent_type == 'item_id') {

				$iNewParentId = null;
				if(isset($aItemIdAllocations[$oItem->parent_id])) {

					$iNewParentId = $aItemIdAllocations[$oItem->parent_id];

				} else {

					$aItemAdditionalInfo = (array)Ext_Thebing_Util::reconvertMixed($oItem->additional_info);

					// Nur Item-ID suchen, wenn noch keine gesetzt ist
					if($oItem->parent_id == 0) {

						// Parent ID muss umgeschrieben werden -> passende ID suchen
						foreach((array)$aItems as $oTempItem) {
							
							$aParentItemAdditionalInfo = (array)Ext_Thebing_Util::reconvertMixed($oTempItem->additional_info);

							// Wenn item-keys gesetzt und gleich sind, dann ist das Eltern-Item gefunden
							if(
								isset($aItemAdditionalInfo['parent_item_key']) &&
								isset($aParentItemAdditionalInfo['item_key'])
							) {

								if($aItemAdditionalInfo['parent_item_key'] == $aParentItemAdditionalInfo['item_key']) {
									$iNewParentId = $oTempItem->id;
									break;
								}

							}

						}
						
					}
					
				}

				if($iNewParentId !== null) {
					$oItem->parent_id = (int)$iNewParentId;
					$oItem->updateItemCache();
					$oItem->save();

					// TODO Muss eventuell eingebaut werden, wenn es wieder parent_id = 0 geben sollte (solange nicht Diff)
					//Ext_Thebing_Inquiry_Document_Version_Item::setInstance($oItem);

				}
				
			}
		}

	}
	
	/**
	 * @return Ext_TS_Inquiry
	 */
	public function getInquiry(Ext_Thebing_Inquiry_Document $oDocument = null) {

		if($this->_oInquiry !== null) {
			return $this->_oInquiry;
		}
			
		if($oDocument === null)	{
			// TODO Ist $bUseCache = false noch korrekt? So funktioniert keine Version objekt-relational ohne manuelles setInquiry()
			$oDocument = $this->getDocument(false);
		}

		return $oDocument->getInquiry();
	}
	
	public function setInquiry(Ext_TS_Inquiry_Abstract $oInquiry)
	{
		$this->_oInquiry = $oInquiry;
	}
	
	public function getAddress() {
		
        $aAddresses = (array)$this->addresses;
		
        $aAddress = reset($aAddresses);
		
		// Fallback
		if(empty($aAddress)) {
			
			$inquiry = $this->getInquiry();
			if($inquiry instanceof Ext_TS_Inquiry) {
				// Agentur
				if($inquiry->hasAgency()) {
					$aAddress = [
						'type' => 'agency',
						'type_id' => $inquiry->agency_id
					];
				} elseif($inquiry->hasGroup()) {
					$aAddress = [
						'type' => 'group',
						'type_id' => $inquiry->group_id
					];
				}
			}
			
		}
		
		return $aAddress;
	}

	public function getAddressee() {

		$aAddress = $this->getAddress();

		if(!empty($aAddress)) {

			$oInquiry = $this->getInquiry();

			if($oInquiry instanceof Ext_TS_Inquiry_Abstract) {
				$oDocumentAddress = new Ext_Thebing_Document_Address($oInquiry);
				return [$oDocumentAddress->getLabel($aAddress['type'], $aAddress['type_id']), $aAddress['type']];
			}

		}

		return null;
	}

    public function getAddresseeEmail():?string
    {
        $aAddress = $this->getAddress();

        if (!empty($aAddress)) {

            $oInquiry = $this->getInquiry();
            if ($oInquiry instanceof Ext_TS_Inquiry_Abstract) {

                $oDocumentAddress = new Ext_Thebing_Document_Address($oInquiry);

                $languageObject = new \Tc\Service\Language\Frontend($oInquiry->getSchool()->getLanguage());
                $addressData = $oDocumentAddress->getAddressData($aAddress, $languageObject);

                return $addressData['document_email']??'';

            }
        }

        return null;
    }


    public function getAddressType() {
        
        $oDoc           = $this->getDocument();
        
        if(
            $oDoc->type == 'manual_creditnote' ||
            $oDoc->type == 'creditnote'
        ){
            $sAddressType = 'agency';
        } else {
            $aAddress       = $this->getAddress();
            $sAddressType   = (string)$aAddress['type'];

            if(empty($sAddressType)) {
				$sAddressType = 'address';
            }
        }        

        return $sAddressType;
    }
    
    public function getAddressTypeId(){
        
        $iAddressTypeId = 0;
        
        $oDoc = $this->getDocument();
        
        if(
            $oDoc->type == 'manual_creditnote' ||
            $oDoc->type == 'creditnote'
        ){
            
            $oCN = $oDoc->getManualCreditnote();
            
            if($oCN){
                $oAgency = $oCN->getAgency();
            } else {
                $oInquiry = $this->getInquiry();
                if($oInquiry){
                    $oAgency = $oInquiry->getAgency();
                }
            }
            
            if($oAgency){
                $iAddressTypeId = $oAgency->getId();
            }
            
        } else {
            $aAddress       = $this->getAddress();
			
			if(
				empty($aAddress['type_id']) &&
				$aAddress['type'] === 'address'
			) {
				$aAddress['type_id'] = 0;
			}
			
            $iAddressTypeId = (int)$aAddress['type_id'];
        }
       
        return $iAddressTypeId;
    }
	
	public function getAddressNameData() {
		
		$aData = [];
		
		$sAddressType = $this->getAddressType();
		$sAddressTypeId	= $this->getAddressTypeId();

		$aData['type'] = $sAddressType;
		$aData['id'] = (int)$sAddressTypeId;
		
		switch($sAddressType) {
			
			case 'address':
								
				$oInquiry = $this->getInquiry();
				
				if($oInquiry) {
					
					$oTraveller	= $oInquiry->getFirstTraveller();

					if($oTraveller) {
						$aData['firstname'] = $oTraveller->firstname;
						$aData['lastname']	= $oTraveller->lastname;	
						$aData['id'] = $oTraveller->id;
					}
					
					$aData['type'] = 'contact';

				}
				
				break;
			case 'billing':
				
				$oInquiry = $this->getInquiry();
				
				if($oInquiry) {
					
					$billingContact = $oInquiry->getBooker();
					
					if($billingContact) {
						$billingAddress = $billingContact->getAddress('billing');

						$aData['object_name'] = $billingAddress->company;
						$aData['firstname'] = $billingContact->firstname;
						$aData['lastname']	= $billingContact->lastname;
						$aData['id'] = $billingContact->id;
					}

					$aData['type'] = 'contact';
					
				}
				
				break;
			
			case 'agency':
				
				$oAgency = Ext_Thebing_Agency::getInstance($sAddressTypeId);
				
				$aData['object_name'] = $oAgency->getName(true);
				
				$oContactPerson = $oAgency->getMasterContact();
				
				if($oContactPerson) {
					$aData['firstname']	= $oContactPerson->firstname;					
					$aData['lastname']	= $oContactPerson->lastname;
				}
				
				break;
			
			case 'group':
				
				$oGroup					= Ext_Thebing_Inquiry_Group::getInstance($sAddressTypeId);
				
				$aData['object_name']	= $oGroup->getName();
				
				$oContactPerson			= $oGroup->getContactPerson();
				
				$aData['firstname']		= $oContactPerson->firstname;

				$aData['lastname']		= $oContactPerson->lastname;
				
				break;
			
			case 'sponsor':
			
				$oSponsor = TsSponsoring\Entity\Sponsor::getInstance($sAddressTypeId);
				
				$aData['object_name'] = $oSponsor->name;
				
				$oInquiry = $this->getInquiry();
				if($oInquiry->sponsor_contact_id) {
					
					$oSponsorContact = TsSponsoring\Entity\Sponsor\Contact::getInstance($oInquiry->sponsor_contact_id);
				
					$aData['firstname'] = $oSponsorContact->firstname;
					$aData['lastname'] = $oSponsorContact->lastname;
					
				}

				break;
			
			default:
				
				break;
		}
	
		return $aData;
	}

	/**
	 * Gruppen: Anzahlungsbeträge anteilig auf Gruppenmitglieder aufteilen
	 *
	 * @param Ext_Thebing_Inquiry_Document_Version[] $aVersions
	 * @see calculateBackPrepayAmount
	 */
	public function calculatePrepayAmount(array $aVersions) {
		
		$oInquiry = $this->getInquiry();

		if(
			!$oInquiry instanceof Ext_TS_Inquiry ||
			!$oInquiry->hasGroup()
		) {
			return;
		}

		$fGroupAmount = 0;
		foreach($aVersions as $oVersion) {
			$fGroupAmount += $oVersion->getAmount();
		}

		$fStudentAmount = $this->getAmount();

		if(
			bccomp($fGroupAmount, 0) === 0 &&
			bccomp($fStudentAmount, 0) === 0
		) {
			return;
		}

		if(bccomp($fGroupAmount, 0) === 0) {
			throw new LogicException('calculatePrepayAmount: $fGroupAmount == 0 but $fStudentAmount > 0');
		}

		$aPaymentTerms = $this->getPaymentTerms();

		$fPercent = $fStudentAmount / $fGroupAmount;

		foreach($aPaymentTerms as $oPaymentTerm) {
			$oPaymentTerm->amount *= $fPercent;

			// TODO Besser lösen, aber die Version wird hiernach nicht mehr gespeichert
			$oPaymentTerm->save();
		}

	}

	/**
	 * Gruppen: Anzahlungsbeträge müssen bei der Anzeige wieder summiert werden, da der Dialog EINE Version anzeigt
	 *
	 * Hoffentlich werden Version oder Dokument nach Aufruf dieser Methode niemals gespeichert…
	 *
	 * @param Ext_TS_Document_Version_PaymentTerm[] $aPaymentTerms
	 * @see calculatePrepayAmount
	 */
	public function calculateBackPrepayAmount(array $aPaymentTerms) {

		$aDocuments = $this->getDocument()->getDocumentsOfSameNumber(); // $oVersion != $oDocument->getLastVersion()
		foreach($aDocuments as $oTmpDocument) {
			$oTmpVersion = $oTmpDocument->getLastVersion();
			if($this->id == $oTmpVersion->id) {
				if($this !== $oTmpVersion) {
					throw new RuntimeException('Different WDBasic objects for same ID (Ext_Thebing_Inquiry_Document_Version::'.$this->id.')');
				}

				continue;
			}

			$aTmpPaymentTerms = array_values($oTmpVersion->getPaymentTerms());

			// Das ist der Normalfall
			if(count($aTmpPaymentTerms) == count($aPaymentTerms)) {
				foreach($aTmpPaymentTerms as $iKey => $oPaymentTerm) {
					$aPaymentTerms[$iKey]->amount += $oPaymentTerm->amount;
				}
			// Falls das abweicht, warum auch immer, werden alle Beträge auf das erste Item gepackt
			} else {
				$firstPaymentTerm = reset($aPaymentTerms);
				foreach($aTmpPaymentTerms as $iKey => $oPaymentTerm) {
					$firstPaymentTerm->amount += $oPaymentTerm->amount;
				}
			}
			
		}

	}

	/**
	 * Version (manuell) klonen (war früher Bestandteil der cloneDocument())
	 *
	 * Das PDF wird nicht mitkopiert und auch nicht an dieser Stelle erzeugt!
	 *
	 * @param bool $bCloneForDocumentCopy Kopieren für Dokument-Kopie oder klonen
	 * @param array $aCloneForDocumentOptions
	 * @return array|bool|Ext_Thebing_Inquiry_Document_Version
	 */
	public function cloneVersion($bCloneForDocumentCopy = false, array $aCloneForDocumentOptions=[]) {
		global $user_data;

		$log = \Log::getLogger('default', 'clone_version');
		$log->info('Start version '.$this->id, $this->aData);
		$log->info('Options '.$this->id, $aCloneForDocumentOptions);
		
		$iTemplateId = $this->template_id;

		$oOriginalDocument = $this->getDocument();
		
		if(!$bCloneForDocumentCopy) {
			$oDocument = $this->getDocument();
			$iVersion = $this->version + 1;
			$sToType = $oDocument->type;
			$sTextPdf = $this->txt_pdf;
			$sComment = $this->comment;
			$oContact = null;
		} else {
			$oDocument = $aCloneForDocumentOptions['document'];
			$iVersion = 1;
			$sToType = $aCloneForDocumentOptions['to_document_type'];
			$sTextPdf = '';
			$sComment = $aCloneForDocumentOptions['version_comment'];
			$oContact = $aCloneForDocumentOptions['contact'];
			if($aCloneForDocumentOptions['template_id'] > 0) {
				$iTemplateId = $aCloneForDocumentOptions['template_id'];
			}
		}

		// letzte Version Clonen
		$oNewVersion = $oDocument->newVersion();
		$oNewVersion->version = $iVersion;
		$oNewVersion->template_id = $iTemplateId;
		$oNewVersion->template_language = $this->template_language;
		$oNewVersion->date = $this->date;
		$oNewVersion->txt_address = $this->txt_address;
		$oNewVersion->txt_subject = $this->txt_subject;
		$oNewVersion->txt_intro = $this->txt_intro;
		$oNewVersion->txt_outro = $this->txt_outro;
		$oNewVersion->txt_enclosures = $this->txt_enclosures;
		$oNewVersion->txt_pdf = $sTextPdf;
		$oNewVersion->signature_user_id = $this->signature_user_id;
		$oNewVersion->txt_signature = $this->txt_signature;
		$oNewVersion->signature = $this->signature;
		$oNewVersion->user_id = (int)$user_data['id'];
		$oNewVersion->comment = $sComment;
		$oNewVersion->tax = $this->tax;
		$oNewVersion->payment_condition_id = $this->payment_condition_id;

		$oNewVersion->addresses = $this->addresses;

		foreach($this->getPaymentTerms() as $oPaymentTerm) {
			/** @var Ext_TS_Document_Version_PaymentTerm $oNewPaymentTerm */
			$oNewPaymentTerm = $oNewVersion->getJoinedObjectChild('paymentterms');
			$oNewPaymentTerm->setting_id = $oPaymentTerm->setting_id;
			$oNewPaymentTerm->type = $oPaymentTerm->type;
			$oNewPaymentTerm->date = $oPaymentTerm->date;
			$oNewPaymentTerm->amount = $oPaymentTerm->amount;
		}

		$oNewVersion->save();

		$log->info('New version '.$this->id, $oNewVersion->aData);
		
		// In den Instanz-Cache setzen, sonst steht in den JoinedObjectChilds des Dokuments nicht diese Instanz
		self::setInstance($oNewVersion);

		// Editierbare Felder mit clonen
		$aFields = $this->getLayoutFields();

		foreach((array)$aFields as $oField){
			$oNewField = $oNewVersion->getNewLayoutField();
			$oNewField->block_id = $oField->block_id;
			$oNewField->content = $oField->content;
			$oNewField->save();
		}

		// Zuweisung alte zu neue Item-ID
		$aItemIdAllocations = array();

		$aItems = $this->getItemObjects();

		// Price Indexe setzen
		$oPriceIndex = new Ext_Thebing_Inquiry_Document_Version_Price();

		foreach($aItems as $oItem) {

			$log->info('Start item '.$this->id, $oItem->aData);
			
			$oItemContact = $oItem->getContact();

			// Wenn ein $oContact übergeben wurde, dann werden wirklich NURnoch die Items dieses Customers gecloned
			if(
				!is_null($oContact) &&
				!is_null($oItemContact) // TODO: Workaround wieder rausnehmen #9827
			) {
				if($oContact->id != $oItemContact->id) {
					$log->info('Skip item '.$this->id, ['contact_id' => $oContact->id, 'item_contact_id' => $oItemContact->id]);
					continue;
				}
			}

			$oNewItem = $oNewVersion->newItem();
			$oNewItem->setOtherItemData($oItem);

			// Bei CN Beträge umdrehen, aber nur wenn das Original nicht auch eine CN war
			if(
				$bCloneForDocumentCopy &&
				strpos($sToType, 'credit') !== false &&
				strpos($oOriginalDocument->type, 'credit') === false
			) {
				$oNewItem->amount = $oItem->amount * -1;
				$oNewItem->amount_net = $oItem->amount_net * -1;
				$oNewItem->amount_provision	= $oItem->amount_provision * -1;
			}

			// Special-Relation mit clonen, damit Verknüpfung erhalten bleibt
			$oNewItem->specials = $oItem->specials;

			// Contact mit clonen
			if(is_object($oItemContact)) {
				$oNewItem->contact_id = (int)$oItemContact->id;
			}

			$oNewItem->save();

			// Zuweisung alte zu neue ID
			$aItemIdAllocations[$oItem->id] = $oNewItem->id;

			$oPriceIndex->addItem($oNewItem);

			$log->info('End item '.$this->id, $oNewItem->aData);
			
		}

		// Price Index speichern
		$mSuccess = $oPriceIndex->savePrice($oNewVersion->id);
		if(is_array($mSuccess)) {
			return $mSuccess;
		}

		// parent_type = item_id muss aktualisiert werden
		$oNewVersion->updateItemIds($aItemIdAllocations);

		$log->info('End version '.$this->id, $oNewVersion->aData);

		return $oNewVersion;
	}

	/**
	 * Foreign-Keys von geklonten Items aktualisieren (das ist für Auswertungen total wichtig)
	 *
	 * @link https://redmine.fidelo.com/projects/schule/wiki/DB-Felder_der_Document-Items
	 */
	public function adaptItems() {

		/** @var Ext_TS_Inquiry_Journey $journey */
		$journey = $this->getInquiry()->getJourney();

		foreach ($this->getItemObjects() as $item) {

			// Ein Item kann mehr als einen FK haben
			$mappings = array_filter(Ext_Thebing_Inquiry_Document_Version_Item::RElATION_MAPPING, function (array $mapping) use ($item) {
				return $mapping[0] === $item->type;
			});

			if (empty($mappings)) {
				throw new LogicException('Unknown item type: '.$item->type);
			}

			foreach ($mappings as $mapping) {

				if (!isset($mapping[2])) {
					continue;
				}

				[, $relation, $foreignKey] = $mapping;
				$foreignId = data_get($item, $foreignKey);

				// Wenn es keinen Fremdschlüssel gibt, muss auch nichts angepasst werden
				if (empty($foreignId)) {
					continue;
				}

				$newService = collect($journey->getJoinedObjectChilds($relation))->first(function (Ext_TS_Inquiry_Journey_Service $service) use ($foreignId) {
					if (empty($service->transients['origin_service'])) {
						throw new LogicException(sprintf('No origin data for service %s::%d', get_class($service), $service->id));
					}
					return $service->transients['origin_service']->id == $foreignId;
				});

				if (empty($newService)) {
					throw new RuntimeException(sprintf('Could not find a new id for item %s %d', $item->type, $item->id));
				}

				if (strpos($foreignKey, 'additional_info') !== false) {
					// Da __get() weder eine Referenz noch ein Objekt zurückliefert, braucht es den expliziten Setter
					$foreignKey = \Illuminate\Support\Str::after($foreignKey, '.');
					$item->additional_info = data_set($item->additional_info, $foreignKey, $newService->id);
				} else {
					$item->{$foreignKey} = $newService->id;
				}

			}

			$item->save();

		}

	}

	/**
	 * DB-Preis-Index (kolumbus_inquiries_documents_versions_priceindex) dieser Version aktualisieren
	 *
	 * @throws Exception
	 */
	public function refreshPriceIndex() {

		// Alle alten Einträge (komplett) löschen, da PriceIndex-Klasse als Fassade arbeitet und neue Einträge erzeugt
		$aPriceIndexes = Ext_Thebing_Inquiry_Document_Version_Price::getByVersion($this);
		foreach($aPriceIndexes as $oPriceIndex) {
			$oPriceIndex->remove();
		}

		$aItems = $this->getJoinedObjectChilds('items'); // Bloß nicht $this->getItemObjects()!
		$oPriceIndex = new Ext_Thebing_Inquiry_Document_Version_Price();
		foreach($aItems as $oItem) {
			$oPriceIndex->addItem($oItem);
		}

		$oPriceIndex->savePrice($this->id);

	}

	/**
	 * Absoluten PDF-Pfad für path vorbereiten
	 *
	 * @param $sFilePath
	 * @return mixed
	 */
	public static function prepareAbsolutePath($sFilePath) {
		$sVersionPath = str_replace(Util::getDocumentRoot(), '', $sFilePath);
		$sVersionPath = str_replace('storage', '', $sVersionPath);
		return $sVersionPath;
	}

	/**
	 * Standardtexte aus Template in die Version setzen
	 *
	 * template_id und signature_user_id sollten für eine korrekte Funktion bereits gesetzt sein.
	 * Achtung! Diese Funktion beachtet nicht eigene Elemente auf dem PDF, diese Platzhalter gehen durch die PDF-Klasse!
	 *
	 * @param Ext_Thebing_Placeholder $oReplace
	 * @param Ext_Thebing_School $oSchool
	 */
	public function setDefaultTemplateTexts($oReplace, Ext_Thebing_School $oSchool) {

		if(empty($this->template_id)) {
			throw new RuntimeException('No template_id set!');
		}

		if($oReplace instanceof Ext_Thebing_Placeholder) {
			$sTemplateLanguage = $oReplace->sTemplateLanguage;
		} else {
			$sTemplateLanguage = $oReplace->getDisplayLanguage();
		}
		
		$oTemplate = Ext_Thebing_Pdf_Template::getInstance($this->template_id);

		$sTxtAddress = $oTemplate->getStaticElementValue($sTemplateLanguage, 'address');
		$sTxtSubject = $oTemplate->getStaticElementValue($sTemplateLanguage, 'subject');
		$sTxtIntro = $oTemplate->getStaticElementValue($sTemplateLanguage, 'text1');
		$sTxtOutro = $oTemplate->getStaticElementValue($sTemplateLanguage, 'text2');
		$sTxtPdf = $oTemplate->getOptionValue($sTemplateLanguage, $oSchool->id, 'first_page_pdf_template');

		if($oTemplate->user_signature) {
			$sTxtSignature = Ext_Thebing_User_Data::getData($this->signature_user_id, 'signature_pdf_'.$sTemplateLanguage);
			$sImgSignature = Ext_Thebing_User_Data::getData($this->signature_user_id, 'signature_img_'.$oSchool->id);
		} else {
			$sTxtSignature = $oTemplate->getOptionValue($sTemplateLanguage, $oSchool->id, 'signatur_text');
			$sImgSignature = $oTemplate->getOptionValue($sTemplateLanguage, $oSchool->id, 'signatur_img', false);
		}

		$exception = null;
		$replacePlaceholders = function ($sText) use ($oReplace, &$exception) {
			try {
				return $oReplace->replace($sText, 0);
			} catch (\Throwable $e) {
				if(!$exception) {
					$exception = $e;
				}
				return $sText;
			}
		};

		$this->txt_address = $replacePlaceholders($sTxtAddress);
		$this->txt_subject = $replacePlaceholders($sTxtSubject);
		$this->txt_intro = $replacePlaceholders($sTxtIntro);
		$this->txt_outro = $replacePlaceholders($sTxtOutro);
		$this->txt_pdf = $replacePlaceholders($sTxtPdf);
		$this->txt_signature = $replacePlaceholders($sTxtSignature);
		$this->signature = $replacePlaceholders($sImgSignature);

		if ($exception) {
			throw $exception;
		}
	}

	/**
	 * Ausgewählte Rechnung bei Zusatzdokumenten
	 *
	 * @return null|Ext_Thebing_Inquiry_Document
	 */
	public function getSelectedInvoiceDocument() {

		$oDocument = Ext_Thebing_Inquiry_Document::getInstance($this->invoice_select_id);
		if($oDocument->exist()) {
			return $oDocument;
		}

		return null;

	}

	/**
	 * Zahlungsbedingung dieser Version (Select)
	 *
	 * @return Ext_TS_Payment_Condition|null
	 */
	public function getPaymentCondition() {

		$oPaymentCondition = Ext_TS_Payment_Condition::getInstance($this->payment_condition_id);
		if($oPaymentCondition->exist()) {
			return $oPaymentCondition;
		}

		return null;

	}

	/**
	 * Zahlungsbedingungen (Anzahlungen, Restzahlung) dieser Version
	 *
	 * @return Ext_TS_Document_Version_PaymentTerm[]
	 */
	public function getPaymentTerms() {
		return $this->getJoinedObjectChilds('paymentterms', true);
	}

	/**
	 * Journey-Services, die in Rechnung gestellt wurden (nicht die der Buchung)
	 *
	 * Diffs und sonstige Logiken werden bisher nicht beachtet!
	 *
	 * @param string $sType
	 * @return Ext_TS_Inquiry_Journey_Course[]
	 */
	public function getInvoicedJourneyServices($sType) {

		$aJourneyServices = [];

		$oInquiry = $this->getInquiry();
		if(!$oInquiry instanceof Ext_TS_Inquiry_Abstract) {
			// Bei ID 0 ist die Verbindung tot
			return [];
		}

		foreach($this->getItemObjects(true) as $oItem) {
			if($oItem->type === $sType) {
				$aJourneyServices[] = $oInquiry->getServiceObject($oItem->type, $oItem->type_id);
			}
		}

		return $aJourneyServices;

	}

	/**
	 * Übersicht der eingestellten Zahlungsbedingungen dieser Version:
	 * Anzahl der erwarteten Zahlungen + eingestellte Zahlungen pro Zeile
	 *
	 * \n dient als Trennzeichen und wird von der Formatklasse geteilt.
	 *
	 * @return string
	 */
	public function getIndexPaymentTermOverview($bAddNumber=false) {

		$oInquiry = $this->getInquiry();
		$oFormatDate = new Ext_Thebing_Gui2_Format_Date();
		$oFormatAmount = new Ext_Thebing_Gui2_Format_Amount();
		$aPaymentTerms = $this->getPaymentTerms();

		$fPayedAmount = $this->getDocument()->getPayedAmount();
		$fPaymentTermAmount = 0;
		
		$sText = count($aPaymentTerms)."\n";

		if($bAddNumber === true) {
			$sText .= '<strong>'.$this->getDocument()->document_number.'</strong><br>';
		}
		
		$dNow = new DateTime;
		
		// Das kann sonst zu Fehlern beim Index führen (zu viel Text)
		if(count($aPaymentTerms) > 40) {
			$aPaymentTerms = array_slice($aPaymentTerms, 0, 40);
		}
		
		$iCount = 1;
		foreach($aPaymentTerms as $oPaymentTerm) {
			
			$fPaymentTermAmount += (float)$oPaymentTerm->amount;

			$sStyle = '';
			
			// Offen
			if($fPayedAmount < $fPaymentTermAmount) {
				
				// Fällig
				$dDate = new DateTime($oPaymentTerm->date);
				if($dDate < $dNow) {
					$sStyle = 'color: '.Ext_Thebing_Util::getColor('bad_font').';';
				}

			} else {
				$sStyle = 'color: '.Ext_Thebing_Util::getColor('good_font').';';
			}
			
			$sText .= '<span style="'.$sStyle.'">'.$iCount++.'. ';
			$sText .= $oFormatDate->format($oPaymentTerm->date).': ';
			$oDummy = null;
			$aResultData = ['currency_id' => $oInquiry->getCurrency()];
			$sText .= $oFormatAmount->format($oPaymentTerm->amount, $oDummy, $aResultData);
			$sText .= "</span><br>";
		}

		return $sText;
	}

	/**
	 * Datum oder Betrag der nächsten Zahlung (Vergleich mit Payments)
	 *
	 * @param string $sField
	 * @return float|null|string|\Ts\Dto\ExpectedPayment
	 */
	public function getIndexPaymentTermData($sField) {

		$aPaymentTerms = $this->getPaymentTerms();

		$oDocument = $this->getDocument();

		$sDate = $fAmount = $fOpenAmount = null;
		$fPayedAmount = $oDocument->getPayedAmount();
		$fPaymentTermAmount = 0;

		foreach($aPaymentTerms as $oPaymentTerm) {

			$fPaymentTermAmount += (float)$oPaymentTerm->amount;

			if($fPayedAmount < $fPaymentTermAmount) {
				$sDate = $oPaymentTerm->date;
				$fAmount = (float)$oPaymentTerm->amount;
				$fOpenAmount = $fPaymentTermAmount - $fPayedAmount;
				break;
			}

		}

		if($sField === 'paymentterms_next_payment_date') {
			return $sDate;
		} else if($sField === 'paymentterms_next_payment_amount') {
			return $fAmount;
		} else if($sField === 'paymentterms_next_payment_object') {
			if ($fOpenAmount !== null) {
				$currency = $oDocument->getCurrency();
				return new \Ts\Dto\ExpectedPayment($oDocument, Carbon::make($sDate), new Amount((float)$fAmount, $currency), new Amount((float)$fOpenAmount,  $currency));
			}
			return null;
		} else {
			throw new \InvalidArgumentException('Unknown field: '.$sField);
		}

	}
	
	public function insertTransactions($bNegate=false) {

		$oDocument = $this->getDocument();
		$oInquiry = $this->getInquiry();
		$oCurrency = $oInquiry->getCurrency(true);

		$aAddressData = $this->getAddressNameData();
		$aPaymentTerms = $this->getPaymentTerms();

		if(
			$aAddressData['type'] === 'contact' &&
			$oInquiry->hasGroup()
		) {
			$aAddressData['type'] = 'group';
			$aAddressData['id'] = $oInquiry->group_id;
		}
		
		$sType = 'invoice';
		if($oDocument->isProforma()) {
			$sType = 'proforma';	
		}

		foreach($aPaymentTerms as $oPaymentTerm) {
			$fAmount = $oPaymentTerm->amount;
			if($bNegate) {
				$fAmount *= -1;
			}
			\TsAccounting\Service\Accounts\Transactions::add($aAddressData['type'], $aAddressData['id'], $sType, $this->document_id, $fAmount, $oCurrency->getIso(), new Carbon($oPaymentTerm->date));
		}
	}
	
	public function getVatRates($sLanguage) {

		if(!$this->exist()) {
			return [];
		}
		
		$oInquiry = $this->getInquiry();
		$oDocument = $this->getDocument();
		
		if($oInquiry->hasGroup()){
			$aItems	= (array)$this->getGroupItems($oDocument, false, false, true, true);	
			$aDataTax = Ext_TS_Vat::addGroupTaxRows($aItems, $oInquiry, $sLanguage);
		} else {
			$aItems	= (array)$this->getItems($oDocument, false, false, true);
			$aDataTax = Ext_TS_Vat::addTaxRows($aItems, $oInquiry, $sLanguage, $this);
		}
		
		/*
		 * @todo Total bescheuert, dass sich dieses Array nicht allgemein auf das Dokument bezieht
		 * Aktuell muss man sich je nach Typ die richtigen Werte aus unterschiedlichen properties ziehen.
		 */
		$sAmountColumn = 'amount_vat';
		$sVatColumn = 'amount';
		if(
			strpos($oDocument->type, 'netto') !== false ||
			(
				$oDocument->type == 'storno' &&
				$oInquiry->hasNettoPaymentMethod()
			)
		) {
			$sAmountColumn = 'amount_net_vat';
			$sVatColumn = 'amountNet';
		} elseif(strpos($oDocument->type, 'creditnote') !== false) {
			$sAmountColumn = 'amount_commission_vat';
			$sVatColumn = 'amountProv';
		}
		
		foreach($aDataTax['general'] as $aTaxRate) {
			$oVatRate = new Ts\Model\Document\Version\VatRate;
			$oVatRate->note = $aTaxRate['note'];
			$oVatRate->amount = $aTaxRate[$sAmountColumn];
			$oVatRate->amount_net = $aTaxRate[$sAmountColumn] - $aTaxRate[$sVatColumn];
			$oVatRate->vat = $aTaxRate[$sVatColumn];
			$oVatRate->vat_rate = $aTaxRate['tax_rate'];
			$oVatRate->lines = (array)$aTaxRate['lines'];
			$aVatRates[] = $oVatRate;
		}
		
		return $aVatRates;
	}
	
	public function updateHasCommissionableItems() {
		
		$inquiry = $this->getInquiry();
		
		if(!$inquiry instanceof Ext_TS_Inquiry) {
			return;
		}
		
		// Nur Agenturbuchungen können provisionspflichtige Rechnungspositionen haben
		if(!$inquiry->hasAgency()) {
			$this->has_commissionable_items = 0;
			return;
		}
		
		$items = $this->getJoinedObjectChilds('items');
		
		$hasCommissionableItems = false;
		foreach($items as $item) {

			// Nur Items berücksichtigen, die auch aktiv sind
			if(
				$item->onPdf != 1 ||
				$item->calculate != 1
			) {
				continue;
			}
			
			if($item->amount_provision != 0) {
				$hasCommissionableItems = true;
				break;
			}
			
			// Wird mit Fake-Amount berechnet, damit dieser Flag die Einstellungen berücksichtigt und nicht den tatsächlichen Preis
			$commission = $item->getNewProvisionAmount(100);
			
			if($commission != 0) {
				$hasCommissionableItems = true;
				break;
			}
			
		}
		
		$this->has_commissionable_items = (int)$hasCommissionableItems;
		
	}

	public function getServicePeriod(): ?CarbonPeriod {

		/** @var Ext_Thebing_Inquiry_Document_Version_Item[] $items */
		$items = $this->getJoinedObjectChilds('items', true);
		$dates = collect();

		foreach ($items as $item) {
			if (
				$item->active &&
				$item->onPdf &&
				Core\Helper\DateTime::isDate($item->index_from, 'Y-m-d') &&
				Core\Helper\DateTime::isDate($item->index_until, 'Y-m-d')
			) {
				$dates->push(Carbon::parse($item->index_from));
				$dates->push(Carbon::parse($item->index_until));
			}
		}

		if ($dates->isEmpty()) {
			// Da die Platzhalter nicht mit Call Paths umgehen können, muss immer ein Objekt existieren
			return CarbonPeriod::create();
//			return null;
		}

		return CarbonPeriod::create($dates->min(), $dates->max());

	}
	
	public function getCurrency() {
		return $this->getDocument()->getCurrency();
	}

	/**
	 * Erstellt einen Hash String der Pdf Datei
	 *
	 * @return false|string
	 */
	public function getFileHash(): string|false
	{
		return hash_file('sha256', $this->getPath(true));
	}

}
