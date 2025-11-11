<script>
export default {
	expose: [
		'focus'
	],
	props: {
		request: { type: Function, required: true }, // Needs to return Promise
		disabled: { required: false },
		url: { type: String, required: true },
		translations: { type: Object, required: true },
		method: { type: Object, required: true }
	},
	emits: [
		'approve', // approve(paymentData)
		'cancel', // cancel()
		'error', // error(type, error, errorMessage)
		'loading', // (state, text)
		'update:method' // ({...method})
	],
	methods: {
		focus() {
			// noop
		},
		requestStatus(data) {
			return this.request({ check: true, payment: data });
		}
	}
}
</script>
