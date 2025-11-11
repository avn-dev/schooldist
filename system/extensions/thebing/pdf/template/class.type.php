<?php 

class Ext_Thebing_Pdf_Template_Type extends Ext_Thebing_Basic {

	protected $_sTable = 'kolumbus_pdf_templates_types';

	protected $_aFormat = array(
		'created' => array(
			'format' => 'TIMESTAMP'
			),
		'changed' => array(
			'format' => 'TIMESTAMP'
			),
		'font_type' => array(
			'required' => true
		),
		'font_spacing' => array(
			'validate' => 'FLOAT'
		),
		'page_format_width' => [
			'required' => true,
			'validate' => 'FLOAT_POSITIVE'
		],
		'page_format_height' => [
			'required' => true,
			'validate' => 'FLOAT_POSITIVE'
		]
	);

	protected $_aJoinTables = array(
		'type_elements' => array(
			'table' => 'kolumbus_pdf_templates_types_elements',
			'foreign_key_field' => '',
			'primary_key_field' => 'type_id',
			'sort_column' => 'position'
		)
	);

	/**
	 * @return Ext_Thebing_Pdf_Template_Type_Element[]
	 */
	public function getElements() {

		$sSql = "
			SELECT `id` FROM
				`kolumbus_pdf_templates_types_elements`
			WHERE
				`type_id` = :type_id AND
				`active` = 1
			ORDER BY
				`position`
		";

		$aResult = DB::getPreparedQueryData($sSql, array(
			'type_id'=>(int)$this->id
		));

		$aBack = array();
		foreach($aResult as $aData){
			$aBack[] = Ext_Thebing_Pdf_Template_Type_Element::getInstance($aData['id']);
		}

		return $aBack;

	}

	/*
	 * Liefert mir alle editierbaren Elemente des Layouts 
	 */
	public function getEditableElements(){
		$aResult = Ext_Thebing_Util::convertDataIntoObject($this->type_elements, 'Ext_Thebing_Pdf_Template_Type_Element');
		return $aResult;
	}

	/**
	 * Erzeugt ein Query für eine Liste mit Items dieses Objektes
	 * @return array
	 */
	public function getListQueryData($oGui=null) {

		$aQueryData = array();

		$sFormat = $this->_formatSelect();

		$sFormat = str_replace('(`', '(`kptt`.`', $sFormat);

		$aQueryData['data'] = array();

		$aQueryData['sql'] = "
				SELECT
					kptt.*,
					su.firstname,
					su.lastname
					{FORMAT}
				FROM
					`{TABLE}` `kptt` LEFT OUTER JOIN
					`system_user` `su` ON
						`kptt`.`user_id` = `su`.`id`
			";

		if(array_key_exists('active', $this->_aData)) {
			$aQueryData['sql'] .= " WHERE `kptt`.`active` = 1 ";
		}

		$aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
		$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);

		return $aQueryData;

	}
	
}