
function requireComponents(app) {
	[
		require.context('../components/payment', false, /\.(vue)$/),
		// Wegen Projektstruktur und statischer Analyse nicht anders möglich
		require.context('@TsFrontend/components/payment', false, /\.(vue)$/)
	].forEach(ctx => ctx.keys().forEach(fileName => {
		const componentName = fileName.split('/').pop().replace(/\.\w+$/, '');
		app.component(componentName, ctx(fileName).default);
	}));
}

function findMethodComponent(ref, key) {
	if (!Array.isArray(ref)) {
		return null;
	}
	const method = ref.find(m => m.method.key === key);
	if (method) {
		return method;
	}
	return null;
}

export {
	findMethodComponent,
	requireComponents
}
