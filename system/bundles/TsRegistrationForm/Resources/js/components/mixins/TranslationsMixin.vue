<script>
	export default {
		props: {
			translations: {
				type: Object,
				required: true
			}
		},
		methods: {
			$t(translation) {
				return this.translations[translation];
			}
		}
	}
</script>
