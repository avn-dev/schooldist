<style>
	.complaint_history_container {
		font-size:8pt;
		padding: 10px;
		width: 97.8%;
	}
	.history_header {
		width: 100%;
	}
	.history_name {
		float: left;
		width: 50%;
		text-align: left;
	}
	.history_state {
		float: left;
		width: 50%;
		text-align: right;
	}
	.history_date {
		padding-bottom: 5px;
		border-bottom: 1px #d3d3d3 solid;
	}
	.history_message {
		padding-top: 5px;

	}
	.divCleaner {
		clear: both;
	}
</style>
<div id="complaint_history_container_{$id}" class="complaint_history_container">
	<div class="row">
		<div id="history_header" class="history_header">
			<div class="history_name">
				{$name}
			</div>
			<div class="history_state">
				{$comment_state}
			</div>
			<div class="divCleaner"></div>
		</div>
		<div class="history_date">
			{$comment_date}
		</div>
		<div class="row">
			<div class="history_message">
				{$comment}
			</div>
		</div>
	</div>
</div>