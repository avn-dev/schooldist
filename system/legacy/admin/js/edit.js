var status = new Array();
var onoff = new Array();
var currentdiv = new Array();
var currentProperty = false;
var compvar = "";
var img = new Array();
var tEdit;

function save_db(page_id,myitem,div) {

	img[0] = new Image();
	img[0].src = "/admin/media/icon_status.gif";

	if(
		parent.content && 
		parent.content.edittop
	) {
		parent.content.edittop.document.all.btnStatus.src = img[0].src;
	}

	var target = parent.save;
	var winf = "no";
	var el = parent.content.page.document.getElementById(div);
	compvar = el.innerHTML;

	if(el.status == "preview" || el.status == undefined){
		tempcode = encodeURIComponent(el.innerHTML);
	} else {
		tempcode = encodeURIComponent(document.all.codearea.value);
	}

	target.document.save_db_form.edit.value = "ok";
	target.document.save_db_form.page_id.value = page_id;
	target.document.save_db_form.item.value = myitem;
	target.document.save_db_form.focus.value = winf;
	target.document.save_db_form.code.value = tempcode+"XXX_CODE_ENDE_XXX";
	target.document.save_db_form.submit();
	target.winfocus = self;

}

function saveContentCallback(objResponse) {
	alert(objResponse.responseText);
}

function changeeditable(page_id,myitem,div) {
	div_id = div;
	var el = parent.content.page.document.getElementById(div);
	if(el.contentEditable == 'true') {
		parent.content.edittop.document.getElementById('DIV_publish').style.visibility = "hidden";
		savecheck('check');
		currentdiv = false;
		div_id = false;
		parent.content.page.document.getElementById('scontentmain').style.display='none';
		el.contentEditable = 'false';
		el.style.borderColor= "lime";
		el.onoff = "off";
		tEdit.__hideArrows();
		tEdit.__hideTableIcon();
		tEdit = false;
	} else {
		tEdit = new tableEditor(div, '');
		savecheck('init');
		currentdiv = new Array(page_id,myitem,div);
		el = parent.content.page.document.getElementById(div);
		doc = el;
		doc.focus();
		//	initEditor();
	    init();
		toggle_borders();
		parent.content.page.document.getElementById('scontentmain').style.display='block';
		el.contentEditable = 'true';
		el.style.borderColor= "red";
		el.onoff = "on";
		hOnMouseDblClick = "dblclickfkt();";
		el.ondblclick = handlemousedblclick;
	}

}

function show_code(page_id,myitem,div,num) {
	currentdiv = new Array(page_id,myitem,div);
	savecheck('init');
	var obj = "opener.document.getElementById('"+div+"')";
	parent.content.page.open('/admin/editor.html?page_id='+page_id+'&id='+myitem+'&type=div&target='+obj,'editor','status=no,resizable=yes,menubar=no,scrollbars=yes,width=770,height=550');

	/*
	var el = parent.content.page.document.getElementById(div);
	tempcode = el.innerHTML;
	if(el.status == "preview" || el.status == undefined){
		el.contentEditable = 'false';
		el.style.borderColor= "red";
		var areacode = '<textarea style="width:100%;" rows="10" name="codearea" id="codearea" wrap="physical" style="border: none;">'+tempcode+'</textarea>';
		el.innerHTML = areacode;
		el.status = "code";
	}
	*/
}

function show_preview(page_id,myitem,div) {
	var el = parent.content.page.document.getElementById(div);
	tempcode = el.innerText;
	
	if(el.status == "code"){
		el.innerHTML = tempcode;
		el.status = "preview";
		if(el.onoff == "on") {
			el.style.borderColor = "red";
			el.contentEditable = 'true';
		} else {
			el.style.borderColor = "lime";
		el.contentEditable = 'false';
		}
	}
}

function swap_icons(div,elem) {
	var el = parent.content.page.document.getElementById(div);

	if(el.status == "preview" || el.status == undefined){
		parent.content.page.document.getElementById('edit_preview1_'+elem).style.display = "none";
		parent.content.page.document.getElementById('edit_preview2_'+elem).style.display = "inline";
	}else{
		parent.content.page.document.getElementById('edit_preview2_'+elem).style.display = "none";
		parent.content.page.document.getElementById('edit_preview1_'+elem).style.display = "inline";
	}
}

function swap_icons2(div,elem) {
	if(div == 1 || div != 2){
		parent.content.page.document.getElementById('editable_1_'+elem).style.display = "none";
		parent.content.page.document.getElementById('editable_2_'+elem).style.display = "inline";
	}else{
		parent.content.page.document.getElementById('editable_2_'+elem).style.display = "none";
		parent.content.page.document.getElementById('editable_1_'+elem).style.display = "inline";
	}
}

function selectmodul(page_id,myitem,div) {
parent.content.page.window.open("/admin/select_mod.html?page_id=" + page_id + "&item=" + myitem + "","edit","status=no,resizable=yes,menubar=no,scrollbars=yes,width=500,height=400");
}

function savecheck(mode) {
	if(currentdiv.length > 0) {
		var el = parent.content.page.document.getElementById(currentdiv[2]);
		if(mode == "init") {
			compvar = el.innerHTML;
		}
		if(mode == "check") {
			if(compvar != el.innerHTML) {
				if(confirm("Möchten Sie die Änderungen speichern?")) {
					save_db(currentdiv[0],currentdiv[1],currentdiv[2]);
					return false;
				} else {
					return true;
				}
			}
		}
	}
}

function resetpublish() {
	parent.content.edittop.document.all.DIV_publish.style.visibility = "hidden";
}

  function toggle_borders()
  {
      tbls = doc.getElementsByTagName("TABLE");
    var tbln = 0;
    if (tbls != null) tbln = tbls.length;
    for (ti = 0; ti<tbln; ti++)
    {
      if ((tbls[ti].style.borderWidth == 0 || tbls[ti].style.borderWidth == "0px") &&
          (tbls[ti].border == 0 || tbls[ti].border == "0px"))
      {
        tbls[ti].runtimeStyle.borderWidth = "1px";
        tbls[ti].runtimeStyle.borderStyle = "dashed";
        tbls[ti].runtimeStyle.borderColor = "#aaaaaa";
      } // no border
      else 
      {
        tbls[ti].runtimeStyle.borderWidth = "";
        tbls[ti].runtimeStyle.borderStyle = "";
        tbls[ti].runtimeStyle.borderColor = "";
      }
        
      var cls = tbls[ti].cells;
      // loop through cells
      for (ci = 0; ci<cls.length; ci++)
      {
        if ((tbls[ti].style.borderWidth == 0 || tbls[ti].style.borderWidth == "0px") &&
            (tbls[ti].border == 0 || tbls[ti].border == "0px") && 
            (cls[ci].style.borderWidth == 0 || cls[ci].style.borderWidth == "0px"))
        {
          cls[ci].runtimeStyle.borderWidth = "1px";
          cls[ci].runtimeStyle.borderStyle = "dashed";
          cls[ci].runtimeStyle.borderColor = "#aaaaaa";
        }
        else 
        {
          cls[ci].runtimeStyle.borderWidth = "";
          cls[ci].runtimeStyle.borderStyle = "";
          cls[ci].runtimeStyle.borderColor = "";
        }
      } // cells loop
    } // tables loop
  }
  

function showhelp(text) {
document.all.help.innerHTML = "<b>&nbsp;"+text+"</b>";
}

function MM_swapImgRestore() { //v3.0
  var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
}
function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}
function MM_findObj(n, d) { //v4.0
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && document.getElementById) x=document.getElementById(n); return x;
}
function MM_swapImage() { //v3.0
  var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
   if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}
// hover(): Handles mouse hovering over toolbar buttons
function hover(on)
{
	var el = parent.content.page.event.srcElement;
	if (el && el.nodeName == "IMG") {
		if (on) {
			el.className = "rebar_button_hover";
		} else {
			el.className = "rebar_button";
		}
	}
}
// hover(): Handles mouse clicks on toolbar buttons
function press(on)
{
	var el = parent.content.page.event.srcElement;
	if (el && el.nodeName == "IMG") {
		if (on) {
			el.className = "rebar_button_down";
		} else { 
			el.className = el.className == "rebar_button_down"
							? "rebar_button_hover"
							: "rebar_button";
		}
	}
}

function reload() {
	parent.content.page.document.location.reload();
}

function editdiv(on){
	if(parent.content.edittop) {
		if(on == "hide") {
			parent.content.edittop.document.getElementById('btn_edit').style.display = "none";
		} else if(on == "on") {
			parent.content.edittop.document.getElementById('btn_edit').style.display = "inline";
		} else if(on == "off") {
			parent.content.edittop.document.getElementById('btn_edit').style.display = "none";
		}
	}
}

function propertiesdiv(on){
	if(parent.content.edittop) {
		if(on == "hide") {
			parent.content.edittop.document.getElementById('btn_properties').style.display = "none";
		} else if(on == "on") {
			parent.content.edittop.document.getElementById('btn_properties').style.display = "inline";
		} else if(on == "off") {
			parent.content.edittop.document.getElementById('btn_properties').style.display = "none";
		}
	}
}

function structurediv(on){
	if(parent.content.edittop) {
		if(on == "hide") {
			parent.content.edittop.document.getElementById('btn_structure').style.display = "none";
		} else if(on == "on") {
			parent.content.edittop.document.getElementById('btn_structure').style.display = "inline";
		} else if(on == "off") {
			parent.content.edittop.document.getElementById('btn_structure').style.display = "none";
		}
	}
}

//Start - Function hide/show Onlinebutton
function onlinediv(on){
	if(parent.content.edittop) {
		if(on == "hide") {
			parent.content.edittop.document.getElementById('btn_online').style.display = "none";
		} else if(on == "on") {
			parent.content.edittop.document.getElementById('btn_online').style.display = "inline";
		} else if(on == "off") {
			parent.content.edittop.document.getElementById('btn_online').style.display = "none";
		}
	}
}
//End

function showCurrentElement(obj) {

	if(currentProperty) {
		currentProperty.style.display = "none";
	}
	if(!obj)
		var p = parent.content.page.event.srcElement;
	else 
		var p = obj;

	var a = [];
	while (p && (p.nodeType == 1) && (p.tagName.toLowerCase() != 'div')) {
		status += p.nodeName;
		a.push(p);
		p = p.parentNode;
	}
	var _statusBarTree = parent.content.editbottom.document.getElementById('statusbar')
	ancestors = a;
	_statusBarTree.innerHTML = ''; // clear
	for (var i = ancestors.length; --i >= 0;) {
		var el = ancestors[i];
		if (!el) {
			// hell knows why we get here; this
			// could be a classic example of why
			// it's good to check for conditions
			// that are impossible to happen ;-)
			continue;
		}
		var a = parent.content.editbottom.document.createElement("a");
		a.href = "#";
		a.el = el;
		a.editor = this;
		a.onclick = function() {
			this.blur();
			this.editor.selectNodeContents(this.el);
			parent.parent.preload.showCurrentElement(this.el);
			return false;
		};
		a.oncontextmenu = function() {
			// TODO: add context menu here
			this.blur();
			var info = "Inline style:\n\n";
			info += this.el.style.cssText.split(/;\s*/).join(";\n");
			alert(info);
			return false;
		};
		var txt = el.tagName.toUpperCase();
		a.title = el.style.cssText;
		if (el.id) {
			txt += "#" + el.id;
		}
		if (el.className) {
			txt += "." + el.className;
		}
		a.appendChild(parent.content.editbottom.document.createTextNode(txt));
		_statusBarTree.appendChild(a);
		if (i != 0) {
			_statusBarTree.appendChild(parent.content.editbottom.document.createTextNode(String.fromCharCode(0xbb)));
		}
		if(i==0) {
			var objProperty = parent.content.editbottom.document.getElementById("properties_"+el.tagName.toLowerCase());
			if(objProperty) {
				objProperty.style.display = "block";
				parent.content.editbottom.getProperties(el,el.tagName.toLowerCase());
				currentProperty=objProperty;
			}
		}
	}
}

// Selects the contents inside the given node
function selectNodeContents(node, pos) {
	parent.content.editbottom.document.focus();
	var range;
	var collapsed = (typeof pos != "undefined");
	range = parent.content.page.document.body.createTextRange();
	range.moveToElementText(node);
	(collapsed) && range.collapse(pos);
	range.select();
}

function showStatus(text) {
	parent.content.editbottom.document.getElementById('statusbar').innerText = text;
}

var isparentmod = new Array();
// This defines the scriptlets public interface.  See rte_interface.js for
// the actual interface definition.
var public_description =  new RichEditor();
function initEditor() {
	if (!public_description.styleData) {
		public_description.put_styleData(null);
	}
	// Apply default editor options
	// applyOptions("history=1;source=1");

	// Apply editor options
	//if (public_description.options) {
	//	applyOptions(public_description.options);
	//}
}

function image() {
	var temp_div = div_id;
	parent.content.page.open('/admin/frame.html?file=media&mode=insert&target=top.opener&div=' + temp_div,'media','status=no,resizable=yes,menubar=no,scrollbars=yes,width=770,height=550');
}
function makelink() {
	var temp_div = div_id;
	parent.content.page.open('/admin/ins_link.html?target=opener&div=' + temp_div,'link','status=no,resizable=no,menubar=no,scrollbars=yes,width=300,height=200');
}
function modul() {
	var temp_div = div_id;
	parent.content.page.open('/admin/ins_modul.html?page_id='+parent.content.edittop.pageId+'&div=' + temp_div + '&parent=' + isparentmod[temp_div],'modul','status=no,resizable=no,menubar=no,scrollbars=yes,width=300,height=200');
}
function linkglossary() {
	var temp_div = div_id;
	parent.content.page.open('/admin/ins_glossary.html?page_id='+parent.content.edittop.pageId+'&div=' + temp_div,'modul','status=no,resizable=no,menubar=no,scrollbars=yes,width=300,height=200');
}
function content() {
	var temp_div = div_id;
	parent.content.page.open('/admin/ins_content.html?page_id='+parent.content.edittop.pageId+'&div=' + temp_div,'modul','status=no,resizable=no,menubar=no,scrollbars=yes,width=300,height=200');
}
function setnew(what) {
	parent.content.page.open('/admin/dlg_ins_' + what + '.html','setnew','status=no,resizable=no,menubar=no,scrollbars=no,width=300,height=400');
}
function resetstatus() {
	parent.content.editbottom.document.getElementById('statusbar').innerText ='';
}

function generate(on,temp_page_id,active) {
	var buffer = "";
	if(!active) {
		if(confirm("Diese Seite ist momentan inaktiv. Klicken Sie bitte auf 'OK' wenn Sie diese Seite freischalten möchten?")) {
			buffer = "&activate=1";
		}
	}
	parent.remote.location.href = 'remote.html?page_id='+temp_page_id+'&action=' + on + buffer;
}

function switchLayer(layer) {
	parent.content.page.location.href = "?mode=edit&layer="+layer;
}


function openSubmodul(file,pageId,elementId,contentId) {
	parent.content.page.open('/admin/frame.html?file=extensions/'+file+'&page_id='+pageId+'&element_id='+elementId+'&content_id='+contentId,'modul','status=no,resizable=yes,menubar=no,scrollbars=yes,width=650,height=450');
}