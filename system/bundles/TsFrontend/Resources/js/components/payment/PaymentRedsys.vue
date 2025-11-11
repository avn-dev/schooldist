<template>
	<div :class="['payment-redsys', { disabled }]">

		<div
			v-show="!data.challenge.url"
			:id="$id('container')"
			:style="{ width: `${challengeWindowSize[1]}px`, height: `${challengeWindowSize[2]}px` }"
		/>

		<input
			:id="$id('token')"
			type="hidden"
			ref="token"
		/>

		<input
			:id="$id('error')"
			type="hidden"
			ref="error"
		/>

		<form
			:action="data.challenge.url"
			:target="$id('iframe')"
			method="POST"
			ref="challengeForm"
		>
			<input
				type="hidden"
				name="creq"
				:value="data.challenge.request"
			>
		</form>

		<iframe
			v-if="data.status === 'challenge'"
			src="about:blank"
			:name="$id('iframe')"
			referrerpolicy="origin"
			sandbox="allow-same-origin allow-scripts allow-forms"
			style="display: block; margin: auto;"
			:style="{ width: `${challengeWindowSize[1]}px`, height: `${challengeWindowSize[2]}px` }"
		/>

	</div>
</template>

<script>
import LoadingOverlay from '@TcFrontend/common/components/LoadingOverlay';
import PaymentMixin from '@TcFrontend/common/components/payment/PaymentMixin.vue';

export default {
	mixins: [PaymentMixin],
	components: {
		LoadingOverlay
	},
	data() {
		return {
			data: { challenge: {} },
			listener: null,
			challengeWindowSize: ['01', 200, 400]
		}
	},
	mounted() {
		this.addMessageListener();
		this.calculateChallengeFrameSize();

		Promise.all([this.request(), this.$loadScript(this.url)]).then(values => {
			this.data = values[0];

			if (!document.getElementById(this.$id('container'))) {
				this.$emit('error', 'script');
				return;
			}

			window.getInSiteFormJSON({
				id: this.$id('container'),
				buttonValue: this.translations['pay_now'].replace('{amount}', this.data.amount),
				fuc: this.data.fuc,
				terminal: this.data.terminal,
				order: this.data.order_id,
				idioma: this.data.language
			});

			this.$emit('loading', false);
		});
	},
	// TODO Vue 3: beforeDestroy => beforeUnmount
	beforeDestroy() {
		if (this.listener) {
			this.listener.abort();
		}
	},
	methods: {
		addMessageListener() {
			if (this.listener) {
				return;
			}

			this.listener = new AbortController();
			window.addEventListener('message', event => {
				if (window.storeIdOper) {
					// Redsys: IdOper ermitteln (CC-Daten tokenized)
					window.storeIdOper(event, this.$id('token'), this.$id('error'));
				}
				if (event.data.idOper) {
					// Redsys: IdOper wurde gesetzt, submit an Backend
					this.submit();
				}
				if (event.data.status === 'challenge_response') {
					// Payment Form: redsys.tpl schickt Nachricht herhin, Form Submit
					this.$set(this.data.challenge, 'response', event.data.response);
					this.approve();
				}
			}, { signal: this.listener.signal });
		},
		calculateChallengeFrameSize() {
			// 3DS API: Optionen für challengeWindowSize
			const sizes = [
				['01', 200, 400], // 250 ist für manche mobiles zu groß und das Iframe ragt raus
				['01', 250, 400],
				['02', 390, 400],
				['03', 500, 600],
				['04', 600, 400],
				// ['05', null, null],
			];

			// Wenn größer als 600px: Dynamisch full width hinzufügen (mit aspect ratio)
			if (this.$el.offsetWidth > sizes[sizes.length - 1][1]) {
				const height = this.$el.offsetWidth * 0.66;
				sizes.push(['05', this.$el.offsetWidth, (height <= 750 ? height : 750)]);
			}
			// Eine default-Größe setzen, sonst ist keine da für kleine Bildschirme
			sizes.forEach(v => {
				if (
					this.$el.offsetWidth >= v[1] ||
					!this.challengeWindowSize
				) {
					this.challengeWindowSize = v;
				}
			});
		},
		async submit() {
			if (this.$refs.token.value.length !== 40) {
				this.error('provider', `No valid token: ${this.$refs.token.value}:${this.$refs.error.value}`);
				return;
			}

			this.$emit('loading', true);
			this.requestStatus({
				...this.data,
				token: this.$refs.token.value,
				browser: {
					depth: screen.colorDepth,
					width: screen.width,
					height: screen.height,
					tz: new Date().getTimezoneOffset(),
					size: this.challengeWindowSize[0]
				}
			}).then(request => {
				if (!request || !request.status) {
					// 200 ohne Body oder so?
					this.error('server');
				}
				if (request.status === 'challenge' && request.challenge.error) {
					// Fehlermeldung ans Frontend
					this.error('payment', request.status, `${this.translations['payment_failed']} ${request.challenge.error}`);
				} else if (request.status === 'challenge') {
					// 3DS Challenge
					this.data.status = 'challenge';
					this.$set(this.data, 'challenge', request.challenge);
					this.$nextTick(() => {
						this.$refs.challengeForm.submit();
						this.$emit('loading', false);
					});
				} else if (request.status === true) {
					// Frictionless
					this.approve();
				}
			}).catch(e => {
				this.error('server', e);
			});
		},
		async approve() {
			this.$emit('loading', true);
			this.data.status = 'approve';
			this.requestStatus({
				...this.data,
				status: 'check',
				token: this.$refs.token.value }
			).then(request => {
				this.$emit('loading', false);
				if (request.status === true) {
					this.$emit('approve', { ...this.data, token: this.$refs.token.value });
				} else {
					this.error('payment', request.status, this.translations['payment_failed']);
				}
			}).catch(e => {
				this.error('server', e);
			});
		},
		error(type, error, errorMessage) {
			this.$emit('update:method'); // Abwählen, da bei Redsys der Submit-Button gesperrt bleibt
			this.$emit('error', type, error, errorMessage);
		}
	}
}
</script>
