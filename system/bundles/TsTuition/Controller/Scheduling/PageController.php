<?php

namespace TsTuition\Controller\Scheduling;

use TsTuition\Helper\State;

class PageController extends \MVC_Abstract_Controller {

	protected $_sAccessRight = 'thebing_tuition_planificaton';

	protected $_sViewClass = '\MVC_View_Smarty';

	static public function getStudentGui2($sType, $sHash, $iSchoolId, $bSkipTranslation = false) {

		$oGui2 = new \Ext_Thebing_Gui2($sHash, 'Ext_Thebing_School_Tuition_Block_Students_Gui2');
		$oGui2->column_sortable		= 1;
		$oGui2->row_sortable		= 0;
		$oGui2->multiple_selection	= 1;
		$oGui2->load_admin_header	= 0;
		$oGui2->bSkipTranslation = $bSkipTranslation;
		if($sType === 'unallocated') {
			$oGui2->rows_clickable = 0;
		}
		$oGui2->gui_description = 'Thebing » Tuition » Planification';
		$oGui2->class_js = 'Students';
		$oGui2->sView = $sType;
		$oGui2->query_id_column = 'inquiry_course_id';
		$oGui2->access = ['thebing_tuition_planificaton', ''];

		if($sType === 'unallocated') {
			$oGui2->encode_data = array('inquiry_course_id', 'program_service_id');
		} else {
			$oGui2->encode_data = array('inquiry_course_id', 'program_service_id', 'allocation_id');
            $oGui2->setOption('communication_encode_field', 'allocation_id');

            $oGui2->setOption('communication_model_class', \Ext_Thebing_School_Tuition_Allocation::class);
            $oGui2->setOption('communication_encode_model_id', 'allocation_id');
		}

		$oGui2->row_style = new \Ext_Thebing_Gui2_Style_School_Tuition_Row();
		$oGui2->row_multiple_checkbox = new \Ext_Thebing_Gui2_MultipleCheckbox_Tuition_Students();
		$oGui2->sSection = 'student_record_course';

		$oGui2->additional_sections = [
			[
				'section' => 'student_record_journey_course',
				'primary_key' => 'inquiry_course_id'
			],
			[
				'section' => 'placementtests_results',
				'primary_key' => 'placementtest_result_id',
			],
		];

		$oGui2->setWDBasic('Ext_Thebing_School_Tuition_Block_Students');
		$oGui2->setTableData('where', array(
				'ts_i_j.school_id'	=> $iSchoolId
		));
		$oGui2->setTableData('limit', 30);
		$oGui2->setTableData('orderby', array('cdb1.lastname'=>'ASC', 'cdb1.firstname'=>'ASC'));

		$oData = $oGui2->getDataObject(); /** @var \Ext_Thebing_School_Tuition_Block_Students_Gui2 $oData */
		$oData->initColumns();

		return $oGui2;
	}

	public function ViewAction() {

		ob_start();

		/**
		 * Get objects
		 */
		$oSchool	= \Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId	= $oSchool->id;

		/**
		 * Init GUI
		 */
		$oGui					= new \Ext_Thebing_Gui2(md5('thebing_tuition_blocks_classes'), 'Ext_Thebing_Tuition_Class_Gui2');
		$oGui->sView			= 'planification';
		$oGui->access = [$this->_sAccessRight, ''];

		/** @var \Ext_Thebing_Tuition_Class_Gui2 $oDataClass */
		$oDataClass				= $oGui->getDataObject();
		// Daten vorbereiten
		$oDataClass->prepareDialogData();

		//Kursselect vorbereiten
		$aCourses = $oDataClass->getPreparedCourseData();
		$sEmptyString = '--- ' . \L10N::t('Kurs','Thebing » Tuition » Planification') . ' ---';

		$aCourses = \Ext_Thebing_Util::addEmptyItem($aCourses, $sEmptyString);

		//Kurskategorieselect vorbereiten
		$aCourseCategories		= $oSchool->getCourseCategoriesList('select');
		$sEmptyString			= '--- ' . \L10N::t('Kurskategorie','Thebing » Tuition » Planification') . ' ---';
		$aCourseCategories		= \Ext_Thebing_Util::addEmptyItem($aCourseCategories, $sEmptyString);

		// Gruppen-Filter vorbereiten
		$aYesNo = \Ext_Thebing_Util::getYesNoArray(true);

		$sLanguage = \Ext_TC_System::getInterfaceLanguage();
		$oLanguage= new \Tc\Service\Language\Backend($sLanguage);
		$oLanguage->setPath($oGui->gui_description);

		//Status vorbereiten
		$oStateHelper = new State(State::KEY_BINARY, $oLanguage);
		$aState = $oStateHelper->getOptions(false, true);
		$aCourseState = $oStateHelper->getOptions(true, true);

		$aYesNo = \Ext_Thebing_Util::addEmptyItem($aYesNo, '--- ' . \L10N::t('Gruppen', 'Thebing » Tuition » Planification') . ' ---', 'xNullx');

		$aStates				= \Ext_Thebing_Util::addEmptyItem($aState, '--- ' . \L10N::t('Buchungsstatus','Thebing » Tuition » Planification') . ' ---');
		$aCourseStates			= \Ext_Thebing_Util::addEmptyItem($aCourseState, '--- ' . \L10N::t('Kursstatus','Thebing » Tuition » Planification') . ' ---');

		$sInterfaceLanguage		= $oSchool->getInterfaceLanguage();
		$aLevels				= $oSchool->getLevelList(true,$sInterfaceLanguage,1, false, true);
		$sEmptyString			= '--- ' . \L10N::t('Level','Thebing » Tuition » Planification') . ' ---';
		$aLevels				= \Ext_Thebing_Util::addEmptyItem($aLevels, $sEmptyString);
		$aLevelGroups = \Ext_Thebing_Tuition_LevelGroup::getSelectOptions(schools:  [$oSchool]);
		$aLevelGroups = \Ext_Thebing_Util::addEmptyItem($aLevelGroups, '--- '.$oGui->t('Kurssprache').' ---');

		$agencies = $oSchool->getAgencies(true);
		$agencies = \Ext_Thebing_Util::addEmptyItem($agencies, '--- '.$oGui->t('Agenturen').' ---');

		$yesNoCheckinArray = \Ext_Thebing_Util::addEmptyItem(\Ext_TC_Util::getYesNoArray(false), '--- ' . $oGui->t('Eingecheckt') . ' ---');

		$aCustomerStatus		= \Ext_TS_Inquiry_Index_Gui2_Data::getCustomerStatusOptions();
		$sEmptyString			= '--- ' . \L10N::t('Schülerstatus','Thebing » Tuition » Planification') . ' ---';
		$aCustomerStatus		= \Ext_Thebing_Util::addEmptyItem($aCustomerStatus, $sEmptyString);

		$oClient				= \Ext_Thebing_System::getClient();
		$aInboxes				= $oClient->getInboxList(true, true);
		$sEmptyString			= '--- ' . \L10N::t('Inbox', 'Thebing » Tuition » Planification') . ' ---';
		$aInboxes		= \Ext_Thebing_Util::addEmptyItem($aInboxes, $sEmptyString);

		$aDocumentTypeFilter = [
			'' => $oGui->t('Alle Dokumente'),
			'invoice' => $oGui->t('Nur Rechnungen'),
			'proforma' => $oGui->t('Nur Proforma')
		];

		$sUnallocatedHash		= md5('thebing_tuition_blocks_students_unallocated');
		$sAllocatedHash			= md5('thebing_tuition_blocks_students_allocated');

		$oGuiUnallocatedStudents = self::getStudentGui2('unallocated', $sUnallocatedHash, $iSchoolId);
		$oGuiAllocatedStudents = self::getStudentGui2('allocated', $sAllocatedHash, $iSchoolId);

		$sL10NAllGUIs			= \Ext_Gui2::$sAllGuiListL10N;

		$aWeeks = (array)\Ext_Thebing_Util::getWeekOptions(null, 2, $oSchool->course_startday);
		$aWeeks = \Ext_Thebing_Util::addEmptyItem($aWeeks, \L10N::t('bitte wählen','Thebing » Tuition » Planification'));

		$aWeek = \Ext_Thebing_Util::getWeekTimestamps(0, $oSchool->course_startday);
		$iCurrentWeekStart = $aWeek['start'];
		if(empty($_SESSION['tuition']['planification']['week'])){
			$_SESSION['tuition']['planification']['week'] = $iCurrentWeekStart;
		}

		$iNextWeek = strtotime("+1 week", $_SESSION['tuition']['planification']['week']) ;
		$iPrevWeek = strtotime("-1 week", $_SESSION['tuition']['planification']['week']) ;

		$oSelectX = new \Ext_Gui2_Html_Select();
		$oSelectX->name = 'week';
		$oSelectX->id = 'week';
		$oSelectX->class = 'form-control input-sm';
		$oSelectX->onChange = 'preparePlanification(1);';

		$iDefaultValue = $_SESSION['tuition']['planification']['week'];

		foreach($aWeeks as $iWeek => $sWeek) {
			$oOption = new \Ext_Gui2_Html_Option();
			$oOption->value = $iWeek;
			$oOption->setElement($sWeek);

			if($iDefaultValue == $iWeek) {
				$oOption->selected = 'selected';
			}

			$oSelectX->setElement($oOption);
		}

		$sWeekSelect = $oSelectX->generateHTML();

		?>
		<style type="text/css">
		<!--
		.sortasc{
			background-color:#DDFFAC !important;
		}
		-->
		</style>

			<div class="divHeader" id="divHeader">

                <div class="divHeaderSeparator"><div class="header-line"></div></div>

                <div class="clearfix guiTableBars">
                    <div id="divToolbar_0" class="divToolbar form-inline">

                        <div class="grow">
                            <div class="elements-container">

                                <div class="guiBarFilter">
                                    <input type="text" class="form-control input-sm" value="" id="search" onkeyup="prepareFilterSearch();" placeholder="<?=\L10N::t('Suche','Thebing » Tuition » Planification')?>…" />
                                </div>

                                <div class="divToolbarSeparator"></div>

                                <div class="guiBarFilter">
                                    <div class="divToolbarLabel">
                                        <?=\L10N::t('Kurswoche','Thebing » Tuition » Planification')?>
                                    </div>
                                    <?=$sWeekSelect?>
                                </div>

                                <div class="guiBarElement guiBarLink">
                                    <div class="divToolbarIcon" id="toolbar_last_week" onclick="changeWeek('last');">
                                        <i class="fa fa-colored fa-backward"  alt="<?=\L10N::t('Eine Woche zurück','Thebing » Tuition » Planification')?>" title="<?=\L10N::t('Eine Woche zurück','Thebing » Tuition » Planification')?>"></i>
                                    </div>
                                </div>
                                <div class="guiBarElement guiBarLink">
                                    <div class="divToolbarIcon" id="toolbar_current_week" onclick="changeWeek('current');">
                                        <i class="fa fa-colored fa-stop" alt="<?=\L10N::t('Aktuelle Woche','Thebing » Tuition » Planification')?>" title="<?=\L10N::t('Aktuelle Woche','Thebing » Tuition » Planification')?>"></i>
                                    </div>
                                </div>
                                <div class="guiBarElement guiBarLink">
                                    <div class="divToolbarIcon" id="toolbar_next_week" onclick="changeWeek('next');">
                                        <i class="fa fa-colored fa-forward" alt="<?=\L10N::t('Eine Woche vor','Thebing » Tuition » Planification')?>" title="<?=\L10N::t('Eine Woche vor','Thebing » Tuition » Planification')?>"></i>
                                    </div>
                                </div>

                                <div class="divToolbarSeparator"> </div>

                                <div class="guiBarFilter">
                                    <select name="course_filter" id="course_filter" onchange="prepareFilterSearch();" class="form-control input-sm">
                                        <?php foreach($aCourses as $iCourseId => $sCourseName): ?>
                                            <option value="<?php echo $iCourseId ?>">
                                                <?php echo $sCourseName ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="guiBarFilter">
                                    <select name="group_filter" id="group_filter" onchange="prepareFilterSearch();" class="form-control input-sm">
                                        <?php foreach($aYesNo as $iYesNo => $sYesNo): ?>
                                            <option value="<?php echo $iYesNo ?>">
                                                <?php echo $sYesNo ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="guiBarFilter">
                                    <select name="checkin_filter" id="checkin_filter" onchange="prepareFilterSearch();" class="form-control input-sm">
                                        <?php foreach($yesNoCheckinArray as $yesNoCheckinArrayKey => $yesNoCheckinArrayValue): ?>
                                            <option value="<?=$yesNoCheckinArrayKey?>">
                                                <?=$yesNoCheckinArrayValue?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <!--<div class="clearfix"></div>-->

                                <div class="guiBarFilter">
                                    <select name="course_category_filter" id="course_category_filter" onchange="prepareFilterSearch();" class="form-control input-sm">
                                        <?php foreach($aCourseCategories as $iCourseCategoryId => $sCourseCategoryName): ?>
                                            <option value="<?php echo $iCourseCategoryId ?>">
                                                <?php echo $sCourseCategoryName ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="guiBarFilter">
                                    <select name="week_state_filter" id="week_state_filter" onchange="prepareFilterSearch();" class="form-control input-sm">
                                        <?php foreach($aStates as $sStateKey => $sStateValue): ?>
                                            <option value="<?php echo $sStateKey ?>">
                                                <?php echo $sStateValue ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="guiBarFilter">
                                    <select name="week_course_state_filter" id="week_course_state_filter" onchange="prepareFilterSearch();" class="form-control input-sm">
                                        <?php foreach($aCourseStates as $sStateKey => $sStateValue): ?>
                                            <option value="<?php echo $sStateKey ?>">
                                                <?php echo $sStateValue ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="guiBarFilter">
                                    <select name="level_filter" id="level_filter" onchange="prepareFilterSearch();" class="form-control input-sm">
                                        <?php foreach($aLevels as $iLevelId => $sLevel): ?>
                                            <option value="<?php echo $iLevelId ?>">
                                                <?php echo $sLevel ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="guiBarFilter">
                                    <select name="customer_status_filter" id="customer_status_filter" onchange="prepareFilterSearch();" class="form-control input-sm">
                                        <?php foreach($aCustomerStatus as $iStatusId => $sCustomerStatus): ?>
                                            <option value="<?php echo $iStatusId ?>">
                                                <?php echo $sCustomerStatus ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="guiBarFilter">
                                    <select name="inbox_filter" id="inbox_filter" onchange="prepareFilterSearch();" class="form-control input-sm">
                                        <?php foreach($aInboxes as $iInboxId => $sInbox): ?>
                                            <option value="<?php echo $iInboxId ?>">
                                                <?php echo $sInbox ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <?php
                                    if((int)$oClient->show_customer_without_invoice !== 2) {
                                ?>
                                <div class="guiBarFilter">
                                    <select name="document_type_filter" id="document_type_filter" onchange="prepareFilterSearch();" class="form-control input-sm">
                                        <?php foreach($aDocumentTypeFilter as $sKey => $sValue): ?>
                                            <option value="<?php echo $sKey ?>">
                                                <?php echo $sValue ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <?php
                                    }
                                ?>

                                <div class="guiBarFilter">
                                    <select name="levelgroup_filter" id="levelgroup_filter" onchange="prepareFilterSearch();" class="form-control input-sm">
                                        <?php foreach($aLevelGroups as $iLevelGroupId => $sLevelGroup): ?>
                                            <option value="<?= $iLevelGroupId ?>">
                                                <?= $sLevelGroup ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="guiBarFilter">
                                    <select name="agency_filter" id="agency_filter" onchange="prepareFilterSearch();" class="form-control input-sm">
                                        <?php foreach($agencies as $agencyId => $agency): ?>
                                            <option value="<?= $agencyId ?>">
                                                <?= $agency ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="divToolbarIcon">
                                    <img src="/admin/media/spacer.gif" height="16" width="1" alt="" />
                                </div>

                                <div class="guiBarElement">
                                    <div class="divToolbarIcon">
                                        <i id="loading_indicator" class="fa fa-colored fa-spinner fa-pulse" style="display: none;"></i>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="flex-none toggle-bar"></div>

                    </div>

                    <!--<div class="divCleaner"></div>-->

                    <div class="divToolbar form-inline" style="width: 100%;">

                        <div id="toolbar_<?=$sUnallocatedHash?>" style="float:left;width: 58%;">

                            <div class="elements-container">

                                <div id="pagination_container_<?=$sUnallocatedHash?>" style="float:left;"></div>

                                <div style="" class="divToolbarSeparator "> <span class="hidden">::</span> </div>

                                <label style="" class="divToolbarLabelGroup"><?=\L10N::t('Export','Thebing » Tuition » Planification')?></label>

                                <div class="guiBarElement guiBarLink" onclick="executeAction('unallocated', 'export');">
                                    <div class="divToolbarIcon w16" id="toolbar_export">
                                        <i class="fas fa-colored fa-file-csv" alt="<?=\L10N::t('Export CSV','Thebing » Tuition » Planification')?>" title="<?=\L10N::t('Export CSV','Thebing » Tuition » Planification')?>"></i>
                                    </div>
                                <div class="divToolbarLabel">CSV</div></div>

                                <div class="guiBarElement">
                                    <div class="divToolbarIcon">
                                        <i id="loading_unallocated" class="fa fa-colored fa-spinner fa-pulse" style="display: none;"></i>
                                    </div>
                                </div>

                                <div class="divCleaner"></div>
                            </div>
                        </div>

                        <div id="toolbar_<?=$sAllocatedHash?>" style="float:left;">

                            <div class="elements-container">

                                <div id="pagination_container_<?=$sAllocatedHash?>" style="float:left"></div>

                                <div style="" class="divToolbarSeparator "> <span class="hidden">::</span> </div>

                                <?php
                                if(\Ext_Thebing_Access::hasRight(['thebing_tuition_planificaton', 'students_remove'])) {
                                ?>
                                <label style="" class="divToolbarLabelGroup"><?=\L10N::t('Aktionen','Thebing » Tuition » Planification')?></label>

                                <div class="guiBarElement guiBarLink">
                                    <div class="divToolbarIcon" id="toolbar_student_delete">
                                        <i class="fa fa-colored fa-user-times" onclick="executeAction('0', 'student_delete');" alt="<?=\L10N::t('Schüler löschen','Thebing » Tuition » Planification')?>" title="<?=\L10N::t('Schüler löschen','Thebing » Tuition » Planification')?>"></i>
                                    </div>
                                </div>

                                <div class="guiBarElement guiBarLink">
                                    <div class="divToolbarIcon" id="toolbar_student_communication">
                                        <i class="fa fa-colored fa-envelope" onclick="executeAction('0', 'student_communication');" alt="<?=\L10N::t('Kommunikation','Thebing » Tuition » Planification')?>" title="<?=\L10N::t('Kommunikation','Thebing » Tuition » Planification')?>"></i>
                                    </div>
                                </div>

                                <div style="" class="divToolbarSeparator "> <span class="hidden">::</span> </div>
                                <?php
                                }
                                ?>
                                <label style="" class="divToolbarLabelGroup"><?=\L10N::t('Export','Thebing » Tuition » Planification')?></label>

                                <div class="guiBarElement guiBarLink" onclick="executeAction('allocated', 'export');">
                                    <div class="divToolbarIcon w16" id="toolbar_export">
                                        <i class="fas fa-colored fa-file-csv" alt="<?=\L10N::t('Export CSV','Thebing » Tuition » Planification')?>" title="<?=\L10N::t('Export CSV','Thebing » Tuition » Planification')?>"></i>
                                    </div>
                                <div class="divToolbarLabel">CSV</div></div>

                                <div class="guiBarElement">
                                    <div class="divToolbarIcon">
                                        <i id="loading_allocated" class="fa fa-colored fa-spinner fa-pulse" style="display: none;"></i>
                                    </div>
                                </div>

                                <div class="divCleaner"></div>
                            </div>

                        </div>

                    </div>

                </div>

			</div>

			<div id="divStudents" style="position: relative;" class="bg-white rounded-b-md pb-2">
				<div id="divStudentsUnallocatedContainer" style="z-index: 2; position: absolute;">

				</div>
				<div id="divStudentsUnallocated" style="z-index: 1; float: left; width: 58%; overflow: hidden;">
					<script type="text/javascript">
						var sStudentsUnallocatedHash = '<?php echo $sUnallocatedHash; ?>';
					</script>
					<?php
					$oGuiUnallocatedStudents->display([], true);
					?>
				</div>
				<div id="divStudentsAllocated" style="float: right; width: 42%; overflow: hidden;" class="divStudents">
					<script type="text/javascript">
						var sStudentsAllocatedHash = '<?php echo $sAllocatedHash; ?>';
					</script>
					<?php
					$oGuiAllocatedStudents->display([], true);
					?>
				</div>
				<div class="divCleaner"></div>
			</div>

			<div id="Gui2ChildTableDraggable_<?php echo $sUnallocatedHash; ?>" class="Gui2ChildTableDraggable" style="position: relative;"></div>

            <script type="text/javascript">
                <?php
                    $oFloors			= \Ext_Thebing_Tuition_Floors::getInstance();
                    $aFloorBuildings	= $oFloors->getListWithBuildingsByClassrooms();

                    $aFloorOptions = [];
                    if(!empty($aFloorBuildings))
                    {
                        $aFloorBuildings = \Ext_Thebing_Util::addEmptyItem($aFloorBuildings, \L10N::t('Gebäude, Etage','Thebing » Tuition » Planification'));
                        $aFloorOptions = collect($aFloorBuildings)->map(fn ($text, $id) => ['value' => $id, 'text' => $text])->values()->toArray();
                    }
                    echo "var aFloorOptions = ".json_encode($aFloorOptions).";\n";
                ?>
            </script>

			<div id="divLowerContainer" class="bg-white rounded-md mt-2">

                <div class="divHeaderSeparator"><div class="header-line"></div></div>

				<div id="divWeekDaySwitch">

                    <div id="filter-bar" class="filters"></div>

                    <div style="float:left;" class="divToolbarSeparator "> <span class="hidden">::</span> </div>

                    <div class="weekdays-nav">
					<?php

					//echo '<div class="weekDayLabel">'.\L10N::t('Wochentage', 'Thebing » Tuition » Planification').'&nbsp;</div>';
					//echo '<div class="weekDayLabel" style="height: 100%; padding: 0"></div>';


				$aWeekDays = \Ext_Thebing_Util::getDays('%A', null ,$oSchool->course_startday);
				foreach((array)$aWeekDays as $iDay=>$sDay) {
		?>
					<div id="weekday_<?=(int)$iDay?>" class="divButton" onclick="changeWeekDay(<?=(int)$iDay?>);"><?=$sDay?></div>
		<?php
				}
		?>
                    </div>
					<div style="float:left;" class="divToolbarSeparator "> <span class="hidden">::</span> </div>

					<label style="float:left;" class="divToolbarLabelGroup"><?=\L10N::t('Aktionen','Thebing » Tuition » Planification')?></label>

					<?php
					if(\Ext_Thebing_Access::hasRight(['thebing_tuition_planificaton', 'edit'])) {
					?>
					<div class="guiBarElement guiBarLink">
						<div class="divToolbarIcon" id="toolbar_copy">
							<i class="fa fa-colored fa-clone" onclick="executeAction(0, 'copy');" alt="<?=\L10N::t('Folgende Woche mit dieser überschreiben','Thebing » Tuition » Planification')?>" title="<?=\L10N::t('Folgende Woche mit dieser überschreiben','Thebing » Tuition » Planification')?>"></i>
						</div>
					</div>
					<div class="guiBarElement guiBarLink">
						<div class="divToolbarIcon" id="toolbar_replace_data">
							<i class="fa fa-colored fa-exchange" onclick="executeAction(0, 'replace_data');" alt="<?=\L10N::t('Vertauschen von Raum und Lehrern','Thebing » Tuition » Planification')?>" title="<?=\L10N::t('Vertauschen von Raum und Lehrern','Thebing » Tuition » Planification')?>"></i>
						</div>
					</div>
					<?php
					}
					?>

					<?php
					if(\Ext_Thebing_Access::hasRight(['thebing_tuition_planificaton', 'new'])) {
					?>
					<div class="guiBarElement guiBarLink">
						<div class="divToolbarIcon" id="toolbar_new">
							<i class="fa fa-colored fa-plus" onclick="executeAction(0, 'new');" alt="<?=\L10N::t('Neue Klasse anlegen','Thebing » Tuition » Planification')?>" title="<?=\L10N::t('Neue Klasse anlegen','Thebing » Tuition » Planification')?>"></i>
						</div>
					</div>
					<?php
					}
					?>

					<?php
					if(
						\Ext_Thebing_Access::hasRight(['thebing_tuition_planificaton', 'edit']) ||
						\Ext_Thebing_Access::hasRight(['thebing_tuition_planificaton', 'show'])
					) {
					?>
					<div class="guiBarElement guiBarLink">
						<div class="divToolbarIcon" id="toolbar_edit">
							<i class="fa fa-colored fa-pencil" onclick="executeAction(0, 'edit');" alt="<?=\L10N::t('Klasse bearbeiten','Thebing » Tuition » Planification')?>" title="<?=\L10N::t('Klasse bearbeiten','Thebing » Tuition » Planification')?>"></i>
						</div>
					</div>
					<?php
					}
					?>
					<?php
					if(\Ext_Thebing_Access::hasRight(['thebing_tuition_planificaton', 'edit'])) {
					?>
					<div class="guiBarElement guiBarLink">
						<div class="divToolbarIcon" id="toolbar_delete">
							<i class="fa fa-colored fa-remove" onclick="executeAction(0, 'delete');" alt="<?=\L10N::t('Block löschen','Thebing » Tuition » Planification')?>" title="<?=\L10N::t('Block löschen','Thebing » Tuition » Planification')?>"></i>
						</div>
					</div>
					<div class="guiBarElement guiBarLink">
						<div class="divToolbarIcon" id="toolbar_teacher_replace">
							<i class="fa fa-colored fa-retweet" onclick="executeAction(0, 'teacher_replace');" alt="<?=\L10N::t('Ersatzlehrer verwalten','Thebing » Tuition » Planification')?>" title="<?=\L10N::t('Ersatzlehrer verwalten','Thebing » Tuition » Planification')?>"></i>
						</div>
					</div>
					<?php
					}
					?>

					<div style="float:left;" class="divToolbarSeparator "> <span class="hidden">::</span> </div>

					<label style="float:left;" class="divToolbarLabelGroup"><?=\L10N::t('Export','Thebing » Tuition » Planification')?></label>
					<!--
					<div class="guiBarElement guiBarLink">
						<div class="divToolbarIcon" id="toolbar_roomplanpdf">
							<img src="/admin/extensions/thebing/images/tuition_pdf_export.png" onclick="showRoomAllocationByDayPdfView();" alt="<?php #L10N::t('Show room allocations by day as PDF','Thebing » Tuition » Planification')?>" title="<?php #L10N::t('Show room allocations by day as PDF','Thebing » Tuition » Planification')?>" />
						</div>
					</div>
					-->
					<div class="guiBarElement guiBarLink">
						<div class="divToolbarIcon" id="toolbar_exportWeek">
							<i class="fa fa-colored fa-file-excel-o" onclick="executeAction(0, 'exportWeek');" alt="<?=\L10N::t('Export Week','Thebing » Tuition » Planification')?>" title="<?=\L10N::t('Export Week','Thebing » Tuition » Planification')?>"></i>
						</div>
					</div>

					<div class="guiBarElement">
						<div class="divToolbarIcon">
							<i id="week_loading" class="fa fa-colored fa-spinner fa-pulse" style="display: none;"></i>
						</div>
					</div>

					<div class="divCleaner"></div>
				</div>

				<div id="divPlanification" style="float:left;">



				</div>
				<div id="divPlanificationOtherRooms" style="float:left;">


				</div>
				<div class="divCleaner"></div>
			</div>

			<div class="divFooter" id="divFooter_planification">
				<div style="width: 100%;height:35px;" class="divToolbar_planification">
					<?php
						$oLegend = new \Ext_Gui2_Bar_Legend($oGui);
						$oLegend->style = 'height:35px;';
						$oLegend->addTitle(\L10N::t('Schülerliste','Thebing » Tuition » Planification'));
						$oLegend->addInfo(\L10N::t('Schüler mit Minusstunden','Thebing » Tuition » Planification'), \Ext_Thebing_Util::getColor('changed'));
						$oLegend->addInfo(\L10N::t('Starttag ist unterschiedlich','Thebing » Tuition » Planification'), \Ext_Thebing_Util::getColor('inactive_font') . ';font-style:italic;', true);
						$oLegend->addElement('<span style="float:left;margin-right:13px;">|</span>');
						$oLegend->addTitle(\L10N::t('Klassenplanung','Thebing » Tuition » Planification'));
						$oLegend->addInfo(\L10N::t('Staatliche Feiertage','Thebing » Tuition » Planification'), '#66FFFF');
						$oLegend->addInfo(\L10N::t('Schulferien','Thebing » Tuition » Planification'), '#22BBFF');
						echo $oLegend;
					?>
				</div>
			</div>

			<?php
		$oGui->setWDBasic('Ext_Thebing_Tuition_Class');
		$oGui->setTableData('where', array(
				'ktcl.school_id' => $iSchoolId,
				'ktcl.active'	=> 1,
				'ktb.active'	=> 1,
		));

		$oGui->query_id_column		= 'id';
		$oGui->query_id_alias		= 'ktcl';
		$oGui->class_js				= 'Classes';

		//Dialoge
		$oDialogNew					= $oDataClass->getDialogNew();
		$oDialogEdit				= $oDataClass->getDialogEdit();
		$oDialogDailyComments = $oDataClass->getDialogDailyComments();
		$oDialogCopy				= $oDataClass->getDialogCopy();
		$oDialogReplace				= $oDataClass->getDialogCopy(true);

		// Buttons
		$oBar			= $oGui->createBar();
		$oBar->width	= '100%';
		/*$oLabelGroup	= $oBar->createLabelGroup($oGui->t('Aktionen'));
		$oBar->setElement($oLabelGroup);*/
		$oIcon			= $oBar->createNewIcon($oGui->t('Neuer Eintrag'), $oDialogNew, $oGui->t('Neuer Eintrag'));
		$oBar->setElement($oIcon);
		$oIcon			= $oBar->createEditIcon($oGui->t('Editieren'), $oDialogEdit, $oGui->t('Editieren'));
		$oBar->setElement($oIcon);
		$oIcon			= $oBar->createDeleteIcon($oGui->t('Löschen'), $oGui->t('Löschen'));
		$oBar->setElement($oIcon);

		$oIcon					= $oBar->createIcon('f', 'openDialog');
		$oIcon->action			= 'copy';
		$oIcon->dialog_data		= $oDialogCopy;
		$oIcon->dialog_title	= $oGui->t('Kopieren');
		$oBar->setElement($oIcon);
		$oGui->setBar($oBar);

		$oIcon					= $oBar->createIcon('g', 'openDialog');
		$oIcon->action			= 'copy';
		$oIcon->additional		= 'replace_data';
		$oIcon->dialog_data		= $oDialogReplace;
		$oIcon->dialog_title	= $oGui->t('Vertauschen von Räumen und Lehrern');
		$oBar->setElement($oIcon);

		$oIcon					= $oBar->createIcon('g', 'openDialog');
		$oIcon->action			= 'daily_comments';
		$oIcon->dialog_data		= $oDialogDailyComments;
		$oIcon->dialog_title	= $oGui->t('Tägliche Kommentare');
		$oBar->setElement($oIcon);

		$oGui->setBar($oBar);

		ob_start();
		$oGui->display();
		$aBarList = $oGui->getBarList();
		foreach((array)$aBarList as $iKey => $oBar){
			$oBar->getRequestBarData(array(), $aData['body'], $oGui);
		}
		$oGui->save();
		ob_end_clean();

		$aAbsenceCategories = \Ext_Thebing_Absence_Category::getList();
		$aAbsenceCategoryColors = \Ext_Thebing_Util::convertArrayForSelect($aAbsenceCategories, 'color');

		$sHtml = ob_get_clean();

		$oGui2Html = new \Ext_Gui2_Html($oGui);

		$this->set('sHtml', $sHtml);
		$this->set('oGui', $oGui);
		$this->set('oGuiUnallocatedStudents', $oGuiUnallocatedStudents);
		$this->set('oGuiAllocatedStudents', $oGuiAllocatedStudents);
		$this->set('aOptions', $oGui2Html->generateHtmlHeader());
		$this->set('aAbsenceCategoryColors', $aAbsenceCategoryColors);
		$this->set('sJs', $oGui2Html->getJsFooter());

		$this->set('iCurrentWeekStart', $iCurrentWeekStart);
		$this->set('oSchool', $oSchool);

		$sTemplate = 'system/bundles/TsTuition/Resources/views/scheduling.tpl';

		$this->_oView->setTemplate($sTemplate);

	}

}
