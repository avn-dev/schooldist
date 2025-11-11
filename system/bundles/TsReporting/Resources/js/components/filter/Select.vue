<script lang="ts">
import { defineComponent } from 'vue3'
// @ts-ignore 2307 type-definitions nicht verfügbar für Vue 3/Beta
import VueSelect from 'vue-select'
import { EMITS, PROPS } from '../../composables/filter'

export default defineComponent({
	components: { VueSelect },
	props: PROPS,
	emits: EMITS,
	methods: {
		update(value: unknown[]) {
			if (value.length) {
				this.$emit('update:model-value', value)
			} else {
				this.$emit('update:model-value', null)
			}
		}
	}
})
</script>

<template>
	<!-- eslint-disable @typescript-eslint/no-explicit-any -->
	<vue-select
		:model-value="modelValue"
		:clearable="false"
		:multiple="type === 'multiple'"
		:options="options"
		:reduce="option => option.key"
		@update:model-value="update"
	/>
</template>
