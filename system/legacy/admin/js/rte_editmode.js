
// setEditMode(): switch between html and textview
function setEditMode() {
	document.all.switchMode.blur(); // htmlview
	if (document.all.switchMode.checked == true) {
		document.all.ctlStyle.disabled = document.all.ctlFont.disabled = document.all.ctlSize.disabled = true;
		document.all.doc.style.fontFamily = "Courier";
		document.all.doc.style.fontSize = "10px";
		// doc.style.backgroundColor ="#ececec";
		RichEditor.txtView = false;
		document.all.doc.innerText = codeSweeper();
		document.all.doc.innerHTML = ccParser(doc.innerHTML);
	} else {
		document.all.ctlStyle.disabled=document.all.ctlFont.disabled=document.all.ctlSize.disabled=false
		document.all.doc.style.fontFamily = document.all.doc.style.fontSize = ""; //=doc.style.backgroundColor
		RichEditor.txtView = true;
		document.all.doc.focus();
		document.all.doc.innerHTML = document.all.doc.innerText + '		';
	}
}

// resetMode();
function resetMode(){
	if (switchMode.checked == true) {
		switchMode.click();
	}
}

// ccParser(): colorcode-parser for html-editing view
function ccParser(html) {

	html = html.replace(/@/gi,"_AT_");
	html = html.replace(/#/gi,"_HASH_");

	var htmltag = /(&lt;[\w\/]+[ ]*[\w\=\"\'\.\/\;\: \)\(-]*&gt;)/gi;
	html = html.replace(htmltag,"<span class=ccp_tag>$1</span>");

	var imgtag = /<span class=ccp_tag>(&lt;IMG[ ]*[\w\=\"\'\.\/\;\: \)\(-]*&gt;)<\/span>/gi;
	html = html.replace(imgtag,"<span class=ccp_img>$1</span>");

	var formtag = /<span class=ccp_tag>(&lt;[\/]*(form|input){1}[ ]*[\w\=\"\'\.\/\;\: \)\(-]*&gt;)<\/span>/gi;
	html = html.replace(formtag,"<br><span class=ccp_form>$1</span>");

	var tabletag = /<span class=ccp_tag>(&lt;[\/]*(table|tbody|th|tr|td){1}([ ]*[\w\=\"\'\.\/\;\:\)\(-]*){0,}&gt;)<\/span>/gi;
	html = html.replace(tabletag,"<span class=ccp_table>$1</span>");

	//var Atag = /<span class=ccp_tag>(&lt;(\/a&gt;|[\W _\w\=\"\'\.\/\;\:\)\(-]&gt;){1})<\/span>/gi;
	var Atag = /<span class=ccp_tag>(&lt;\/a&gt;){1}<\/span>/gi;
	html = html.replace(Atag,"<span class=ccp_A>$1</span>");

	var Atag = /<span class=ccp_tag>(&lt;a [\W _\w\=\"\'\.\/\;\:\)\(-]+&gt;){1,}<\/span>/gi;
	html = html.replace(Atag,"<span class=ccp_A>$1</span>");

	var parameter = /=("[ \w\'\.\/\;\:\)\(-]+"|'[ \w\"\.\/\;\:\)\(-]+')/gi;
	html = html.replace(parameter,"=<span class=ccp_paramvalue>$1</span>");

	var entity = /&amp;([\w]+);/gi;
	html = html.replace(entity,"<span class=ccp_entity>&amp;$1;</span>");

	var comment = /(&lt;\!--[\W _\w\=\"\'\.\/\;\:\)\(-]*--&gt;)/gi;
	html = html.replace(comment,"<br><span class=ccp_htmlcomment>$1</span>");

	html = html.replace(/_AT_/gi,"@");
	html = html.replace(/_HASH_/gi,"#");

	return html;
}
