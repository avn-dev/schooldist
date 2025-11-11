<script lang="ts">
import { defineComponent, ref, nextTick, type Ref, type PropType } from 'vue'
import { Passkey } from '../../types/common'
import { buildPrimaryColorElementCssClasses } from '../../utils/primarycolor'

export default defineComponent({
	name: "Passkey",
	props: {
		passkey: { type: Object as PropType<Passkey>, required: true },
	},
	emits: ['update', 'delete'],
	setup(props, { emit}) {
		const nameRef: Ref<HTMLInputElement|null> = ref(null)
		const editing: Ref<boolean> = ref(false)
		const localName: Ref<string> = ref(props.passkey.text)

		const toggleEditing = async () => {
			editing.value = !editing.value
			await nextTick()
			if (nameRef.value) {
				(editing.value) ? nameRef.value.focus() : emit('update', {
					...props.passkey,
					text: localName.value
				})
			}
		}

		const watchInput = async (event: KeyboardEvent) => {
			if (event.key === 'Enter' && nameRef.value) {
				nameRef.value.blur()
			}
		}

		return {
			nameRef,
			editing,
			localName,
			toggleEditing,
			watchInput,
			buildPrimaryColorElementCssClasses
		}
	}
})
</script>

<template>
	<div class="flex rounded-md shadow-xs dark:shadow-none h-full">
		<div class="bg-gray-100/50 flex w-16 shrink-0 items-center justify-center rounded-l-md text-sm font-medium text-white">
			<i class="fa fa-fingerprint text-gray-500 text-xl" />
		</div>
		<div class="flex flex-1 items-center justify-between truncate rounded-r-md border-t border-r border-b border-gray-100/50 bg-white dark:border-white/10 dark:bg-gray-800/50">
			<div class="flex-1 truncate px-4 py-2 text-sm">
				<div
					class="font-medium hover:text-gray-600 dark:text-white dark:hover:text-gray-200"
					@click="toggleEditing"
				>
					<span v-show="!editing">
						{{ localName }}
					</span>
					<input
						v-show="editing"
						ref="nameRef"
						v-model="localName"
						class="border-b border-gray-100/50"
						@blur="toggleEditing"
						@keydown="watchInput($event)"
					>
				</div>
				<p class="text-xs text-gray-400">
					{{ passkey.created }}
					<span v-if="passkey.last_login">
						&centerdot; {{ $l10n.translate('my_profile.last_login') }}: {{ passkey.last_login }}
					</span>
				</p>
			</div>
			<div class="shrink-0 pr-2">
				<button
					:class="[
						'inline-flex size-8 items-center justify-center rounded-md text-gray-400 hover:text-gray-500 focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600 dark:hover:text-white dark:focus:outline-white',
						buildPrimaryColorElementCssClasses('hover:')
					]"
					type="button"
					@click="$emit('delete')"
				>
					<i class="fa w-5 h-5 fa-trash text-sm" />
				</button>
			</div>
		</div>
	</div>
</template>
