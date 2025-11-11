
var bolReadyState = 1;

function _doUnload() {
	if(bolReadyState) {
        window.setTimeout("_testUnload()", 5);
	}
}

function _testUnload() {
    if(bolReadyState) {
		
		var intOffset;
		if (typeof window.pageYOffset != 'undefined') {
		   intOffset = window.pageYOffset;
		}
		else if (typeof document.compatMode != 'undefined' &&
		     document.compatMode != 'BackCompat') {
		   intOffset = document.documentElement.scrollTop;
		}
		else if (typeof document.body != 'undefined') {
		   intOffset = document.body.scrollTop;
		}
		
		if(document.getElementById('myMessage'))
		{
			document.getElementById('myMessage').style.display = '';
			document.getElementById('myMessage').style.top = intOffset+'px';
		}
    }
}

function showRow(id,on) {

	var oPopupTr = document.getElementById('tr_'+id);
	
	if(on==1) {
		oPopupTr.style.backgroundColor = '#d4ddf0';
	} else {
		oPopupTr.style.backgroundColor = '';
	}
}

var setHighlightRow = function() {
	var objRow = this;
	if(objRow) {
		$(objRow).addClass('highlightRow');
	}			
}

var resetHighlightRow = function() {
	var objRow = this; 
	if(objRow) {
		$(objRow).removeClass('highlightRow');
	}
}

function processLoading(event) {

	var intCounter = 0;
	var aRows = $('table.highlightRows tr');

	$.each(aRows, function( index, oRow ) {

		if(!$(oRow).hasClass('noHighlight')) {
			if(!oRow.id) {
				oRow.id = 'tr_'+intCounter;
			}
			$(oRow).mouseout(resetHighlightRow);
			$(oRow).mousemove(setHighlightRow);

			intCounter++;
		}

	});

}

$(function() {

	processLoading();

	if(typeof tinymce !== 'undefined') {
		tinymce.init({
			selector: "textarea.tinymce",
			// General options
			mode: "none",
			theme: "modern",
			skin: "lightgray",
			plugins: [
				"advlist autolink lists link image charmap print preview hr anchor pagebreak",
				"searchreplace wordcount visualblocks visualchars code fullscreen",
				"insertdatetime media nonbreaking save table contextmenu directionality",
				"emoticons template paste textcolor colorpicker textpattern responsivefilemanager"
			],
			menubar: false,
			branding: false,
			statusbar: false,
			toolbar1: "undo redo | styleselect | searchreplace pastetext visualblocks visualchars | bold italic underline | alignleft aligncenter alignright | bullist numlist outdent indent | preview code fullscreen",
			toolbar2: "forecolor backcolor | link image | charmap table | responsivefilemanager",
			toolbar_items_size: 'small',
			image_advtab: true,
			theme_modern_toolbar_location: "top",
			theme_modern_toolbar_align: "left",
			theme_modern_statusbar_location: "none",
			theme_modern_resizing: true,
			theme_modern_path: false,
			readonly: false,
			forced_root_block: false,
			verify_html: false,
			convert_urls: false,
			remove_script_host: true,
			resize: "both",
			external_filemanager_path: "/tinymce/resource/filemanager/",
			filemanager_title: "Responsive Filemanager",
			external_plugins: { 
				"filemanager" : "/tinymce/resource/filemanager/plugin.min.js"
			},
			extended_valid_elements: "style[*]",
			valid_children: "+body[style]"
		});
	}

});
