<template>
	<div v-if="visible" :class="cssClass">

		<div
			v-show="$v.$error"
			class="alert alert-danger"
			role="alert"
			ref="error"
		>
			{{ $t('error') }}
		</div>

		<component
			v-for="(item, index) in services"
			:key="index"
			:is="component"
			:block="block"
			:index="index"
			:translations="translations"
			view="container"
			v-bind="$attrs"
		/>

		<button
			v-if="count < max && !$v.$invalid"
			type="button"
			class="btn btn-outline-primary btn-add-service float-right"
			@click="addService(null)"
		>
			<i :class="$icon('plus')"></i>
			{{ $t('add') }}
		</button>

	</div>
</template>

<script>
	import ServiceContainerMixin from '../mixins/ServiceContainerMixin';
	import ServiceCourse from '../services/ServiceCourse';
	import ServiceAccommodation from '../services/ServiceAccommodation';

	export default {
		components: {
			ServiceCourse,
			ServiceAccommodation
		},
		mixins: [ServiceContainerMixin],
		mounted() {
			this.checkMinimumQuantity();
		},
		beforeUpdate() {
			this.checkMinimumQuantity();
		},
		methods: {
			checkMinimumQuantity() {
				if (this.visible && this.min > 0 && !this.services.length) {
					this.addService(null, {}, true);
				}
			}
		}
	}
</script>
