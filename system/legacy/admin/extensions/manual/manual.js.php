<?
include (\Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

header("Content-type: text/javascript");
?>
	

	function makeSortable(){
		
		Sortable.create('tree', 
							{
								tree:true,
								scroll:window,
								dropOnEmpty:true,
								onUpdate:saveTree,
								hoverclass:'page-item-hover'
							}
						);  
	}
	
	function makeDroppables(){
		
		Droppables.add('tree', {
			  onDrop: function(element) {
			 	addTreeBranch($('tree'));
			  },
			  hoverclass : 'page-item-hover',
			  accept:'new_page'
			 });
		
		var aDrops = $$('.edit_list');
	    aDrops.each(function(oElement){
	    	Droppables.add(oElement.id, {
			  onDrop: function(element) {
			 	addTreeBranch(oElement);
			  },
			  hoverclass : 'page-item-hover',
			  accept:'new_page'
			 });
	   });
	}
	

	function saveEdit(){
		
		
		var url = '/system/extensions/manual/ajax.php';
		var iId = $('save[id]').value;
		
		var oEditorStart = FCKeditorAPI.GetInstance('PageEditor');
   	 	var HTML = oEditorStart.GetHTML();
		HTML = encodeURIComponent(HTML);
		var title = $('save[title]').value;
		title = encodeURIComponent(title);
		new Ajax.Request(url, {
		  method: 'post',
		  parameters : '&task=savePage&id='+iId+'&title='+title+'&html='+HTML,
		  onComplete : reloadTitle
		});
		
	}
	function reloadTitle(objResponse){
		
		var aData  = objResponse.responseText.evalJSON();
		var oElement = $('page_'+aData['id']);
		var oA = oElement.down('span');
		oA.innerHTML = aData['title'];
		printTrue();
	}
	function deleteTreeBranch(iId){
		
		if (confirm("<?=L10N::t('Wollen Sie die Seite wirklich löschen?')?>")) {
			showLoading();
			$('page_'+iId).remove();
			
			var url = '/system/extensions/manual/ajax.php';
			
			new Ajax.Request(url, {
			  method: 'post',
			  parameters : '&task=deleteTreeBranch&id='+iId,
			  onComplete : hideLoading
			});
		}
	}

    function saveTree(){
    	
    	showLoading();
    	
    	var sSort = Sortable.serialize('tree');
    	
    	var url = '/system/extensions/manual/ajax.php';
		
		new Ajax.Request(url, {
		  method: 'post',
		  parameters : '&task=saveTree&'+sSort,
		  onComplete : hideLoading
		});
    	
    }
    
    var lastTreeBranchElement;
    
    function addTreeBranch(oElement){
    	lastTreeBranchElement = oElement;
    	showLoading();
    	var parent_id = oElement.id.replace('droppoint_', '');
    	
    	var url = '/system/extensions/manual/ajax.php';
		
		new Ajax.Request(url, {
		  method: 'post',
		  parameters : '&task=addTreeBranch&parent_id='+parent_id,
		  onComplete : addTreeBranchCallback
		});
    	    	
    }
    
    function addTreeBranchCallback(objResponse){

		var aData  = objResponse.responseText.evalJSON();
		var iId = aData['id'];
		var html = aData['html'];
    	var oLi = new Element('li');
        oLi.id =  'page_'+iId;  
        oLi.className =  'clear-element page-item sort-handle left';  
        oLi.innerHTML = html;               
        var oUl = new Element('ul');
        oUl.id = "droppoint_"+iId;
        oUl.className = "edit_list";

    	oLi.insert(oUl,'content');

    	lastTreeBranchElement.insert(oLi, 'content');
		saveTree();
    	hideLoading();
    }
    

    
    function loadEdit(id){

    	showLoading();
    	
    	var url = '/system/extensions/manual/ajax.php';
		
		new Ajax.Request(url, {
		  method: 'post',
		  parameters : '&task=loadEdit&id='+id,
		  onComplete : openEdit
		});
    	    	
    }
    
    function openEdit(objResponse){
		var aData  = objResponse.responseText.evalJSON();
    	
    	var HTML = "";
    	HTML +=	'<input type="hidden" id="save[id]" name ="save[id]" value="'+aData['id']+'"';
    	HTML +=	'<label for="title"><?=L10N::t('Seitentitel')?>: </label><input class="txt" id="save[title]" name="save[title]" value="'+aData['title']+'" />';
      	HTML +=	'<br/><br/>';
    	var oFCKeditorS    = new FCKeditor('PageEditor');
    	oFCKeditorS.ToolbarSet  = 'Default';
	    oFCKeditorS.Value   = aData['content'];
	    oFCKeditorS.Height   = '400';
	    oFCKeditorS.Width   = '100%';
	    HTML  += oFCKeditorS.CreateHtml();
    	HTML  += '<button class="btn" onclick="saveEdit();return false;" style="opacity:1; filter:alpha(opacity=100);"><?=L10N::t('Speichern')?></button>';
    	prepareDialog(HTML);
    	
    }
    
    
    // Generelle Funktionen

	var objLitBox;
	function prepareDialog(HTML){
		
		var iWidth = 700 - 20;
		
		var HTML_ = '<div style="width:'+iWidth+'px;"><div id="dialog_content" style="padding-left:10px;padding-right:10px;"></div></div>';
		if(!objLitBox){
			objLitBox = new LITBox(HTML_, {type:'alert', overlay:true,height:500, width:700, resizable:false, opacity:.9});
		} else {
			if(!$('dialog_content')){
				objLitBox.getWindow();
				objLitBox.d4.update(HTML_);
				objLitBox.display();
			}
		}
		openDialog(HTML);
	}



	function openDialog(HTML){
	
		$('dialog_content').update(HTML);
		hideLoading();
	}
	
	function printTrue(){
		
		var HTML = '<div style="color:green;"><?=L10N::t('Erfolgreich gespeichert!')?></div>';
		updateLBTitle(HTML);
	}
	function printFalse(){
		var HTML = '<div style="color:red;"><?=L10N::t('Speichern fehlgeschlagen!')?></div>';
		updateLBTitle(HTML);
	}
    function updateLBTitle(HTML){
		if($('LB_title')){
			$('LB_title').update(HTML);
			clearLbTitle();
		}
	}
	function clearLbTitle(){
		
		new PeriodicalExecuter(function(pe) {
		  	if($('LB_title')){
		  		$('LB_title').update("");
		  	}
		    pe.stop();
		}, 5);
		
	}
    function showLoading(){
    	
    	$('loading').show();
    	    	
    }
    function hideLoading(){
    	Sortable.destroy('tree');  
    	makeSortable();
    	makeDroppables();
    	$('loading').hide();
    }
    