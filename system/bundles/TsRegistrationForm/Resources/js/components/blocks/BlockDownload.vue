<template>
	<div class="form-group">
		<label :for="id">{{ label }}</label>
		<button
			:id="id"
			:class="['btn btn-secondary form-control', { 'is-invalid': error }]"
			@click="click"
		>
			<i :class="$icon('download')"></i> {{ $t('download') }}
		</button>
		<a ref="link" :href="blob" :download="file"></a>
		<p class="invalid-feedback">{{ $s('translation_internal_error') }}</p>
	</div>
</template>

<script>
	import { file } from '../../utils/api';
	import TranslationsMixin from '../mixins/TranslationsMixin';

	export default {
		mixins: [
			TranslationsMixin
		],
		props: {
			label: String,
			file: String
		},
		data() {
			return {
				id: this.$id('download'),
				blob: null,
				error: false
			}
		},
		methods: {
			click() {
				this.error = false;
				file({ name: this.file }).then(response => {
					this.blob = window.URL.createObjectURL(response.data);
					this.$nextTick(() => {
						this.$refs.link.click();
						// Not sure if some browsers still (e.g. Firefox) bug without this
						setTimeout(this.reset, 100);
					});
				}).catch(error => {
					this.error = true;
					this.$log.error('File download failed', error);
				});
			},
			reset() {
				window.URL.revokeObjectURL(this.blob);
				this.blob = null;
			}
		}
	}
</script>
