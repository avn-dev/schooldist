
// object:		RichEditor()
// description: This object provides the interface to the calling page.
function RichEditor()
{
	this.put_docHtml			= put_docHtml;
	this.get_docHtml			= get_docHtml;			// OZ
	this.put_defaultFont		= put_defaultFont;
	this.put_defaultFontSize	= put_defaultFontSize;
	this.put_styleData			= put_styleData;		// LEON
	this.put_options			= put_options;
	this.addField				= addField;
	this.getValue				= getValue;
}

// property:	docHtml
// access:		read/write
// description: Set this property to define the initial HTML to be
//				edited.
// author:		austin.france@ramesys.com
function put_docHtml(passedValue) {
	doc.innerHTML = passedValue;
	var r = document.selection.createRange();
	r.select();
	doc.focus();
	reset();
}
function get_docHtml() {
	return doc.innerHTML;
}

// property:	defaultFont
// access:		write only
// description:	Sets the default font for the editor.  The default
//				if this is not specified is whatever the microsoft
//				html editing component decides (Times New Roman
//				typically)
// author:		austin.france@ramesys.com
function put_defaultFont(passedValue) {
	doc.style.fontFamily = passedValue;
}

// property:	defaultFontSize
// access:		write only
// description:	Sets the default font size for the editor.
// author:		austin.france@ramesys.com
function put_defaultFontSize(passedValue) {
	switch(passedValue) {
	case "1": passedValue = "xx-small"; break;
	case "2": passedValue = "x-small";	break;
	case "3": passedValue = "small";	break;
	case "4": passedValue = "medium";	break;
	case "5": passedValue = "large";	break;
	case "6": passedValue = "x-large";	break;
	case "7": passedValue = "xx-large";	break;
	}
	doc.style.fontSize = passedValue;
}

// property:	styleData
// access:		writeOnly
// description:	Defines extended style data for the style dropdown
// author:		leonreinders@hetnet.nl
function put_styleData(passedValue) {

	var a,b;

	// Define the default style list
	this.styleList = [
		// element		description			Active
		[null,			"Normal",			0],
		[null,			"Heading 1",		0],
		[null,			"Heading 2",		0],
		[null,			"Heading 3",		0],
		[null,			"Heading 4",		0],
		[null,			"Heading 5",		0],
		[null,			"Heading 6",		0],
		[null,			"Address",			0],
		[null,			"Formatted",		0],
		["BLOCKQUOTE",	"Blockquote",		0],
		["CITE",		"Citation",			0],
		["BDO",			"Reversed",			0],
		["BIG",			"Big",				0],
		["SMALL",		"Small",			0],
		["DIV",			"Div",				0],
		["SUP",			"Superscript",		0],
		["SUB",			"Subscript",		0]
	];

	// Add the passed styles to the documents stylesheet
	for (var i = 0; passedValue && i < passedValue.length; i++)
	{
		for (var j = 0; j < passedValue[i].rules.length; j++)
		{
			// Extract the rule and the rule definition from the passed style
			// data.
			a = passedValue[i].rules[j].selectorText.toString().toLowerCase();
			b = passedValue[i].rules[j].style.cssText.toLowerCase();

			// Ignore non-style entries
			if (!a || !b) continue;

			// Add this rule to our style sheet
			document.styleSheets[0].addRule(a,b);

			// Id: These are added to the document style sheet but are not
			// available in the style dropdown
			if (a.indexOf("#") != -1) {
				continue;
			}

			// Class: Append a cless element to the style list
			if (a.indexOf(".") == 0) {
				this.styleList[this.styleList.length] = [a, "Class " + a, 1];
			}

			// SubClass: Append the sub-class to the style list
			else if(a.indexOf(".") > 0) {
				this.styleList[this.styleList.length] = [a, a, 1];
			}

			// Otherwise, assume it's a tag and select the existing tag entry
			// in the style list.
			else {
				for (var k = 0; k < this.styleList.length; k++) {
					if (this.styleList[k][0] == a) {
						this.styleList[k][2] = 1;
						break;
					}
				}
			}
		}
	}

	// Initialise the style dropdown with the new style list
	initStyleDropdown(this.styleList);
}

function addField(name, label, maxlen, value, size) {
	var row = rebarBottom.parentElement.insertRow(rebarBottom.rowIndex);
	var cell = row.insertCell();
	cell.className = 'rebar';
	cell.width = '100%';
	cell.innerHTML = '<nobr width="100%"><span class="field" width="100%">'
						+ '<img class="spacer" src="spacer.gif" width="2">'
						+ '<span class="start"></span>'
						+ '<span class="label">' + label + ':</span>'
						+ '&nbsp;<input class="field" type="text"'
							+ ' name="' + name + '" maxsize="' + maxlen + '"'
								+ (value ? ' value="' + value + '"' : '')
								+ 'size="' + (size ? size : 58) + '"'
								+ '>&nbsp;'
						+ '</span>'
						+ '</nobr>';
}

function getValue(name) {
	return document.all(name).value;
}

// property:	options
// access:		writeOnly
// description:	Sets options for the editor.  Used by the editor to control
//				certain features
//
//				viewsource=<true|false>;...
//
// author:		austin.france@ramesys.com
function put_options(passedValue) {
	this.options = passedValue;
}
