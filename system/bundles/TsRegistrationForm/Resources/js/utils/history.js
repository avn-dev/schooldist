// TODO #17229 Must be removed
let vueInstance;

const initHistory = function (vue) {
	vueInstance = vue;
	window.history.replaceState({ page: 0 }, '');
	registerPopState();
	registerBeforeUnload();
}

const registerPopState = () => {
	window.addEventListener('popstate', (event) => {
		const page = event.state?.page ?? 0;
		vueInstance.$log.info(`history.popstate: event page ${page} (${event.state?.page}), current page ${vueInstance.$store.state.form.state.page_current}`);
		if (page < vueInstance.$store.state.form.state.page_current) {
			vueInstance.$store.dispatch('prevPage');
		}
	});
};

const registerBeforeUnload = () => {
	if (vueInstance.$s('debug')) {
		return;
	}
	window.addEventListener('beforeunload', (event) => {
		// A successful submit will dispatch resetVuelidate action
		if (vueInstance.$store.getters.$xv.$anyDirty) {
			event.preventDefault();
			event.returnValue = ''; // Chrome requires returnValue to be set
		} else {
			delete event['returnValue'];
		}
	});
};

const pushState = (data) => {
	window.history.pushState(data, '');
	vueInstance.$log.info('history.pushState:', data);
};

export {
	initHistory,
	pushState
}
