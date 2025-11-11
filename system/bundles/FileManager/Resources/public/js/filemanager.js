
function saveTags() {

	$('#tagsinput-loading').show();

	var aValues = $('#tagsinput-values').val();

	var oData = {
		tags : aValues,
		class: sClass,
		id: iId
	};

	$.post(
		'/wdmvc/file-manager/tags/save',
		oData,
		callbackSaveTags,
		'json'
	);

}

function callbackSaveTags(oResponse) {

	$('#tagsinput-values').tagsinput('removeAll');

	var aTags = oResponse.tags;
	
	$(aTags).each(function(i, sTag) {
		$('#tagsinput-values').tagsinput('add', sTag);
	});

	loadFiles();

	$('#tagsinput-loading').hide();

}

function saveFileTags() {

	$('#container-loading').show();
	
	var sData = $('.file-tags').serialize()

	sData += '&class='+sClass;
	sData += '&id='+iId;

	$.post(
		'/wdmvc/file-manager/interface/save-tags',
		sData,
		callbackSaveFileTags,
		'json'
	);

}

function callbackSaveFileTags() {

	$('#container-loading').hide();
	
}

function loadFiles() {
	
	$('#container-loading').show();
	
	var oData = {
		class: sClass,
		id: iId
	};

	$.post(
		'/wdmvc/file-manager/interface/load',
		oData,
		callbackLoadFile,
		'json'
	);
}

function callbackLoadFile(oResponse) {

	var sHtml = oResponse.html;
	
	$('#filemanager-container').html(sHtml);
	
	$('.file-tags').on('change', function() {
		saveFileTags();
	});

	$('.filemanager-edit').click(function() {
		editFileMeta(this);
	});

	$('.filemanager-delete').click(function() {
		if(confirm(oTranslations.delete_confirm)) {
			var iFileId = $(this).attr('data-id');
			deleteFile(iFileId);
		}
	});

	var oDropzone = $("#filemanager-dropzone").get(0).dropzone;
	oDropzone.removeAllFiles();

	$('#filemanager-container').sortable({
		update: function (event, ui) {
			var sData = $(this).sortable('serialize');

			sData += '&class='+sClass;
			sData += '&id='+iId;

			// POST to server using $.post or $.ajax
			$.ajax({
				data: sData,
				type: 'POST',
				url: '/wdmvc/file-manager/interface/sortable',
			});
		}
	});
    $('#filemanager-container').disableSelection();

	$('#container-loading').hide();
	
}

function editFileMeta(oIcon) {

	var iFileId = $(oIcon).attr('data-id');

	var sData = '';

	sData += '&class='+sClass;
	sData += '&id='+iId;
	sData += '&file_id='+iFileId;

	$.post(
		'/wdmvc/file-manager/interface/get-meta',
		sData,
		callbackEditFileMeta,
		'json'
	);
	
}

function callbackEditFileMeta(aData) {

	$('#filemanager-container').enableSelection();

	var oContainer = $('#filemanager-file-'+aData.file_id);

	var oFormContainer = oContainer.find('.form-container');
	
	oFormContainer.show();

	oFormContainer.html($('#form-template').html());
	
	var aFields = oFormContainer.find('.input-title,.input-description,.input-source');
	
	var aMeta = aData.meta;
	
	aFields.each(function() {
		var sName = $(this).attr('name');
		var aParts = sName.split(/\[/);

		var sLanguageIso = aParts[1].replace(/\]/, '');
		var sField = aParts[0];
		
		if(
			aMeta[sLanguageIso] &&
			aMeta[sLanguageIso][sField]
		) {
			$(this).val(aMeta[sLanguageIso][sField]);
		}

	});

	$("form").submit(function(e){
        e.preventDefault();
    });
	
	oFormContainer.find('button.cancel').click(function() {
		
		var oContainer = $(this).parents('.filemanager-file');
		var oFormContainer = oContainer.find('.form-container');
		oFormContainer.html('');
		oFormContainer.hide();

		$('#filemanager-container').disableSelection();
		
	});
	
	oFormContainer.find('button.save').click(function() {
		
		var oContainer = $(this).parents('.filemanager-file');
		var oForm = $(this).parents('form');

		var sData = oForm.serialize()

		sData += '&class='+sClass;
		sData += '&id='+iId;
		sData += '&file_id='+oContainer.attr('id').replace(/filemanager\-file\-/, '');

		$.post(
			'/wdmvc/file-manager/interface/save-meta',
			sData,
			callbackSaveFileMeta,
			'json'
		);

		$('#filemanager-container').disableSelection();

	});
	
}

function callbackSaveFileMeta(aResponse) {
	
	if(
		aResponse.success && 
		aResponse.success === true
	) {

		var oContainer = $('#filemanager-file-'+aResponse.file_id);

		var oFormContainer = oContainer.find('.form-container');

		oFormContainer.show();

		oFormContainer.html('<i class="icon fa fa-check"></i>');

		oFormContainer.delay(2000).fadeOut();

	}
	
}

function deleteFile(iFileId) {
	
	$('#container-loading').show();
	
	var oData = {
		class: sClass,
		id: iId,
		file_id: iFileId
	};

	$.post(
		'/wdmvc/file-manager/interface/delete',
		oData,
		callbackDeleteFile,
		'json'
	);
}

function callbackDeleteFile(oResponse) {
	
	$('#filemanager-file-'+oResponse.id).remove();

	$('#container-loading').hide();
	
}
	
Dropzone.options.filemanagerDropzone = {
	init: function() {
		this.on("success", function(file) { 
			loadFiles(); 
		});
	},
	parallelUploads: 99,
	uploadMultiple: true
};

$(function() {
		
	$('#tagsinput-save').click(function() {
		saveTags();
	});
	
	loadFiles();
	
});