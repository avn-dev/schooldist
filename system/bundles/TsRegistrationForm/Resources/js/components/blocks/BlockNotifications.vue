<template>
	<div v-show="notifications.length" ref="container">
		<!--<transition-group tag="ul" name="fade" class="list-group">-->
		<ul class="list-group">
			<li
				v-for="notification in notifications"
				:key="notification.key"
				class="list-group-item"
				:class="`list-group-item-${notification.type}`"
			>
				<i :class="$icon(notification.type)"></i>
				{{ notification.message }}
				<button type="button" class="close" aria-label="Close" @click="remove(notification)">
					<i :class="$icon('times')" aria-hidden="true"></i>
				</button>
			</li>
		<!--</transition-group>-->
		</ul>
	</div>
</template>

<script>
	import { scrollToElement } from '@TcFrontend/common/utils/widget';

	export default {
		data() {
			return {
				storeWatchers: []
			}
		},
		computed: {
			notifications() {
				return this.$store.state.form.notifications;
			}
		},
		created() {
			// Prefer activeErrors as $anyError doesn't seem to be responsive if values within are reset(?)
			this.storeWatchers.push(this.$store.watch((state, getters) => getters.$xvVue.activeErrors, (value) => {
				if (value.length === 0) {
					// Delete error message when Vuelidate's main $error is resolved
					// A page change purges all notifications anyway but this is for UX
					this.$store.commit('DELETE_NOTIFICATION', 'validation_error'); // Do not change key
				}
			}));
			// Watch for scroll_to_notifications when new notification is added
			this.storeWatchers.push(this.$store.watch((state) => state.form.state.scroll_to_notifications, (value) => {
				if (value) {
					scrollToElement(this.$refs.container);
					// this.$scrollTo(this.$refs.container);
					this.$store.commit('SET_STATE', { key: 'scroll_to_notifications', status: false });
				}
			}));
		},
		// TODO Vue 3: beforeDestroy => beforeUnmount
		beforeDestroy() {
			this.storeWatchers.forEach((fn) => fn());
		},
		methods: {
			remove(notification) {
				this.$store.commit('DELETE_NOTIFICATION', notification.key);
			}
		}
	}
</script>
