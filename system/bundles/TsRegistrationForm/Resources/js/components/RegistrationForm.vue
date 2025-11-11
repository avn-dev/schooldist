<template>
	<div class="fidelo-registration-form">
		<div class="container-fluid">
			<loading-overlay :active="isLoading" :icon="$icon('spinner')" :z-index="1000"></loading-overlay>
			<components-view :components="currentPageComponents"></components-view>
		</div>
	</div>
</template>

<script>
	import { initHistory} from '../utils/history';
	import store from '../store/index';
	import LoadingOverlay from '@TcFrontend/common/components/LoadingOverlay';
	import ComponentsView from './common/ComponentsView';

	export default {
		store,
		components: {
			ComponentsView,
			LoadingOverlay
		},
		computed: {
			currentPageComponents() {
				return this.$store.state.form.pages[this.$store.state.form.state.page_current].components;
			},
			isLoading() {
				return this.$store.getters.getLoadingState('form');
			}
		},
		created() {
			initHistory(this);
		},
		mounted() {
			// Img-Preload
			this.$store.state.form.accommodations.forEach((accommodation) => {
				if (accommodation.img) {
					const img = new Image();
					img.src = this.$path(accommodation.img);
				}
			});
		}
	}
</script>
