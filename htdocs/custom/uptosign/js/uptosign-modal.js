/*
 * UptoSign - opens the signature position wizard in an isolated modal.
 *
 * The wizard runs in an iframe pointing at the same page with standalone=1, which
 * renders without llxHeader: no theme, no menu, no third party CSS or JS. That
 * isolation is what keeps the drag targets and the PDF canvas where the wizard
 * expects them. This file is loaded on every page (module_parts js) so any "sign"
 * button can call uptosignOpenWizard().
 */

(function () {
	'use strict';

	var overlay = null;

	/**
	 * Remove the modal from the page.
	 */
	function closeWizard() {
		if (!overlay) {
			return;
		}
		document.removeEventListener('keydown', onKeyDown);
		if (overlay.parentNode) {
			overlay.parentNode.removeChild(overlay);
		}
		overlay = null;
		document.body.classList.remove('uptosign-modal-open');
	}

	/**
	 * Close on Escape.
	 *
	 * @param {KeyboardEvent} event key event
	 */
	function onKeyDown(event) {
		if (event.key === 'Escape' || event.key === 'Esc') {
			closeWizard();
		}
	}

	/**
	 * Open the wizard for the given URL.
	 *
	 * @param {string} url standalone wizard URL
	 */
	function uptosignOpenWizard(url) {
		if (!url || overlay) {
			return;
		}

		overlay = document.createElement('div');
		overlay.className = 'uptosign-modal-overlay';

		var frame = document.createElement('iframe');
		frame.className = 'uptosign-modal-frame';
		frame.setAttribute('src', url);
		frame.setAttribute('title', 'UptoSign');

		// Close button of the host page, not of the framed document: it stays reachable
		// whatever the iframe ends up displaying. The wizard has its own close button,
		// but a page reached from inside the frame may have none.
		var close = document.createElement('button');
		close.type = 'button';
		close.className = 'uptosign-modal-close';
		close.setAttribute('aria-label', 'Close');
		close.innerHTML = '&times;';
		close.addEventListener('click', closeWizard);

		// Clicking the backdrop, next to the frame, closes too.
		overlay.addEventListener('click', function (event) {
			if (event.target === overlay) {
				closeWizard();
			}
		});

		overlay.appendChild(close);
		overlay.appendChild(frame);
		document.body.appendChild(overlay);
		document.body.classList.add('uptosign-modal-open');
		document.addEventListener('keydown', onKeyDown);
	}

	/**
	 * Add standalone=1 to an URL that does not carry it yet.
	 *
	 * @param  {string} url source URL
	 * @return {string}     URL asking for the chrome-less wizard
	 */
	function withStandalone(url) {
		if (url.indexOf('standalone=1') !== -1) {
			return url;
		}
		return url + (url.indexOf('?') === -1 ? '?' : '&') + 'standalone=1';
	}

	// The wizard talks back when it is done: it cannot redirect the top window itself.
	window.addEventListener('message', function (event) {
		if (event.origin !== window.location.origin || !event.data) {
			return;
		}
		if (event.data.uptosign === 'close') {
			closeWizard();
		} else if (event.data.uptosign === 'done' && event.data.url) {
			closeWizard();
			window.location.href = event.data.url;
		}
	});

	// Any link flagged by the module opens the wizard instead of navigating.
	document.addEventListener('click', function (event) {
		var link = event.target.closest ? event.target.closest('a.uptosign-open-wizard') : null;
		if (!link) {
			return;
		}
		var href = link.getAttribute('href');
		if (!href || href === '#') {
			return;
		}
		event.preventDefault();
		uptosignOpenWizard(withStandalone(href));
	});

	window.uptosignOpenWizard = uptosignOpenWizard;
	window.uptosignCloseWizard = closeWizard;
})();
