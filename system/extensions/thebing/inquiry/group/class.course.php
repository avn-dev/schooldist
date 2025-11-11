<?php
/**
 * @property int $id 	
 * @property int $group_id 	
 * @property int $changed 	
 * @property int $created 	
 * @property int $course_id 	
 * @property int $level_id 	
 * @property int $weeks 	
 * @property date $from 	
 * @property date $until 	
 * @property string $comment 	
 * @property int $calculate 	
 * @property int $visible 	
 * @property int $active 	
 * @property int $creator_id 	
 * @property int $units 	
 * @property int $type
 * 
 */
class Ext_Thebing_Inquiry_Group_Course extends Ext_Thebing_Inquiry_Group_Service {

	use Ts\Traits\Course\AdjustData;

	const JOIN_ADDITIONALSERVICES = 'additionalservices';

	protected $_sTable = 'kolumbus_groups_courses';

	protected $_aFormat = array(
		'group_id' => array(
			'required'=>true,
			'validate'=>'INT_POSITIVE',
			'not_changeable' => true
		),
		'level_id' => array(
			'validate' => 'INT'
		),
		'from' => array(
			'validate' => 'DATE',
			'required'=>true,
		),
		'until' => array(
			'validate' => 'DATE',
			'required'=>true,
		),
		'weeks' => array(
			'validate' => 'INT_POSITIVE',
			'required' => true
		),
	);


	protected $_aJoinTables = [
		self::JOIN_ADDITIONALSERVICES => [
			'table'	=> 'ts_groups_additionalservices',
			'primary_key_field'	=> 'relation_id',
			'static_key_fields' => ['relation' => 'course'],
			'class'	=> 'Ext_Thebing_School_Additionalcost',
			'autoload' => false
		]
	];

	/**
	 * @return Ext_Thebing_Tuition_Course
	 */
	public function getCourse() {
		return Ext_Thebing_Tuition_Course::getInstance($this->course_id);
	}

	/**
	 * @return \TsTuition\Entity\Course\Program
	 */
	public function getProgram() {
		return \TsTuition\Entity\Course\Program::getInstance($this->program_id);
	}

}
