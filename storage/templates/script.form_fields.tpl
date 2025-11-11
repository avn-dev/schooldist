
			<style>
			
			{literal}
			
			table.jCalendar {
				border: 0;
				background: #f0f0f0;
			    border-collapse: separate;
			    border-spacing: 2px;
			}
			table.jCalendar th {
				background: #dedede;
				color: #000;
				font-weight: bold;
				padding: 3px 5px;
				font-size: 11px;
			}
			
			table.jCalendar td {
				background: #f0f0f0;
				color: #000;
				padding: 3px 5px;
				text-align: center;
				font-size: 11px;
			}
			table.jCalendar td.other-month {
				color: #aaa;
			}
			table.jCalendar td.today {
				background: #666;
				color: #fff;
			}
			table.jCalendar td.selected {
				background: #999;
				color: #fff;
			}
			table.jCalendar td.selected.dp-hover {
				background: #f33;
				color: #fff;
			}
			table.jCalendar td.dp-hover,
			table.jCalendar tr.activeWeekHover td {
				background: #fff;
				color: #000;
			}
			table.jCalendar tr.selectedWeek td {
				background: #f66;
				color: #fff;
			}
			table.jCalendar td.disabled, table.jCalendar td.disabled.dp-hover {
				color: #888;
			}
			table.jCalendar td.unselectable,
			table.jCalendar td.unselectable:hover,
			table.jCalendar td.unselectable.dp-hover {
				background: #bbb;
				color: #888;
			}
			
			/* For the popup */
			
			/* NOTE - you will probably want to style a.dp-choose-date - see how I did it in demo.css */
			
			div.dp-popup {
				position: relative;
				background: #dedede;
				font-size: 10px;
				font-family: arial, sans-serif;
				padding: 2px;
				width: 171px;
				line-height: 1.2em;
			}
			div#dp-popup {
				position: absolute;
				z-index: 199;
			}
			div.dp-popup h2 {
				color: #000;
				font-size: 12px;
				text-align: center;
				margin: 2px 0;
				padding: 0;
			}
			a#dp-close {
				font-size: 11px;
				padding: 4px 0;
				text-align: center;
				display: block;
			}
			a#dp-close:hover {
				text-decoration: underline;
			}
			div.dp-popup a {
				color: #000;
				text-decoration: none;
				padding: 3px 2px 0;
			}
			div.dp-popup div.dp-nav-prev {
				position: absolute;
				top: 2px;
				left: 4px;
				width: 100px;
			}
			div.dp-popup div.dp-nav-prev a {
				float: left;
			}
			/* Opera needs the rules to be this specific otherwise it doesn't change the cursor back to pointer after you have disabled and re-enabled a link */
			div.dp-popup div.dp-nav-prev a, div.dp-popup div.dp-nav-next a {
				cursor: pointer;
			}
			div.dp-popup div.dp-nav-prev a.disabled, div.dp-popup div.dp-nav-next a.disabled {
				cursor: default;
			}
			div.dp-popup div.dp-nav-next {
				position: absolute;
				top: 2px;
				right: 4px;
				width: 100px;
			}
			div.dp-popup div.dp-nav-next a {
				float: right;
			}
			div.dp-popup a.disabled {
				cursor: default;
				color: #aaa;
			}
			div.dp-popup td {
				cursor: pointer;
			}
			div.dp-popup td.disabled {
				cursor: default;
			}
			
			a.dp-choose-date {
				float: left;
				width: 16px;
				height: 16px;
				padding: 0;
				margin: 2px 3px 0;
				display: block;
				text-indent: -2000px;
				overflow: hidden;
				background: url('?&task=get_image&image=calendar.png') no-repeat; 
			}
			a.dp-choose-date.dp-disabled {
				background-position: 0 -20px;
				cursor: default;
			}
			input.dp-applied {
				width: 140px;
				float: left;
			}
			{/literal}
			
			</style>

			<!-- jQuery -->
			<script type="text/javascript" src="?&task=get_js&file=jquery-1.3.2.min.js"></script>
			
			<!-- required plugins -->
			<script type="text/javascript" src="?&task=get_js&file=date.js"></script>
			{if $sLocaleDate}
			<script type="text/javascript" src="?&task=get_js&file=date_{$sLocaleDate}.js"></script>
			{/if}
			<!--[if IE]><script type="text/javascript" src="?&task=get_js&file=jquery.bgiframe.js"></script><![endif]-->
			
			<!-- jquery.datePicker.js -->
			<script type="text/javascript" src="?&task=get_js&file=jquery.datePicker.min-2.1.2.js"></script>

			{foreach item=aField from=$aFields}
			
				{if $aField.type == 'tab' || $aField.type == 'h2'}
					<h3>{$aField.label}</h3>
				{elseif $aField.type == 'h3'}
					<p><strong>{$aField.label}</strong></p>
				{elseif $aField.type == 'p'}
					<p>{$aField.label}</p>
				{elseif $aField.type == 'hidden'}
					<input type="hidden" id="{$aField.field}" name="{$aField.field}" value="{$aField.value|escape}" />
				{elseif $aField.type == 'btn'}
					{if $sTask == 'add'}
						<p><input type="button" value="{$aField.label}" onclick="{$aField.onclick} return false;" /></p>
					{/if}
				{else}
					
					<div class="divFormElement">
						<label for="search">{$aField.label}</label>
						<div class="divValue">
						{if $sTask == 'add'}
							{if $aField.type == 'select'}
								
								<select name="{$aField.field}" onchange="{$aField.onchange} return false;">
									{html_options options=$aField.data_array selected=$aField.value}
								</select>
						
							{elseif $aField.type == 'date'}
								<input type="text" class="inputDatePicker" id="{$aField.field}" name="{$aField.field}" value="{$aField.value|date_format:$aField.date_format}" />
							{elseif $aField.type == 'time'}
								<input type="text" id="{$aField.field}" name="{$aField.field}" value="{$aField.value|date_format:"%X"}" />
							{elseif $aField.type == 'image' || $aField.type == 'file'}
								<input type="file" id="{$aField.field}" name="{$aField.field}" value="{$aField.value|escape}" />
							{elseif $aField.type == 'textarea'}
								<textarea id="{$aField.field}" name="{$aField.field}" style="{$aField.style}">{$aField.value|escape}</textarea>
							{else}
								<input type="text" id="{$aField.field}" name="{$aField.field}" value="{$aField.value|escape}" />
							{/if}
						{else}
							{if $aField.type == 'select'}
								{assign var=sValue value=$aField.value}
								{assign var=aValues value=$aField.data_array}
								{$aValues.$sValue}
							{elseif $aField.type == 'date'}
								{$aField.value|date_format:"%x"}
							{elseif $aField.type == 'time'}
								{$aField.value|date_format:"%X"}
							{elseif $aField.type == 'image'}
								{if $aField.value}
									<img src="#page:imgbuilder:2:{$aField.value}#" alt="" />
								{/if}
							{elseif $aField.type == 'file'}
								{if $aField.value}
									<a href="{$aField.value}" onclick="window.open(this.href);">{$aField.value}</a>
								{/if}
							{else}
								{$aField.value}
							{/if}
						{/if}
						</div>
						<div class="clear"></div>
					</div>

				{/if}
			
			{/foreach}
			
			
			<script type="text/javascript">

				Date.format = '{$sDateFormat}';
			
				$('.inputDatePicker').datePicker({ldelim}startDate:'{$iStartDate|date_format:"%x"}'{rdelim});

			</script>