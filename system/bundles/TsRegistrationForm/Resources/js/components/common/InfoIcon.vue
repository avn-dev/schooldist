<template>
	<span v-if="content" class="info-icon">
		 <i :class="$icon('question-circle')" v-tooltip="options"></i>
	</span>
</template>

<script>
import { VTooltip, VPopover } from 'v-tooltip';

// Overwrite default class .tooltip
const template = '<div class="v-tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>';

export default {
	directives: {
		tooltip: VTooltip
	},
	components: {
		VPopover
	},
	props: {
		content: { required: true }
	},
	data() {
		return {
			options: {
				content: this.content,
				container: this.$root.$el, // Adapt BS4 styles within form container
				trigger: 'hover',
				autoHide: false, // Make links clickable
				hideOnTargetClick: false, // Make links clickable
				delay: { show: 0, hide: 500 }, // Make links clickable
				placement: 'right',
				template
			}
		};
	},
	created() {
		this.resize();
		window.addEventListener('resize', this.resize);
	},
	destroyed() {
		window.removeEventListener('resize', this.resize);
	},
	methods: {
		resize() {
			this.options.trigger = window.innerWidth > 768 ? 'hover' : 'click';
		}
	}
}
</script>
