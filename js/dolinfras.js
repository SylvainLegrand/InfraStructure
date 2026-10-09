/**
 * Copyright (C) 2025-2026	Fallinah Ranasolonirina	- <contact@infras.fr>	InfraS - <https://www.infras.fr>
 *
 * Detection of dark background from Dolibarr theme variable --colorbline
 * Adds class 'infras-dark-bg' on <html> when the background is dark
 */
$(document).ready(function() {
	if (window.location.pathname !== '/admin/modules.php') {
		return;
	}
	var bg = getComputedStyle(document.documentElement).getPropertyValue('--colorbline').trim();
	if (bg) {
		var rgb = null;
		if (bg.startsWith('#')) {
			var hex = bg.replace('#', '');
			if (hex.length === 3) {
				hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
			}
			rgb = [parseInt(hex.substring(0,2),16), parseInt(hex.substring(2,4),16), parseInt(hex.substring(4,6),16)];
		} else {
			var match = bg.match(/\d+/g);
			if (match && match.length >= 3) {
				rgb = [parseInt(match[0]), parseInt(match[1]), parseInt(match[2])];
			}
		}
		if (rgb) {
			// ITU-R BT.601 luminance formula
			var luminance = (0.299 * rgb[0] + 0.587 * rgb[1] + 0.114 * rgb[2]) / 255;
			if (luminance < 0.5) {
				document.documentElement.classList.add('dolinfras-dark-bg');
			}
		}
	}
});
