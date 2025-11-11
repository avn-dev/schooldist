<script>
export default {
	props: {
		method: { type: Object, required: true },
		active: { type: Boolean, required: true },
		request: { type: Function, required: true },
		disabled: { type: Boolean, required: true }
	},
	expose: [
		'method',
		'paymentComponent'
	],
	emits: [
		'change'
	]
}
</script>

<template>
	<div :class="['card', `payment-method-${method.key}`, { active }]">
		<div class="card-body">
			<div class="card-title custom-control custom-radio">
				<input
					:id="$id(method.key)"
					type="radio"
					class="custom-control-input"
					:checked="active"
					:disabled="disabled"
					@change="$emit('change')"
				>
				<label
					:for="$id(method.key)"
					class="custom-control-label payment-method-label"
				>
					<!-- icon_class: E.g. Skip, Stripe Card -->
					<!-- icon: E.g. Klarna, TransferMate -->
					<!-- icon_only: E.g. PayPal -->
					<span>
						<i
							v-if="method.icon_class"
							:class="method.icon_class"
							aria-hidden="true"
						/>
						<img
							v-if="method.icon"
							:src="method.icon"
							:alt="method.alt"
							:title="method.alt"
						>
						<span v-if="!method.icon_only">
							&nbsp;{{ method.label }}
						</span>
					</span>
					<small
						v-if="method.subtitle"
						class="subtitle"
					>
						{{ method.subtitle }}
					</small>
				</label>
			</div>
			<component
				v-if="active"
				ref="component"
				:is="method.component"
				:method="method"
				:request="request"
				:translations="method.translations"
				:url="method.url"
				:disabled="disabled"
				v-on="$listeners"
			></component>
		</div>
	</div>
</template>
