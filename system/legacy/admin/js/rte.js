// Internal (private) properties
RichEditor.txtView = true;			// WYSIWYS mode.  false == View Source

// checkRange(): make sure our pretend document (the content editable
// DIV with id of "doc") has focus and that a text range exists (which
// is what execCommand() operates on).
function checkRange()
{
	if (!RichEditor.txtView) return;		// Disabled in View Source mode
	parent.content.page.document.focus();
	if (parent.content.page.document.selection.type == "None") {
		return parent.content.page.document.selection.createRange();
	}
}

// post(): Called in response to clicking the post button in the
// toolbar. It fires an event in the container named post, passing the
// HTML of our newly edited document as the data argument.
function post()
{
	window.external.raiseEvent("post", doc.innerHTML);
}

// insert(): called in response to clicking the insert table, image,
// smily icons in the toolbar.  Loads up an appropriate dialog to
// prompt for information, the dialog then returns the HTML code or
// NULL.  We paste the HTML code into the document.
function insert(what)
{
	if (!RichEditor.txtView) return;		// Disabled in View Source mode

	// Chose action based on what is being inserted.
	switch(what)
	{
		case "char":
			strPage = "/admin/ins_char.html";
			strAttr = "status:no;dialogWidth:425px;dialogHeight:250px;help:no;scroll:no;";
		break;
	}

	// run the dialog that implements this type of element
	html = showModalDialog(strPage, RichEditor, strAttr);
	if (html) {
		doc.focus();
		var sel = document.selection.createRange();
		sel.pasteHTML(html);
	}

	//saveHistory();				// Record undo information
}

// doStyle(): called to handle the simple style commands such a bold,
// italic etc.  These require no special handling, just a call to
// execCommand().  We also call reset so that the toolbar represents
// the state of the current text.
function doStyleold(s){ 
	if(!RichEditor.txtView) return; 
	checkRange(); 
	if(s!='InsertHorizontalRule'){ 
		/* what command string? */ 
		parent.content.page.document.execCommand(s); 
	} else if( s=='InsertHorizontalRule') { 
    	/* if s=='InsertHorizontalRule then use this command */ 
		parent.content.page.document.execCommand('inserthorizontalrule', false, null);
   } 
   reset(); 
} 

function doStyle(s)
{
	if (!RichEditor.txtView) return;		// Disabled in View Source mode
	
	checkRange();
	parent.content.page.document.execCommand(s);
	var func = "";
	switch(s) {
		case "JustifyLeft":
		func = 'left';
		break;
		case "JustifyCenter":
		func = 'middle';
		break;
		case "JustifyRight":
		func = 'right';
		break;
	}
	
	var div = parent.content.page.document.selection.createRange();
	if (div.length == 1 && div(0).tagName == "IMG") {
		div(0).align = func;
		return;
	}

	var elem = div.parentElement();
	if(elem.tagName == "P") {
		elem.setAttribute("align",func,"0");
	}
	var arr = elem.getElementsByTagName("P");

	for(var i=0;i<arr.length;i++) {
		arr[i].setAttribute("align",func,"0");
	}
	reset();
	//saveHistory();				// Record undo information
	return false;
}

// dojustify(): macht schönen blocksatz
function doJustify(func) {

	checkRange();
	parent.content.page.document.execCommand('justifyleft');
	var div = parent.content.page.document.selection.createRange();
	var elem = div.parentElement();
	if(elem.tagName == "P") {
		elem.setAttribute("align",func,"0");
	}
	var arr = elem.getElementsByTagName("P");

	for(var i=0;i<arr.length;i++) {
		arr[i].setAttribute("align",func,"0");
}

//	reset();
//	saveHistory();				// Record undo information

}

// link(): called to insert a hyperlink.  It will use the selected text
// if there is some, or the URL entered if not.  If clicked when over a
// link, that link is allowed to be edited.
function link(on)
{
	if(on == "remove") {
		var r = parent.content.page.document.selection.createRange();
		r.execCommand("unlink");
	} else {
		if (!RichEditor.txtView) return;		// Disabled in View Source mode

		var strURL = "http://";
		var strText;

		// First, pick up the current selection.
		doc.focus();
		var r = parent.content.page.document.selection.createRange();
		var el = r.parentElement();

		// Is this aready a link?
		if (el && el.nodeName == "A") {
			//r.moveToElementText(el);
			if (on == 'remove') {		// If removing the link, then replace all with
				el.outerHTML = el.innerHTML;
				return;
			}
			strURL = el.href;
		}

		// Get the text associated with this link
		strText = r.text;

		// Prompt for the URL
		strURL = window.prompt("Enter URL", strURL);
		if (strURL) {
			// Default the TEXT to the url if non selected
			if (!strText || !strText.length) {
				strText = strURL;
			}

			// Replace with new URL
			r.pasteHTML('<A href=' + strURL + ' target=_new>' + strText + '</a>');
		}
	}
	reset();
}

// sel(); similar to doStyle() but called from the dropdown list boxes
// for font and style commands.
function sel(el)
{
	if (!RichEditor.txtView) return;		// Disabled in View Source mode
	//saveHistory();				// Record undo information
	checkRange();
	switch(el.id)
	{
	case "ctlFont":
		document.execCommand('FontName', false, el[el.selectedIndex].value);
		break;
	case "ctlSize":
		document.execCommand('FontSize', false, el[el.selectedIndex].value);
		break;
	case "ctlStyle":
		document.execCommand('FormatBlock', false, el[el.selectedIndex].text);
		break;
	}
	doc.focus();
	reset();
}

// pickColor(): called when the text or fill color icons are clicked.  Displays
// the color chooser control.  The color setting is completed by the event
// handler of this control (see richedit.html)
function pickColor(fg)
{
	if (!RichEditor.txtView) return;		// Disabled in View Source mode
	checkRange();
	var el = window.event.srcElement;
	if (el && el.nodeName == "IMG") {
		setState(el, true);
	}
	document.all.color.style.top = window.event.clientY + 10;
	document.all.color.style.left = window.event.clientX - 280;
	document.all.color.style.display = 'block';
	document.all.color._fg = fg;
}

// setValue(): called from reset() to make a select list show the current font
// or style attributes
function selValue(el, str)
{
	if (!RichEditor.txtView) return;		// Disabled in View Source mode
// window.status += ": " + el.id + ", [" + str + "]";
	for (var i = 0; i < el.length; i++) {
		if ((!el[i].value && el[i].text == str) || el[i].value == str) {
			el.selectedIndex = i;
			return;
		}
	}
	el.selectedIndex = 0;
}

// setState(): called from reset() to make a button represent the state
// of the current text.  Pressed is on, unpressed is off.
function setState(el, on)
{
	if (!RichEditor.txtView) return;		// Disabled in View Source mode
	if (!el.disabled) {
		if (on) {
			el.className = "down";
		} else {
			el.className = null;
		}
	}
}

// getStyle(): called to obtain the class or type of formatting applied to an element,
// This is used by reset() to set the state of the toolbar to indicate the class of
// the current element.
function getStyle() {
	var style = document.queryCommandValue('FormatBlock');
//window.status = 'style=[' + style + ']';
	if (style == "Normal") {
		doc.focus();
		var rng = document.selection.createRange();
		if (typeof(rng.parentElement) != "undefined") {
			var el = rng.parentElement();
			var tag = el.nodeName.toUpperCase();
			var str = el.className.toLowerCase();
//window.status += ", parent=" + tag + "#" + el.id + "." + str;
			if (!(tag == "DIV" && el.id == "doc" && str == "textedit")) {
				if (tag == "SPAN") {
					style = "." + str;
				} else if (str == "") {
					style = tag;
				} else {
					style = tag + "." + str;
				}
			}
//window.status += ', class=[' + style + ']';
			return style;
		}
	}
	return style;
}

// reset(): called from all over the place to make the toolbar
// represent the current text. If el specified, it was called from
// hover(off)
function reset(el)
{
	if (!RichEditor.txtView) return;		// Disabled in View Source mode
	// if (!el) parent.content.page.document.all.color.style.display = 'none';
	// if (!el || el == document.all.ctlStyle)			selValue(document.all.ctlStyle, getStyle());
	// if (!el || el == document.all.ctlFont)			selValue(document.all.ctlFont, document.queryCommandValue('FontName'));
	// if (!el || el == document.all.ctlSize)			selValue(document.all.ctlSize, document.queryCommandValue('FontSize'));
	if (!el || el == parent.content.page.document.all.btnBold)			setState(parent.content.page.document.all.btnBold, 		parent.content.page.document.queryCommandValue('Bold'));
	if (!el || el == parent.content.page.document.all.btnItalic)		setState(parent.content.page.document.all.btnItalic,	parent.content.page.document.queryCommandValue('Italic'));
	if (!el || el == parent.content.page.document.all.btnUnderline)		setState(parent.content.page.document.all.btnUnderline, parent.content.page.document.queryCommandValue('Underline'));
	if (!el || el == parent.content.page.document.all.btnStrikethrough)	setState(parent.content.page.document.all.btnStrikethrough, parent.content.page.document.queryCommandValue('Strikethrough'));
	if (!el || el == parent.content.page.document.all.btnLeftJustify)	setState(parent.content.page.document.all.btnLeftJustify, parent.content.page.document.queryCommandValue('JustifyLeft'));
	if (!el || el == parent.content.page.document.all.btnCenter)		setState(parent.content.page.document.all.btnCenter,	parent.content.page.document.queryCommandValue('JustifyCenter'));
	if (!el || el == parent.content.page.document.all.btnRightJustify)	setState(parent.content.page.document.all.btnRightJustify, parent.content.page.document.queryCommandValue('JustifyRight'));
	if (!el || el == parent.content.page.document.all.btnNumList)		setState(parent.content.page.document.all.btnNumList, parent.content.page.document.queryCommandValue('InsertOrderedList'));
	if (!el || el == parent.content.page.document.all.btnBulList)		setState(parent.content.page.document.all.btnBulList, parent.content.page.document.queryCommandValue('InsertUnorderedList'));
}

// hover(): Handles mouse hovering over toolbar buttons
function hover(on)
{
	if (!RichEditor.txtView) return;		// Disabled in View Source mode
	var el = window.event.srcElement;
	if (el && !el.disabled && el.nodeName == "IMG" && el.className != "spacer") {
		if (on) {
			el.className = "hover";
		} else {
			el.className = null;
		}
	}
}
// hover(): Handles mouse clicks on toolbar buttons
function press(on)
{
	if (!RichEditor.txtView) return;		// Disabled in View Source mode
	var el = window.event.srcElement;
	if (el && !el.disabled && el.nodeName == "IMG" && el.className != "spacer") {
		if (on) {
			el.className = "down";
		} else {
			el.className = el.className == "down" ? "hover" : null;
		}
	}
}

// init(): Initialise toolbar, called once the body has loaded.  Sets the
// initial state of the toolbar to represent the current text.
function init() {
	doc.focus();
	reset();
	//showHistory();
}

// Styleclass handling for central webDynamics-Style-Classes

// If someone presses the StyleClass-Button an additional div whith a list of avaliable classes appears.
// This function switches between showing and hiding this div
function switchFontclassDlg() {
	if(parent.content.page.document.getElementById('fontclass').style.display=="none") {
		parent.content.page.document.getElementById('fontclass').style.display="inline";
	} else
		parent.content.page.document.getElementById('fontclass').style.display="none";
}

// sets an selected textrange to a choosen Textclass
function setclass(class_name)
{   
	if (!RichEditor.txtView) return;		// Disabled in View Source mode
	checkRange();
	var r = parent.content.page.document.selection.createRange();
	r.select();
	doStyle('RemoveFormat');
	
	if (r.length == 1 && r(0).tagName == "IMG") {
		r(0).className = class_name;
		return;
	}
	
	var s = r.htmlText;
	// If we have some selected text, then ignore silly selections
	if (s == " " || s == "&nbsp;") {
		return;
	}
	r.pasteHTML("<FONT class='"+class_name+"'>" + s + "</FONT>")
	//saveHistory();				// Record undo information
}

// addTag(): This is the handler for the style dropdown.  This takes value
// selected and interprates it and makes the necessary changes to the HTML to
// apply this style.
function addTag(obj) {

	if (!RichEditor.txtView) return;		// Disabled in View Source mode

	// Determine the type of element we are dealing with.
	// TYPE 0 IS NORMAL-TAG, 1 IS CLASS, 2 IS SUBCLASS, 3 = Format Block command
	var value = obj[obj.selectedIndex].value;
	if (!value) {								// Format Block
		sel(obj);
		return;
	}

	var type = 0;								// TAG

	if (value.indexOf(".") == 0) {				// .className
		type = 1;
	} else if (value.indexOf(".") != -1) {		// TAG.className
		type = 2;
	}

	doc.focus();

	// Pick up the highlighted text
	var r = document.selection.createRange();
	r.select();
	var s = r.htmlText;

	// If we have some selected text, then ignore silly selections
	if (s == " " || s == "&nbsp;") {
		return;
	}

	// How we apply formatting is based upon the type of formitting being
	// done.
	switch(type)
	{
	case 1:
		// class: Wrap the selected text with a span of the specified
		// class name
		value = value.substring(1,value.length);
		r.pasteHTML("<span class="+value+">" + r.htmlText + "</span>")
		break;

	case 2:
		// subclass: split the value into tag + class
		value = value.split(".");
		r.pasteHTML('<' + value[0] + ' class="' + value[1] +'">'
					+ r.htmlText
					+ '</' + value[0] + '>'
				);
		break;

	default:
		// TAG: wrap up the highlighted text with the specified tag
		r.pasteHTML("<"+value+">"+r.htmlText+"</"+value+">")
		break;
	}

	//saveHistory();				// Record undo information
}

// initStyleDropdown(): This takes the passed styleList and generates the style
// dropdown list box from it.
function initStyleDropdown(styleList) {

	// Build the option list for the styles dropdown from the passed styles
	for (var i = 0; i < styleList.length; i++) {
		var oOption = document.createElement("OPTION");
		if (styleList[i][0]) oOption.value = styleList[i][0];
		oOption.text = styleList[i][1];
		oOption.style.backgroundColor = 'white';
		document.all.ctlStyle.add(oOption);
	}
}

// applyOptions(): This takes the passed options string and actions them.
// Called during the init process.
function applyOptions(str)
{
	var options = str.split(";");
	for (var i = 0; i < options.length; i++) {
		var eq = options[i].indexOf('=');
		var on = eq == -1 ? true : "yes;true;1".indexOf(options[i].substr(eq).toLowerCase()) != -1;
		switch(options[i].substr(0,eq))
		{
			case "history": 
				document.all.featureHistory.style.display = (on ? 'block' : 'none'); 
				break;
			case "source":
				document.all.featureSource.style.display = (on ? 'block' : 'none'); 
				break;
		}	
	}
}

function addRow(id){
    var tbody = document.getElementById
(id).getElementsByTagName("TBODY")[0];
    var row = document.createElement("TR")
    var td1 = document.createElement("TD")
    td1.appendChild(document.createTextNode("column 1"))
    var td2 = document.createElement("TD")
    td2.appendChild (document.createTextNode("column 2"))
    row.appendChild(td1);
    row.appendChild(td2);
    tbody.appendChild(row);
  }

function tableedit(action) {
	
	on = document.selection.createRange();

if(document.selection.type == 'Text') {
on = on.parentElement();
while(on.parentElement.tagName != 'TABLE') {
on = on.parentElement;
}
} else {
on = on(0);
}
			
switch(action) {

 case "newrow":
 newrow = on.insertRow();
 i = 0;
 k = on.rows.length - 1;
 while(on.rows[k-2].cells(i)) {
 newrow = on.rows[k].insertCell();
 i++;
 }
 newrow = on.insertCell();
 break;

 case "moverow":
 newrow = on.moveRow();
 break;

 case "deleterow":
 newrow = on.deleteRow();
 break;

 case "newcell":
 i = 0;
 while(on.rows[i]) {
 newrow = on.rows[i].insertCell();
 i++;
 }
 break;

 case "deletecell":
 i = 0;
 while(on.rows[i]) {
 newrow = on.rows[i].deleteCell();
 i++;
 }
 break;

}
	//saveHistory();
}

// farbwähler

function callColorDlg(on){

	var sColor = parent.content.page.dlgHelper.ChooseColorDlg();
	//change decimal to hex
	sColor = sColor.toString(16);
	//add extra zeroes if hex number is less than 6 digits
	if (sColor.length < 6) {
		var sTempString = "000000".substring(0,6-sColor.length);
		sColor = sTempString.concat(sColor);
	}
	//change color of the text in the div
	var r = parent.content.page.document.selection.createRange();
	r.execCommand(on, false, sColor);
	//saveHistory(); // speichert vorgang in der history
}

function clean_word() {
	var t = doc.innerHTML;
	t = t.replace( /<span.*?>(.*?)/gi, '$1' );
	t = t.replace( /<\/span>/gi, '' );
	
	t = t.replace( /<font[^>]*?><\/font>/gi, '' );
	t = t.replace( /<font[^>]*?><\/font>/gi, '' );
	
	t = t.replace( /<p class=Mso[^>]*?>/gi, '' );
	t = t.replace( /<?xml.*?>/gi, '' );
	t = t.replace( /<\/?o:p>/gi, '' );
	t = t.replace( /(<td.*?>)\s*<p.*?>(.*?)<\/p>\s*<\/td>/gi, '$1$2</td>' );

	t = t.replace( /<(.*) class=Mso[^>]*?>/gi, '<$1>' );
	
	t = t.replace( /<t((?:body|r|d|able)\s.*?)style=".*?"(.*?)>/gi, '<t$1$2>' );
	t = t.replace( /(<p>&nbsp;<\/P>)/gi, '<br>' );
	t = t.replace( /(<\/P>)/gi, '<br>' );
	doc.innerHTML = t;
	parent.bottom.document.getElementById('statusbar').innerText ='Code wurde bereinigt!';
}
