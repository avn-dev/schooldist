{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}
	{literal}
	<style>
		
		#breadcrumb {
			padding: 6px 0;
		}
		
		.breadcrumb {
			margin-bottom: 0;
			padding: 0;
			background: none;
		}
		
		.navbar {
			padding: 8px 15px;
		}
		
		#top {height:52px;}
		#mkdir {display:inline-block;float:right;}

		#file_drop_target {border: 2px dashed #ccc;float:right;margin-right:20px;padding: 2px;}
		#file_drop_target input {display: inline;}
		#file_drop_target.drag_over {border: 4px dashed #96C4EA; color: #96C4EA;}
		#upload_progress {padding: 4px 0;}
		#upload_progress .error {color:#a00;}
		#upload_progress > div { padding:3px 0;}
		.no_write #mkdir, .no_write #file_drop_target {display: none}
		.progress_track {display:inline-block;width:200px;height:10px;border:1px solid #333;margin: 0 4px 0 10px;}
		.progress {background-color: #82CFFA;height:10px; }
		footer {font-size:11px; color:#bbbbc5; padding:4em 0 0;text-align: left;}
		footer a, footer a:visited {color:#bbbbc5;}
		#folder_actions {width: 50%;float:right;}
		
		.sort_hide{ display:none;}

		.is_dir .size {color:transparent;font-size:0;}
		.is_dir .size:before {content: "--"; font-size:14px;color:#333;}
		.is_dir .download{visibility: hidden}
		a.delete {display:inline-block;
			color:#d00;	margin-left: 15px;
		}
	</style>
	{/literal}
{/block}

{block name="content"}

		<!-- Content Header (Page header) -->
		<section class="content-header">
		  <h1>
			{'Dateiverwaltung'|L10N}
		  </h1>
		</section>

		<!-- Main content -->
		<section class="content">

			<div class="box box-default">
				
				<div class="box-body">

					<nav class="navbar navbar-default">

						<form action="?" method="post" id="mkdir" class="form-inline" />
							<label for=dirname>{'Neuen Ordner erstellen'|L10N:'Dateiverwaltung'}</label> <input id=dirname class="form-control input-sm" type=text name=name value="" />
							<input type="submit" class="btn btn-sm btn-default" value="{'Erstellen'|L10N:'Dateiverwaltung'}" />
						</form>

						<div id="file_drop_target" class="form-inline">
							{'Dateien hierher verschieben'|L10N:'Dateiverwaltung'} <b>{'oder'|L10N:'Dateiverwaltung'}</b> <input type="file" multiple />
						</div>

						<div id="breadcrumb">&nbsp;</div>
	
				</nav>

						<div id="upload_progress"></div>
						<table id="table" class="table table-striped table-hover"><thead><tr>
							<th style="width: auto;">{'Name'|L10N:'Dateiverwaltung'}</th>
							<th style="width: 100px;">{'Größe'|L10N:'Dateiverwaltung'}</th>
							<th style="width: 160px;">{'Änderungszeitpunkt'|L10N:'Dateiverwaltung'}</th>
							<th style="width: 200px;">{'Berechtigungen'|L10N:'Dateiverwaltung'}</th>
							<th style="width: 200px;">{'Aktionen'|L10N:'Dateiverwaltung'}</th>
						</tr></thead><tbody id="list">

						</tbody></table>

				</div>
			</div>
			
		</section>
{/block}
					
{block name="footer"}
	{literal}
	<script>
	(function($){
		$.fn.tablesorter = function() {
			var $table = this;
			this.find('th').click(function() {
				var idx = $(this).index();
				var direction = $(this).hasClass('sort_asc');
				$table.tablesortby(idx,direction);
			});
			return this;
		};
		$.fn.tablesortby = function(idx,direction) {
			var $rows = this.find('tbody tr');
			function elementToVal(a) {
				var $a_elem = $(a).find('td:nth-child('+(idx+1)+')');
				var a_val = $a_elem.attr('data-sort') || $a_elem.text();
				return (a_val == parseInt(a_val) ? parseInt(a_val) : a_val);
			}
			$rows.sort(function(a,b){
				var a_val = elementToVal(a), b_val = elementToVal(b);
				return (a_val > b_val ? 1 : (a_val == b_val ? 0 : -1)) * (direction ? 1 : -1);
			})
			this.find('th').removeClass('sort_asc sort_desc');
			$(this).find('thead th:nth-child('+(idx+1)+')').addClass(direction ? 'sort_desc' : 'sort_asc');
			for(var i =0;i<$rows.length;i++)
				this.append($rows[i]);
			this.settablesortmarkers();
			return this;
		}
		$.fn.retablesort = function() {
			var $e = this.find('thead th.sort_asc, thead th.sort_desc');
			if($e.length)
				this.tablesortby($e.index(), $e.hasClass('sort_desc') );

			return this;
		}
		$.fn.settablesortmarkers = function() {
			this.find('thead th span.indicator').remove();
			this.find('thead th.sort_asc').append('<span class="indicator">&darr;<span>');
			this.find('thead th.sort_desc').append('<span class="indicator">&uarr;<span>');
			return this;
		}
	})(jQuery);
	
	$(function() {

		var XSRF = (document.cookie.match('(^|; )_sfm_xsrf=([^;]*)')||0)[2];
		var MAX_UPLOAD_SIZE = 999999999;
		var $tbody = $('#list');
		$(window).bind('hashchange',list).trigger('hashchange');
		$('#table').tablesorter();

		$('#mkdir').submit(function(e) {
			var hashval = window.location.hash.substr(1),
				$dir = $(this).find('[name=name]');
			e.preventDefault();
			$dir.val().length && $.post('?',{'do':'mkdir',name:$dir.val(),xsrf:XSRF,file:hashval},function(data){
				list();
			});
			$dir.val('');
			return false;
		});

		// file upload stuff
		$('#file_drop_target').bind('dragover',function(){
			$(this).addClass('drag_over');
			return false;
		}).bind('dragend',function(){
			$(this).removeClass('drag_over');
			return false;
		}).bind('drop',function(e){
			e.preventDefault();
			var files = e.originalEvent.dataTransfer.files;
			$.each(files,function(k,file) {
				uploadFile(file);
			});
			$(this).removeClass('drag_over');
		});
		$('input[type=file]').change(function(e) {
			e.preventDefault();
			$.each(this.files,function(k,file) {
				uploadFile(file);
			});
		});
		function uploadFile(file) {
			var folder = window.location.hash.substr(1);
			if(file.size > MAX_UPLOAD_SIZE) {
				var $error_row = renderFileSizeErrorRow(file,folder);
				$('#upload_progress').append($error_row);
				window.setTimeout(function(){$error_row.fadeOut();},5000);
				return false;
			}

			var $row = renderFileUploadRow(file,folder);
			$('#upload_progress').append($row);
			var fd = new FormData();
			fd.append('file_data',file);
			fd.append('file',folder);
			fd.append('xsrf',XSRF);
			fd.append('do','upload');
			var xhr = new XMLHttpRequest();
			xhr.open('POST', '?');
			xhr.onload = function() {
				$row.remove();
				list();
			};
			xhr.upload.onprogress = function(e){
				if(e.lengthComputable) {
					$row.find('.progress').css('width',(e.loaded/e.total*100 | 0)+'%' );
				}
			};
			xhr.send(fd);
		}
		function renderFileUploadRow(file,folder) {
			return $row = $('<div/>')
				.append( $('<span class="fileuploadname" />').text( (folder ? folder+'/':'')+file.name))
				.append( $('<div class="progress_track"><div class="progress"></div></div>')  )
				.append( $('<span class="size" />').text(formatFileSize(file.size)) )
		};
		function renderFileSizeErrorRow(file,folder) {
			return $row = $('<div class="error" />')
				.append( $('<span class="fileuploadname" />').text( 'Error: ' + (folder ? folder+'/':'')+file.name))
				.append( $('<span/>').html(' file size - <b>' + formatFileSize(file.size) + '</b>'
					+' exceeds max upload size of <b>' + formatFileSize(MAX_UPLOAD_SIZE) + '</b>')  );
		}

		function list() {
			var hashval = window.location.hash.substr(1);
			$.get('?',{'do':'list','file':hashval},function(data) {
				$tbody.empty();
				$('#breadcrumb').empty().html(renderBreadcrumbs(hashval));
				if(data.success) {
					$.each(data.results,function(k,v){
						$tbody.append(renderFileRow(v));
					});
					!data.results.length && $tbody.append('<tr><td class="empty" colspan=5>This folder is empty</td></tr>')
					data.is_writable ? $('body').removeClass('no_write') : $('body').addClass('no_write');
					
					$('.delete').on('click',function(data) {
						if(confirm({/literal}'{'Möchten Sie diesen Eintrag löschen?'|L10N:'Dateiverwaltung'}'{literal})) {
							$.post("",{'do':'delete',file:$(this).attr('data-file'),xsrf:XSRF},function(response) {
								list();
							});
						}
						return false;
					});
					
				} else {
					console.warn(data.error.msg);
				}
				$('#table').retablesort();
			},'json');
		}
		function renderFileRow(data) {
		
			var sName = '';
		
			if(data.is_dir) {
				sName += '<i class="fa fa-fw fa-folder-o"></i> ';
			} else {
				sName += '<i class="fa fa-fw fa-file-o"></i> ';
			}
		
			sName += data.name;
		
			var $link = $('<a class="name" />')
				.attr('href', data.is_dir ? '#' + data.path : '?do=download&file='+encodeURIComponent(data.path))
				.html(sName);
			var allow_direct_link = true;
				if (!data.is_dir && !allow_direct_link)  $link.css('pointer-events','none');
				{/literal}	
			var $dl_link = $('<a/>').attr('href','?do=download&file='+encodeURIComponent(data.path))
				.addClass('download').text('{'Herunterladen'|L10N:'Dateiverwaltung'}');
			var $delete_link = $('<a href="#" />').attr('data-file',data.path).addClass('delete').text('{'Löschen'|L10N:'Dateiverwaltung'}');
				
			var perms = [];
			if(data.is_readable) perms.push('{'Lesen'|L10N:'Dateiverwaltung'}');
			if(data.is_writable) perms.push('{'Schreiben'|L10N:'Dateiverwaltung'}');
			if(data.is_executable) perms.push('{'Ausführen'|L10N:'Dateiverwaltung'}');
			var $html = $('<tr />')
				.addClass(data.is_dir ? 'is_dir' : '')
				.append( $('<td class="first" />').append($link) )
				.append( $('<td/>').attr('data-sort',data.is_dir ? -1 : data.size)
					.html($('<span class="size" />').text(formatFileSize(data.size))) ) 
				.append( $('<td/>').attr('data-sort',data.mtime).text(formatTimestamp(data.mtime)) )
				.append( $('<td/>').text(perms.join(', ')) )
				.append( $('<td/>').append($dl_link).append( data.is_deleteable ? $delete_link : '') )
			return $html;
			{literal}
		}
		function renderBreadcrumbs(path) {
			var base = "",
				$html = $('<ol/>').attr('class', 'breadcrumb').append( $('<li><a href=#>Home</a></li>') );
			$.each(path.split('/'),function(k,v){
				if(v) {
					$html.append( $('<li/>').append( $('<a/>').attr('href','#'+base+v).text(v)) );
					base += v + '/';
				}
			});
			return $html;
		}
		function formatTimestamp(unix_timestamp) {
			var m = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
			var d = new Date(unix_timestamp*1000);
			return d.toLocaleString();
		}
		function formatFileSize(bytes) {
			var s = ['bytes', 'KB','MB','GB','TB','PB','EB'];
			for(var pos = 0;bytes >= 1000; pos++,bytes /= 1024);
			var d = Math.round(bytes*10);
			return pos ? [parseInt(d/10),".",d%10," ",s[pos]].join('') : bytes + ' bytes';
		}
	})
	</script>
	{/literal}
{/block}
