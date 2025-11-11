<template>
	<div ref="element">
		<slot />
	</div>
</template>

<script>
import { ref, onMounted, nextTick } from "vue3"

export default {
	name: "ViewPort",
	emits: ['enter', 'leave', 'change'],
	setup(props, context) {

		const observer = ref(null)
		const element = ref(null)

		onMounted(() => {

			if(!('IntersectionObserver' in window)) {
				context.emit('enter', element.value)
				return
			}

			observer.value = new IntersectionObserver((entries) => {

				if (!entries[0].isIntersecting) {
					context.emit('leave', [entries[0]])
				} else {
					context.emit('enter', [entries[0]])
				}

				context.emit('change', [entries[0]])

			}, {
				threshold: [0, 0.2],
				root: null,
				rootMargin: '0px 0px 0px 0px'
			})

			nextTick(() => {
				observer.value.observe(element.value)
			})
		})

		return {
			element
		}
	}
}
</script>

