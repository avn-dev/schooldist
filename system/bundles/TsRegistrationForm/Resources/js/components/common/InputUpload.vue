<template>
	<div>
		<div class="input-group">
			<!-- Do not add elements within .custom-file, it won't work due to fixed height -->
			<div class="custom-file">
				<input
					:id="id"
					:name="name"
					:disabled="!enabled"
					:class="['custom-file-input', { 'is-invalid': isInvalid }]"
					type="file"
					@change="change"
				>
				<label :for="id" class="custom-file-label" ref="label">
					{{ label }}
				</label>
			</div>
			<div v-if="value" class="input-group-append">
				<!-- .is-invalid: BS4 hack as border is not styled and overwrites label button (looks ugly) -->
				<button :class="['btn btn-outline-secondary', { 'is-invalid': isInvalid }]" type="button" @click="remove">
					<i :class="$icon('times')"></i>
				</button>
			</div>
		</div>
		<div v-if="progress" class="progress">
			<div
				class="progress-bar progress-bar-striped progress-bar-animated"
				:style="{ width: progress + '%' }"
				role="progressbar"
				aria-valuemin="0"
				aria-valuemax="100"
				:aria-valuenow="progress"
			></div>
		</div>
		<!-- .invalid-feedback does not work within .input-group: https://github.com/twbs/bootstrap/issues/23454 -->
		<!-- Also place under progress bar anyway as this can't also be within input-group -->
		<div class="invalid-feedback" :style="{ display: isInvalid ? 'block' : 'none' }">
			{{ errors }}
		</div>
	</div>
</template>

<script>
	import { upload } from '../../utils/api';
	import { updateField } from '../../utils/store';
	import TranslationsMixin from '../mixins/TranslationsMixin';

	export default {
		mixins: [
			TranslationsMixin
		],
		props: {
			value: String,
			id: { type: String, required: true },
			name: String,
			disabled: Boolean,
			isInvalid: Boolean,
			errors: String
		},
		data() {
			return {
				label: '',
				progress: 0,
				localDisabled: false
			};
		},
		computed: {
			enabled() {
				return !this.disabled && !this.localDisabled;
			}
		},
		mounted() {
			this.resetLabel();
			// It's not possible to translate BS4 custom-file "Browse" in HTML directly and it's impossible to select pseudo elements in JS
			// So the trick is to replace the CSS variable in its parent element to change the label
			this.$refs.label.style.setProperty('--label', `"${this.$t('browse')}"`)
		},
		methods: {
			change(event) {
				if (!event.target.files.length) {
					return;
				}

				const file = event.target.files[0];
				this.localDisabled = true;
				this.label = file.name;

				const data = this.createFormData();
				data.append(this.name, file, file.name);

				upload(data, (progressEvent) => {
					this.progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
				}).then(response => {
					if (response.data.value) {
						updateField.call(this, this.name, response.data.value);
					}
				}).catch(xhr => {
					this.resetLabel();
					let messages = [this.$s('translation_internal_error')];
					if (xhr.response?.data.messages) {
						messages = xhr.response.data.messages;
					}
					// Call updateField directly instead of $emit as ADD_SERVER_VALIDATION must happen afterwards
					updateField.call(this, this.name, null).then(() => {
						this.$store.commit('ADD_SERVER_VALIDATION', { key: `fields.${this.name}`, messages });
						this.$store.getters.$xv.fields[this.name].$touch(); // Won't be triggered by updateField if there is no value change
						this.resetLabel();
					});
				}).then(() => {
					this.progress = 0;
					this.localDisabled = false;
				});
			},
			remove() {
				const data = this.createFormData();
				data.append('delete', '1');
				upload(data);
				updateField.call(this, this.name, null).then(() => {
					this.resetLabel();
				});
			},
			createFormData() {
				const data = new FormData();
				data.append('name', this.name);
				data.append('value', this.value); // Send old file name to delete old file if it exists
				return data;
			},
			resetLabel() {
				this.label = this.$t('choose');
				if (this.value) {
					this.label = this.value.split(':')[1];
				}
			}
		}
	}
</script>