<template>
	<div class="row">
		<div v-for="col in cols" :class="'col-md-' + col.width">
			<components-view :components="col.components"></components-view>
		</div>
	</div>
</template>

<script>
	import ComponentsView from '../common/ComponentsView';

	export default {
		components: {
			ComponentsView
		},
		props: {
			cols: {
				type: Array,
				required: true
			}
		}
	}
</script>
