<template>
	<div
		v-show="active"
		class="loading-overlay"
		:style="{ 'z-index': zIndex }"
		role="status"
		@click="$emit('click', $event)"
	>
		<div>
			<i v-if="icon" :class="icon"></i>
			<span class="sr-only">Loading…</span>
			<slot></slot>
		</div>
	</div>
</template>

<script>
	export default {
		props: {
			active: Boolean,
			icon: String,
			zIndex: { type: Number, default: 500 } // PayPal already has 100
		}
	}
</script>
