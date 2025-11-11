<script lang="ts">
import { PropType, defineComponent } from 'vue3'
import { User } from '../types/backend/app'

export default defineComponent({
	name: "UserAvatar",
	props: {
		user: { type: Object as PropType<User>, required: true },
	},
})
</script>

<template>
	<div
		v-if="user.avatar"
		class="rounded-full !bg-transparent"
	>
		<img
			:src="user.avatar"
			:alt="user.name"
			class="rounded-full h-full w-full"
		>
		<slot />
	</div>
	<span
		v-else
		class="grid items-center place-content-center rounded-full font-semibold"
	>
		<i
			v-if="user.icon"
			:class="user.icon"
		/>
		<span v-else>
			{{ user.initials }}
		</span>
		<slot />
	</span>
</template>
