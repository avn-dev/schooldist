<script lang="ts">
import { defineComponent } from 'vue3'
import { ChatState } from '../../types/backend/app'
import { getPrimaryColor, buildPrimaryColorCssClass, getPrimaryColorContrastShade, buildPrimaryColorElementCssClasses } from "../../utils/primarycolor"
import { useSupport } from "../../composables/support"
import { useNavigation } from "../../composables/navigation"
import { useSearch } from "../../composables/search"
import { useUser } from "../../composables/user"
import { useInterface } from "../../composables/interface"
import { useTooltip } from '../../composables/tooltip'
import UserAvatar from "../../components/UserAvatar.vue"
import router from "../../router"
import Tenants from './header/Tenants.vue'

export default defineComponent({
	name: "PageHeader",
	components: { Tenants, UserAvatar },
	setup() {
		const { logo, tenants } = useInterface()
		const { chatState, isEnabled: hasSupport, hasFeature: hasSupportFeature } = useSupport()
		const navigation = useNavigation()
		const search = useSearch()
		const { user, unseenNotifications } = useUser()
		const { showTooltip } = useTooltip()

		const primaryColor = getPrimaryColor()

		return {
			ChatState,
			logo: logo.value.system ?? null,
			tenants,
			supportLogo: logo.value.support ?? null,
			chatState,
			navigation,
			search,
			user,
			unseenNotifications,
			primaryColor,
			router,
			hasSupport,
			hasSupportFeature,
			buildPrimaryColorCssClass,
			getPrimaryColorContrastShade,
			buildPrimaryColorElementCssClasses,
			showTooltip
		}
	}
})
</script>

<template>
	<div
		:class="[
			'sticky top-0 z-10 flex-none flex h-12 shrink-0 items-center lg:gap-x-6',
			buildPrimaryColorCssClass('bg')
		]"
	>
		<div class="flex items-center gap-x-2 px-2 lg:px-4 lg:hidden">
			<button
				type="button"
				:class="[
					'px-2 py-1 rounded lg:hidden',
					buildPrimaryColorCssClass('hover:bg', getPrimaryColorContrastShade(), 20),
					buildPrimaryColorCssClass('text', getPrimaryColorContrastShade('text')),
				]"
				@click="navigation.toggleNavigation"
			>
				<i
					class="fa fa-bars"
					aria-hidden="true"
				/>
			</button>
			<div
				:class="[
					'flex-none h-6 w-px lg:hidden',
					buildPrimaryColorCssClass('bg', getPrimaryColorContrastShade(), 50)
				]"
				aria-hidden="true"
			/>
		</div>
		<div class="flex flex-1 self-stretch">
			<div class="relative flex flex-1 items-center">
				<slot />
			</div>
			<div class="flex items-center gap-x-2 pr-2 relative justify-end">
				<div class="h-full py-1 hidden lg:block">
					<Tenants
						v-if="tenants.length > 0"
						:tenants="tenants"
					/>
					<img
						v-else-if="logo"
						:src="logo"
						class="h-full"
					>
				</div>
				<div
					:class="[
						'flex-none h-6 w-px',
						buildPrimaryColorCssClass('bg', getPrimaryColorContrastShade(), 50),
						{ 'lg:hidden': !logo && !tenants }
					]"
					aria-hidden="true"
				/>
				<button
					v-if="hasSupport()"
					type="button"
					:class="[
						'relative h-8 w-8 rounded-full flex items-center place-content-center font-normal',
						buildPrimaryColorCssClass('bg', getPrimaryColorContrastShade(), 20),
						buildPrimaryColorCssClass('hover:bg', getPrimaryColorContrastShade(), 40),
						(primaryColor.base <= 100) ? 'text-black' : 'text-white'
					]"
					@click="router.openSupport"
					@mouseenter="showTooltip(`${$l10n.translate('interface.support')}: ${$l10n.translate(`interface.support.chat.${chatState}`)}`, $event, 'bottom')"
				>
					<img
						v-if="supportLogo"
						:src="supportLogo"
						class="h-4"
					>
					<i
						v-else
						class="fa fa-life-ring"
					/>
					<span
						v-if="hasSupportFeature('support_chat') && chatState === ChatState.online"
						:class="[
							'animate-ping absolute -right-0 -top-0 rounded text-xs h-2 w-2',
							buildPrimaryColorCssClass('bg', getPrimaryColorContrastShade())
						]"
					/>
					<span
						v-if="hasSupportFeature('support_chat') && [ChatState.online, ChatState.away].indexOf(chatState) !== -1"
						:class="[
							'absolute -right-0 -top-0 rounded text-xs h-2 w-2',
							buildPrimaryColorCssClass('bg', getPrimaryColorContrastShade())
						]"
					/>
				</button>
				<button
					type="button"
					class="h-8 -m-1.5 flex items-center p-1.5"
					aria-expanded="false"
					aria-haspopup="true"
					@click="router.openUserBoard"
					@mouseenter="showTooltip(user.name, $event, 'bottom')"
				>
					<UserAvatar
						:user="user"
						:class="[
							'relative text-xs h-8 w-8',
							buildPrimaryColorCssClass('bg', getPrimaryColorContrastShade(), 20),
							buildPrimaryColorCssClass('hover:bg', getPrimaryColorContrastShade(), 40),
							(primaryColor.base <= 100) ? 'text-black' : 'text-white'
						]"
					>
						<span
							v-if="unseenNotifications > 0"
							:class="[
								'animate-ping absolute -right-0 -top-0 rounded text-xs h-2 w-2',
								buildPrimaryColorCssClass('bg', getPrimaryColorContrastShade())
							]"
						/>
						<span
							v-if="unseenNotifications > 0"
							:class="[
								'absolute -right-0 -top-0 rounded text-xs h-2 w-2',
								buildPrimaryColorCssClass('bg', getPrimaryColorContrastShade())
							]"
						/>
					</UserAvatar>
				</button>
			</div>
		</div>
	</div>
</template>
