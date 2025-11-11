import { reactive, readonly, nextTick, ref, type Ref } from 'vue3'
import { Navigation, NavigationLayout, NavigationNode } from '../types/backend/app'
import { ContentLoadingState, LoadingState, ComponentApiInterface } from '../types/backend/router'
import { safe } from '../utils/promise'
import { sleep } from '../utils/util'
import router from '../router'

const loadedNodes: Ref<string[]> = ref([])
const navigation: Navigation = reactive({
	layout: NavigationLayout.basic,
	open: false,
	nodes: []
} as Navigation)

const init = async (payload: Navigation) => {
	Object.assign(navigation, payload)
	loadedNodes.value = payload.nodes.map((node) => node.id)
	return true
}

const api = (): ComponentApiInterface => {
	return router.component('navigation')
}

const toggleNavigation = () => {

	navigation.open = !navigation.open

	/*if (navigation.layout === NavigationLayout.basic) {
		navigation.layout = NavigationLayout.extended
	} else {
		navigation.layout = NavigationLayout.basic
	}*/
}

const closeNavigation = () => {
	navigation.open = false
}

const getNavigationNodeChilds = async (id: string): Promise<NavigationNode[]> => {

	let childs = navigation.nodes.filter((loop: NavigationNode) => loop.parent === id)

	if (childs.length === 0) {
		setNodeLoadingState(id, { state: LoadingState.loading })

		const [, response] = await safe<{ nodes: NavigationNode[] }>(api().action('load', { params: { id: id } }))

		let loadingState = LoadingState.failed
		if (response) {
			childs = response.nodes

			response.nodes.forEach((child: NavigationNode) => {
				if (!loadedNodes.value.includes(child.id)) {
					navigation.nodes.push(child)
					loadedNodes.value.push(child.id)
				}
			})

			await nextTick()

			loadingState = LoadingState.loaded
		}

		await sleep(200)

		setNodeLoadingState(id, { state: loadingState })
	}

	return childs
}

const highlightNode = (id: string): Promise<boolean> => {
	return setActive(id, true)
}

const handleNavigationNodeClick = async (id: string): Promise<boolean> => {
	const node = await getNode(id)
	if (node) {
		if (node.action) {
			const success = await setActive(node.id, true)
			if (success) {
				await router.action(node.action)
			}
			return success
		} else {
			return await setActive(node.id, !node.active)
		}
	}

	return false
}

const collapseNavigationNodes = () => {
	const nodes: NavigationNode[] = []

	navigation.nodes.forEach(async (loop: NavigationNode) => {
		loop.active = false
		nodes.push(loop)
	})

	navigation.nodes = nodes
}

const setActive = async (id: string, active: boolean): Promise<boolean> => {

	const node = await getNode(id)

	const nodeIds: string[] = splitNodeId(id)
	const nodes: NavigationNode[] = []

	navigation.nodes.forEach((loop: NavigationNode) => {
		if (node && loop.id === id) {
			loop.active = active
		} else {
			loop.active = nodeIds.indexOf(loop.id) !== -1
		}
		nodes.push(loop)
	})

	navigation.nodes = nodes

	return true
}

const setNodeLoadingState = (id: string, state: ContentLoadingState) => {
	const index = navigation.nodes.findIndex((loop: NavigationNode) => loop.id === id)

	if (index !== -1) {
		navigation.nodes[index].state = state
	}
}

const getNode = async (id: string): Promise<NavigationNode | null> => {

	const existing = navigation.nodes.find((loop: NavigationNode) => loop.id === id)
	if (existing) {
		return existing
	}

	const promises = splitNodeId(id)
		.filter((nodeId: string) => nodeId !== id)
		.map((nodeId: string) => getNavigationNodeChilds(nodeId))

	await Promise.all(promises)

	const node = navigation.nodes.find((loop: NavigationNode) => loop.id === id)

	if (typeof node === 'undefined') {
		console.error(`Cannot find navigation node "${id}"`)
		return null
	}

	return node
}

const splitNodeId = (id: string) => {
	const parts = id.split('-')
	const nodeIds = []

	let prefix = ''
	for (let i = 0; i < parts.length; i++) {
		const id = (prefix !== '') ? prefix + '-' + parts[i] : parts[i]
		nodeIds.push(id)
		prefix = id
	}

	return nodeIds
}

export function useNavigation() {
	return {
		navigation: readonly(navigation),
		init,
		api,
		toggleNavigation,
		closeNavigation,
		collapseNavigationNodes,
		handleNavigationNodeClick,
		highlightNode,
		getNavigationNodeChilds
	}
}