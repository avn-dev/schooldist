{extends file="system/bundles/Gui2/Resources/views/page.tpl"}

{block name="system_head"}
		<link rel="stylesheet" href="/admin/extensions/thebing/css/ac.css?v={\System::d('version')}" />
		<link rel="stylesheet" href="/assets/ts-tuition/css/scheduler.css?v={\System::d('version')}" />
		<link rel="stylesheet" href="/assets/tc/css/communication.css?v={\System::d('version')}" />
		<style>

			.room_container .room_content,
			.room_container .room_content_inactive {
				width: {\System::d('ts_scheduling_block_width', 120) - 3}px;
			}

		</style>
{/block}

{block name="system_footer"}

			<script src="/assets/ts-tuition/js/scheduling.js?v={\System::d('version')}"></script>

			<script type="text/javascript" src="/admin/extensions/thebing/tuition/js/tuition_scrollable.js?v={\System::d('version')}"></script>
			<script type="text/javascript" src="/admin/extensions/thebing/tuition/js/tuition.js?v={\System::d('version')}"></script>

			<script type="text/javascript" src="/admin/extensions/tc/gui2/gui2.js?v={\System::d('version')}"></script>
			<script type="text/javascript" src="/admin/extensions/thebing/gui2/util.js?v={\System::d('version')}"></script>

			<script type="text/javascript" src="/admin/extensions/gui2/jquery/jquery.visible.js?v={\System::d('version')}"></script>

			<script type="text/javascript" src="/admin/extensions/thebing/tuition/js/planification/students.js?v={\System::d('version')}"></script>
			
			<script type="text/javascript" src="/admin/extensions/thebing/tuition/js/planification.js?v={\System::d('version')}"></script>
			<script type="text/javascript" src="/admin/extensions/thebing/tuition/js/classes.js?v={\System::d('version')}"></script>
			<script type="text/javascript" src="/admin/extensions/thebing/js/communication.js?v={\System::d('version')}"></script>
			<script type="text/javascript" src="/admin/extensions/tc/js/communication.js?v={\System::d('version')}"></script>
			<script type="text/javascript" src="/admin/extensions/tc/js/communication_gui.js?v={\System::d('version')}"></script>

			<script type="text/javascript" src="/admin/js/gui/gui.js"></script>
			<script type="text/javascript" src="/admin/js/gui/table.js"></script>
			
			<script type="text/javascript">

			var iDefaultWeek = {$iCurrentWeekStart|number_format:0:"":""};
			var iStartDay = {$oSchool->course_startday|number_format:0:"":""};

			var sGuiHash = '{$oGui->hash}';
			var sGuiInstanceHash = '{$oGui->instance_hash}';
			var sTopGuiHash;
			var aGUI = [];

			function initPage() {

				aGUI['{$oGui->hash}'] = new Classes('{$oGui->hash}', 1, 1);
				aGUI['{$oGui->hash}'].instance_hash = '{$oGui->instance_hash}';
				aGUI['{$oGui->hash}'].request('&task=translations');

				aGUI['{$oGuiUnallocatedStudents->hash}'] = new {$oGuiUnallocatedStudents->class_js}('{$oGuiUnallocatedStudents->hash}', 1, {\System::d('debugmode')}, '{$oGuiUnallocatedStudents->instance_hash}', 0);
				aGUI['{$oGuiUnallocatedStudents->hash}'].instance_hash = '{$oGuiUnallocatedStudents->instance_hash}';
				aGUI['{$oGuiUnallocatedStudents->hash}'].name = '{$oGuiUnallocatedStudents->name}';
				aGUI['{$oGuiUnallocatedStudents->hash}'].sLanguage = '{\System::d('systemlanguage')}';
				aGUI['{$oGuiUnallocatedStudents->hash}'].bPageTopGui = true;
				aGUI['{$oGuiUnallocatedStudents->hash}'].sView = 'unallocated';
				aGUI['{$oGuiUnallocatedStudents->hash}'].setPageEvents();
				aGUI['{$oGuiUnallocatedStudents->hash}'].loadTable(true);

				aGUI['{$oGuiAllocatedStudents->hash}'] = new {$oGuiAllocatedStudents->class_js}('{$oGuiAllocatedStudents->hash}', 1, {\System::d('debugmode')}, '{$oGuiAllocatedStudents->instance_hash}', 0);
				aGUI['{$oGuiAllocatedStudents->hash}'].instance_hash = '{$oGuiAllocatedStudents->instance_hash}';
				aGUI['{$oGuiAllocatedStudents->hash}'].name = '{$oGuiAllocatedStudents->name}';
				aGUI['{$oGuiAllocatedStudents->hash}'].sLanguage = '{\System::d('systemlanguage')}';
				aGUI['{$oGuiAllocatedStudents->hash}'].bPageTopGui = true;
				aGUI['{$oGuiAllocatedStudents->hash}'].sView = 'allocated';
				aGUI['{$oGuiAllocatedStudents->hash}'].setPageEvents();
				aGUI['{$oGuiAllocatedStudents->hash}'].loadTable(true);

				aGUI['{$oGuiUnallocatedStudents->hash}'].loadingIndicator =  'loading_unallocated';
				aGUI['{$oGuiAllocatedStudents->hash}'].loadingIndicator =  'loading_allocated';

				var oTuitionGui = new Tuition();
				oTuitionGui.addTranslation('show_more_options', '{\Util::getEscapedString(\L10N::t('weitere Optionen anzeigen','Thebing » Tuition » Planification'), 'javascript')}');
				oTuitionGui.addTranslation('hide_more_options', '{\Util::getEscapedString(\L10N::t('weitere Optionen ausblenden','Thebing » Tuition » Planification'), 'javascript')}');
				oTuitionGui.toogleFilterBar();
			}

			</script>

			{* TODO: Die Verwendung muss mal überprüft werden, da die meistens Styles nicht mehr benutzt zu werden scheinen *}
			<style>
				.selectedRow {
					background-color: {\Ext_Thebing_Util::getColor('selected')} !important;
				}
				.publicHoliday {
					background-color: {$aAbsenceCategoryColors[-1]} !important;
				}
				.schoolHoliday {
					background-color: {$aAbsenceCategoryColors[-2]} !important;
				}
				.publicHoliday.sortasc {
					background-color: #00cccc !important;
				}
				.schoolHoliday.sortasc {
					background-color: #00eeee !important;
				}
				.holidayLegend {
					position    : relative; 
					margin-left : 10px; 
					float       : left; 
				}
				.holidayLegend.small {
					margin-left : 0px; 
					font-size   : 10px;
				}
				.holidayLegendText {
					position    : relative; 
					float       : left; 
					margin-top  : 4px;
				}
				.holidayLegendBullet {
					position    : relative; 
					float       : left; 
					text-align  : center;
					margin-left : 5px;
					margin-right: 10px;
					margin-top  : 4px;
					width       : 12px;
					height      : 10px;
					border      : 1px dotted #CCCCCC;
				}
				.small .holidayLegendBullet {
					margin-top  : default;
				}
				.small .holidayLegendBullet {
					margin-left : 0px;
					margin-right: 3px;
					width       : 8px;
					height      : 7px;
				}
				.small .holidayLegendText {
					position    : relative; 
					float       : left; 
					margin-top  : 0px;
				}
				.row_edit2{
					clear:both;
					padding: 3px;
				}
				.copyDialogTable th,
				.copyDialogTable td {
					vertical-align: middle !important;
				}
			</style>
{/block}
