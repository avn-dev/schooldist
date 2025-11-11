<script lang="ts">
import { defineComponent, ref, onMounted, watch, type PropType, type Ref } from 'vue3'
import { UserNotificationAnnouncement } from '../../../types/backend/app'
import { useInterface } from '../../../composables/interface'

export default defineComponent({
	name: "Announcement",
	props: {
		announcement: { type: Object as PropType<UserNotificationAnnouncement>, required: true },
	},
	setup(props) {
		const { scope } = useInterface()
		const image: Ref<{src: string, height: number, width: number}|null> = ref(null)
		const elementRef: Ref<HTMLElement|null> = ref(null)
		const bodyRef: Ref<HTMLElement|null> = ref(null)

		const prepareImage = () => {
			if (
				!props.announcement.data.image ||
				!props.announcement.data.image_width ||
				!props.announcement.data.image_height ||
				!bodyRef.value || !elementRef.value
			) {
				console.error('Missing announcement properties')
				return
			}

			const bodyHeight = bodyRef.value.getBoundingClientRect().height
			const maxWidth = elementRef.value.getBoundingClientRect().width
			const ratio = props.announcement.data.image_width / props.announcement.data.image_height

			let width = maxWidth
			let height = (ratio > 1)
				? props.announcement.data.image_height / ratio
				: width * ratio

			while (scope.height > 0 && (height + bodyHeight + 200) > scope.height) {
				width -= 20
				height = width * ratio
			}

			image.value = {
				src: props.announcement.data.image,
				height: height,
				width: width
			}
		}

		onMounted(() => {
			prepareImage()
			watch(scope, prepareImage)
		})

		return {
			elementRef,
			bodyRef,
			image
		}
	}
})
</script>

<template>
	<div ref="elementRef">
		<div
			v-if="image"
			class="flex justify-center bg-gray-50 bg-center rounded-t-md overflow-hidden"
		>
			<img
				:src="image.src"
				:height="image.height"
				:width="image.width"
				:alt="announcement.subject"
				class=""
			>
		</div>
		<div
			ref="bodyRef"
			class="text-xs"
		>
			<div class="px-4 py-4">
				<h4 class="font-bold">
					{{ announcement.subject }}
				</h4>
				<!-- eslint-disable vue/no-v-html -->
				<p
					class="mt-1"
					v-html="announcement.message"
				/>
			</div>
		</div>
	</div>
</template>