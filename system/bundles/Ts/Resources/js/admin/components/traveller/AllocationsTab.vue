<script lang="ts">
import { PropType } from "vue3"
import type { Traveller, Inquiry } from "../../types"

export default {
	name: "AllocationsTab",
	props: {
		student: { type: Object as PropType<Traveller>, required: true },
		inquiry: { type: Object as PropType<Inquiry>, required: true },
	},
}
</script>

<template>
	<div>
		<div>UK Matching</div>
	</div>
</template>
