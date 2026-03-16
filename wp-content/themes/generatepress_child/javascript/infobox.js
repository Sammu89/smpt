document.addEventListener('click', function (event) {
	var trigger = event.target.closest('[data-smpt-infobox-close]');

	if (!trigger) {
		return;
	}

	var infobox = trigger.closest('.smpt-infobox');

	if (infobox) {
		infobox.hidden = true;
	}
});
