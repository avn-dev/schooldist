<script>
import LogViewerRow from './LogViewerRow.vue'

export default {
	components: { LogViewerRow },
	props: { fileOptions: { type: Object, required: true }},
	data() {
		return {
			loading: false,
			file: null,
			lines: [],
			// levels: Object.keys(LEVEL_COLORS),
			from: null,
			until: null,
			// level: null
		}
	},
	methods: {
		load(limit) {
			this.loading = true
			if (!limit) {
				this.lines = []
			}

			const body = {
				file: this.file,
				offset: this.lines.length,
				limit: limit ?? 0,
				from: this.from,
				until: this.until,
				// level: this.level
			}

			fetch('/admin/tools/log-viewer/load-log', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(body)
			}).then(async resp => {
				const json = await resp.json()
				this.lines = this.lines.concat(json.lines)
			}).finally(() => {
				this.loading = false
			})
		}
	}
}
</script>

<template>
	<div class="box-body log-viewer">
		<div class="input-group">
			<select
				v-model="file"
				class="form-control"
				@change="load()"
			>
				<option
					v-for="[value, label] in fileOptions"
					:key="value"
					:value="value"
				>
					{{ label }}
				</option>
			</select>
			<span class="input-group-btn">
				<button
					type="button"
					class="btn btn-default"
					@click="load()"
				>
					<i class="fa fa-refresh" />
				</button>
			</span>
		</div>
		<div class="table-responsive">
			<table class="table table-striped table-bordered table-hover">
				<thead>
					<tr>
						<th style="width: 250px;">
							Date
						</th>
						<th style="width: 100px;">
							Channel
						</th>
						<th style="width: 75px;">
							Level
						</th>
						<th style="width: auto">
							Message
						</th>
						<th
							title="Context"
							style="width: 50px"
						>
							Ctx
						</th>
					</tr>
					<tr>
						<td style="display: flex">
							<input
								v-model="from"
								type="date"
								class="form-control input-sm"
								@change="load()"
							>
							<input
								v-model="until"
								type="date"
								class="form-control input-sm"
								@change="load()"
							>
						</td>
						<td />
						<td>
							<!--<select v-model="level" class="form-control input-sm">
								<option></option>
								<option v-for="level in levels" :value="level">{{ level }}</option>
							</select>-->
						</td>
						<td />
						<td />
					</tr>
				</thead>
				<tbody>
					<tr v-if="!lines.length">
						<td
							colspan="5"
							class="text-center"
						>
							No data
						</td>
					</tr>
					<log-viewer-row
						v-for="(line) in lines"
						:key="line.key"
						:line="line"
					/>
				</tbody>
				<tfoot>
					<tr v-if="lines.length">
						<td
							colspan="5"
							class="text-center"
						>
							<button
								class="btn btn-default"
								@click="load(100)"
							>
								Load 100 more …
							</button>&nbsp;
							<button
								class="btn btn-default"
								@click="load(1000)"
							>
								Load 1000 more …
							</button>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>
	<div
		v-if="loading"
		class="overlay"
	>
		<i class="fa fa-refresh fa-spin" />
	</div>
</template>