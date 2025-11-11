
// saveHistory(): Saves a copy of the document in the history.items.items buffer
function saveHistory() {
	codeSweeper();
	history.items[history.items.length] = escape(doc.innerHTML);
	history.cursor = history.items.length;
	window.status = 'saveHistory() cursor=' + history.cursor + ', items = ' + history.items.length;
	showHistory();
}

// goHistory(): Advance or retreat the history.items.items cursor and show the
// document as it was at that point in time.
function goHistory(value) {

	if (!RichEditor.txtView) return;
	switch(value) {
	case -1:
		i = history.cursor - 1;
		// when first start undoing, save final state at end of history buffer
		// so it can be re-done.
		if (history.cursor == history.items.length) {
			saveHistory();
		history.cursor = i - 1;
		} else {
		history.cursor = i;
		}
		break;
	case 1:
		history.cursor ++;
		break;
	}

	if (history.items[history.cursor]) {
		var temp_code = history.items[history.cursor];
		temp_code = unescape(temp_code);
		doc.innerHTML = temp_code;
	}
	window.status = 'goHistory(' + value + ') cursor=' + history.cursor + ', items = ' + history.items.length;
	showHistory()
}

// showHistory(): enable and disable the history.items buttons as appropriate
function showHistory() {

	if (history.cursor > 0) {
		document.all.btnPrev.className = "";
		document.all.btnPrev.disabled = false;
	} else {
		document.all.btnPrev.className = "disabled";
		document.all.btnPrev.disabled = true;
	}

	if (history.cursor < history.items.length - 1) {
		document.all.btnNext.className = "";
		document.all.btnNext.disabled = false;
	} else {
		document.all.btnNext.className = "disabled";
		document.all.btnNext.disabled = true;
	}

}
