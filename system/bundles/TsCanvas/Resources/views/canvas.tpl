
<div style="margin-bottom: 15px;">
	<b>{'URL:'|L10N}</b> {$sUrl}<br>
	<b>{'Access Token:'|L10N}</b> {$sAccessToken}
</div>

{if $bActive}
	<a href="{route name="admin_canvas_deactivate"}" class="btn btn-danger">
	{'Canvas deaktivieren'|L10N}
</a>
{else}
	<a href="{route name="admin_canvas_activate"}" class="btn btn-primary">
		{'Canvas aktivieren'|L10N}
	</a>
{/if}

