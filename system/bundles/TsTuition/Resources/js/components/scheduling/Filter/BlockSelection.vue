<script lang="ts">

import { ref, watch, defineComponent, type Ref } from 'vue3'
import * as debounce from 'debounce-promise'
import { useScheduling } from "../../../scheduling/composables/scheduling"

export default defineComponent({
	name: "BlockSelection",
	setup() {
		const searchValue: Ref<string> = ref('')
		const searching: Ref<boolean> = ref(false)
		const resultIndex: Ref<number> = ref(0)

		const { loading, week, filterValues, blockSelection, fillBlockSelection, unsetBlockSelection, hightlightBlock } = useScheduling()

		watch(week, () => clearSearch())
		watch(searchValue, () => search())
		watch(filterValues, () => search())

		const setValue = debounce((newValue: string) => searchValue.value = newValue, 500)
		const clearSearch = () => {
			searchValue.value = ''
			unsetBlockSelection()
		}

		const paginate = async (value: number) => {

			if (loading.value === true) {
				return
			}

			let next = resultIndex.value + value

			if (next > (blockSelection.value.length - 1)) {
				next = 0
			} else if (next < 0) {
				next = blockSelection.value.length - 1
			}

			resultIndex.value = next

			if (blockSelection.value[next]) {
				await hightlightBlock(blockSelection.value[next])
			}
		}

		const search = async () => {
			if (searchValue.value.length === 0) {
				return
			}

			resultIndex.value = 0

			searching.value = true
			const selection = await fillBlockSelection(searchValue.value)
			// Fürs Auge
			setTimeout(() => searching.value = false, 200)

			if (selection.length > 0) {
				await hightlightBlock(selection[0])
			}
		}

		return {
			searching,
			searchValue,
			resultIndex,
			blockSelection,
			clearSearch,
			paginate,
			setValue
		}
	}
})
</script>

<template>
	<div class="flex flex-row items-center gap-x-1 p-1 bg-white rounded ring-1 ring-gray-100/75">
		<input
			type="text"
			class="grow rounded"
			:value="searchValue"
			@keyup="setValue($event.target.value)"
		>
		<div class="flex-none">
			<div class="flex flex-row items-center gap-x-1">
				<i
					v-if="searching"
					class="fa fa-spinner fa-spin"
				/>
				<div
					v-else-if="searchValue.length > 0"
					class="flex flex-row items-center gap-x-1"
				>
					<i
						v-if="blockSelection.length > 0"
						class="fa fa-arrow-circle-left"
						@click="paginate(-1)"
					/>
					<span v-if="blockSelection.length > 0">
						{{ (resultIndex + 1 ) }}/{{ blockSelection.length }}
					</span>
					<i
						v-if="blockSelection.length > 0"
						class="fa fa-arrow-circle-right"
						@click="paginate(1)"
					/>
					<i
						class="fa fa-times-circle"
						@click="clearSearch"
					/>
				</div>
				<i
					v-else
					class="fa fa-search"
				/>
			</div>
		</div>
	</div>
</template>
