<?php

/**
 * Kommunikation: Templates
 * E-Mail und SMS
 *
 * @since 07.10.2011
 */
class Ext_TC_Communication_Template extends Ext_TC_Basic {

	protected $_sTable = 'tc_communication_templates';
	
	protected $_sTableAlias = 'tc_ct';
	
	protected $_aJoinedObjects = array(
		'contents' => array(
			'class' => 'Ext_TC_Communication_Template_Content',
			'key' => 'template_id',
			'type' => 'child'
		)
	);
	
	protected $_aJoinTables = array(
		'languages' => array(
			'table' => 'tc_communication_templates_languages',
			'foreign_key_field' => 'language_iso',
			'primary_key_field' => 'template_id'
		),
		'objects' => array(
			'table' => 'tc_communication_templates_to_objects',
			'foreign_key_field' => 'object_id',
			'primary_key_field' => 'template_id'
		),
		'applications' => array(
			'table' => 'tc_communication_templates_applications',
			'foreign_key_field' => 'application',
			'primary_key_field' => 'template_id',
			'autoload' => true
		),
		'recipients' => array(
			'table' => 'tc_communication_templates_recipients',
			'foreign_key_field' => 'recipient',
			'primary_key_field' => 'template_id'
		),
		'flags' => array(
			'table' => 'tc_communication_templates_flags',
			'foreign_key_field' => 'flag',
			'primary_key_field' => 'template_id'
		),
		'invoice_types' => array(
			'table' => 'tc_communication_templates_invoices',
			'foreign_key_field' => 'type',
			'primary_key_field' => 'template_id'
		),
		'receipt_types' => array(
			'table' => 'tc_communication_templates_receipts',
			'foreign_key_field' => 'type',
			'primary_key_field' => 'template_id'
		),
		'incoming_files_categories' => array(
			'table' => 'tc_communication_templates_to_incomingfiles_categories',
			'foreign_key_field' => 'incomingfile_id',
			'primary_key_field' => 'template_id'
		),
		'pdf_templates' => array(
			'table' => 'tc_communication_templates_to_pdf_templates',
			'foreign_key_field' => 'pdf_template_id',
			'primary_key_field' => 'template_id'
		),
		'pdf_templates_received' => array(
			'table' => 'tc_communication_templates_to_pdf_templates_received',
			'foreign_key_field' => 'pdf_template_id',
			'primary_key_field' => 'template_id'
		),
		'uploads_received' => array(
			'table' => 'tc_communication_templates_to_uploads_received',
			'foreign_key_field' => 'upload_id',
			'primary_key_field' => 'template_id'
		)
	);

	/*public function __construct($iDataID = 0, $sTable = null) {
		
		parent::__construct($iDataID, $sTable);

		if($this->type !== 'email') {
			$this->shipping_method = 'text';
		}
		
	}*/

	public function getCC(): array
	{
		$cc = explode(';', $this->cc);
		array_walk($cc, 'trim');
		return array_filter($cc, fn ($route) => !empty($route));
	}

	public function getBCC(): array
	{
		$bcc = explode(';', $this->bcc);
		array_walk($bcc, 'trim');
		return array_filter($bcc, fn ($route) => !empty($route));
	}

	/**
	 * Prüfmethode für Getter und Setter, um herauszufinden, ob der Key dem Content-Objekt gehört
	 *
	 * @param string $sName
	 * @return bool
	 */
	protected function _isContentField($sName)
	{
		$bReturn = false;

		if(
			mb_strpos($sName, 'subject_') !== false ||
			mb_strpos($sName, 'content_') !== false ||
			mb_strpos($sName, 'to_uploads_') !== false ||
			mb_strpos($sName, 'content_uploads_') !== false ||
			mb_strpos($sName, 'layout_id_') !== false
		) {
			$bReturn = true;
		}

		return $bReturn;
	}

	/**
	 * Trennt für Getter und Setter bei den Content-Feldern den ISO-String ab
	 *
	 * @param $sName
	 * @return array
	 */
	protected function _splitContentFieldIso($sName)
	{
		$sField = mb_substr($sName, 0, mb_strrpos($sName, '_'));
		$aParts = explode('_', $sName);
		$sIso = end($aParts);

		$aReturn = array(
			'field' => $sField,
			'iso' => $sIso
		);

		return $aReturn;
	}

	/**
	 * Holt ein Content anhand seiner Sprache
	 * @param $sIso
	 * @return \Ext_TC_Communication_Template_Content
	 */
	public function getContentObjectByIso($sIso) {

		$oFoundContent = $this->getJoinedObjectChildByValue('contents', 'language_iso', $sIso);

		if(!$oFoundContent) {
			$oFoundContent = $this->getJoinedObjectChild('contents');
			$oFoundContent->language_iso = $sIso;
		}

		return $oFoundContent;

	}

	public function __get($sName) {

		if($this->_isContentField($sName)) {

			$aFieldData = $this->_splitContentFieldIso($sName);

			$oContent = $this->getContentObjectByIso($aFieldData['iso']);
			$mValue = $oContent->{$aFieldData['field']};

			if($aFieldData['field'] === 'content_uploads') {
				$mValue = !empty($mValue) ? reset($mValue) : null;
			}

		} else {
			
			// Nur bei Typ E-Mail kann das Format was anderes als text sein.
			if(
				$sName === 'shipping_method' &&
				$this->type !== 'email'
			) {
				return 'text';
			}
			
			$mValue = parent::__get($sName);
		}

		return $mValue;		
	}

	public function __set($sName, $mValue) {

		if($this->_isContentField($sName)) {

			$aFieldData = $this->_splitContentFieldIso($sName);
			$oContent = $this->getContentObjectByIso($aFieldData['iso']);

			$mOriginalValue = $oContent->{$aFieldData['field']};

			if($aFieldData['field'] === 'content_uploads') {
				$mValue = (array)$mValue;

				if(
					count($mValue) == 1 &&
					empty($mValue[0]))
				{
					$mValue = array();
				}
			}

			// Leere Objekte vermeiden
			if($mValue != $mOriginalValue) {
				$oContent->{$aFieldData['field']} = $mValue;
			}

		} else {
			parent::__set($sName, $mValue);
		}

	}
	
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$aSqlParts['select'] .= ", GROUP_CONCAT(DISTINCT `languages`.`language_iso` ORDER BY `languages`.`language_iso` SEPARATOR ',') `languages`
			                     , GROUP_CONCAT(DISTINCT `objects`.`object_id` ORDER BY `objects`.`object_id` SEPARATOR ',') `objects`
			                     , GROUP_CONCAT(DISTINCT `applications`.`application` ORDER BY `applications`.`application` SEPARATOR ',') `applications`
			                     , GROUP_CONCAT(DISTINCT `recipients`.`recipient` ORDER BY `recipients`.`recipient` SEPARATOR ',') `recipients`
								";

	}
	
	/**
	 * Template Select Options
	 * @param string $channel email oder sms
	 * @param array $options Filteroptionen
	 * @return \Illuminate\Support\Collection
	 */
	public static function getSelectOptions($channel, $options = array())
	{
		$channel = \Communication\Services\Communication::MESSAGE_TYPE_CHANNEL_MAPPING[$channel] ?? $channel;

		$query = static::query()
			->select('tc_ct.*')
			->where('type', $channel);

		if (!empty($options['application'])) {
			$query->join('tc_communication_templates_applications as applications', function (\Illuminate\Database\Query\JoinClause $join) use ($options) {
				$join->on('applications.template_id', '=', 'tc_ct.id')
					->whereIn('applications.application', (array)$options['application']);
			});
		}

		if (!empty($options['recipient'])) {
			$query->join('tc_communication_templates_recipients as recipients', function (\Illuminate\Database\Query\JoinClause $join) use ($options) {
				$join->on('recipients.template_id', '=', 'tc_ct.id')
					->whereIn('recipients.recipient', (array)$options['recipient']);
			});
		}

		if (!empty($options['sub_objects'])) {
			$query->join('tc_communication_templates_to_objects as objects', function (\Illuminate\Database\Query\JoinClause $join) use ($options) {
				$join->on('objects.template_id', '=', 'tc_ct.id')
					->whereIn('objects.object_id', (array)$options['sub_objects']);
			});
		}

		if (!empty($options['languages'])) {
			$query->join('tc_communication_templates_languages as languages', function (\Illuminate\Database\Query\JoinClause $join) use ($options) {
				$join->on('languages.template_id', '=', 'tc_ct.id')
					->whereIn('languages.language_iso', (array)$options['languages']);
			});
		}

		$templates = $query->groupBy('tc_ct.id')->get()
			->mapWithKeys(fn ($template) => [$template->id => $template->name])
			->sort();

		if($templates->isNotEmpty()) {
			$templates = $templates->prepend('', 0);
		}
		
		return $templates;
	}

	/**
	 * Versandarten
	 * @return array
	 */
	public static function getShippingMethods()
	{
		return array(
			'text' => Ext_TC_Communication::t('Text'),
			'html' => Ext_TC_Communication::t('HTML inklusive Text')
		);
	}
	
}
