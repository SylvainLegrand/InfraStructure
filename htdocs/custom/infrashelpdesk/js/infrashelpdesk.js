	/**
 	* Copyright (C) 2026	Fallinah Ranasolonirina	- <contact@infras.fr>	InfraS - <https://www.infras.fr>
	*
	* This program is free software: you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation, either version 3 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program.  If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	* 	\file		../infrashelpdesk/js/infrashelpdesk.js
	* 	\ingroup	InfraS
	* 	\brief		JS Functions used by InfraS module
	************************************************/
		
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
					document.documentElement.classList.add('infrashelpdesk-dark-bg');
				}
			}
		}
	});

	/**
	* FLOATING CONTEXT BUTTON
	*
	* Builds a floating button (all pages) opening a panel that shows the current
	* page hook contexts and the external modules interacting with them.
	* Configuration (contexts, modules, translations) is injected in the page footer
	* by Actionsinfrashelpdesk::printCommonFooter() as `infrashelpdeskCtxConf`.
	*/
	$(document).ready(function () {
		if (typeof window.infrashelpdeskCtxConf === 'undefined') {
			return;
		}
		var conf	= window.infrashelpdeskCtxConf;
		// Panel *****************************************
		var panel	= document.createElement('div');
		panel.id	= 'infrashelpdesk-ctx-panel';
		var panelTitle	= document.createElement('h6');
		panelTitle.id	= 'infrashelpdesk-ctx-title';
		panelTitle.textContent	= conf.langs.title;
		panel.appendChild(panelTitle);

		function addSectionTitle(text) {
			var sectionTitle			= document.createElement('div');
			sectionTitle.className		= 'infrashelpdesk-ctx-section';
			sectionTitle.textContent	= text;
			panel.appendChild(sectionTitle);
		}
		// Current page & contexts of the current page (dev mode only)
		if (conf.devmode) {
			addSectionTitle(conf.langs.page);
			var pageValue			= document.createElement('div');
			pageValue.className		= 'infrashelpdesk-ctx-page';
			pageValue.textContent	= conf.page;
			panel.appendChild(pageValue);

			addSectionTitle(conf.langs.contexts);
			var ctxWrap			= document.createElement('div');
			ctxWrap.className	= 'infrashelpdesk-ctx-chips';
			conf.contexts.forEach(function (context) {
				var chip		= document.createElement('span');
				chip.className	= 'infrashelpdesk-ctx-chip';
				chip.textContent	= context;
				ctxWrap.appendChild(chip);
			});
			panel.appendChild(ctxWrap);

			// Hook functions used by the current page (direct executeHooks + dictionary hooks column),
			// with the external modules implementing each of them on this page
			if (Array.isArray(conf.functionHooks) && conf.functionHooks.length > 0) {
				addSectionTitle(conf.langs.functions);
				var fnWrap			= document.createElement('div');
				fnWrap.className	= 'infrashelpdesk-func-chips';
				conf.functionHooks.forEach(function (entry) {
					var name		= typeof entry === 'string' ? entry : (entry && entry.name ? entry.name : '');
					if (!name) {
						return;
					}
					var mods		= (entry && Array.isArray(entry.modules)) ? entry.modules : [];
					var item		= document.createElement('div');
					item.className	= 'infrashelpdesk-func-item';
					var chip		= document.createElement('span');
					chip.className	= 'infrashelpdesk-func-chip';
					chip.textContent= name;
					chip.title		= name;
					item.appendChild(chip);
					if (mods.length) {
						var modsEl			= document.createElement('span');
						modsEl.className	= 'infrashelpdesk-func-modules';
						modsEl.textContent	= ' : ' + '[' + mods.join(', ') + ']';
						modsEl.title		= mods.join(', ');
						item.appendChild(modsEl);
					}
					fnWrap.appendChild(item);
				});
				panel.appendChild(fnWrap);
			}
		}

		// Wiki dialogs cache: one dialog per URL, hidden on close and reused on reopen
		var wikiDialogs	= {};
		//Opens the wiki page in a modal jQuery UI dialog embedding an iframe.
		function openWikiDialog(url, title) {
			if (wikiDialogs[url]) {	// already built: reopen instantly, size refreshed to the current window
				wikiDialogs[url].dialog('option', {
					width: Math.min(1000, Math.floor($(window).width() * 0.9)),
					height: Math.floor($(window).height() * 0.85)
				}).dialog('open');
				return;
			}
			var container		= document.createElement('div');
			container.className = 'infrashelpdesk-wiki-dialog';
			var loader			= document.createElement('div');
			loader.className	= 'infrashelpdesk-wiki-loader';
			loader.innerHTML	= '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>';
			container.appendChild(loader);
			var iframe			= document.createElement('iframe');
			iframe.src			= url;
			iframe.setAttribute('frameborder', '0');
			iframe.addEventListener('load', function () {	// hide the spinner once the wiki page is rendered
				loader.style.display = 'none';
			});
			container.appendChild(iframe);
			wikiDialogs[url] = $(container).dialog({
									modal: true,
									title: title,
									dialogClass: 'infrashelpdesk-wiki-dialog-wrap',
									width: Math.min(1000, Math.floor($(window).width() * 0.9)),
									height: Math.floor($(window).height() * 0.85)
								});
		}

		// Wiki page URLs matching the current page (page-based lookup done server side)
		var wikiEntries = Array.isArray(conf.wiki) ? conf.wiki.filter(function (entry) {
			return entry && entry.url;
		}) : [];
		if (wikiEntries.length > 0) {
			// Preconnect to the wiki origin so DNS + TCP + TLS are already established when a link is clicked
			try {
				var wikiOrigin = new URL(wikiEntries[0].url, window.location.href).origin;
				if (wikiOrigin && wikiOrigin !== window.location.origin && !document.querySelector('link[rel="preconnect"][href="' + wikiOrigin + '"]')) {
					var preconnect	= document.createElement('link');
					preconnect.rel	= 'preconnect';
					preconnect.href	= wikiOrigin;
					document.head.appendChild(preconnect);
				}
			} catch (e) {}
			addSectionTitle(conf.langs.wiki);
			var seenWikiUrls= [];
			wikiEntries.forEach(function (entry) {
				var url		= entry.url;
				var label	= entry.label || url;
				if (seenWikiUrls.indexOf(url) !== -1) {	// several rows of the dictionary can share the same wiki page
					return;
				}
				seenWikiUrls.push(url);
				var link		= document.createElement('a');
				link.className	= 'infrashelpdesk-ctx-wikilink';
				link.href		= url;
				link.textContent= label;
				link.addEventListener('click', function (e) {
					e.preventDefault();
					openWikiDialog(url, label);
				});
				panel.appendChild(link);
			});
		}

		// External modules interacting with these contexts
		addSectionTitle(conf.langs.modules);
		if (conf.modules.length === 0) {
			var noModule		= document.createElement('div');
			noModule.className	= 'infrashelpdesk-ctx-nomodule';
			noModule.textContent= conf.langs.nomodule;
			panel.appendChild(noModule);
		} else {
			conf.modules.forEach(function (module) {
				var row			= document.createElement('div');
				row.className	= 'infrashelpdesk-ctx-module';
				var head		= document.createElement('div');
				var name;
				if (module.url) {	// opens the module wiki documentation in the modal dialog
					name		= document.createElement('a');
					name.href	= module.url;
					name.title	= conf.langs.wiki;
					name.addEventListener('click', function (e) {
						e.preventDefault();
						openWikiDialog(module.url, module.name);
					});
				} else {
					name		= document.createElement('span');
				}
				name.className	= 'infrashelpdesk-ctx-module-name';
				name.textContent= module.name;
				head.appendChild(name);
				row.appendChild(head);
				panel.appendChild(row);
			});
		}

		// Floating button *******************************
		var floatingWrap	= document.createElement('div');
		floatingWrap.id		= 'infrashelpdesk-ctx-floating';
		var toggleBtn		= document.createElement('button');
		toggleBtn.type		= 'button';
		toggleBtn.id		= 'infrashelpdesk-ctx-toggle';
		toggleBtn.setAttribute('title', conf.langs.title);
		toggleBtn.innerHTML	= '<i class="fa fa-lightbulb" aria-hidden="true"></i>';
		toggleBtn.addEventListener('click', function (e) {
			e.stopPropagation();
			if (!hasDragged) {
				floatingWrap.classList.toggle('--open');
			}
		});
		document.addEventListener('click', function (e) {
			if (!floatingWrap.contains(e.target)) {
				floatingWrap.classList.remove('--open');
			}
		});
		floatingWrap.appendChild(toggleBtn);
		floatingWrap.appendChild(panel);
		document.body.appendChild(floatingWrap);
		// Draggable behavior (position persisted in localStorage)
		var isDragging	= false;
		var hasDragged	= false;
		var dragStartX, dragStartY, elemStartX, elemStartY;
		var DRAG_THRESHOLD	= 5;
		var STORAGE_KEY		= 'infrashelpdesk_ctx_pos';
		// Restore saved position
		try {
			var savedPos = JSON.parse(localStorage.getItem(STORAGE_KEY));
			if (savedPos && typeof savedPos.left === 'number' && typeof savedPos.top === 'number') {
				var maxLeft	= window.innerWidth - floatingWrap.offsetWidth;
				var maxTop	= window.innerHeight - floatingWrap.offsetHeight;
				floatingWrap.style.right	= 'auto';
				floatingWrap.style.bottom	= 'auto';
				floatingWrap.style.left		= Math.min(Math.max(0, savedPos.left), maxLeft) + 'px';
				floatingWrap.style.top		= Math.min(Math.max(0, savedPos.top), maxTop) + 'px';
			}
		} catch (e) {}
		function startDrag(clientX, clientY) {
			var rect	= floatingWrap.getBoundingClientRect();
			elemStartX	= rect.left;
			elemStartY	= rect.top;
			dragStartX	= clientX;
			dragStartY	= clientY;
			isDragging	= true;
			hasDragged	= false;
			floatingWrap.style.transition	= 'none';
			floatingWrap.style.right		= 'auto';
			floatingWrap.style.bottom		= 'auto';
			floatingWrap.style.left			= elemStartX + 'px';
			floatingWrap.style.top			= elemStartY + 'px';
			floatingWrap.classList.add('--dragging');
		}

		function moveDrag(clientX, clientY) {
			if (!isDragging) { return; }
			var dx = clientX - dragStartX;
			var dy = clientY - dragStartY;
			if (!hasDragged && (Math.abs(dx) > DRAG_THRESHOLD || Math.abs(dy) > DRAG_THRESHOLD)) {
				hasDragged = true;
				floatingWrap.classList.remove('--open');
			}
			if (hasDragged) {
				var newLeft = Math.max(0, Math.min(window.innerWidth - floatingWrap.offsetWidth, elemStartX + dx));
				var newTop	= Math.max(0, Math.min(window.innerHeight - floatingWrap.offsetHeight, elemStartY + dy));
				floatingWrap.style.left = newLeft + 'px';
				floatingWrap.style.top = newTop + 'px';
			}
		}
		function endDrag() {
			if (!isDragging) { return; }
			isDragging = false;
			floatingWrap.style.transition = '';
			floatingWrap.classList.remove('--dragging');
			if (hasDragged) {
				try {
					localStorage.setItem(STORAGE_KEY, JSON.stringify({
						left: parseFloat(floatingWrap.style.left),
						top: parseFloat(floatingWrap.style.top)
					}));
				} catch (e) {}
			}
		}
		toggleBtn.addEventListener('mousedown', function (e) {
			if (e.button !== 0) { return; }
			startDrag(e.clientX, e.clientY);
		});
		document.addEventListener('mousemove', function (e) {
			moveDrag(e.clientX, e.clientY);
		});
		document.addEventListener('mouseup', function () {
			endDrag();
		});
		toggleBtn.addEventListener('touchstart', function (e) {
			var t = e.touches[0];
			startDrag(t.clientX, t.clientY);
		}, { passive: true });
		document.addEventListener('touchmove', function (e) {
			if (!isDragging) { return; }
			var t = e.touches[0];
			moveDrag(t.clientX, t.clientY);
		}, { passive: false });
		document.addEventListener('touchend', function () {
			endDrag();
		});
	});
