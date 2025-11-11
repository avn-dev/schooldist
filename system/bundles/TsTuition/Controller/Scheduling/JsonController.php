<?php

namespace TsTuition\Controller\Scheduling;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use PhpOffice\PhpSpreadsheet;

class JsonController extends \MVC_Abstract_Controller {

	protected $_sAccessRight = 'thebing_tuition_planificaton';

	protected $oMonitoringService;
	
	protected $aTransfer = [];

	public function beforeAction($sAction=null) {
		
		$aData = array(
			'hash' => 'tuition_planification',
			'task' => $sAction
		);
		$this->oMonitoringService = new \Gui2\Service\MonitoringService($aData);

	}

	public function afterAction($sAction=null) {

		if($this->_oRequest->get('debug')) {

			__out(microtime(true) - $iRuntimeStart);

			global $session_data;
			__out($session_data['queryhistory']);
			$iTotalDBTime = 0;
			$aQueryDiff = array();
			foreach((array)$session_data['queryhistory'] as $iKey => $aData){

				try {
					$sExplain = \DB::getQueryData('EXPLAIN '.$aData['query']);
				} catch (\Exception $exc) {
					$sExplain = '';
				}

				$iTotalDBTime += $aData['duration'];

				$sKey = md5($aData['query']);
				$aQueryDiff[$sKey]['query'] = $aData['query'];
				$aQueryDiff[$sKey]['count']++;
				$aQueryDiff[$sKey]['duration'] += $aData['duration'];
				$aQueryDiff[$sKey]['class'][] = $aData['class'];
				$aQueryDiff[$sKey]['class'] = array_unique($aQueryDiff[$sKey]['class']);
				$aQueryDiff[$sKey]['explain'] = $sExplain;

			}

			usort($aQueryDiff, function($a, $b){ 
				if($a['count'] > $b['count']){
					return -1;
				} else if($a['count'] < $b['count']){
					return 1;
				} else {
					return 0;
				}
			});
			__out($iTotalDBTime);
			__out($aQueryDiff); 

		}

		\Core\Facade\SequentialProcessing::execute();

		if(
			isset($this->aTransfer['success']) &&
			$this->aTransfer['success'] == 1
		) {
			$bSuccess		= \Ext_Gui2_Index_Stack::executeCache();

			if($bSuccess) {
				$bSuccess = \Ext_Gui2_Index_Stack::save();
			}

			if(!$bSuccess) {
				$this->aTransfer['success'] = 0;
				$this->aTransfer['message'] = \L10N::t('Der Index konnte nicht generiert werden!','Thebing » Tuition');	
			}
		}

		$this->setTransfer($this->aTransfer);
		
		$this->oMonitoringService->save();
		
	}

	public function LoadWeekAction() {

		$iWeek = $this->_oRequest->get('week');
		$bTableInitialized = (bool)$this->_oRequest->get('table_initialized');

		if(
			$this->_oRequest->exists('floor') &&
			(int)$this->_oRequest->get('floor') === 0
		) {
			$bTableInitialized = false;
		}

		$oWeek = new \WDDate($iWeek); /** @var @deprecated $dWeekCheck */
		$dWeek = \Core\Helper\DateTime::createFromLocalTimestamp($iWeek);

		$iSelectedDay = (int)$this->_oRequest->get('day');
		$_SESSION['tuition']['planification']['week'] = $iWeek;
		$_SESSION['tuition']['planification']['selected_day'] = $iSelectedDay;

		$oSchool = \Ext_Thebing_School::getSchoolFromSession();

		$aWeek = \Ext_Thebing_Util::getWeekTimestamps($iWeek);
		$iTime = $aWeek['start'] + (60*60*24*($iSelectedDay-1));

		$aHolidays = $oSchool->getHolidays($aWeek['start'], $aWeek['end'], true, true);

		$oDay = new \WDDate($iTime);
		$sDate = $oDay->get(\WDDate::DB_DATE);

		if(
			isset($aHolidays[$sDate])
		) {
			if($aHolidays[$sDate] == -2) {
				$sStyleClass = " schoolHoliday " ;
			}
			if($aHolidays[$sDate] == -1) {
				$sStyleClass = " publicHoliday " ;
			}
		} 

		$aDays = array();
		for ($iLoopDay=0; $iLoopDay < 7; $iLoopDay++) {

			$iTime = $aWeek['start'] + (60*60*24*($iLoopDay));

			$oDay = new \WDDate($iTime);
			$sDate = $oDay->get(\WDDate::DB_DATE);

			$sStyleClassName = "";
			if(
				isset($aHolidays[$sDate])
			) {
				if($aHolidays[$sDate] == -2) {
					$sStyleClassName = " schoolHoliday " ;
				}
				if($aHolidays[$sDate] == -1) {
					$sStyleClassName = " publicHoliday " ;
				}
			}

			$aDays[$iLoopDay] = $sStyleClassName;

		}
		$this->aTransfer['aHolidays'] = $aDays;

		// if copy last week
		if($this->_oRequest->get('copy_last_week') == 1) {

			list($dWeekLast, $bDiffCheck) = self::checkCopyLastWeek($dWeek, $oSchool);

			if(
				!empty($dWeekLast) &&
				$bDiffCheck
			) {

				$aClasses = \Ext_Thebing_Tuition_Class::getClassesForWeek($dWeekLast->format('Y-m-d'));

				/** @var \Ext_Thebing_Tuition_Class $oTuitionClass */
				foreach($aClasses as $oTuitionClass) {

					$aBlocks = $oTuitionClass->getBlocks($dWeekLast->format('Y-m-d'), true);

					if(!empty($aBlocks)) {

						$iWeekCount			= $oTuitionClass->getCurrentWeek($iWeek);
						$iTuitionBlockIdOne	= reset($aBlocks);
						$oTuitionBlockOne	= \Ext_Thebing_School_Tuition_Block::getInstance($iTuitionBlockIdOne);
						$iCurrentLevel		= $oTuitionBlockOne->level_id;

						$aBlockSaveData = $oTuitionClass->prepareBlockSaveDataArray($aBlocks, true);

						$oTuitionClass->setCurrentWeek($dWeekLast->getTimestamp());
						$oTuitionClass->weeks			= $iWeekCount;
						$oTuitionClass->current_level	= $iCurrentLevel;
						$oTuitionClass->setSaveBlocks($aBlockSaveData);
						$mSuccess = $oTuitionClass->save();

						// Wenn ein Fehler aufgetreten ist
						if(
							is_array($mSuccess) || 
							false === $mSuccess
						) {

							// Wenn es ein softer Fehler ist
							if(true === $oTuitionClass->bCanIgnoreErrors) {

								$oTuitionClass->weeks = $iWeekCount;
								$oTuitionClass->ignore_errors = 1;
								$mSuccess = $oTuitionClass->save();

							} else {

								$oHelper = new \Ext_Thebing_Tuition_Class_Helper_ErrorMessage($oTuitionClass);		

								$sError = '';

								foreach($mSuccess as $sKey => $aError) {	
									$sErrorKey = reset($aError);
									$sErrorMessage = $oHelper->getErrorMessage($sErrorKey, $sKey);
									if($oHelper->bFound) {
										$sError .= $sErrorMessage . "\n";
									}					

								}

								$this->aTransfer['errors'] = $sError;							
							}
						}

					}
				}
			}

		}

		// get rooms
		$aRooms = $oSchool->getClassRooms(false, $oWeek->get(\WDDate::DB_DATE), true, $this->_oRequest->get('floor'));
		$iRooms = count($aRooms);
		//falls Floorfilter existiert, muss der Content erneut gebildet werden
		if(!empty($this->_oRequest->get('floor'))){
			$bTableInitialized = false;
		}

		//get blocks

		// habe floor filter hinzu ergänzt, keine Ahnung warum unten !array_key_exists($aItem['room_id'], $aRooms) 
		// Bedingung abgefragt und dafür gesorgt wurde, dass zugewiesene immer angezeigt wurden und floor filter ignoriert wurden (#3778)
		$aBlockData = $oSchool->getWeekBlocks($aWeek['start'], $aWeek['end'], $aRooms, $iSelectedDay, $this->_oRequest->get('floor'));

		$aVirtualClassRooms = array();
		$aWithoutClassRooms = array();
		$aBlocks = array();

		foreach((array)$aBlockData as $aItem) {

			$aItem['from'] = \Ext_Thebing_Util::convertTimeToSeconds($aItem['from']);
			$aItem['until'] = \Ext_Thebing_Util::convertTimeToSeconds($aItem['until']);

			$iKey = $aItem['from'];
			// change from to full quarter
			if(fmod($iKey / 60 / 15, 1) > 0) {
				$iKey = $iKey - round(fmod($iKey / 60 / 15, 1) * 15 * 60);
			}

			/**
			 * @todo: Klassen auf Räumen effizient verteilen
			 */
			if($aItem['room_id'] == 0) {

				$aItem['room_type'] = 'no_room';

				if(empty($aWithoutClassRooms)) {
					$aWithoutClassRooms[] = array($iKey=>$aItem);
				} else {

					foreach((array)$aWithoutClassRooms as $iColumn => $aBlockDataWithoutClassRomm) {
						$bInsert = true;
						foreach((array)$aBlockDataWithoutClassRomm as $iBlockId => $aBlock) {

							if(
								$aBlock['until'] >= $aItem['from'] &&
								$aItem['until'] >= $aBlock['from']
							) {
								$bInsert = false;
							}

						}

						if($bInsert == true) {
							$aWithoutClassRooms[$iColumn][$iKey] = $aItem;
							break;
						}

					}

					if(!$bInsert) {
						$aWithoutClassRooms[count($aWithoutClassRooms)] = array($iKey=>$aItem);
					}

				}

			} elseif($aItem['room_id'] < 0) {
				$aItem['room_type'] = 'virtual';
				$aVirtualClassRooms[] = array($iKey => $aItem);
			} else {
				if(
					!array_key_exists($aItem['room_id'], $aRooms)
				){
					$oRoom = \Ext_Thebing_Tuition_Classroom::getInstance($aItem['room_id']);
					$aRooms[] = $oRoom;
				}
				$aBlocks[$aItem['room_id']][$iKey] = $aItem;
			}

		}

		$aOtherRooms					= array_merge($aWithoutClassRooms,$aVirtualClassRooms);
		$iOtherRooms					= count($aOtherRooms);

		$this->aTransfer['count_other_rooms']	= $iOtherRooms;
		$this->aTransfer['timestamps']		= $aWeek;

		$bOtherRooms = false;
		if($iOtherRooms > 0) {
			$bOtherRooms = true;
		}

		if(!$bTableInitialized) {
			$this->aTransfer['html']				.= \Ext_Thebing_School_Tuition_Block_Content::getRoomHeadContent($oSchool, $aRooms);
		} else {
			$this->aTransfer['container'] = array();
		}

		if($bOtherRooms) {
			$this->aTransfer['html_other_rooms'] .= \Ext_Thebing_School_Tuition_Block_Content::getRoomHeadContent($oSchool, $aOtherRooms,'other');
		}

		$aClassesTimes = $oSchool->getClassTimes();

		$iClassesTime = 0;
		foreach((array)$aClassesTimes as $oClassesTime) {

			$iStart = $oClassesTime->getFromSeconds();
			$iEnd = $oClassesTime->getUntilSeconds();

			// get time rows
			$aTimeRows = \Ext_Thebing_Util::getTimeRows('assoc', $oClassesTime->interval, $iStart, $iEnd, false);
			$iTimeRows = count($aTimeRows);

			$bFirst = 1;

			if(!$bTableInitialized) {
				$iColspan = (int)($iRooms + 1);
				$this->aTransfer['html'] .= \Ext_Thebing_School_Tuition_Block_Content::getClassTimesContent($iClassesTime, $aTimeRows, $iColspan, '');
			}

			if($bOtherRooms) {
				$iColspan = (int)($iOtherRooms + 1);
				$this->aTransfer['html_other_rooms'] .= \Ext_Thebing_School_Tuition_Block_Content::getClassTimesContent($iClassesTime, $aTimeRows, $iColspan, 'Other');
			}

			$iTotalHeight = (($iTimeRows) * 22 - 1);

			foreach((array)$aRooms as $oRoom) {

				if(!$bTableInitialized) {
					$this->aTransfer['html'] .= '<td style="margin: 0px; padding: 0px;">';
					$this->aTransfer['html'] .= '<div class="room_container'.$sStyleClass.'" style="height: '.$iTotalHeight.'px;" id="room_container_'.$oClassesTime->id.'_'.$oRoom->id.'">';
				}

				foreach((array)$aBlocks[$oRoom->id] as $iTime => $aBlock) {

					if($bTableInitialized) {
						$this->aTransfer['html'] = '';
					}

					$this->aTransfer['html'] .= \Ext_Thebing_School_Tuition_Block_Content::getBlockHtml($aBlock, $sStyleClass, $iStart, $iEnd, $oClassesTime->interval, $iSelectedDay);

					if(!isset($this->aTransfer['container'][$oClassesTime->id][$oRoom->id])){
						$this->aTransfer['container'][$oClassesTime->id][$oRoom->id] = '';
					}
					$this->aTransfer['container'][$oClassesTime->id][$oRoom->id] .= $this->aTransfer['html'];

				}

				if(!$bTableInitialized) {
					$this->aTransfer['html'] .= '</div></td>';
				}

			}

			foreach($aOtherRooms as $iKey => $aRoomGroupedData)
			{
				$aItem = reset($aRoomGroupedData);
				$sType = $aItem['room_type'];

				if($bOtherRooms) {
					$this->aTransfer['html_other_rooms'] .= '<td style="margin: 0px; padding: 0px;">';
					$this->aTransfer['html_other_rooms'] .= '<div class="room_container'.$sStyleClass.'" style="height: '.$iTotalHeight.'px;" id="room_container_'.$oClassesTime->id.'_'.$sType.'_'.$iKey.'">';
				}

				foreach($aRoomGroupedData as $iTime => $aBlock)
				{
					#if($bTableInitialized) {
						#$this->aTransfer['html_other_rooms']				= '';
					#}

					$this->aTransfer['html_other_rooms'] .= \Ext_Thebing_School_Tuition_Block_Content::getBlockHtml($aBlock, $sStyleClass, $iStart, $iEnd, $oClassesTime->interval);
				}

				#if(!$bTableInitialized) {
					$this->aTransfer['html_other_rooms'] .= '</div></td>';
				#}
			}

			if(!$bTableInitialized) {
				$this->aTransfer['html'] .= '</tr>';
			}

			if($bOtherRooms) {
				$this->aTransfer['html_other_rooms'] .= '</tr>';
			}

			$iClassesTime++;

		}

		if(!$bTableInitialized) {
			$this->aTransfer['html'] .= '</tbody>';
			$this->aTransfer['html'] .= '</table>';
		} else {
			unset($this->aTransfer['html']);
			#unset($this->aTransfer['html_other_rooms']);
		}

		if($bOtherRooms) {
			$this->aTransfer['html_other_rooms'] .= '</tbody>';
			$this->aTransfer['html_other_rooms'] .= '</table>';
		}

		$this->aTransfer['block_count'] = count($aBlocks)+count($aWithoutClassRooms)+count($aVirtualClassRooms);

		$this->aTransfer['week'] = $iWeek;

		if(
			$this->aTransfer['block_count'] == 0 &&
			$this->_oRequest->get('check_empty_week') == 1
		) {
			$this->aTransfer['message'] = \L10N::t('In dieser Woche wurden noch keine Kurse angelegt. Sollen die Kurse der letzten Woche dupliziert werden?', 'Thebing » Tuition');
			$this->aTransfer['can_copy_last_week'] = 0;

			list($dWeekLast, $bDiffCheck) = self::checkCopyLastWeek($dWeek, $oSchool);
			if(
				$dWeekLast &&
				$bDiffCheck
			) {
				$this->aTransfer['can_copy_last_week'] = 1;
			}

		}

		$_SESSION['tuition']['roomplanHTML'] = $this->aTransfer['html'];

		// Danach noch die Toolbardaten laden
		$this->CheckToolbarAction();

	}

	public function searchAction(Request $request) {

		if (empty($search = $request->input('search'))) {
			return response()
				->json([]);
		}

		$school = \Ext_Thebing_School::getSchoolFromSession();

		$week = $request->input('week', time());
		$weekday = $request->input('weekday', 0);
		$floorId = (int)$request->input('floor', 0);

		$week = \Core\Helper\DateTime::createFromLocalTimestamp($week);

		$where = "";
		if(!empty($floorId)) {
			$where = " AND `kc`.`floor_id` = :floor_id ";
		}

		$sql = "
			SELECT
				`ktb`.`id`,
				`ktbd`.`day`,
				`ktc`.`name` `class_name`,
				`ktt`.`from` `time_from`,
				`ktt`.`until` `time_until`,
				/*GROUP_CONCAT(CONCAT(`ktbr`.`room_id`, '|', `kc`.`name`)) `rooms`*/
				`ktbr`.`room_id`,
				`kc`.`name` `room_name`
			FROM
			    `kolumbus_tuition_blocks` `ktb` INNER JOIN
			    `kolumbus_tuition_classes` `ktc` ON 
			    	`ktc`.`id` = `ktb`.`class_id` AND
			    	`ktc`.`active` = 1 INNER JOIN
			    `kolumbus_tuition_templates` `ktt` ON 
			    	`ktt`.`id` = `ktb`.`template_id` AND
			    	`ktt`.`active` = 1 INNER JOIN
			    `kolumbus_tuition_blocks_days` `ktbd` ON 
			    	`ktbd`.`block_id` = `ktb`.`id` LEFT JOIN
			    `kolumbus_tuition_blocks_to_rooms` `ktbr` ON 
			    	`ktbr`.`block_id` = `ktb`.`id` LEFT JOIN
				`kolumbus_classroom` `kc` ON
			        `kc`.`id` = `ktbr`.`room_id`AND
			    	`kc`.`idSchool` = :school_id AND
			        `kc`.`active` = 1 LEFT JOIN 
				`ts_teachers` `kt` ON
					`kt`.`id` = `ktb`.`teacher_id` AND
					`kt`.`active` = 1 LEFT JOIN
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
					`ktbic`.`block_id` = `ktb`.`id` AND
					(
					 	`ktbic`.`room_id` = `ktbr`.`room_id` OR
					 	`ktbic`.`room_id` = 0
					) AND
					`ktbic`.`active` = 1 LEFT JOIN
				`ts_inquiries_journeys_courses` `ts_ijc` ON
					`ts_ijc`.`id` = `ktbic`.`inquiry_course_id` AND
					`ts_ijc`.`active` = 1 LEFT JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`id` = `ts_ijc`.`journey_id` AND
					`ts_ij`.`active` = 1 LEFT JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`inquiry_id` = `ts_ij`.`inquiry_id` LEFT JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_itc`.`contact_id`
			WHERE
			    `ktb`.`active` = 1 AND
			    `ktb`.`week` = :week AND
			    `ktb`.`school_id` = :school_id AND
			    (`kc`.`id` IS NULL OR `kc`.`active` = 1)
			    ".$where."
			    AND (
			       `ktc`.`name` LIKE :search OR
			       `kt`.`firstname` LIKE :search OR
			       `kt`.`lastname` LIKE :search OR
			       `tc_c`.`firstname` LIKE :search OR
			       `tc_c`.`lastname` LIKE :search
			    )			    
			GROUP BY
			    `ktb`.`id`, `ktbd`.`day`, `ktbr`.`room_id`
			ORDER BY
			    `ktb`.`id`, `ktbd`.`day`
		";

		$blocks = (array)\DB::getQueryRows($sql, [
			'week' => $week->format('Y-m-d'),
			'school_id' => $school->id,
			'search' => '%'.$search.'%',
			'floor_id' => $floorId
		]);

		$found = [];
		foreach ($blocks as $block) {
			// Nach Wochentagen gruppieren damit nicht unnötig zwischen den Tagen gewechselt wird
			$found[$block['day']][] = [
				'name' => $block['class_name'],
				'description' => sprintf('%s - %s, %s', $block['time_from'], $block['time_until'], $block['room_name'] ?? '-'),
				'weekday' => $block['day'],
				'container' => sprintf('room_content_%d_%d', $block['id'], (int)$block['room_id'])
			];
		}

		$currentWeekday = Arr::only($found, $weekday);
		$found = Arr::except($found, $weekday);

		ksort($found);

		if (!empty($currentWeekday)) {
			// Aktuell Woche in der sich der Benutzer befindet beibehalten um in dieser zuerst die Ergebnisse anzuzeigen
			$found = $currentWeekday + $found;
		}

		return response()
			->json(Arr::flatten($found, 1));
	}

	public function MoveBlockAction() {

		\DB::begin(__METHOD__);

		preg_match('/^room_content_([0-9]+)_(\-?[0-9]+)?$/', $this->_oRequest->get('content_id'), $aContentMatches);
		$iBlockId = (int)$aContentMatches[1];
		$iOldRoomId = (int)$aContentMatches[2];
		
		$sContainerId = $this->_oRequest->get('container_id');
		if(strpos($sContainerId, 'no_room') !== false) {
			$iRoomId = 0;
		} elseif(strpos($sContainerId, 'virtual') !== false) {
			$iRoomId = -1;
		} else {
			preg_match('/^room_container_[0-9]+_([0-9]+)$/', $sContainerId, $aContainerMatches);
			$iRoomId = $aContainerMatches[1];
		}
		
		preg_match('/^room_container_([0-9]+)_/', $sContainerId, $aContainerMatches);
		$iClassTimeId = $aContainerMatches[1];

		$oBlock = \Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
		$oRoom = \Ext_Thebing_Tuition_Classroom::getInstance($iRoomId);
		$oOldRoom = \Ext_Thebing_Tuition_Classroom::getInstance($iOldRoomId);

		if($iOldRoomId == -1) {
			$oOldRoom->online = 1;
		}
		if($iRoomId == -1) {
			$oRoom->online = 1;
		}
		
		if($oOldRoom->isOnline() && !$oRoom->isOnline()) {
		    $mAllowed = 'room_not_online';
        } else if(!$oOldRoom->isOnline() && $oRoom->isOnline() ) {
            $mAllowed = 'room_online';
        } else {

            $aAllocatedRooms = $oBlock->getRoomIds();

            if(
                // Nur wenn wirklich verschoben wurde
                $iRoomId !== $iOldRoomId &&
                // darf nicht über einen anderen Raum des selben Blocks geschoben werden
                !in_array($iRoomId, $aAllocatedRooms)
            ) {
                if(empty($aAllocatedRooms)) {
                    $aNewRooms = [$iRoomId];
                } else if($iRoomId == 0) {
                    $aNewRooms = [];
                } else {
                    // alten Raum durch neuen Raum ersetzen
                    $aNewRooms = array_replace($aAllocatedRooms,
                        array_fill_keys(
                            array_keys($aAllocatedRooms, $iOldRoomId),
                            $iRoomId
                        )
                    );
                }

                $oBlock->setRoomIds($aNewRooms);
            }

            /* checkExistingBlocks(
             * 	$iSchoolId,
             * 	$iRoomId,
             * 	$iTeacherId,
             * 	$iWeek,
             * 	$aDays,
             * 	$iTemplate,
             * 	$iBlock,
             * 	$iLevel,
             * 	$aCourses,
             * 	$bNoCheck
            ) */
            $oClone = clone($oBlock);
			$aClassBlocks = $oBlock->getClass()->getBlocks(strtotime($oBlock->week)); // Muss übergeben werden für Raumprüfung		
            $mAllowed = $oClone->checkExistingBlocks(false, false, [], $aClassBlocks);

            /*
            $mAllowed = \Ext_Thebing_School_Tuition_Block::checkExistingBlocks(
                $oBlock->school_id,
                $oBlock->room_id,
                $oBlock->teacher_id,
                $oBlock->week,
                $oBlock->days,
                $oBlock->template_id,
                $oBlock->id,
                false, // $oBlock->level_id,
                $oBlock->courses
            );*/

            //$iDiff = $oBlock->until - $oBlock->from;

            //$oBlock->from = $iFrom;
            //$oBlock->until = $iFrom + $iDiff;

            // Weiche Fehler rauslöschen damit Blöcke immer verschiebbar sind
            // auser bei fehlern die dan raum betreffen!
            // #2773
            if(is_array($mAllowed)){
                $mAllowed = array_flip($mAllowed);
                unset($mAllowed['course_not_available']);
                unset($mAllowed['teacher_allocated']);
                unset($mAllowed['teacher_holiday']);
                unset($mAllowed['teacher_worktime']);
                unset($mAllowed['teacher_level']);
                unset($mAllowed['teacher_not_valid']);
                unset($mAllowed['teacher_course_category']);
                unset($mAllowed['no_online_room_allocated_students']);
                unset($mAllowed['no_offline_room_allocated_students']);
                $mAllowed = array_flip($mAllowed);
                if(empty($mAllowed)){
                    $mAllowed = true;
                }
            }

        }

		if($mAllowed === true) {

			// Räume der Zuweisungen müssen beim Verschieben auch aktualisiert werden
			$aAllocations = $oBlock->getAllocations();
			foreach ($aAllocations as $oAllocation) {
				if ((int)$oAllocation->room_id === $iOldRoomId) {
					$oAllocation->room_id = $iRoomId;
					$oAllocation->save();
				}
			}

			$oBlock->save();

			$this->aTransfer['success'] = 1;
			$this->aTransfer['message'] = \L10N::t('Speichern erfolgreich!','Thebing » Tuition');

			\DB::commit(__METHOD__);

		} else {

			\DB::rollback(__METHOD__);

			$mAllowed = (array)$mAllowed;
			$this->aTransfer['success'] = 0;
			if(in_array('no_days' , $mAllowed)){
				$this->aTransfer['message'] = \L10N::t('Keine Tage!','Thebing » Tuition');
			}elseif(in_array('no_courses' , $mAllowed)){
				$this->aTransfer['message'] = \L10N::t('Keine Kurse!','Thebing » Tuition');
			//}elseif(in_array('course_not_available' , $mAllowed)){
				//$this->aTransfer['message'] = \L10N::t('Kurs nicht verfügbar!','Thebing » Tuition');
			}elseif(in_array('room_allocated' , $mAllowed)){
				$this->aTransfer['message'] = \L10N::t('Klassenzimmer belegt, bitte wählen Sie ein neues Zimmer!','Thebing » Tuition');
            }elseif(in_array('room_not_online' , $mAllowed)){
                $this->aTransfer['message'] = \L10N::t('Das Klassenzimmer ist nicht für Onlinekurse verfügbar.','Thebing » Tuition');
            }elseif(in_array('room_online' , $mAllowed)){
                $this->aTransfer['message'] = \L10N::t('Das Klassenzimmer ist nur für Onlinekurse verfügbar.','Thebing » Tuition');
			}elseif(in_array('room_multiple_incompatibility' , $mAllowed)){
				$this->aTransfer['message'] = \L10N::t('Ein Block mit mehreren Räumen kann nur in den Einstellungen der Klasse verschoben werden.','Thebing » Tuition');
			//}elseif(in_array('teacher_allocated' , $mAllowed)){
				//$this->aTransfer['message'] = \L10N::t('Lehrer zu der Zeit nicht verfügbar, bitte wählen Sie einen anderen Lehrer aus!','Thebing » Tuition');
			//}elseif(in_array('teacher_holiday' , $mAllowed)){
				//$this->aTransfer['message'] = \L10N::t('Lehrer hat zu der Zeit Ferien, bitte wählen Sie einen anderen Lehrer aus!','Thebing » Tuition');
			//}elseif(in_array('teacher_worktime' , $mAllowed)){
				//$this->aTransfer['message'] = \L10N::t('Lehrer hat zu der Zeit keine Arbeitszeit, bitte wählen Sie einen anderen Lehrer aus!!','Thebing » Tuition');
			//}elseif(in_array('teacher_level' , $mAllowed)){
				//$this->aTransfer['message'] = \L10N::t('Lehrer hat ein anderes Level!','Thebing » Tuition');
			//}elseif(in_array('teacher_not_valid', $mAllowed)){
				//$this->aTransfer['message'] = \L10N::t('Lehrer nicht verfügbar!','Thebing » Tuition');
			}else{
				$this->aTransfer['message'] = \L10N::t('Speichern nicht erfolgreich!','Thebing » Tuition');
			}
		}

	}

	public function PrepareMoveStudentAction() {
	
		// @TODO PHP inklusive JavaScript komplett neu schreiben…

		$oDateFormat = new \Ext_Thebing_Gui2_Format_Date();
		$iDay = (int)$this->_oRequest->get('day');

		$aInquiries = array();

		preg_match('/^inquiry_(un)?allocated_([0-9]+)_([0-9]+)_([0-9]+)$/', $this->_oRequest->get('content_id'), $aContentMatches);
		if($aContentMatches[2]) {
			$aInquiries[(int)$aContentMatches[4]][(int)$aContentMatches[2]][(int)$aContentMatches[3]] = 1;
		}

		preg_match('/^room_content_([0-9]+)_(-?[0-9]+)?$/', $this->_oRequest->get('container_id'), $aContainerMatches);
		$iBlockId = (int)$aContainerMatches[1];
		$iRoomId = (int)$aContainerMatches[2];

		// multi selected inquiries
		if(is_array($this->_oRequest->input('additional_id'))) {
			foreach($this->_oRequest->input('additional_id') as $sId) {
				preg_match('/^checkbox_inquiry_(un)?allocated_([0-9]+)_([0-9]+)_([0-9]+)$/', $sId, $aContentMatches);
				$aInquiries[(int)$aContentMatches[4]][(int)$aContentMatches[2]][(int)$aContentMatches[3]] = 1;
			}
		}

		$this->aTransfer['inquiries'] = array();
		if(is_array($aInquiries)){
			foreach($aInquiries as $iInquiryId=>$aInquiryCourses) {
				foreach($aInquiryCourses as $iInquiryCourseId=>$aCourses) {
					if(is_array($aCourses)){
						foreach($aCourses as $iProgramServiceId=>$iValue) {
							$this->aTransfer['inquiries'][$iInquiryCourseId][$iProgramServiceId] = array('inquiry_course_id'=>$iInquiryCourseId, 'program_service_id'=>$iProgramServiceId, 'check'=>true, 'check_level'=>true);
						}
					}
				}
			}
		}

		$oSelectedBlock = \Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
		$sCurrentWeek = $oSelectedBlock->week;
		$dCurrentWeek = new \DateTime($oSelectedBlock->week);
		$oClass = $oSelectedBlock->getClass();
		$oSchool = $oSelectedBlock->getSchool();

		if($this->_oRequest->get('all_weeks') == 1) {
			$sOperator = '>=';
		} else {
			$sOperator = '=';
		}

		$aBlockedStudents = array();

		$aBlocks = $oClass->getBlocks($sCurrentWeek,true,$sOperator);
		#$aBlocks = $oBlock->getGroupedBlocks();

		$this->aTransfer['block_id'] = $iBlockId;
		$this->aTransfer['room_id']	= $iRoomId;
		$this->aTransfer['success']	= true;

		$aErrorAllocatedGrouped = array();

		$bPaymentError = false;

		$aAllocationCountPerInquiry = [];
		foreach($aInquiries as $iInquiryId => $aJourneyCourses) {
			$aAllocationCountPerInquiry[$iInquiryId] = 1;

			/*
			 * Wenn Zuweisung zu Folgewochen und automatische Zuweisung nach Ferien:
			 * Nachfolgende Kursbuchungen der ausgewählten Kursbuchung (die durch Ferien-Splittung entstanden sind)
			 * ins Array schreiben, damit ein Fehler geschmissen wird, wenn der Schüler nach den Ferien schon zugewiesen ist.
			 */
			if(
				$sOperator === '>=' &&
				$oSchool->tuition_automatic_holiday_allocation
			) {
				foreach(array_keys($aJourneyCourses) as $iJourneyCourseId) {
					$oJourneyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($iJourneyCourseId);
					$aRelatedCourses = $oJourneyCourse->getRelatedServices($dCurrentWeek);
					foreach($aRelatedCourses as $oRelatedJourneyCourse) {
						$aInquiries[$iInquiryId][$oRelatedJourneyCourse->id] = $aJourneyCourses[$iJourneyCourseId];

						// Muss analog zu oben befüllt werden, sonst fehlen für den möglichen 2. Dialog die IDs
						$this->aTransfer['inquiries'][$oRelatedJourneyCourse->id][$iProgramServiceId] = [
							'origin_journey_course_id' => $iJourneyCourseId, // Notwendig für aInquiryCache im JS, sonst stirbt da alles ab
							'inquiry_course_id' => $oRelatedJourneyCourse->id,
							'program_service_id'=> $iProgramServiceId,
							'check' => true,
							'check_level' => true
						];

						// Zähler erhöhen, da Inquiry durch Ferien nun mehrfach im Array vorkommt (siehe $bBlockInquiry)
						$aAllocationCountPerInquiry[$iInquiryId]++;
					}
				}
			}
		}

		foreach((array)$aBlocks as $iBlockId) {

			$oBlock		= \Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);

			$sBlockWeek = $oBlock->week;

			$oDate      = new \DateTime($sBlockWeek);
			$iBlockWeek	= $oDate->getTimestamp();
			foreach($aInquiries as $iInquiryId=>$aInquiryCourses) {

				// Inquiries dürfen bei einer Zuweisung nur 1 Mal vorkommen (oder modifiziert durch Nach-Ferien-Zuweisung)
				$bBlockInquiry = count($aInquiryCourses) > $aAllocationCountPerInquiry[$iInquiryId];

				foreach($aInquiryCourses as $iInquiryCourseId=>$aCourses) {

					$oJourneyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($iInquiryCourseId);
					$bFlexibleAllocation = (bool)$oJourneyCourse->flexible_allocation;

					// Kombikurs-Schüler derselben Kursbuchung dürfen nicht gleichzeitig zugewiesen werden
					if(count($aCourses) > 1) {
						$bBlockInquiry = true;
					}

					/*
					 * Bei flexibler Zuweisung dürfen nur Blöcke des entsprechenden Tags geprüft werden
					 * Bsp.: Klasse hat je Tag einen Block. Schüler wurde schon Montag zugewiesen,
					 * nun würde aber auch ohne continue die Zuweisung auf Montag überprüft werden
					 * und der Schüler wäre niemals nochmal zuweisbar in der Woche. #9025
					 */
					if(
						$bFlexibleAllocation &&
						!in_array($iDay, $oBlock->days)
					) {
						continue;
					}

					// Prüfen, ob der Schüler diesem Block bereits zugewiesen wurde
					if(
						$bBlockInquiry === true || (
							(
								/*
								 * Bei einer flexiblen Zuweisung darf nur der ausgewählte Block auf
								 * doppelte Zuweisung geprüft werden. Ohne das hier könnte man einen
								 * Schüler mit flexibler Klassenplanung nicht zwei verschiedenen Blöcken
								 * einer Klasse individuell zuweisen. #7840
								 */
								!$bFlexibleAllocation ||
								$oBlock->id == $oSelectedBlock->id
							) &&
							$oBlock->isDoubleAllocation($iInquiryCourseId)
						)
					) {
						$this->aTransfer['success'] = false;

						$oClass = $oBlock->getClass();

						$aBlockedStudents[] = array(
							'inquiry_course_id' => $iInquiryCourseId,
							'week' => $oDateFormat->format($sBlockWeek),
							'class' => $oClass->name
						);
						continue;
					}

					foreach((array)$aCourses as $iProgramServiceId => $iValue) {

						// if allocated student, no check if allocated
						if(empty($aContentMatches[1])) {
							$mCheck = $oBlock->checkInquiryCourse($iInquiryCourseId, $iProgramServiceId, $iRoomId,0);
							$this->aTransfer['type'] = 'allocated';
						} else {
							$mCheck = $oBlock->checkInquiryCourse($iInquiryCourseId, $iProgramServiceId, $iRoomId, 1);
							$this->aTransfer['type'] = 'unallocated';
						}

						if($sBlockWeek == $sCurrentWeek) {
							$oProgramService = \TsTuition\Entity\Course\Program\Service::getInstance($iProgramServiceId);
							$oCourse		= $oProgramService->getService();
							$oLevelGroup	= $oCourse->getLevelgroup();
							$mCheckLevel	= $oBlock->checkInquiryCourseLevel($iInquiryCourseId, (int)$oLevelGroup->id);
						} else {
							//Folgewochen nicht nach Level überprüfen
							$mCheckLevel = true;
						}

						// show error or check level change
						if(
							$mCheck !== true ||
							$mCheckLevel === false ||
							$bFlexibleAllocation
						) {
							$this->aTransfer['inquiries'][$iInquiryCourseId][$iProgramServiceId]['check'] = $mCheck;
							$this->aTransfer['inquiries'][$iInquiryCourseId][$iProgramServiceId]['check_level'] = $mCheckLevel;
							$this->aTransfer['inquiries'][$iInquiryCourseId][$iProgramServiceId]['check_flexible_allocation'] = $bFlexibleAllocation;
							$this->aTransfer['success'] = false;
						}

						if($mCheck !== true) {

							if($mCheck == 'payments_exists'){
								$bPaymentError = true;
							}

							$sTempError = \L10N::t('Schüler "%s" in der Woche "%week" (%class)', 'Thebing » Tuition');
							$aWeek	= \Ext_Thebing_Util::getWeekTimestamps($iBlockWeek);
							$sStart = \Ext_Thebing_Format::LocalDate($aWeek['start']);
							$sEnd	= \Ext_Thebing_Format::LocalDate($aWeek['end']);
							if(
								$mCheck == 'allocated' || 
								$mCheck == 'payments_exists'
							) {
								$aAllocations = $oBlock->getInquiryCourseAllocations($iInquiryCourseId);

								$aAllocationInfo = array();
								if($mCheck=='allocated'){
									foreach((array)$aAllocations as $aAllocation) {
										$sInfo = $aAllocation['class_name'];
										if($aAllocation['room_name']) {
											$sInfo .= ', '.$aAllocation['room_name'];
										}
										$aAllocationInfo[] = $sInfo;
									}
								}else{
									$oClass			= $oBlock->getClass();
									$sClassName		= $oClass->name;
									$aAllocationInfo[] = $sClassName;
								}

								$sTempError = str_replace('%week', $sStart.' - '.$sEnd, $sTempError);
								$sTempError = str_replace('%class', implode('; ', $aAllocationInfo), $sTempError);
								$aErrorAllocatedGrouped[$iBlockWeek][$iInquiryCourseId][$iProgramServiceId] = $sTempError;
							}
						}
					}
				}
			}
		}

		$aTemp = $this->aTransfer['inquiries'];
		$this->aTransfer['inquiries'] = array();
		foreach((array)$aTemp as $iInquiryCourseId=>$aCourses) {
			foreach((array)$aCourses as $aCourse) {
				$this->aTransfer['inquiries'][] = $aCourse;
			}
		}

		if($this->aTransfer['success']) {
			$this->aTransfer['lang']['save']	= \L10N::t('save', 'Thebing » Tuition');
			$this->aTransfer['lang']['save']	= \L10N::t('save', 'Thebing » Tuition');
			$this->aTransfer['all_weeks']		= (int)$this->_oRequest->get('all_weeks');
		} else {
			if(!empty($aBlockedStudents)) 
			{
				// Wenn Schüler bereits zugewiesen wurde, dürfen die Checkboxen (Leveländerung, flexible Zuweisung) nicht erscheinen
				// TODO Hier müsste mal eingebaut werden, dass ein Schüler nicht hundert Mal angezeigt wird (gleicher Tuition-Kurs und gleiche Woche, aber unerschiedliche Kursbuchungen)
				foreach($aBlockedStudents as &$aBlockedStudent) {
					foreach($this->aTransfer['inquiries'] as &$arTmpInquiry) {
						if($aBlockedStudent['inquiry_course_id'] == $arTmpInquiry['inquiry_course_id']) {
							$arTmpInquiry['check_level'] = true; // Logik ist irgendwie andersrum
							$arTmpInquiry['check_flexible_allocation'] = false;

							// Da das schlaue JS mit zwei Arrays für die Dialog-Infos arbeitet, muss die Info hier ergänzt werden
							if(isset($arTmpInquiry['origin_journey_course_id'])) {
								$aBlockedStudent['origin_journey_course_id'] = $arTmpInquiry['origin_journey_course_id'];
							}

							continue 2;
						}
					}
				}

				$this->aTransfer['has_double_allocations'] = 1;
				$this->aTransfer['blocked_inquiry_courses'] = $aBlockedStudents;
			} 
			else if(!empty($aErrorAllocatedGrouped))
			{
				$this->aTransfer['has_errors_allocated'] = 1;
				$aErrorAllocatedByInquiryCourse = array();
				ksort($aErrorAllocatedGrouped);
				foreach($aErrorAllocatedGrouped as $iBlockWeek => $aErrorData) {

					foreach($aErrorData as $iInquiryCourseId => $aCourses) {

						foreach($aCourses as $iProgramServiceId => $sError) {
							$aErrorAllocatedByInquiryCourse[$iInquiryCourseId][$iProgramServiceId][] = $sError;
						}

					}
				}
			}

			$this->aTransfer['all_weeks'] = (int)$this->_oRequest->get('all_weeks');
			$this->aTransfer['errors_allocated'] = $aErrorAllocatedByInquiryCourse;
			$this->aTransfer['lang']['title'] = \L10N::t('Zuordnung','Thebing » Tuition');
			$this->aTransfer['lang']['go'] = \L10N::t('Go','Thebing » Tuition');
			$this->aTransfer['lang']['close'] = \L10N::t('Close','Thebing » Tuition');
			$this->aTransfer['lang']['tuition_move_student_failure_allocated'] = \L10N::t('Die folgenden Schüler konnten nicht verschoben werden, da Sie bereits zu dem Zeitpunkt zugeordnet sind.','Thebing » Tuition');
			$this->aTransfer['lang']['tuition_move_student_failure_allocated_activity'] = \L10N::t('Die folgenden Schüler konnten nicht zugewiesen werden, da diese bereits zu Aktivitäten zugewiesen sind.','Thebing » Tuition');
			$this->aTransfer['lang']['tuition_move_student_failure_no_course'] = \L10N::t('Die folgenden Schüler konnten nicht verschoben werden, da der vom Schüler gewählte Kurs nicht Inhalt dieses Unterrichts ist.','Thebing » Tuition');
			$this->aTransfer['lang']['tuition_move_student_failure_wrong_course_language'] = \L10N::t('Die folgenden Schüler konnten nicht verschoben werden, da die vom Schüler gewählte Kurssprache nicht Inhalt dieses Unterrichts ist.','Thebing » Tuition');
			$this->aTransfer['lang']['tuition_move_student_failure_level'] = \L10N::t('Die folgenden Schüler haben ein abweichendes Niveau. Bitte markieren Sie die Einträge bei denen das Niveau des Kurses übernommen werden soll.','Thebing » Tuition');
			$this->aTransfer['lang']['tuition_move_student_failure_exists'] = \L10N::t('Die folgenden Schüler konnten nicht verschoben werden, da Sie bereits zu dieser Klasse zugeordnet sind.','Thebing » Tuition');
			$this->aTransfer['lang']['tuition_move_student_flexible_allocation'] = \L10N::t('Die folgenden Schüler können flexibel zu den Blöcken zugewiesen werden. Bitte markieren Sie die Schüler, welche für die ganze Woche zugewiesen werden sollen.');
			$this->aTransfer['lang']['tuition_move_student_failure_wrong_course'] = \L10N::t('Die folgenden Schüler konnten nicht verschoben werden, da der Kurs nicht Bestandteil der Kursbuchung ist. Wurden die Buchungen zwischenzeitlich verändert?', 'Thebing » Tuition');
			$this->aTransfer['lang']['tuition_move_student_failure_no_online_room'] = \L10N::t('Die folgenden Schüler konnten nicht verschoben werden, da der Raum nicht für Onlinekurse zur Verfügung steht.', 'Thebing » Tuition');
			$this->aTransfer['lang']['tuition_move_student_failure_wrong_room_multiple_allocation'] = \L10N::t('Die folgenden Schüler konnten nicht verschoben werden, da der gewählte Raum nicht durchgängig verfügbar ist.', 'Thebing » Tuition');
			$this->aTransfer['lang']['tuition_move_student_failure_not_enough_lessons'] = \L10N::t('Die folgenden Schüler konnten nicht verschoben werden, da der der Schüler nicht mehr genug Lektionen für diesen Block zur Verfügung hat.', 'Thebing » Tuition');
			if($bPaymentError === true){
				$this->aTransfer['lang']['tuition_move_student_failure_allocated'] = \L10N::t('Die folgenden Schüler konnten nicht verschoben werden, da bereits Lehrerzahlungen existieren.','Thebing » Tuition');
			}
		}

	}
	
	public function MoveStudentAction() {

		\DB::begin(__METHOD__);

		$aInquiries = (array)$this->_oRequest->input('inquiries');
		$aApplyLevel = (array)$this->_oRequest->input('apply_level');
		$aApplyAllBlocksForFlexibleAllocation = (array)$this->_oRequest->input('apply_all_blocks_to_flexible_allocation');
		$iSelectedDay = (int)$this->_oRequest->input('day');

		$iCurrentBlockId = (int)$this->_oRequest->input('block_id');
		$iRoomId = (int)$this->_oRequest->input('room_id');

		$oCurrentBlock	= \Ext_Thebing_School_Tuition_Block::getInstance($iCurrentBlockId);

		$aCurrentBlockChildrenBlocks = array();
		$sCurrentWeek	= $oCurrentBlock->week;
		$dCurrentWeek = new \DateTime($sCurrentWeek);
		$oClass			= $oCurrentBlock->getClass();
		$oSchool = $oCurrentBlock->getSchool();

		if($this->_oRequest->input('all_weeks')==1){
			$sOperator = '>=';
		}else{
			$sOperator = '=';
		}

		$aBlockIds = $oClass->getBlocks($sCurrentWeek,true,$sOperator);
		$aSavedProgress = [];
		
		// Block-IDs übergeben, damit die Blöcke aktualisiert werden können
		$this->aTransfer['block_ids'] = [];
		$this->aTransfer['old_block_ids'] = [];

		$iReplaceBlockId = (int)$this->_oRequest->get('replace_block');

		if($iReplaceBlockId == $iCurrentBlockId) {
			$this->aTransfer['success'] = 0;
			$this->aTransfer['message'] = \L10N::t('Der Schüler ist bereits zugewiesen!','Thebing » Tuition');
		} else {

			// Serialisierte IDs trennen (Values der Checkboxen)
			$oSplitCheckboxValues = function(&$aArray) {
				$aTmp = $aArray;
				$aArray = array();

				foreach((array)$aTmp as $sItem) {
					list($iInquiryCourseId, $iCourseId) = explode('_', $sItem);
					$aArray[$iInquiryCourseId][$iCourseId] = 1;
				}
			};

			$oSplitCheckboxValues($aApplyLevel);
			$oSplitCheckboxValues($aApplyAllBlocksForFlexibleAllocation);

			if($iReplaceBlockId > 0) {
				
				$oReplaceBlock		= \Ext_Thebing_School_Tuition_Block::getInstance($iReplaceBlockId);
				$oReplaceClass		= $oReplaceBlock->getClass();
				$aOldBlocks			= $oReplaceClass->getBlocks($sCurrentWeek, true, $sOperator);
				$aSelectedInquiry	= reset($aInquiries);
				$aSplit				= explode('_', $aSelectedInquiry);
				$iInquiryCourseId = (int)$aSplit[0];
				$iProgramServiceId = (int)$aSplit[1];
				$oInquiryCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($iInquiryCourseId);

				if(
					$oInquiryCourse->flexible_allocation &&
					!isset($aApplyAllBlocksForFlexibleAllocation[$iInquiryCourseId][$iProgramServiceId])
				) {
					$aOldBlocks = [$oReplaceBlock->id];
				}

				if($iInquiryCourseId > 0) {
					$validateDeleteErrors = $oReplaceBlock->deactivateBlocksInquiryCoursesById($aOldBlocks, $iInquiryCourseId);
					if (!empty($validateDeleteErrors)) {
						$this->aTransfer['success'] = 0;
						$this->aTransfer['message'] = $validateDeleteErrors;
						\DB::rollback(__METHOD__);
						return;
					}
					$this->aTransfer['old_block_ids'] = $oReplaceClass->getBlocksWeeksIds(new \DateTime($sCurrentWeek));
				}
				
			}

			$aTempForCopy = array();

			/*
			 * Wenn Zuweisung zu Folgewochen und automatische Zuweisung nach Ferien:
			 * Nachfolgende Kursbuchungen der ausgewählten Kursbuchung (die durch Ferien-Splittung entstanden sind)
			 * ins Array schreiben, damit die Zuweisung auch für die nachfolgenden Blöcke stattfindet.
			 */
			if(
				$sOperator === '>=' &&
				$oSchool->tuition_automatic_holiday_allocation
			) {
				foreach($aInquiries as $sJourneyAndTuitionCourseId) {
					list($iJourneyCourseId, $iProgramServiceId) = explode('_', $sJourneyAndTuitionCourseId);
					$oJourneyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($iJourneyCourseId);
					$aRelatedCourses = $oJourneyCourse->getRelatedServices($dCurrentWeek);
					foreach($aRelatedCourses as $oRelatedJourneyCourse) {
						$aInquiries[] = $oRelatedJourneyCourse->id.'_'.$iProgramServiceId;
					}
				}
			}

			if(is_array($aBlockIds)) {
				foreach($aBlockIds as $iBlockId) {

					$oBlock = \Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
					$sBlockWeek = $oBlock->week;
					$dBlockWeek = new \DateTime($oBlock->week);

					foreach($aInquiries as $sJourneyAndTuitionCourseId) {

						list($iInquiryCourseId, $iProgramServiceId) = explode('_', $sJourneyAndTuitionCourseId);
						$oInquiryCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($iInquiryCourseId);

						// Wenn eine Kursbuchung flexible Zuweisung hat: Alle Child-Blöcke (rekursiv!) des selektierten Blocks laden (Lazy Load)
						if(
							$oInquiryCourse->flexible_allocation &&
							empty($aCurrentBlockChildrenBlocks)
						) {
							$aCurrentBlockChildrenBlocks = $oCurrentBlock->getRelevantBlocks();
						}

						$state = $oInquiryCourse->getTuitionIndexValue('state', $dBlockWeek);
						
						// Kurs in Ferien darf nicht zugewiesen werden
						if($state === 'V') {
							continue;
						}
						
						/*
						 * Wenn:
						 * 	Kursbuchung flexible Zuweisung hat,
						 * 	Block nicht der selektierte Block ist,
						 * 	Block auch kein Kind (Folgewoche) des selektierten Blocks ist,
						 * 	Kursbuchung soll auch nicht auf die ganze Woche zugewiesen werden (Checkbox im Dialog)
						 * dann: Kursbuchung nicht dem Block zuweisen
						 */
						if(
							$oInquiryCourse->flexible_allocation &&
							$iBlockId != $iCurrentBlockId &&
							!isset($aCurrentBlockChildrenBlocks[$iBlockId]) &&
							!isset($aApplyAllBlocksForFlexibleAllocation[$iInquiryCourseId][$iProgramServiceId])
						) {
							continue;
						}

						$mCheck = $oBlock->addInquiryCourse($iInquiryCourseId, $iProgramServiceId, $iRoomId);

						if($mCheck == 'expired') {
							continue;
						}

						// Level übernehmen, wenn Checkbox aktiviert
						if(
							!isset($aSavedProgress[$sBlockWeek][$iInquiryCourseId])
							/*
							&&
							(
								(
									$dCurrentWeek == $dBlockWeek &&
									isset($aApplyLevel[$iInquiryCourseId][$iCourseId])
								) ||
								$dCurrentWeek != $dBlockWeek
							)*/
							&& 
							isset($aApplyLevel[$iInquiryCourseId][$iProgramServiceId])
						){
							$oProgramService = \TsTuition\Entity\Course\Program\Service::getInstance($iProgramServiceId);
							$oCourse = $oProgramService->getService();
							$oLevelGroup = $oCourse->getLevelgroup();

							$oLevel = \Ext_Thebing_Tuition_Level::getInstance($oBlock->level_id);

							$oInquiryCourse->saveProgress($dBlockWeek, $oLevel, $oLevelGroup, $oProgramService, $iSelectedDay);
							$aSavedProgress[$sBlockWeek][$iInquiryCourseId] = 1;
						}

						if($sCurrentWeek==$sBlockWeek){
							$this->aTransfer['block_ids'][]	= (int)$iBlockId;
						}

						$this->aTransfer['success']		= 1;

					}

					// TODO ist das richtig? Die Variablen werden in der Schleife drüber benutzt
					$aTempForCopy[] = array(
						'inquiry_course_id' => $iInquiryCourseId,
						'program_service_id' => $iProgramServiceId,
					);

				}
			}

            $this->aTransfer['block_ids'] = array_values($this->aTransfer['block_ids']);

			$oAddRoomId = function($aBlockIds, $iRoomId) {
			    return array_map(function($iBlockId) use ($iRoomId) {
                    return [
                        'block_id' => $iBlockId,
                        'room_id' => $iRoomId,
                    ];
                }, $aBlockIds);
            };

            $this->aTransfer['block_ids'] = $oAddRoomId($this->aTransfer['block_ids'], $iRoomId);

			$this->aTransfer['block_id'] = $iCurrentBlockId;
			$this->aTransfer['room_id'] = $iRoomId;

		}

		\DB::commit(__METHOD__);
		
	}
	
	public function UpdateBlockAction() {

		$iBlockId = (int)$this->_oRequest->input('block_id');
		$iRoomId = (int)$this->_oRequest->input('room_id');

		$oBlock = \Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);

		#$this->aTransfer['content'] = $oBlock->getBlockContent();
		return (new \Illuminate\Http\Response($oBlock->getBlockContent($iRoomId)))->header('Content-Type', 'text/html');
	}
	
	public function DeleteBlockAction() {

		$this->aTransfer['title'] = \L10N::t('Block löschen','Thebing » Tuition');
		
		$iBlockId = (int)$this->_oRequest->input('block_id');
		$oBlock = \Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
		$oClass	= $oBlock->getClass();

		// Letzter Block einer Klasse darf nicht gelöscht werden, sonst verschwindet die Klasse
		// TODO Vorausgehende Blöcke sollten auch nicht gelöscht werden können
		if($this->_oRequest->input('all_weeks')) {
			$aFollowingBlockIds = array_column($oBlock->getRelevantBlocks(new \DateTime($oBlock->week)), 'id');
		} else {
			$aFollowingBlockIds = [$oBlock->id];
		}
		$aClassBlockIds = $oClass->getBlocks(false, true);
		if(empty(array_diff($aClassBlockIds, $aFollowingBlockIds))) {
			$this->aTransfer['success'] = 0;
			$this->aTransfer['error_message'] = \L10N::t('Der letzte Block einer Klasse kann nicht gelöscht werden.','Thebing » Tuition');
			return;
		}

		$bSuccess = $oBlock->delete();

		if($bSuccess === true) {

			$mReturn					= true;

			$oWdDate			= new \WDDate($oBlock->week, \WDDate::DB_DATE);
			$iBlockWeek			= $oWdDate->get(\WDDate::TIMESTAMP);

			if($this->_oRequest->get('all_weeks')==1)
			{
				$aBlocksThisWeek	= $oClass->getBlocks($oBlock->week,true);
				$aBlockCopyData		= $oClass->prepareBlockSaveDataArray($aBlocksThisWeek,true);
				$mReturn			= $oClass->copyDataFromWeek($iBlockWeek, $aBlockCopyData);
			}

			$aCheck = $oClass->getBlocks($iBlockWeek, true, '>=');
			if(empty($aCheck)) {
				$iCurrentWeek	= $oClass->getCurrentWeek($iBlockWeek);
				$iCurrentWeek	= $iCurrentWeek - 1;
				$oClass->weeks	= $iCurrentWeek;
				$oClass->save();
			}

			if($mReturn === true){
				$this->aTransfer['block_ids'][]	= $iBlockId;
				$this->aTransfer['success']		= 1;
			}else{
				$this->aTransfer['success']		= 1;
				$this->aTransfer['alert_message'] = \L10N::t('Fehler beim Kopieren in die Folgewochen. Es existieren noch Lehrerzahlungen.','Thebing » Tuition');
			}

		}else{
			$this->aTransfer['success']		= 0;
			$this->aTransfer['error_message'] = \L10N::t('Block konnte nicht gelöscht werden. Es existieren noch Lehrerzahlungen.','Thebing » Tuition');
		}

	}
	
	public function DeleteStudentAction() {

		$this->aTransfer['title'] = \L10N::t('Schüler löschen','Thebing » Tuition');
		
		$aInquiryCourseIds	= $this->_oRequest->input('inquiry_ids');
		$iBlockId			= $this->_oRequest->input('block_id');
		$iRoomId			= $this->_oRequest->input('room_id');
		$iAllWeeks			= $this->_oRequest->input('all_weeks');

		$aSuccess = \Ext_Thebing_School_Tuition_Allocation::deleteInquiryCourseAllocations($iBlockId, $iRoomId, $aInquiryCourseIds, $iAllWeeks);

		if ($aSuccess['success']==1) {
			$this->aTransfer['block_ids'] = $aSuccess['block_ids'];
			$this->aTransfer['success']	= 1;
		} else {
			$sError = $aSuccess['error'];
			$this->aTransfer['success'] = 0;
			if (is_array($sError)) {
				$this->aTransfer['message'] = implode("<br>", $sError);
			} else {
				switch ($sError) {
					case 'payments_exists':
						$sErrorMessage = 'Fehler beim Löschen der Schüler. Es existieren Lehrerzahlungen.';
						break;
					default:
						$sErrorMessage = 'Fehler beim Löschen der Schüler!';
				}
				$this->aTransfer['message'] = \L10N::t($sErrorMessage,'Thebing » Tuition');
			}
		}
	}

	public function OpenTeacherDialogAction() {

		$iBlockId = (int)$this->_oRequest->input('block_id');
		$oBlock = \Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);

		$oSchool = \Ext_Thebing_School::getSchoolFromSession();

		$aWeekDays = \Ext_Thebing_Util::getDays();
		$this->aTransfer['days'] = array();
		foreach((array)$oBlock->days as $iSelectedDay) {
			$this->aTransfer['days'][] = array($iSelectedDay, $aWeekDays[$iSelectedDay]);
			$teachers = $oBlock->getAvailableTeachers($oBlock->week, false, aDays: [$iSelectedDay]);
			$this->aTransfer['teachers'][$iSelectedDay] = collect($teachers)
				->reject(function ($teacherName, $teacherId) use ($oBlock) {
					return $oBlock->teacher_id == $teacherId;
				})
				->map(function ($teacherName, $teacherId) {
					return [(string)$teacherId, $teacherName];
				})
				->values()
				->toArray();
		}

		$oFormat = new \Ext_Thebing_Gui2_Format_Float(2,false);

		$this->aTransfer['block']	= $oBlock->getArray();
		$this->aTransfer['block']['lessons'] = $oFormat->format($oBlock->lessons);
		$this->aTransfer['week']	= $this->_oRequest->get('week');

		$iStart = \Ext_Thebing_Util::convertTimeToSeconds($oBlock->from);
		$iEnd	= \Ext_Thebing_Util::convertTimeToSeconds($oBlock->until);

		$this->aTransfer['times']	= \Ext_Thebing_Util::getTimeRows('index', 5, $iStart, $iEnd, true);

		$aSubs = $oBlock->getSubstituteTeachers();

		$this->aTransfer['substitute_teachers'] = array();

		foreach((array)$aSubs as $iKey=>$aSub) {
			$aSub['from'] = $aSub['from'];
			$aSub['to'] = $aSub['until'];
			$aSub['lessons'] = $oFormat->format($aSub['lessons']);
			$this->aTransfer['substitute_teachers'][$aSub['day']][] = $aSub;	
		}

		$this->aTransfer['lang']['title'] = \L10N::t('Substitute teachers','Thebing » Tuition');
		$this->aTransfer['lang']['teacher'] = \L10N::t('Teacher','Thebing » Tuition');
		$this->aTransfer['lang']['save'] = \L10N::t('save','Thebing » Tuition');
		$this->aTransfer['lang']['from'] = \L10N::t('From','Thebing » Tuition');
		$this->aTransfer['lang']['to'] = \L10N::t('To','Thebing » Tuition');
		$this->aTransfer['lang']['add'] = \L10N::t('Add substitute teacher','Thebing » Tuition');
		$this->aTransfer['lang']['delete'] = \L10N::t('Delete substitute teacher','Thebing » Tuition');
		$this->aTransfer['lang']['lessons'] = \L10N::t('Lektionen','Thebing » Tuition');

	}
	
	public function SaveTeacherDialogAction() {

		$iBlockId = (int)$this->_oRequest->input('block_id');
		$oBlock = \Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);

		$mSuccess = $oBlock->saveSubstituteTeachers($this->_oRequest->input('substitute'));

		if($mSuccess === true) {

			$this->aTransfer['success'] = 1;
			$this->aTransfer['message'] = \L10N::t('Speichern erfolgreich!','Thebing » Tuition');

		} else {

			$this->aTransfer['success'] = 0;
			if(
				is_array($mSuccess) &&
				array_key_exists('allocated_block', $mSuccess)
			) {
				$aTeachers = array();
				foreach($mSuccess as $aCheckData){
					foreach($aCheckData as $aData){
						$oTeacher = \Ext_Thebing_Teacher::getInstance($aData['substitute_teacher_id']);
						$aTeachers[] = $oTeacher->name;
					}
				}

				$sMessage = \L10N::t('Ein Lehrer ist bereits einem anderen Block zugeordnet (%s)!','Thebing » Tuition');

				$sMessage = str_replace('%s', implode(', ', $aTeachers), $sMessage);
				$this->aTransfer['message'] = $sMessage;
			}elseif($mSuccess=='teacher_absence'){
				$this->aTransfer['message'] = \L10N::t('Ein Lehrer ist abwesend!','Thebing » Tuition');
			}
			else {
				$this->aTransfer['message'] = \L10N::t('Speichern nicht erfolgreich!','Thebing » Tuition');
			}
			$this->aTransfer['block_id'] = $iBlockId;

		}

	}
	
	public function ClearStudentsAction() {

		$iBlockId = (int)$this->_oRequest->input('block_id');
		$iRoomId = (int)$this->_oRequest->input('room_id');

		$oBlock = \Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);

		$oClass = $oBlock->getClass();
		if($this->_oRequest->get('all_weeks')==1){
			$sOperator = '>=';
		}else{
			$sOperator = '=';
		}

		$this->aTransfer = [
			'title' => \L10N::t('Schüler löschen', 'Thebing » Tuition')
		];

		$aReturn = $oClass->clearStudents($iRoomId, $this->_oRequest->get('week'), $sOperator);

		if($aReturn['success']==1)
		{
			$this->aTransfer['block_ids'] = $aReturn['block_ids'];
			$this->aTransfer['success']	= 1;
		}
		else
		{
			$this->aTransfer['success']	= 0;
			if (is_array($aReturn['error'])) {
				$this->aTransfer['error_message'] = implode("<br>", $aReturn['error']);
			} else {
				switch ($aReturn['error']) {
					case 'payment_exists':
						$this->aTransfer['error_message'] = \L10N::t('Fehler beim Löschen der Schüler. Es existieren Lehrerzahlungen.', 'Thebing » Tuition');
						break;
					default:
						$this->aTransfer['error_message'] = \L10N::t('Fehler beim Löschen der Schüler.', 'Thebing » Tuition');
						break;
				}
			}
		}

		#$this->aTransfer['block_ids'] = $oClass->clearStudents($this->_oRequest->get('week'),$sOperator);

	}
	
	public function ExportWeekAction() {

		$iWeek		= $this->_oRequest->get('week');

		$oWeek		= new \WDDate($iWeek);

		$aDays		= \Ext_Thebing_Util::getDays();
		$oSchool	= \Ext_Thebing_School::getSchoolFromSession();
		$sDir		= $oSchool->getSchoolFileDir().'/temp';

		if(!\Util::checkDir($sDir)) {
			throw new \RuntimeException('Could not create school temporary dir '.$sDir.'!');
		}

		$aClassesTimes = $oSchool->getClassTimes();

		$aRooms = $oSchool->getClassRooms(false, $oWeek->get(\WDDate::DB_DATE), true, $this->_oRequest->get('floor'));

		$oWdDate		= new \WDDate();
		$oFormatDate	= new \Ext_Thebing_Gui2_Format_Date();

		$aTimes = array();
		$iPosY	= 2;

		// TODO: wenn eine Klasse angelegt  wurde, und später die Uhrzeiten verändert werden. Merge cels abdecken
		foreach($aClassesTimes as $oClassTime) {

			$iStart = $oClassTime->getFromSeconds();
			$iEnd	= $oClassTime->getUntilSeconds();

			$aTimeRows = \Ext_Thebing_Util::getTimeRows('assoc', $oClassTime->interval, $iStart, $iEnd, false);

			foreach($aTimeRows as $iTime => $sTime) {
				$aTimes[$iTime]['pos']	= $iPosY;
				$aTimes[$iTime]['time'] = $sTime;
				$iPosY++;
			}
		}

		// Position (Zeile) pro Zeit
		$cGetTimesPos = function($iTime, $bFrom, $iSelectedDay) use (&$aTimes) {
			// Wenn Zeit nicht vorhanden: Nächstbeste Zeit suchen
//			if(!isset($aTimes[$iTime])) {
				$iClosestTime = null;
				foreach(array_keys($aTimes) as $iTime2) {
//					if(isset($aTimes[$iTime2]['used_'.$iSelectedDay])) {
//						// Zwei Klassen dürfen sich nicht in derselben Zeile überschneiden
//						// Ansonsten fehlt eine Klasse, da die Zellen falsch gemerged werden
//						// Wenn das so nicht funktioniert (Exception unten), muss wieder -1 bei until eingebaut werden (s.u.)
//						continue;
//					}
					if(
						$iClosestTime === null ||
						abs($iTime - $iClosestTime) > abs($iTime2 - $iTime)
					) {
						$iClosestTime = $iTime2;
					}
				}
				if($iClosestTime === null) {
					throw new \RuntimeException('No time found for '.$iTime.'!');
				}
//				$aTimes[$iClosestTime]['used_'.$iSelectedDay] = true;
				return $aTimes[$iClosestTime]['pos'];
//			}
//			return $bFrom ? $aTimes[$iTime]['pos'] : $aTimes[$iTime]['pos'] - 1;
		};

		$oExcel = new PhpSpreadsheet\Spreadsheet();
		$oExcel->getProperties()->setTitle($oSchool->ext_1.' - '.\L10N::t('Wochenübersicht','Thebing » Tuition'));
		$oExcel->getProperties()->setCreator(\System::d('software_name'));
		$oExcel->getProperties()->setLastModifiedBy(\System::d('software_name'));

//		$oExcelFont = $oExcel->getDefaultStyle()->getFont();

		$iSheetIndex = 0;
		foreach($aDays as $iSelectedDay => $sTitleDay) {

			$aWeek			= \Ext_Thebing_Util::getWeekTimestamps($iWeek);
			$oWdDate->set($aWeek['start'], \WDDate::TIMESTAMP);
			$oWdDate->set($iSelectedDay, \WDDate::WEEKDAY);
			$dDate			= $oFormatDate->formatByValue($oWdDate->get(\WDDate::TIMESTAMP));

			if($iSheetIndex>0) {
				$oExcel->createSheet();
			}

			$oExcel->setActiveSheetIndex($iSheetIndex);

			$oSheet = $oExcel->getActiveSheet();

			$oSheet->getPageSetup()->setOrientation(PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

			$oSheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
			$oSheet->getPageSetup()->setColumnsToRepeatAtLeftByStartAndEnd('A', 'A');

			$oSheet->getPageMargins()->setTop(0.5);
			$oSheet->getPageMargins()->setRight(0.5);
			$oSheet->getPageMargins()->setLeft(0.5);
			$oSheet->getPageMargins()->setBottom(0.5);

			$oSheet->setTitle(\Ext_TC_Util::escapeExcelSheetTitle($sTitleDay.' ('.$dDate.')', '-'));

			//$oSheet->mergeCells("A1:Z1");
//			$oSheet->getRowDimension(1)->setRowHeight(44);
//			$oSheet->getStyle("B1")->getFont()->setSize(16);
//			$oSheet->getStyle("B1")->getFont()->setBold(true);
//			$oSheet->getStyle("B1")->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_TOP);
			#$oSheet->getStyle("A1")->getAlignment()->setWrapText(true);
			$sText = $oSchool->ext_1.' - '.\L10N::t('Wochenübersicht','Thebing » Tuition');
			$sText .= "\n";
			$sText .= $sTitleDay.' ('.$dDate.')';
//			$oSheet->setCellValueByColumnAndRow(1, 1, $sText);

			// https://github.com/PHPOffice/PHPExcel/blob/develop/Documentation/markdown/Overview/08-Recipes.md#setting-the-print-header-and-footer-of-a-worksheet
			$oSheet->getHeaderFooter()->setOddHeader('&B'.str_replace("\n", ' - ', $sText));

			// get blocks
			$aBlockData = $oSchool->getWeekBlocks($aWeek['start'], $aWeek['end'], $aRooms, $iSelectedDay);

			$aVirtualClassRooms = array();
			$aWithoutClassRooms = array();
			$aBlocks = array();
			foreach((array)$aBlockData as $aItem) {

				$aItem['from'] = \Ext_Thebing_Util::convertTimeToSeconds($aItem['from']);
				$aItem['until'] = \Ext_Thebing_Util::convertTimeToSeconds($aItem['until']);

				$iKey = $aItem['from'];
				// change from to full quarter
				if(fmod($iKey / 60 / 15, 1) > 0) {
					$iKey = $iKey - round(fmod($iKey / 60 / 15, 1) * 15 * 60);
				}

				if($aItem['room_id'] == 0) {

					$aItem['room_type'] = 'no_room';

					if(empty($aWithoutClassRooms)) {
						$aWithoutClassRooms[] = array($iKey=>$aItem);
					} else {

						foreach((array)$aWithoutClassRooms as $iColumn => $aBlockDataWithoutClassRomm) {
							$bInsert = true;
							foreach((array)$aBlockDataWithoutClassRomm as $iBlockId => $aBlock) {

								if(
									$aBlock['until'] >= $aItem['from'] &&
									$aItem['until'] >= $aBlock['from']
								) {
									$bInsert = false;
								}

							}

							if($bInsert == true) {
								$aWithoutClassRooms[$iColumn][$iKey] = $aItem;
								break;
							}

						}

						if(!$bInsert) {
							$aWithoutClassRooms[count($aWithoutClassRooms)] = array($iKey=>$aItem);
						}

					}



				} elseif($aItem['room_id'] < 0) {
					$aItem['room_type'] = 'virtual';
					$aVirtualClassRooms[] = array($iKey => $aItem);
				} else {
					$aBlocks[$aItem['room_id']][$iKey] = $aItem;
				}

			}

			$aOtherRooms = array_merge($aWithoutClassRooms,$aVirtualClassRooms);

			$aAllRooms = array_merge($aRooms, $aOtherRooms);

			$iPosX = 2;

			foreach($aAllRooms as $iKey => $mClassroom) {

				if($mClassroom instanceof \Ext_Thebing_Tuition_Classroom) {
					$sMaxText = \L10N::t('max','Thebing » Tuition').'. '.$mClassroom->max_students;
					$sRoomName = $mClassroom->name."\n".$sMaxText;
					$aData = (array)$aBlocks[$mClassroom->id];
				} else {
					$aItem = reset($mClassroom);
					$sType = $aItem['room_type'];
					if($sType === 'virtual') {
						$sRoomName = \L10N::t('Virtueller Klassenraum','Thebing » Tuition');
					} else {
						$sRoomName = \L10N::t('Raumlos','Thebing » Tuition');
					}
					$aData = $mClassroom;
				}

				$oSheet->getCell([$iPosX, 1])->setValue($sRoomName);
				$oCellMain	= $oExcel->getActiveSheet()->getCell([$iPosX, 1]);
				$sColumn	= $oCellMain->getColumn();

				$sWrap = $sColumn.'1';

				$oSheet->getRowDimension(1)->setRowHeight(30); // Workaround für LibreOffice (setWrapText() funktioniert nicht)
				$oSheet->getStyle($sWrap)->getAlignment()->setWrapText(true);
				$oSheet->getStyle($sWrap)->getAlignment()->setVertical(PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

				//$iMaxLength = max(strlen($oClassroom->name),strlen($sMaxText));
//				$iMaxLength = \PHPExcel_Shared_Font::calculateColumnWidth($oExcelFont, $sRoomName, 0, $oExcelFont);

				$iLastYPos = 0;

				foreach($aData as $iStart => $aBlockData) {

					$iPos1 = $cGetTimesPos($aBlockData['from'], true, $iSelectedDay);
					$iPos2 = $cGetTimesPos($aBlockData['until'], false, $iSelectedDay) - 1;

					// Zwei Klassen dürfen sich nicht überschneiden, daher verschieben
					if($iPos1 === $iLastYPos) {
						$iPos1++;
						$iPos2++;
					}

					$iLastYPos = $iPos2;

					$sMerge = $sColumn.$iPos1.':'.$sColumn.$iPos2;
					if($iPos2 - $iPos1 <= 1) {
						$sMerge = $sColumn.$iPos1;
					} else {
						$oExcel->getActiveSheet()->mergeCells($sMerge);
					}

					$oTuitionBlock	= \Ext_Thebing_School_Tuition_Block::getInstance($aBlockData['id']);
					$sContent		= $oTuitionBlock->getBlockContent((int)$aBlockData['room_id'], true, $iSelectedDay);
					$oPurifier		= new \Core\Service\HtmlPurifier(['br']);
					$sContent		= $oPurifier->purify($sContent);

					// Das ist Schwachsinn, da \n ebenso enthalten ist und calculateColumnWidth mit \n ebenso umgehen kann
//					$aContent		= explode("<br />", $sContent);
//					$sContentClean	= '';
//					foreach($aContent as $sContentSplit)
//					{
//						$sContentSplit = trim($sContentSplit);
//						$iLength = strlen($sContentSplit);
//						if($iLength>$iMaxLength)
//						{
//							$iMaxLength = $iLength;
//						}
//						$sContentClean .= $sContentSplit."\r\n";
//					}

					$sContentClean = str_replace('<br />', "\n", $sContent);
//					$iLength = \PHPExcel_Shared_Font::calculateColumnWidth($oExcelFont, $sContentClean, 0, $oExcelFont);
//					if($iLength > $iMaxLength) {
//						$iMaxLength = $iLength;
//					}

					$aStyle = array(
						'font' => [
							'size' => 8
						],
						'alignment' => array(
							'vertical' => PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
							'wrapText' => true
						),
						'borders' => array(
							'allBorders' => [
								'borderStyle' => PhpSpreadsheet\Style\Border::BORDER_THIN
							]
						)
					);

					$oExcel->getActiveSheet()->getStyle($sMerge)->getAlignment()->setVertical(PhpSpreadsheet\Style\Alignment::VERTICAL_BOTTOM);

					$oClass = $oTuitionBlock->getClass();
					$sColor = $oClass->getColor();

					if(!empty($sColor)) {
						$sColor = str_replace('#','',$sColor);

						$aStyle['fill'] = array(
							'fillType' => PhpSpreadsheet\Style\Fill::FILL_SOLID,
							'color' => array(
								'rgb' => $sColor
							)
						);
					}

					$oExcel->getActiveSheet()->getStyle($sMerge)->applyFromArray($aStyle);
					$oExcel->getActiveSheet()->setCellValue($sColumn.$iPos1, $sContentClean);
				}

//				$oExcel->getActiveSheet()->getColumnDimension($sColumn)->setWidth($iMaxLength);
				$oExcel->getActiveSheet()->getColumnDimension($sColumn)->setWidth(16);

				$iPosX++;

			}

			/*foreach($aOtherRooms as $aRoomGroupedData)
			{
				$aItem = reset($aRoomGroupedData);
				$sType = $aItem['room_type'];
				if($sType=='virtual'){
					$sName = \L10N::t('Virtueller Klassenraum','Thebing » Tuition');
				}else{
					$sName = \L10N::t('Raumlos','Thebing » Tuition');
				}
				$oExcel->getActiveSheet()->setCellValueByColumnAndRow($iPosX, 2, $sName);

				$oCellMain	= $oExcel->getActiveSheet()->getCellByColumnAndRow($iPosX, 1);
				$sColumn	= $oCellMain->getColumn();

				$iMaxLength = strlen($sName);
				foreach((array)$aRoomGroupedData as $iStart => $aBlockData)
				{
					$iPos1 = $aTimes[$aBlockData['from']]['pos'];
					$iPos2 = $aTimes[$aBlockData['until']]['pos']-1;

					$oExcel->getActiveSheet()->getColumnDimension($sColumn)->setWidth(30);

					$sMerge = $sColumn.$iPos1.':'.$sColumn.$iPos2;
					if($iPos2 - $iPos1 <= 1){
						$sMerge = $sColumn.$iPos1;
					}else{
						$oExcel->getActiveSheet()->mergeCells($sMerge);
					}

					$oTuitionBlock	= \Ext_Thebing_School_Tuition_Block::getInstance($aBlockData['id']);
					$sContent		= $oTuitionBlock->getBlockContent(true,$iSelectedDay);
					$oPurifier		= new \Ext_TC_Purifier(['br']);
					$sContent		= $oPurifier->purify($sContent);

					$aContent		= explode("<br />", $sContent);
					$sContentClean	= '';
					foreach($aContent as $sContentSplit)
					{
						$sContentSplit = trim($sContentSplit);
						$iLength = strlen($sContentSplit);
						if($iLength>$iMaxLength)
						{
							$iMaxLength = $iLength;
						}
						$sContentClean .= $sContentSplit."\r\n";
					}

					$oClass = $oTuitionBlock->getClass();
					$sColor = $oClass->getColor();

					$aStyle = array(
						'alignment' => array(
							'vertical' => \PHPExcel_Style_Alignment::VERTICAL_TOP,
							'wrap' => true
						),
						'borders' => array(
							'top' => array(
								'style' => \PHPExcel_Style_Border::BORDER_THIN
							),
							'bottom' => array(
								'style' => \PHPExcel_Style_Border::BORDER_THIN
							),
							'left' => array(
								'style' => \PHPExcel_Style_Border::BORDER_THIN
							),
							'right' => array(
								'style' => \PHPExcel_Style_Border::BORDER_THIN
							),
						)
					);

					$oExcel->getActiveSheet()->getStyle($sMerge)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_BOTTOM);

					$oClass = $oTuitionBlock->getClass();
					$sColor = $oClass->getColor();

					if(!empty($sColor)) {
						$sColor = str_replace('#','',$sColor);

						$aStyle['fill'] = array(
							'type' => \PHPExcel_Style_Fill::FILL_SOLID,
							'color' => array(
								'rgb' => $sColor
							)
						);
					}

					$oExcel->getActiveSheet()->getStyle($sMerge)->applyFromArray($aStyle);
					$oExcel->getActiveSheet()->setCellValue($sColumn.$iPos1, $sContentClean);
				}

				$oExcel->getActiveSheet()->getColumnDimension($sColumn)->setWidth($iMaxLength);

				$iPosX++;
			}*/

//			$oSheet->mergeCells('B1:'.$sColumn.'1');

			$oSheet->getColumnDimension('A')->setAutoSize(true);

			foreach($aTimes as $aTimeData) {
				$iPosY	= $aTimeData['pos'];
				$sTime	= $aTimeData['time'];

				$oExcel->getActiveSheet()->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(1). $iPosY, $sTime);
			}

			$iSheetIndex++;
		}

		$oExcel->setActiveSheetIndex(0);

		$sFileName = \Util::getCleanFilename($oSchool->ext_1.'_week_'.$oWeek->get(\WDDate::WEEK).'.xlsx');
		$sFile = $sDir.'/'.$sFileName;

		$oWriter = new PhpSpreadsheet\Writer\Xlsx($oExcel);
		$oWriter->save($sFile);

		header('Content-Disposition: inline; filename="'.$sFileName.'"');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Type: application/msexcel');

		echo file_get_contents($sFile);

		die();
	}
	
	public function CheckToolbarAction() {

		$iBlockId = (int)$this->_oRequest->input('block_id');
		$iInquiryId = (int)$this->_oRequest->input('inquiry_id');

		$arrIcons = array();
		$arrIcons['toolbar_new'] = 0;
		$arrIcons['toolbar_copy'] = 0;
		$arrIcons['toolbar_replace_data'] = 0;
		$arrIcons['toolbar_edit'] = 0;
		$arrIcons['toolbar_delete'] = 0;
		$arrIcons['toolbar_student_delete'] = 0;
		$arrIcons['toolbar_student_communication'] = 0;
		$arrIcons['toolbar_teacher_replace'] = 0;
		$arrIcons['toolbar_export'] = 1;
		$arrIcons['toolbar_exportWeek'] = 1;

		if($iBlockId > 0) {
			$arrIcons['toolbar_edit'] = 1;
			$arrIcons['toolbar_delete'] = 1;
			$oBlock = \Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
			if($oBlock->hasTeacher()){
				$arrIcons['toolbar_teacher_replace'] = 1;
			}
		}

		if($iInquiryId > 0) {
			$arrIcons['toolbar_student_delete'] = 1;
			$arrIcons['toolbar_student_communication'] = 1;
		}

		if(!empty($_SESSION['tuition']['planification']['week'])) {
			$arrIcons['toolbar_new'] = 1;
			$arrIcons['toolbar_copy'] = 1;
			$arrIcons['toolbar_replace_data'] = 1;
			$arrIcons['toolbar_change_room_teacher'] = 1;
			$arrIcons['toolbar_allocation_students'] = 1;
			$arrIcons['toolbar_clear_students'] = 1;
		}

		$this->aTransfer['data'] = $arrIcons;
		
		$this->aTransfer['column_width'] = (int)\System::d('ts_scheduling_block_width', 120);
		
	}
	
	private function setTransfer(array $aTransfer) {
		foreach($aTransfer as $sKey=>$mValue) {
			$this->set($sKey, $mValue);
		}
	}

	/**
	 * @param \DateTime $dWeek
	 * @param \Ext_Thebing_School $oSchool
	 * @return array
	 */
	private function checkCopyLastWeek(\DateTime $dWeek, \Ext_Thebing_School $oSchool) {

		$dWeekCheck = clone $dWeek;
		$dWeekCheck->add(new \DateInterval('P1W'));

		$dWeekLast = \Ext_Thebing_Tuition_Class::searchLastWeekWithClasses($dWeekCheck, $oSchool);

		if(empty($dWeekLast)) {
			return [null, null];
		}

		$oDiff = $dWeekCheck->diff($dWeekLast);
		$bDiffCheck = floor($oDiff->days / 7) == 2;

		return [$dWeekLast, $bDiffCheck];

	}
	
}
