<script>
export default {
	props: {
		url: { type: String, required: true },
		request: { type: Function, required: true },
		descriptionBackdrop: { type: String, required: true },
		descriptionFocus: { type: String, required: true },
		descriptionRedirect: { type: String, required: true },
		popupWidth: { type: Number, default: 1024 },
		popupHeight: { type: Number, default: 840 },
		popupAutoclose: { type: Boolean, default: true }
	},
	expose: [
		'focus',
		'openPopup'
	],
	emits: [
		'approve',
		'cancel',
		'loading'
	],
	data() {
		return {
			timers: [],
			popupFailed: false,
			requestRunning: false
		};
	},
	beforeCreate() {
		// Make NOT responsive in Vue 2 as: Blocked a frame with origin * from accessing a cross-origin frame.
		this.popup = null;
	},
	methods: {
		openPopup() {
			const features = [];
			features.push('popup=true');
			features.push(`width=${this.popupWidth}`);
			features.push(`height=${this.popupHeight}`);
			features.push(`left=${(window.innerWidth / 2) - (this.popupWidth / 2)}`);
			features.push(`top=${(window.innerHeight / 2) - (this.popupHeight / 2)}`);

			this.popup = window.open(this.url, 'fidelo_popup', features.join(','));
			if (!this.popup) {
				// Show link to try again; window.open could return null for popup blocks or in Safari
				this.popupFailed = true;
				return;
			}

			this.$emit('loading', true, this.createLoadingText(false));
			this.popup.focus();
			this.setIntervals();
		},
		setIntervals() {
			this.clearIntervals();

			// Closing window
			this.timers.push(setInterval(() => {
				if (this.popup.closed) {
					this.check();
				}
			}, 250));

			// Background check if window is not closed
			this.timers.push(setInterval(() => {
				this.check(true);
			}, 5000));
		},
		clearIntervals() {
			this.timers.forEach(id => clearInterval(id));
			this.timers = [];
		},
		check(periodically) {
			const clear = () => {
				this.clearIntervals();
				this.$emit('loading', false);
				if (!this.popup.closed) {
					if (this.popupAutoclose) {
						this.popup.close();
					}
					this.popup = null;
					this.popupFailed = false;
				}
			};

			if (this.requestRunning) {
				return;
			}

			this.requestRunning = true;
			return this.request().then(data => {
				if (data.status) {
					this.$emit('approve');
					clear();
				} else if(!periodically) {
					// !periodically means final request/check
					this.$emit('cancel');
					clear();
				}
				this.requestRunning = false;
			}).catch(() => {
				if (!periodically) {
					// !periodically means final request/check
					this.$emit('cancel');
					clear();
				}
				this.requestRunning = false;
			});
		},
		focus() { // Parent/expose
			if (this.popup) {
				// window.focus may not work due to user settings or iOS Safari
				// setTimeout: window.focus is not guranteed to resolve synchronously
				this.popup.focus();
				setTimeout(() => {
					if (
						// Only show hint if first attempt to open popup has failed. Compared to PayPal, PayPal has some big logic block for »is supporting popups«
						// TODO There are still edge cases where focus does not work and this does not trigger (Vivaldi?)
						this.popupFailed &&
						document.visibilityState === 'visible'
					) {
						// Don't use alert() as this halts the entire window (this window; PayPal solves this with a backdrop as iframe to control alert)
						this.$emit('loading', true, this.createLoadingText(true));
					}
				}, 250);
			}
		},
		createLoadingText(focusWarning) {
			let alert = '';
			if (focusWarning) {
				alert = `
					<br>
					<br>
					<div class="alert alert-warning">
						${this.descriptionFocus}
					</div>
				`;
			}

			// Click is handled by parent component and calls PaymentMixin.focus()
			return `
				${this.descriptionBackdrop}
				<br>
				<br>
				<a href="#" onclick="event.preventDefault()">${this.descriptionRedirect}</a>
				${alert}
			`;
		}
	}
}
</script>

<template>
	<p>
		<a
			:href="url"
			@click.prevent="openPopup"
		>
			{{ descriptionRedirect }}
		</a>
	</p>
</template>
