
const checkCustomElements = (el) => {
	if ('customElements' in window) {
		return true;
	}

	console.error('Browser does not support customElements!');

	if (el && (el = document.querySelector(el))) {
		const strong = document.createElement('strong');
		el.innerHTML = 'Please upgrade to a modern browser: Chrome ≥ 54, Firefox ≥ 63, Opera ≥ 64, Safari ≥ 11.1, iOS Safari ≥ 11.3, Edge ≥ 79';
		el.parentNode.insertBefore(strong, el.nextSibling);
	}

	return false;
};

const checkElement = (el) => {
	if (!document.querySelector(el)) {
		console.error(`No <${el}> tag found`);
		return false;
	}

	return true;
}

const checkFidelo = () => {
	const check = (
		window.__FIDELO__ &&
		window.__FIDELO__.getHost &&
		window.__FIDELO__.data
	);

	if (!check) {
		console.error('Fidelo widget: Widget bootstrapper missing!');
	}

	return check;
};

/**
 * Specify CSS variable to add scroll offset for pages using fixed/sticky elements
 * This does not work for IE11 (no CSS vars support) but fixed/sticky does not work in IE11 also.
 * @returns {Number}
 */
const getScrollOffset = () => {
	const addOffset = -10; // Include some additional scroll offset
	// TODO #17229 Must be replaced if more than one widget element is possible
	const el = document.querySelector('fidelo-widget');
	if (!el) {
		return addOffset;
	}
	const offset = window.getComputedStyle(el).getPropertyValue('--fidelo-widget-scroll-offset');
	if (offset) {
		return parseInt(offset) + addOffset;
	}
	return addOffset;
};

/**
 *
 * @param {HTMLElement} element
 */
const scrollToElement = (element) => {
	let y = getScrollOffset() + element.getBoundingClientRect().top; // TODO If this does not work use cumulativeOffset (see vue-scrollTo and iframe-resizer)
	// if (y < 0) {
		// getBoundingClientRect().y is relative to scroll position
		y += window.scrollY;
	// }

	// parentIFrame will be set by iFrameResizer in childs
	if ('parentIFrame' in window) {
		// console.log('window.parentIFrame.scrollToOffset', element, y);
		window.parentIFrame.scrollToOffset(null, y); // Pass x = null as 0 would scroll iframe offset (left side)
	} else {
		// console.log('window.scrollTo', element, y);
		window.scrollTo(0, y);
	}
}

/**
 * Extract query params
 * @returns {{}}
 */
const getUrlParams = () => {
	const params = {};
	window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, (m, key, value) => {
		return params[key] = decodeURIComponent(value);
	});
	return params;
}

/**
 * @TODO Warning: This won't work with cross origin / iframe
 *
 * Parse document.cookie
 * @returns {{}}
 */
const parseCookies = () => {
	// https://gist.github.com/rendro/525bbbf85e84fa9042c2
	return Object.fromEntries(document.cookie.split(/; */).map(c => {
		const [key, ...v] = c.split('=');
		return [key, decodeURIComponent(v.join('='))];
	}));
};

export {
	checkCustomElements,
	checkElement,
	checkFidelo,
	// getScrollOffset,
	getUrlParams,
	parseCookies,
	scrollToElement
}