<?php

namespace Cms\Generator;

use \Carbon\Carbon;

class Stats {
	
	private $log;

	private $site;

	public function __construct(\Cms\Entity\Site $site) {

		$this->log = \Log::getLogger('cms', 'statistics');
		$this->site = $site;

	}
	
	public function generate() {

		set_time_limit(7200);
		ignore_user_abort(true);

		\Util::checkDir(\Util::getDocumentRoot()."storage/cms/stats/");
		
		// Ersten Statistik Eintrag ermitteln
		$tFirst = new Carbon(\DB::getQueryOne("SELECT time FROM cms_stats WHERE site_id = ".$this->site->id." ORDER BY time LIMIT 1"));

		$now = new Carbon;
		
		$aMonth = array();

		while($tFirst <= $now) {

			$sStatsFile = \Util::getDocumentRoot()."storage/cms/stats/stats_".$this->site->id."_".$tFirst->format('Y_m_d').".inc.php";
			if(!is_file($sStatsFile)) {
				$aMonth["i".$tFirst->format('Y')] = array("m"=>0,"y"=>$tFirst->format('Y'));
				$aMonth["i".$tFirst->format('Y').$tFirst->format('n')] = array("m"=>$tFirst->format('n'),"y"=>$tFirst->format('Y'));
			}
			$tFirst->addMonth();
		}

		$aMonth["i".$now->format("Y")] = array("m"=>0,"y"=>$now->format('Y'));
		$aMonth["i".$now->format("Y").$now->format('n')] = array("m"=>$now->format('n'),"y"=>$now->format('Y'));

		foreach($aMonth as $aData) {

			$iMonth = $aData['m'];
			$iYear = $aData['y'];

			$sStatsFile = \Util::getDocumentRoot()."storage/cms/stats/stats_".$this->site->id."_".$iYear."_".$iMonth.".inc.php";
			$content = $this->generateStats($iYear, $iMonth);

			$fh = fopen($sStatsFile,'w');
			fwrite($fh,$content);
			fclose($fh);
			
			\Util::changeFileMode($sStatsFile);
			
		}

		// Eintrag ins Protokoll schreiben
		\Log::add(\Cms\Helper\Log::LOG_STATS_UPDATED);

		// Ältere Einträge löschen
		$lastYear = Carbon::now()->subYear()->endOfYear();
		\DB::executePreparedQuery("DELETE FROM cms_stats WHERE site_id = ".$this->site->id." AND time < :time", ['time'=>$lastYear->toDateTimeString()]);

	}

	private function generateStats($iYear,$iMonth) {

		$_VARS['year']  = $iYear;
		$_VARS['month'] = $iMonth;

		$now = new Carbon;
		
		// ERZEUGUNG START
		ob_start();

		$aValues = array();
		$aDesc	 = array();

		// JAHRESÜBERSICHT START
		if($iMonth == 0) {

			$year = new Carbon();
			$year->year = $iYear;
			$year->startOfYear();
			
			$iGlobalFrom = $year->clone();
			$iGlobalTo = $iGlobalFrom->clone()->endOfYear();
			
			for($i=1;$i<=12;$i++) {

				$timestamp = mktime(0,0,0,$i,1,$_VARS['year']);
				
				$from = $year->clone();
				$from->month = $i;
				$to = $from->clone()->endOfMonth();

				$my_views = \DB::getQueryRow("SELECT COUNT(id) AS anzahl FROM cms_stats WHERE site_id = ".$this->site->id." AND time > '".$from->toDateTimeString()."' AND time < '".$to->toDateTimeString()."'");
				$my_visits['anzahl'] = count((array)\DB::getQueryRows("SELECT id FROM cms_stats WHERE site_id = ".$this->site->id." AND time > '".$from->toDateTimeString()."' AND time < '".$to->toDateTimeString()."' AND session != '' GROUP BY session"));
				$my_exec = \DB::getQueryRow("SELECT AVG(duration) AS anzahl FROM cms_stats WHERE site_id = ".$this->site->id." AND time > '".$from->toDateTimeString()."' AND time < '".$to->toDateTimeString()."'");
				$res_duration = (array)\DB::getQueryRows("SELECT MAX(time)-MIN(time) AS diff FROM cms_stats WHERE site_id = ".$this->site->id." AND time > '".$from->toDateTimeString()."' AND time < '".$to->toDateTimeString()."' AND session != '' GROUP BY session");
				$j=0;
				$duration=0;
				foreach($res_duration as $my_duration) {
					$duration += $my_duration['diff'];
					$j++;
				}
				if($j>0)
					$my_duration['anzahl'] = round($duration / $j / 60);
				else
					$my_duration['anzahl'] = "0";

				$dayname = strftime("%b",$timestamp);

				$my_exec['anzahl'] = round($my_exec['anzahl']*1000);

				$aValues[0][] = $my_visits['anzahl'];
				$aValues[1][] = $my_views['anzahl'];
				if($my_visits['anzahl']>0)
					$iViewsPVisit = round($my_views['anzahl']/$my_visits['anzahl']);
				else
					$iViewsPVisit = "0";
				$aValues[2][] = $iViewsPVisit;
				$aValues[3][] = $my_duration['anzahl'];
				$aValues[4][] = $my_exec['anzahl'];

				$aDesc[] = $dayname;

				$aTable[] = array(strftime("%B",$timestamp),$my_visits['anzahl'],$my_views['anzahl'],$iViewsPVisit,$my_duration['anzahl']." m",$my_exec['anzahl']." ms");

			}

	?>
	<p align="center">
	<?
//	$graph = new classGraph(0);
//	$graph->setValues($aValues);
//	$graph->setXDesc($aDesc);
//	$graph->setWidth(700);
//	$graph->setHeight(150);
//	$graph->showGraph();

	?>
	</p>
	<table class="table table-condensed">
		<tr>
			<th width="20%">Monat</th>
			<th width="15%"><img src="/admin/media/stats_1.png" height="13" width="13" border="0" align="absmiddle"> Besucher						</th>
			<th width="15%"><img src="/admin/media/stats_2.png" height="13" width="13" border="0" align="absmiddle"> Seitenaufrufe</th>
			<th width="15%"><img src="/admin/media/stats_3.png" height="13" width="13" border="0" align="absmiddle"> Seiten pro Besucher</th>
			<th width="15%"><img src="/admin/media/stats_4.png" height="13" width="13" border="0" align="absmiddle"> Aufenthaltsdauer</th>
			<th width="15%"><img src="/admin/media/stats_5.png" height="13" width="13" border="0" align="absmiddle"> Ausführungszeit</th>
		</tr>
	<?
		foreach($aTable as $key=>$elem) {
	?>
		<tr id="tr_<?=$key?>" onmouseout="showRow('tr_<?=$key?>',0)" onmousemove="showRow('tr_<?=$key?>',1);">
	<?
			foreach($elem as $k=>$val) {
				$val = (!$val)?"0":$val;
	?>
			<td align="<?=(($k>0)?"right":"left")?>"><?=$val?></td>
	<?
			}
	?>
		</tr>
	<?
		}
	?>
	</table>

	<?

	// JAHRESÜBERSICHT ENDE
	} else {

	// MONATSÜBERSICHT START

	$month = new Carbon();
	$month->year = $iYear;
	$month->month = $iMonth;
	$month->startOfMonth();

	$from = 0;

	$day = $month->clone();
	
	$end = $month->clone()->endOfMonth();
	
	if($end > $now) {
		$end = $now->clone();
	}
	
	$iGlobalFrom = $month->clone();
	$iGlobalTo =$end->clone();

	while($day <= $end) {

		$from = $day;
		$to = $from->clone()->endOfDay();

		$my_views = \DB::getQueryRow("SELECT COUNT(id) AS anzahl FROM cms_stats WHERE site_id = ".$this->site->id." AND time >= '".$from->toDateTimeString()."' AND time <= '".$to->toDateTimeString()."'");
		$my_visits['anzahl'] = count((array)\DB::getQueryRows("SELECT id FROM cms_stats WHERE site_id = ".$this->site->id." AND time > '".$from->toDateTimeString()."' AND time < '".$to->toDateTimeString()."' AND session != '' GROUP BY session"));
		$my_exec = \DB::getQueryRow("SELECT AVG(duration) AS anzahl FROM cms_stats WHERE site_id = ".$this->site->id." AND time > '".$from->toDateTimeString()."' AND time < '".$to->toDateTimeString()."'");
		$res_duration = (array)\DB::getQueryRows("SELECT MAX(time)-MIN(time) AS diff FROM cms_stats WHERE site_id = ".$this->site->id." AND time > '".$from->toDateTimeString()."' AND time < '".$to->toDateTimeString()."' AND session != '' GROUP BY session");
		$j=0;
		$duration=0;
		foreach($res_duration as $my_duration) {
			$duration += $my_duration['diff'];
			$j++;
		}
		if($j>0)
			$my_duration['anzahl'] = round($duration / $j / 60);
		else
			$my_duration['anzahl'] = "0";

		$dayname = $day->isoFormat('dd');

		$my_exec['anzahl'] = round($my_exec['anzahl']*1000);

		$aValues[0][] = $my_visits['anzahl'];
		$aValues[1][] = $my_views['anzahl'];
		if($my_visits['anzahl']>0)
			$iViewsPVisit = round($my_views['anzahl']/$my_visits['anzahl']);
		else
			$iViewsPVisit = "0";
		$aValues[2][] = $iViewsPVisit;
		$aValues[3][] = $my_duration['anzahl'];
		$aValues[4][] = $my_exec['anzahl'];

		$aDesc[] = $dayname;

		$aTable[] = array($day->isoFormat('D. dd'),$my_visits['anzahl'],$my_views['anzahl'],$iViewsPVisit,$my_duration['anzahl']." m",$my_exec['anzahl']." ms");

		$day->addDay();
		
	}

	?>
	<p align="center">
	<?
//	$graph = new classGraph(0);
//	$graph->setValues($aValues);
//	$graph->setXDesc($aDesc);
//	$graph->setWidth(750);
//	$graph->setHeight(150);
//	$graph->showGraph();
	?>
	</p>
	<table class="table table-condensed">
		<tr>
			<th width="20%">Tag</th>
			<th width="15%"><img src="/admin/media/stats_1.png" height="13" width="13" border="0" align="absmiddle"> Besucher						</th>
			<th width="15%"><img src="/admin/media/stats_2.png" height="13" width="13" border="0" align="absmiddle"> Seitenaufrufe</th>
			<th width="15%"><img src="/admin/media/stats_3.png" height="13" width="13" border="0" align="absmiddle"> Seiten pro Besucher</th>
			<th width="15%"><img src="/admin/media/stats_4.png" height="13" width="13" border="0" align="absmiddle"> Aufenthaltsdauer</th>
			<th width="15%"><img src="/admin/media/stats_5.png" height="13" width="13" border="0" align="absmiddle"> Ausführungszeit</th>
		</tr>
	<?
		foreach($aTable as $key=>$elem) {
	?>
		<tr id="tr_<?=$key?>" onmouseout="showRow('tr_<?=$key?>',0)" onmousemove="showRow('tr_<?=$key?>',1);">
	<?
			foreach($elem as $k=>$val) {
				$val = (!$val)?"0":$val;
	?>
			<td align="<?=(($k>0)?"right":"left")?>"><?=$val?></td>
	<?
			}
	?>
		</tr>
	<?
		}
	?>
	</table>

	<?
	// MONATSÜBERSICHT ENDE
	}


	//WOCHENÜBERSICHT START
	$aValues = array();//Werte zurücksetzen
	$aDesc = array();
	$timestamp = strtotime("last Monday");

		//SCHLEIFE FÜR WOCHENTAGE => WHERE DAYOFWEEK($wd)
		for($wd=0;$wd < 7;$wd++){
			$my_visits = \DB::getQueryRow("SELECT `time`, COUNT(`id`) as anzahl, WEEKDAY(`time`) as tag FROM cms_stats WHERE site_id = ".$this->site->id." AND time > '".$iGlobalFrom->toDateTimeString()."' AND time < '".$iGlobalTo->toDateTimeString()."' AND WEEKDAY(`time`) = ".$wd." GROUP BY tag");
			$my_views['anzahl'] =  count((array)\DB::getQueryRows("SELECT `id` FROM cms_stats WHERE site_id = ".$this->site->id." AND time > '".$iGlobalFrom->toDateTimeString()."' AND time < '".$iGlobalTo->toDateTimeString()."' AND session!='' AND WEEKDAY(`time`) = ".$wd." GROUP BY session"));
			$res_duration = (array)\DB::getQueryRows("SELECT MAX(time)-MIN(time) AS diff FROM cms_stats WHERE site_id = ".$this->site->id." AND time > '".$from->toDateTimeString()."' AND time < '".$to->toDateTimeString()."' AND session != '' AND WEEKDAY(`time`) = ".$wd." GROUP BY session");
			$my_exec = \DB::getQueryRow("SELECT AVG(duration) AS anzahl FROM cms_stats WHERE site_id = ".$this->site->id." AND time > '".$iGlobalFrom->toDateTimeString()."' AND time < '".$iGlobalTo->toDateTimeString()."' AND WEEKDAY(`time`) = ".$wd."");

			if(!empty($my_visits['anzahl']))
				$iViewsPVisit = round($my_views['anzahl']/$my_visits['anzahl']);
			else
				$iViewsPVisit = "0";


			$j=0;
			$duration=0;
			foreach($res_duration as $my_duration) {
				$duration += $my_duration['diff'];
				$j++;
			}
			if($j>0)
			$my_duration['anzahl'] = round($duration / $j / 60);
			else
			$my_duration['anzahl'] = "0";

			$aDesc[] = strftime("%a",strtotime("+".$wd." Day",$timestamp));
			$aValues[0][] = $my_views['anzahl'] ?? 0;
			$aValues[1][] = $my_visits['anzahl'] ?? 0;
			$aValues[2][] = $iViewsPVisit;
			$aValues[3][] = $my_duration['anzahl'] ?? 0;
			$aValues[4][] = round($my_exec['anzahl']*1000);
		}


	?>
	<!--
	<table class="table table-condensed">
		<tr>
			<td><h2>Wochentage</h2></td>
			<td><h2>Tageszeit</h2></td>
		</tr>
		<tr>
			<td>
				<?
				//GRAPH FÜR WOCHENÜBERSICHT
//				$graph = new classGraph(0);
//				$graph->setValues($aValues);
//				$graph->setXDesc($aDesc);
//				$graph->setWidth(300);
//				$graph->setHeight(60);
//				$graph->showGraph();
	//WOCHENÜBERSICHT ENDE
				?>
			</td>
			<td>
				<?
	//TAGÜBERSICHT START
				$my_views = $aValues = array();//Werte zurücksetzen
				$aDesc = array();
				$timestamp = strtotime("last Monday");

				//SCHLEIFE FÜR WOCHENTAGE => WHERE DAYOFWEEK($wd)
				for($ih=0;$ih < 24;$ih++){
					$my_visits = \DB::getQueryRow("SELECT `time`, COUNT(`id`) as anzahl, HOUR(FROM_UNIXTIME(`time`)) as stunde FROM cms_stats WHERE site_id = ".$this->site->id." AND time > '".$iGlobalFrom->toDateTimeString()."' AND time < '".$iGlobalTo->toDateTimeString()."' AND HOUR(FROM_UNIXTIME(`time`)) = ".$ih." GROUP BY stunde");
					$my_views['anzahl'] = count((array)\DB::getQueryRows("SELECT `id` FROM cms_stats WHERE time > '".$iGlobalFrom->toDateTimeString()."' AND site_id = ".$this->site->id." AND time < '".$iGlobalTo->toDateTimeString()."' AND session!='' AND HOUR(FROM_UNIXTIME(`time`)) = ".$ih." GROUP BY session"));
					$res_duration = (array)\DB::getQueryRows("SELECT MAX(time)-MIN(time) AS diff FROM cms_stats WHERE time > '".$from->toDateTimeString()."' AND site_id = ".$this->site->id." AND time < '".$to->toDateTimeString()."' AND session != '' AND HOUR(FROM_UNIXTIME(`time`)) = ".$ih." GROUP BY session");
					$my_exec = \DB::getQueryRow("SELECT AVG(duration) AS anzahl FROM cms_stats WHERE site_id = ".$this->site->id." AND time > '".$iGlobalFrom->toDateTimeString()."' AND time < '".$iGlobalTo->toDateTimeString()."' AND HOUR(FROM_UNIXTIME(`time`)) = ".$ih."");

					if(!empty($my_visits['anzahl']))
						$iViewsPVisit = round($my_views['anzahl']/$my_visits['anzahl']);
					else
						$iViewsPVisit = "0";

					$j=0;
					$duration=0;
					foreach($res_duration as $my_duration) {
						$duration += $my_duration['diff'];
						$j++;
					}
					
					if($j>0)
						$anzahl = round($duration / $j / 60);
					else
						$anzahl = "0";

					$aDesc[] = $ih;
					$aValues[0][] = $my_views['anzahl'];
					$aValues[1][] = $my_visits['anzahl'] ?? 0;
					$aValues[2][] = $iViewsPVisit;
					$aValues[3][] = $anzahl;
					$aValues[4][] = round($my_exec['anzahl']*1000);
				}

				//GRAPH FÜR TAGÜBERSICHT
//				$graph = new classGraph(0);
//				$graph->setValues($aValues);
//				$graph->setXDesc($aDesc);
//				$graph->setWidth(600);
//				$graph->setHeight(60);
//				$graph->showGraph();
	//TAGÜBERSICHT ENDE
	?>
			</td>
		</tr>
	</table>
	-->

	<?
	//SEITENCHARTS START
		$rCharts = array();
		$aCharts = array();
		$iCount = $key;
	?>

	<div class="row">
		<div class="col-md-6">
			
	
	<h3>Seitenzugriffe</h3>
				<table class="table table-condensed">
					<tr>
						<th width="70%">Seite</th>
						<th>Zugriffe</th>
						<th>(relativ)</th>
					</tr>
	<?
					$iZugriffe = count((array)\DB::getQueryRows("SELECT id FROM `cms_stats` WHERE site_id = ".$this->site->id." AND time > '".$iGlobalFrom->toDateTimeString()."' AND time < '".$iGlobalTo->toDateTimeString()."'"));
					$rCharts = (array)\DB::getQueryRows("SELECT page_id,COUNT(id) as anzahl FROM `cms_stats` WHERE site_id = ".$this->site->id." AND time > '".$iGlobalFrom->toDateTimeString()."' AND time < '".$iGlobalTo->toDateTimeString()."' GROUP BY `page_id` ORDER BY anzahl DESC");
					$i = 0;
					foreach($rCharts as $aCharts) {
						$iCount++;
						$i++;

						if($i>5) $tag = " style=\"display:none;\" id=\"seite_$i\" onmouseout=\"showRow('seite_$i',0)\" onmousemove=\"showRow('seite_$i',1)\" ";
						else $tag = " id=\"tr_$iCount\" onmouseout=\"showRow('tr_$iCount',0)\" onmousemove=\"showRow('tr_$iCount',1)\" ";?>
						<tr <?=$tag?>>
							<td><?=\Cms\Entity\Page::getInstance($aCharts['page_id'])->getTrack();?></td>
							<td align="right"><?=$aCharts['anzahl']?></td>
							<td align="right"><?=($iZugriffe>0)?round($aCharts['anzahl']/$iZugriffe*100,1):"0";?>%</td>
						</tr>
	<?				} ?>
						<tr><td colspan=3><a href="javascript:;" onclick="bolReadyState = 0;switchtr('seite');"><img id="seite" src="/admin/media/2downarrow.gif"></a></td></tr>
				</table>

		</div>
		<div class="col-md-6">
	<?
	//SEITENCHARTS ENDE

	//BROWSER START
		$rCharts = array();
		$aCharts = array();
	?>
		<h3>Browser</h3>
	<?
				$aBrowserName = array();
				$aBrowserNameVersion = array();
				$aOsName = array();

				$iZugriffe = count((array)\DB::getQueryRows("SELECT `session` FROM `cms_stats` WHERE site_id = ".$this->site->id." AND time > '".$iGlobalFrom->toDateTimeString()."' AND time < '".$iGlobalTo->toDateTimeString()."'"));
				$iBesucher = count((array)\DB::getQueryRows("SELECT `session` FROM `cms_stats` WHERE site_id = ".$this->site->id." AND time > '".$iGlobalFrom->toDateTimeString()."' AND time < '".$iGlobalTo->toDateTimeString()."' GROUP BY `session`"));
				$rCharts = (array)\DB::getQueryRows("SELECT agent, COUNT(session) as anzahl FROM `cms_stats` WHERE site_id = ".$this->site->id." AND time > '".$iGlobalFrom->toDateTimeString()."' AND time < '".$iGlobalTo->toDateTimeString()."' GROUP BY session ORDER BY anzahl DESC");
				foreach($rCharts as $aCharts) {

					$aBrowserInfo = \Core\Helper\Agent::getInfo($aCharts['agent']);
					
					if(!isset($aBrowserName[$aBrowserInfo['agent']])) {
						$aBrowserName[$aBrowserInfo['agent']] = 0;
					}
					
					$aBrowserName[$aBrowserInfo['agent']]++;
					
					if(!isset($aBrowserNameVersion[$aBrowserInfo['agent']][$aBrowserInfo['version']])) {
						$aBrowserNameVersion[$aBrowserInfo['agent']][$aBrowserInfo['version']] = 0;
					}
					
					$aBrowserNameVersion[$aBrowserInfo['agent']][$aBrowserInfo['version']]++;
					
					if(!isset($aOsName[$aBrowserInfo['os']])) {
						$aOsName[$aBrowserInfo['os']] = 0;
					}
					
					$aOsName[$aBrowserInfo['os']]++;
				} ?>

				<table class="table table-condensed">
					<tr>
						<th width="70%">Browser</th>
						<th>Zugriffe</th>
						<th>(relativ)</th>
					</tr>

	<?
	//ZUGRIFFE PER BROWSERNAME
					$i = 0;
					foreach((array)$aBrowserName as $key=>$val){
					 $iCount++;
					 $i++;
					 ?>
					  <tr id="tr_<?=$iCount?>" onmouseout="showRow('tr_<?=$iCount?>',0)" onmousemove="showRow('tr_<?=$iCount?>',1)">
						<td><?=$key?></td>
						<td align="right"><?=$val?></td>
						<td align="right"><?=($iBesucher>0)?round($val/$iBesucher*100, 1):"0"?>%</td>
					  </tr>
	<?				 foreach((array)$aBrowserNameVersion[$key] as $keyVersion=>$iv){
						$iCount++;
						$i++;
						$tag = " style=\"display:none;\" id=\"browser_$i\" onmouseout=\"showRow('browser_$i',0)\" onmousemove=\"showRow('browser_$i',1)\" ";
						?>
						<tr <?=$tag?>>
							<td><div style="text-indent: 4em;"><?=$key?> <?=$keyVersion?></div></td>
							<td align="right"><?=$iv?></td>
							<td align="right"><?=($iBesucher>0)?round($iv/$iBesucher*100,1):"0";?>%</td>

						</tr>
	<?				 }
					}
					?>

						<tr><td colspan=3><a href="javascript:;" onclick="bolReadyState = 0;switchtr('browser');"><img id="browser" src="/admin/media/2downarrow.gif"></a></td></tr>
				</table>
	<?
	//BROWSER ENDE
	?>
</div>
		</div>
	
	
	
	<div class="row">
		<div class="col-md-6">
	
	<?
	//BETRIEBSSYSTEM START
	?>
	<h2>Betriebssysteme</h2>
				<table class="table table-condensed">
					<tr>
						<th width="70%">Betriebssysteme</th>
						<th>Zugriffe</th>
						<th>(relativ)</th>
					</tr>
	<?				$i=0;
					foreach((array)$aOsName as $key=>$val){
						$iCount++;
						$i++;
						if($i>5) $tag = " style=\"display:none;\" id=\"os_$i\" onmouseout=\"showRow('os_$i',0)\" onmousemove=\"showRow('os_$i',1)\" ";
							else $tag = " id=\"tr_$iCount\" onmouseout=\"showRow('tr_$iCount',0)\" onmousemove=\"showRow('tr_$iCount',1)\" ";?>
						<tr <?=$tag?>>
							<td><?=$key?></td>
							<td align="right"><?=$val?></td>
							<td align="right"><?=($iBesucher>0)?round($val/$iBesucher*100,1):"0";?>%</td>
						</tr>
	<?
					}
					?>
						<tr><td colspan=3><a href="javascript:;" onclick="bolReadyState = 0;switchtr('os');"><img id="os" src="/admin/media/2downarrow.gif"></a></td></tr>
				</table>
	<?
	//BETRIEBSSYSTEM ENDE
	?>

	</div>
	<div class="col-md-6">


	<?
	//REFERER START
	?>
	<h2>Woher?</h2>
				<table class="table table-condensed">
					<tr>
						<th width="70%">URL</th>
						<th>Zugriffe</th>
						<th>(relative)</th>
					</tr>
	<?				$rCharts = array();
					$aCharts = array();
					$i = 0;

					$iLinkedBesucher = count((array)\DB::getQueryRows("SELECT SUBSTRING_INDEX(referer,'/',3) as adresse FROM `cms_stats` WHERE site_id = ".$this->site->id." AND `referer` != '' AND LOCATE(host,referer) = 0 "));
					$rCharts = (array)\DB::getQueryRows("SELECT SUBSTRING_INDEX(referer,'/',3) as adresse, COUNT(SUBSTRING_INDEX(referer,'/',3)) as anzahl FROM `cms_stats` WHERE site_id = ".$this->site->id." AND time > '".$iGlobalFrom->toDateTimeString()."' AND time < '".$iGlobalTo->toDateTimeString()."' AND `referer` != '' AND LOCATE(host,referer) = 0 GROUP BY adresse ORDER BY anzahl DESC");
					foreach($rCharts as $aCharts) {
						$iCount++;
						$i++;
						$aReferer[$aCharts['adresse']] = $aCharts['anzahl'];

						if($i>5) $tag = " style=\"display:none;\" id=\"referer_$i\" onmouseout=\"showRow('referer_$i',0)\" onmousemove=\"showRow('referer_$i',1)\" ";
							else $tag = " id=\"tr_$iCount\" onmouseout=\"showRow('tr_$iCount',0)\" onmousemove=\"showRow('tr_$iCount',1)\" ";?>
						<tr <?=$tag?>>
							<td><?=($aCharts['adresse'] == '')?"Direkteingabe":$aCharts['adresse'];?></td>
							<td align="right"><?=$aCharts['anzahl']?></td>
							<td align="right"><?=($iLinkedBesucher > 0)?round($aCharts['anzahl']/$iLinkedBesucher*100,1):"0";?>%</td>
						</tr>
					<?
					}
					?>
						<tr><td colspan=3><a href="javascript:;" onclick="bolReadyState = 0;switchtr('referer');"><img id="referer" src="/admin/media/2downarrow.gif"></a></td></tr>
				</table>
				<?
	//REFERER ENDE

	//GOOGLE START
	?>
	</div>
		</div>
	
	<div class="row">
		<div class="col-md-6">
	
	
	<?php
	//SESSION TRACKING START
	?>
	<h2>Session Tracking</h2>
				<table class="table table-condensed">
					<tr>
						<th width="70%">Session</th>
						<th>Aufenthaltsdauer</th>
						<th>besuchte Seiten</th>
					</tr>
	<?
					$my_session = array();
					$query = "SELECT id,ip,session,COUNT(session) AS anzahl,MIN(time) AS start,MAX(time) AS end, (MAX(time) - MIN(time)) AS period FROM cms_stats WHERE site_id = ".$this->site->id." AND session != '' GROUP BY session ORDER BY start DESC LIMIT 100";
					$result_session = (array)\DB::getQueryRows($query);
					$i=1;

					foreach($result_session as $my_session) {
						$iCount++;
						$i++;

						if($i>5) $tag = " style=\"display:none;\" id=\"sessiontrack_".$i."\" onmouseout=\"showRow('sessiontrack_".$i."',0)\" onmousemove=\"showRow('sessiontrack_".$i."',1)\" ";
						else $tag = " id=\"tr_".$iCount."\" onmouseout=\"showRow('tr_".$iCount."',0)\" onmousemove=\"showRow('tr_".$iCount."',1)\" ";?>
					<tr <?=$tag?>>
						<td><a href="javascript:popUpWindow('<?=$my_session['session']?>');"><?=($my_session['start']." - ".$my_session['session']);?></a></td>
						<td align="right"><?=(round($my_session['period']/60));?>min <?=($my_session['period']%60);?>sek</td>
						<td align="right"><?=$my_session['anzahl']?></td>
					</tr>
	<?				}
	?>
					<tr><td colspan=3><a href="javascript:;" onclick="bolReadyState = 0;switchtr('sessiontrack');" ><img id="sessiontrack" src="/admin/media/2downarrow.gif"></a></td></tr>
				</table>
			
	
	</div>
	<div class="col-md-6">

	<?
	//SESSION TRACKING ENDE



	//AUSFÜHRUNGSZEITEN START
		$rCharts = false;
		$aCharts = array();
	?>
	
	<h2>Generierungsdauer</h2>
				<table class="table table-condensed">
					<tr>
						<th width="70%">Seite</th>
						<th>Zugriffe</th>
						<th>Generierung</th>
					</tr>
	<?
					$rCharts = (array)\DB::getQueryRows("SELECT page_id,COUNT(id) as anzahl, AVG(duration) AS dauer FROM `cms_stats` WHERE site_id = ".$this->site->id." AND time > '".$iGlobalFrom->toDateTimeString()."' AND time < '".$iGlobalTo->toDateTimeString()."' GROUP BY `page_id` ORDER BY dauer DESC");
					$i = 0;
					foreach($rCharts as $aCharts) {
						$iCount++;
						$i++;

						if($i>5) $tag = " style=\"display:none;\" id=\"dauer_".$i."\" onmouseout=\"showRow('dauer_".$i."',0)\" onmousemove=\"showRow('dauer_".$i."',1)\" ";
						else $tag = " id=\"tr_".$iCount."\" onmouseout=\"showRow('tr_".$iCount."',0)\" onmousemove=\"showRow('tr_".$iCount."',1)\" ";?>
						<tr <?=$tag?>>
							<td><?=\Cms\Entity\Page::getInstance($aCharts['page_id'])->getTrack();?></td>
							<td align="right"><?=$aCharts['anzahl']?></td>
							<td align="right"><?=round($aCharts['dauer'],3)?> sek</td>
						</tr>
	<?php				} ?>
						<tr><td colspan=3><a href="javascript:;" onclick="bolReadyState = 0;switchtr('dauer');"><img id="dauer" src="/admin/media/2downarrow.gif"></a></td></tr>
				</table>

</div>
		</div>
	
	<p class="text-muted"><?=sprintf(\L10N::t('Generiert am %s', 'CMS'), strftime('%x %X'))?></p>
	
	<?php

		$content = ob_get_clean();

		return $content;
	}

}
