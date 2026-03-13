(function () {
	'use strict';

	var iframe = document.querySelector('.tv iframe');
	if (!iframe) return;

	// Extract episode number from the #titulo element when it changes.
	var titulo = document.getElementById('titulo');
	var tracked = new Set();

	// Observe iframe src changes to detect episode plays.
	var observer = new MutationObserver(function (mutations) {
		mutations.forEach(function (m) {
			if (m.type === 'attributes' && m.attributeName === 'src') {
				var src = iframe.getAttribute('src');
				if (!src) return;

				// Get episode number from titulo element.
				var epText = titulo ? titulo.textContent : '';
				var match = epText.match(/\d+/);
				var epNum = match ? match[0] : 'unknown';
				var itemId = 'nostalgia_ep_' + epNum.padStart(3, '0');

				if (!tracked.has(itemId) && typeof window.smptTrack === 'function') {
					tracked.add(itemId);
					window.smptTrack('nostalgia_play', itemId);
				}
			}
		});
	});

	observer.observe(iframe, { attributes: true, attributeFilter: ['src'] });
})();
