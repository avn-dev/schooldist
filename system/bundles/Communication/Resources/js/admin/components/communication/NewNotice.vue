<script lang="ts">
import { defineComponent, inject, reactive, ref, nextTick, onMounted, type Ref, type PropType } from 'vue'
import { ComponentApiInterface } from '@Admin/types/backend/router'
import { SelectOption as SelectOptionType } from '@Admin/types/common'
import { Alert } from "@Admin/types/interface"
import { useTooltip } from '@Admin/composables/tooltip'
import AlertMessage from '@Admin/components/AlertMessage.vue'
import { CommunicationContact, CommunicationNoticeForm } from '../../types/communication'
import RecipientSelection from './message/RecipientSelection.vue'

export default defineComponent({
	name: "NewNotice",
	components: { RecipientSelection, AlertMessage },
	props: {
		api: { type: Object as PropType<ComponentApiInterface>, required: true },
		types: { type: Array as PropType<SelectOptionType[]|null>, default: () => [] },
		contacts: { type: Array as PropType<CommunicationContact[]|null>, default: () => [] },
	},
	emits: ['close'],
	setup(props) {
/*		const contentRef: Ref<HTMLElement|null> = ref(null)
		const loading: Ref<boolean> = ref(false)
		const sending: Ref<boolean> = ref(false)
		const alerts: Ref<Alert[]> = ref([])
		const form: CommunicationNoticeForm = reactive({
			type: props.types[0].value,
			direction: 'in',
			to: [],
			subject: '',
			content: ''
		})

		let contentTinyMCE: string|null = null

		const resetView = inject<() => void>('resetView')

		const { showTooltip } = useTooltip()

		const init = async () => {
			await initHtml()
		}

		const discard = async () => {
			await resetView()
		}

		const initHtml = async () => {

			let editor

			if (contentTinyMCE !== null) {
				// @ts-ignore
				editor = window.tinymce.get(contentTinyMCE)
			} else if (contentRef.value) {
				// @ts-ignore
				const editors = await window.tinymce.init({
					target: contentRef.value.getElementsByTagName('textarea')[0],
					mode: "exact",
					theme: "modern",
					skin: "lightgray",
					plugins: [
						"advlist autolink lists link image charmap print preview hr anchor pagebreak",
						"searchreplace wordcount visualblocks visualchars code fullscreen",
						"insertdatetime media nonbreaking save table contextmenu directionality",
						"emoticons template paste textcolor colorpicker textpattern"
					],
					menubar: false,
					toolbar1: 'undo redo | styleselect | searchreplace pastetext visualblocks visualchars | bold italic underline | alignleft aligncenter alignright | forecolor | bullist numlist outdent indent | preview code fullscreen',
					toolbar2: "backcolor | link | charmap table",
					toolbar_items_size: 'small',
					forced_root_block: false,
					verify_html: false,
					convert_urls: false,
					remove_script_host: true,
					resize: false
				})

				editor = editors[0] ?? null
			}

			if (editor) {
				contentTinyMCE = editor.id
				editor.setContent(form.content, { format : 'html' })

				setTimeout(() => resizeHtmlEditor(), 1)

			} else {
				console.error('Unable to init HTML editor')
			}

		}

		const resizeHtmlEditor = async () => {
			await nextTick()

			if (!contentRef.value) {
				console.warn('Missing container ref')
				return
			}

			const editorBody = contentRef.value.getElementsByTagName('iframe')[0]

			const fullHeight = contentRef.value.getBoundingClientRect().height
			const editorHeight = contentRef.value.getElementsByClassName('mce-tinymce')[0].getBoundingClientRect().height
			const editorBodyHeight = editorBody.getBoundingClientRect().height // iframe

			editorBody.style.height = `${fullHeight - editorHeight + editorBodyHeight - 1}px`
		}

		onMounted(() => init())

		return {
			contentRef,
			loading,
			sending,
			alerts,
			form,
			discard,
			showTooltip
		}*/
	}
})
</script>

<template>
	<!--<div class="flex-none h-full">
		<div class="flex flex-col h-full gap-1">
			<AlertMessage
				v-for="(alert, index) in alerts"
				:key="index"
				:type="alert.type"
				:heading="alert.heading ?? null"
				:message="alert.message"
				:icon="alert.icon"
				class="flex-none p-2 text-xs"
			/>
			<div class="grow overflow-scroll">
				<div class="h-full flex flex-col gap-1 bg-white p-1 rounded-md">
					<div class="flex-none font-medium text-gray-500 divide-y divide-gray-50 border-b border-gray-50">
						<div class="flex flex-row items-center gap-1 pb-1">
							<button
								type="button"
								class="size-7 rounded hover:bg-gray-100/50 disabled:opacity-50"
								:disabled="loading || sending"
								@click="discard"
								@mouseenter="showTooltip($l10n.translate('communication.message.discard'), $event, 'top')"
							>
								<i class="far fa-trash-alt" />
							</button>
						</div>
						<div class="flex flex-row items-center gap-2 p-1">
							<!--<span class="w-10 text-right flex-none font-medium text-gray-500">
								{{ $l10n.translate('communication.notice.type') }}
							</span>-->
							<div class="grow flex flex-row gap-1">
								<select
									v-model="form.type"
									class="bg-white text-gray-500 border border-gray-100/50 rounded h-6 px-1"
								>
									<option
										v-for="type in types"
										:key="type.value"
										:value="type.value"
										:selected="form.type === type.value"
									>
										{{ type.text }}
									</option>
								</select>
								<select
									v-model="form.direction"
									class="bg-white text-gray-500 border border-gray-100/50 rounded h-6 px-1"
								>
									<option
										value="in"
										:selected="form.direction === 'in'"
									>
										{{ $l10n.translate('communication.notice.direction.in') }}
									</option>
									<option
										value="out"
										:selected="form.direction === 'in'"
									>
										{{ $l10n.translate('communication.notice.direction.out') }}
									</option>
								</select>
							</div>
						</div>
						<div class="flex flex-row items-center gap-2 p-1">
							<span class="w-10 text-center flex-none font-medium text-gray-500 bg-gray-50 rounded py-1">
								<i
									class="fa fa-user"
									@mouseenter="showTooltip($l10n.translate('communication.notice.to'), $event, 'top')"
								/>
							</span>
							<div class="grow">
								<RecipientSelection
									v-model="form.to"
									:api="api"
									channel="notice"
									:config="{}"
									:contacts="contacts"
								/>
							</div>
						</div>
						<div class="flex flex-row items-center gap-2 p-1">
							<span class="w-10 text-center flex-none font-medium text-gray-500 bg-gray-50 rounded py-1">
								<i class="fa fa-comments" />
							</span>
							<div class="grow flex flex-row gap-1">
								<input
									v-model="form.subject"
									type="text"
									class="w-full h-6 placeholder:text-gray-200/75 placeholder:font-light"
									:placeholder="$l10n.translate('communication.notice.subject')"
								>
							</div>
							<span class="w-10 text-center flex-none font-medium text-gray-500 bg-gray-50 rounded py-1">
								<i class="fa fa-clock" />
							</span>
							<div class="flex-none">
								<input
									v-model="form.subject"
									type="text"
									class="w-56 h-6 placeholder:text-gray-200/75 placeholder:font-light"
									:placeholder="$l10n.translate('communication.notice.date_time')"
								>
							</div>
						</div>
					</div>
					<div
						ref="contentRef"
						class="grow overflow-scroll rounded text-xs"
					>
						<textarea
							v-model="form.content"
							:class="['h-full w-full p-1 resize-none', {'hidden': loading}]"
						/>
					</div>
				</div>
			</div>
		</div>
	</div>-->
</template>