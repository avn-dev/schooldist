<template>
	<button type="button" class="btn btn-sm btn-outline-danger float-right" @click="click">
		<i :class="$icon('trash')"></i>
		{{ label }}
	</button>
</template>

<script>
	export default {
		props: {
			label: String
		},
		methods: {
			click() {
				this.$emit('click');
			}
		}
	}
</script>
