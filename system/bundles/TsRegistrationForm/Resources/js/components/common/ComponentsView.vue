<template>
	<div>
	<!-- Doesn't work as expected as several elements loose their width while in transition -->
	<!--<transition-group name="fade" tag="div">-->
		<component v-for="component in components"
		   :key="component.key"
		   :is="component.component"
		   v-bind="component"
		></component>
	<!--</transition-group>-->
	</div>
</template>

<script>
	export default {
		props: {
			components: {
				type: Array,
				required: true
			}
		}
	}
</script>
