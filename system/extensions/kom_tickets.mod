<?php
global $oAccessFrontend;

if(
	$oAccessFrontend instanceof Access_Frontend &&
	$oAccessFrontend->checkValidAccess() === true
) {

	$iCustomerId = $oAccessFrontend->customer_id;

	$oCustomer = new Ext_Office_Customer(null, $iCustomerId);
	
	$oTicketFrontend = new Ext_Office_Tickets_Frontend;
	
	$aProjects = $oTicketFrontend->getProjects();

	if(isset($_VARS['project_id'])) {
		$_SESSION['kom_tickets']['project_id'] = (int)$_VARS['project_id'];
	}

	if(isset($_SESSION['kom_tickets']['project_id'])) {
		foreach((array)$aProjects as $aProject) {
			if($aProject['id'] == $_SESSION['kom_tickets']['project_id']) {
				break;
			}
		}
	} else {
		$aProject = reset($aProjects);
	}

	$_SESSION['kom_tickets']['project_id'] = $aProject['id'];
	
	// Ajax, Position speichern
	if(isset($_VARS['positions'])) {
	
		$aPositions = (array)explode(",", $_VARS['positions']);
	
		$iPosition = 1;
		foreach($aPositions as $sTicketId) {
		
			$iTicketId = str_replace('ticket_id_', '', $sTicketId);
			
			$oTicket = new Ext_Office_Tickets($iTicketId);
			
			if($oTicket->id == $iTicketId) {
				$oTicket->position = $iPosition;
				$oTicket->save();
				$iPosition++;
			}
			
		}
	
		while(ob_get_level() > 0) {
			ob_end_clean();
		}
		die('1');
	
	}

	$aStates = Ext_Office_Tickets::getStates();
	
	$_VARS['filter_project'] = (int)$aProject['id'];
	?>
		
	<script src="/media/bootstrap/js/pasteimage.js"></script>
	<script>

		function paste(src) {
			
			if($('#paste-preview').length == 0) {
				return;
			}
			
			$('#paste-preview').html('<img src="' + src + '" style="display: block; max-height: 300px;">');

			// parse the uri to strip out "base64"
			var sourceSplit = src.split("base64,");
			var sourceString = sourceSplit[1];
			// Write base64-encoded string into input field
			$("#paste-image-input").val(sourceString);
			
		}

		$(function() {
			$.pasteimage(paste);
		});

	</script>

	<?php
	echo '<h1>Tickets</h1>';
	echo '<h2>Projekt ';
	echo '<select name="project_id" style="width: auto; font-weight: bold;font-size: 18px;height: auto;margin-bottom: 2px;" onchange="document.location.href=\''.$_SERVER['PHP_SELF'].'?project_id=\'+this.value;">';
	foreach((array)$aProjects as $aSelectProject) {
		echo '<option value="'.$aSelectProject['id'].'"'.(($aSelectProject['id']==$aProject['id'])?' selected="selected"':'').'>'.$aSelectProject['title'].'</option>';
	}
	echo '</select>';
	echo '</h2>';

	if(
		isset($_VARS['ticket_id']) &&
		$_VARS['ticket_id'] > 0
	) {
	
		$oTicket = new Ext_Office_Tickets($_VARS['ticket_id']);
	
		if($_VARS['task'] == 'save') {
		
			$aData = array(
				'id' => (int)$oTicket->id,
				'project_id' => (int)$aProject['id'],
				'state' => $_VARS['state'],
				'notice' => $_VARS['notice'],
			);

			$iLastState = $oTicket->getLastState();
			
			// Erledigt 
			if($_VARS['state'] == 6) {

				// Auf abgenommen
				if($_VARS['done'] == 1) {
					$aData['state'] = 7;
				// Zurücksetzen
				} else {
					$aData['state'] = $iLastState;
				}

			} elseif($_VARS['state'] == 4) {

				// Aufwandsschätzung
				if($_VARS['cost_estimation'] == 'accepted') {
					$aData['state'] = 5;
				} elseif($_VARS['cost_estimation'] == 'rejected') {
					$aData['state'] = 1;
				}

			}
			
			// Rückfrage auf In Bearbeitung
			if($_VARS['state'] == 3) {
				$aData['state'] = $iLastState;
			}

			$oNotice = $oTicketFrontend->save($aData);

			if(is_file($_FILES['upload']['tmp_name'])) {
				$oTicket->saveFile($_FILES['upload']['tmp_name'], $_FILES['upload']['name'], $oNotice->id);
			}

			if(!empty($_POST["paste-image-input"])) {
				$sSourceString = $_POST["paste-image-input"];
				$oImage = imagecreatefromstring(base64_decode($sSourceString));

				$sTmpFile = tempnam(sys_get_temp_dir(), 'kom');

				imagepng($oImage, $sTmpFile);

				if(is_file($sTmpFile)) {
					$oTicket->saveFile($sTmpFile, 'kom_tickets_'.Util::generateRandomString(8).'.png', $oNotice->id);
				}
			}
			
		}
		
		echo '<h3>Ticket '.$oTicket->id.': '.$oTicket->title.'</h3>';
	
		echo '<p>Aufwand: ';
		if($oTicket->billing == 1) {
			echo 'Nach Aufwand';
		} else {
			echo number_format($oTicket->hours, 2, ",", ".").' Stunden';
		}
		echo '</p>';

		$aNotices = $oTicket->getNotices();
		
		foreach($aNotices as $aNotice) {
		
			$oNotice = Ext_Office_Ticket_Notice::getInstance($aNotice['id']);
		
			$aFiles = $oNotice->getFiles();
		
			$sDate = strftime('%x %X', $aNotice['created']);
		
			echo $sDate.' - '.$aStates[$aNotice['state']].' - ';
		
			if($aNotice['user']) {
				echo $aNotice['user'];
			} else {
				echo $aNotice['contact'];
			}
		
			echo '<div class="notice">'.nl2br($aNotice['text']);
			
			if(!empty($aFiles)) {
				echo '<hr/>Uploads: '.implode(', ', $aFiles);
			}
			
			echo '</div>';
		
		}
	
?>

		<h2>Neuer Kommentar</h2>
		
		<form action="#page:11:pagelink#" method="post" enctype="multipart/form-data" role="form">
		  <input type="hidden" name="task" value="save" />
		  <input type="hidden" name="ticket_id" value="<?=$oTicket->id?>" />
		  <input type="hidden" name="state" value="<?=$aNotice['state']?>" />
		  <div class="form-group">
			<label class="control-label" for="notice">Kommentar</label>
			<div class="controls">
			  <textarea class="form-control" style="height: 100px;" id="notice" name="notice"></textarea>
			</div>
		  </div>
		  <div class="form-group">
			<label class="control-label" for="upload">Upload</label>
			<div class="controls">
			  <input type="file" name="upload" id="upload">
			</div>
		  </div>
		  <div class="form-group">
			<label class="control-label" for="upload">Screenshot</label>
			<div class="controls" id="paste-preview" style="max-height: 300px;">
				Bitte klicken Sie Strg+V, um einen Screenshot einzufügen!
			</div>
			<input type="hidden" name="paste-image-input" id="paste-image-input">
		  </div>

<?
		if($aNotice['state'] == 6) {
		?>
		 <div class="control-group">
			<label class="control-label" for="done">Als abgenommen markieren</label>
			<div class="controls">
			  <input type="checkbox" id="done" name="done" value="1" />
			</div>
		  </div>
		<?
		} elseif($aNotice['state'] == 4) {
		?>
		 <div class="control-group">
			<label class="control-label" for="cost_estimation">Aufwandsschätzung</label>
			<div class="controls">
				<select id="cost_estimation" name="cost_estimation" class="form-control">
					<option value=""></option>
					<option value="accepted">Angenommen</option>
					<option value="rejected">Abgelehnt</option>
				</select>
			</div>
		  </div>
		<?
		}
?>		  
		  <button type="submit" class="btn btn-primary">Kommentar speichern</button>
		  <button type="submit" class="btn btn-primary" style="float: right;" onclick="location.href='#page:11:pagelink#';return false;">Zurück</button>
		</form>
		
<?
	
	} else {
		
		if($_VARS['task'] == 'save') {
		
			$aData = array(
				'project_id' => (int)$aProject['id'],
				'title' => $_VARS['title'],
				'area' => 'Sonstiges',
				'type' => $_VARS['type'],
				'billing' => $_VARS['billing'],
				'state' => 1,
				'notice' => $_VARS['notice'],
			);
		
			$oNotice = $oTicketFrontend->save($aData);
		
			$oTicket = new Ext_Office_Tickets($oNotice->ticket_id);

			if(is_file($_FILES['upload']['tmp_name'])) {
				$oTicket->saveFile($_FILES['upload']['tmp_name'], $_FILES['upload']['name'], $oNotice->id);
			}
		
			if(!empty($_POST["paste-image-input"])) {
				$sSourceString = $_POST["paste-image-input"];
				$oImage = imagecreatefromstring(base64_decode($sSourceString));

				$sTmpFile = tempnam(sys_get_temp_dir(), 'kom');

				imagepng($oImage, $sTmpFile);

				if(is_file($sTmpFile)) {
					$oTicket->saveFile($sTmpFile, 'kom_tickets_'.Util::generateRandomString(8).'.png', $oNotice->id);
				}
			}

		}	
		
		$aTickets = (array)$oTicketFrontend->getTableListData();

	?>
		
		<h2>Tickets, die auf Ihre Rückmeldung warten</h2>
		
		<table class="table table-condensed table-hover">
			<thead>
				<tr>
					<th style="width: 25px;">ID</th>
					<th style="width: auto;">Titel</th>
					<th style="width: 130px;">Status</th>
					<th style="width: 60px;">Aufwand</th>
					<th style="width: 80px;">Fortschritt</th>
					<th style="width: 140px;">Letzte Änderung</th>
					<th style="width: 130px;">Bearbeiter</th>
				</tr>
			</thead>
			<tbody class="sortable">
	<?
	
			$oEmployee = new Ext_Office_Employee;
	
			foreach($aTickets['data'] as $aTicket) {
				if(
					$aTicket['state_id'] == 3 ||
					$aTicket['state_id'] == 6 ||
					$aTicket['state_id'] == 4
				) {
					$aHours = $oEmployee->getFormatedTimes($aTicket['hours']*60*60);
	?>
			<tr id="ticket_id_<?=$aTicket['id']?>">
				<td style="text-align:right;"><?=$aTicket['id']?></td>
				<td><a href="<?=$_SERVER['PHP_SELF']?>?ticket_id=<?=$aTicket['id']?>"><?=$aTicket['title']?></a></td>
				<td><?=$aTicket['state']?></td>
				<td><?=(($aTicket['billing'] == 1)?$aTicket['time_total']:$aHours['T'])?></td>
				<td><?=$aTicket['progressbar']?></td>
				<td><?=$aTicket['changed']?></td>
				<td><?=$aTicket['editor']?></td>
			</tr>
	<?
				}
			}
	?>
			<tbody>
		</table>
		
		<h2>Offene Tickets</h2>
		
		<table class="table table-condensed table-hover">
			<thead>
				<tr>
					<th style="width: 25px;">ID</th>
					<th style="width: auto;">Titel</th>
					<th style="width: 130px;">Status</th>
					<th style="width: 60px;">Aufwand</th>
					<th style="width: 80px;">Fortschritt</th>
					<th style="width: 140px;">Letzte Änderung</th>
					<th style="width: 130px;">Bearbeiter</th>
				</tr>
			</thead>
			<tbody class="sortable">
	<?
			foreach($aTickets['data'] as $aTicket) {
				if(
					$aTicket['state_id'] != 3 &&
					$aTicket['state_id'] != 4 &&
					$aTicket['state_id'] != 6 &&
					$aTicket['state_id'] != 7
				) {
					$aHours = $oEmployee->getFormatedTimes($aTicket['hours']*60*60);
	?>
			<tr id="ticket_id_<?=$aTicket['id']?>">
				<td style="text-align:right;"><?=$aTicket['id']?></td>
				<td><a href="<?=$_SERVER['PHP_SELF']?>?ticket_id=<?=$aTicket['id']?>"><?=$aTicket['title']?></a></td>
				<td><?=$aTicket['state']?></td>
				<td><?=(($aTicket['billing'] == 1)?$aTicket['time_total']:$aHours['T'])?></td>
				<td><?=$aTicket['progressbar']?></td>
				<td><?=$aTicket['changed']?></td>
				<td><?=$aTicket['editor']?></td>
			</tr>
	<?
				}
			}
	?>
			</tbody>
		</table>
		
		<div class="alert alert-info"><small>Sie können die Priorität Ihrer Tickets per Drag and Drop verändern.</small></div>
		
		<script>
			$(".sortable").sortable({
				update: function( event, ui ) {
					
					var aPositions = $(this).sortable('toArray').toString();
					$.get('<?=$_SERVER['PHP_SELF']?>', {positions:aPositions});
					/*
					$.ajax({
						type: "POST",
						url: "<?=$_SERVER['PHP_SELF']?>?positions",
						data: { name: "John", location: "Boston" }
					}).done(function( msg ) {
						
					});
					*/
				}
			});
		</script>
		
		<h2>Neues Ticket</h2>
		
		<form action="#page:11:pagelink#" method="post" enctype="multipart/form-data" role="form">
		  <input type="hidden" value="save" name="task" />
		  <div class="form-group">
			<label class="control-label" for="title">Titel</label>
			<div class="controls">
			  <input type="text" name="title" id="title" class="form-control input-xxlarge">
			</div>
		  </div>
		  <div class="form-group">
			<label class="control-label" for="notice">Beschreibung</label>
			<div class="controls">
			  <textarea class="form-control" style="height: 100px;" id="notice" name="notice"></textarea>
			</div>
		  </div>	
		  <div class="form-group">
			<label class="control-label" for="type">Typ</label>
			<div class="controls">
				<select id="type" name="type" class="form-control">
					<option value="ext">Erweiterung</option>
					<option value="bug">Fehler</option>
				</select>
			</div>
		  </div>
		  <div class="form-group">
			<label class="control-label" for="billing">Abrechnung</label>
			<div class="controls">
				<select id="billing" name="billing" class="form-control">
					<option value="1">Abrechnung nach Aufwand</option>
					<option value="0">Aufwandschätzung anfragen</option>
				</select>
			</div>
		  </div>	
		  <div class="form-group">
			<label class="control-label" for="upload">Upload</label>
			<div class="controls">
			  <input type="file" name="upload" id="upload">
			</div>
		  </div>
		  <div class="form-group">
			<label class="control-label" for="upload">Screenshot</label>
			<div class="controls" id="paste-preview" style="max-height: 300px;">
				Bitte klicken Sie Strg+V, um einen Screenshot einzufügen!
			</div>
			<input type="hidden" name="paste-image-input" id="paste-image-input">
		  </div>
		  <button type="submit" class="btn btn-primary">Ticket speichern</button>
		</form>
		
<?

	}

}