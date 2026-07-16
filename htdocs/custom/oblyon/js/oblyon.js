/* Copyright (C) 2026   Sylvain Legrand   <contact@infras.fr>   InfraS - <https://www.infras.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/*
 * Touch menu mode
 *
 * Oblyon dropdown sub-menus open only on CSS :hover. On a touch screen there is no
 * hover, so a tap fires a transient hover that collapses as soon as the finger lifts
 * ("se replie de facon incontrolee"). This script replaces the :hover trigger by a
 * tap-to-toggle behaviour using the .is-touch-open class (see themeoblyon/touchmenu.inc.php).
 *
 * The mode is enabled when:
 *  - the admin option OBLYON_TOUCH_MENU forces it (CSS var --oblyon-touchmenu-forced = 1), or
 *  - the device is detected as touch only ((hover: none) and (pointer: coarse), or ontouchstart).
 */
jQuery(document).ready(function () {
	'use strict';

	var $ = jQuery;
	var OPEN = 'is-touch-open';

	/*
	 * Inverted top menu (MAIN_MENU_INVERT + THEME_STICKY_TOPMENU) : dynamic content offset.
	 *
	 * The inverted top bar (#tmenu_tooltipinvert) is position:fixed and the content below
	 * (#id-left, #id-right which contains .fiche) is pushed down by a HARDCODED padding-top
	 * (40/52px) sized for a single 40px row. When there are too many entries for the available
	 * width, the bar wraps onto several lines and grows taller, but the static offset does not
	 * follow, so the top of the content is hidden behind the bar. We recompute the offset from
	 * the real bar height. Runs on every device, so it must stay BEFORE the touch early-return.
	 * The sub-menus are position:absolute (excluded from outerHeight), so hovering does not
	 * change the measured height.
	 */
	(function () {
		var $bar = $('#tmenu_tooltipinvert');
		// Only when the bar is sticky (fixed) : otherwise it is in normal flow and pushes the
		// content by itself, no offset to patch.
		if (!$bar.length || $bar.css('position') !== 'fixed') {
			return;
		}
		var $left = $('#id-left');
		var $right = $('#id-right');
		function adjustInvertOffset() {
			var h = $bar.outerHeight();
			if (!h) {
				return;
			}
			if ($left.length) {
				$left.css('padding-top', h + 'px');
			}
			if ($right.length) {
				$right.css('padding-top', (h + 12) + 'px');	// keep the original 12px gap (id-left 40 / id-right 52)
			}
		}
		adjustInvertOffset();
		$(window).on('load', adjustInvertOffset);	// fonts / FA icons can change the height after load
		var resizeTimer;
		$(window).on('resize', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(adjustInvertOffset, 150);
		});
	})();

	// Read the flags exposed by the theme CSS (touchmenu.inc.php)
	function cssFlag(name) {
		try {
			return parseInt(getComputedStyle(document.documentElement).getPropertyValue(name), 10) === 1;
		} catch (e) {
			return false;
		}
	}

	var forced = cssFlag('--oblyon-touchmenu-forced');
	var reduceHover = cssFlag('--oblyon-reduce-hover');
	var autoTouch = ('ontouchstart' in window) || (window.matchMedia && window.matchMedia('(hover: none) and (pointer: coarse)').matches);

	// Mouse device without forcing: keep the native hover behaviour, do nothing.
	if (!forced && !autoTouch) {
		return;
	}

	$('body').addClass('oblyon-touchmenu');

	// Close every open menu, except an optional one to keep open.
	function closeAll($except) {
		$('.' + OPEN).each(function () {
			if (!$except || this !== $except.get(0)) {
				$(this).removeClass(OPEN);
			}
		});
	}

	var $invertbar = $('#tmenu_tooltipinvert');

	// --- Inverted top menu (MAIN_MENU_INVERT) - li/ul structure ---
	$invertbar.on('click', '.sec-nav.is-inverted .sec-nav__item.item-heading > a.sec-nav__link', function (e) {
		var $item = $(this).closest('.sec-nav__item');
		// No sub-menu: let the link navigate normally.
		if (!$item.find('.sec-nav__sub-list').length) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();
		var willOpen = !$item.hasClass(OPEN);
		closeAll();
		if (willOpen) {
			$item.addClass(OPEN);
			$invertbar.addClass(OPEN);
		} else {
			$invertbar.removeClass(OPEN);
		}
	});

	// --- Inverted top menu - legacy div.menu_titre / div.menu_contenu structure ---
	$invertbar.on('click', 'div.menu_titre > a.vmenu', function (e) {
		var $titre = $(this).closest('div.menu_titre');
		var $contenu = $titre.next('div.menu_contenu');
		if (!$contenu.length) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();
		var willOpen = !$titre.hasClass(OPEN);
		closeAll();
		if (willOpen) {
			$titre.addClass(OPEN);
			$invertbar.addClass(OPEN);
		} else {
			$invertbar.removeClass(OPEN);
		}
	});

	// --- Reduced left menu with "hover" effect (OBLYON_REDUCE_LEFTMENU + effect hover) ---
	// First tap expands the collapsed menu (and swallows that tap to avoid an accidental
	// navigation on an icon); once expanded, links navigate normally.
	if (reduceHover) {
		$(document).on('click', '.vmenu', function (e) {
			var $vmenu = $(this);
			if (!$vmenu.hasClass(OPEN)) {
				e.preventDefault();
				e.stopPropagation();
				closeAll($vmenu);
				$vmenu.addClass(OPEN);
			}
		});
	}

	// --- Close open menus on a tap outside ---
	$(document).on('click', function (e) {
		if (!$(e.target).closest('#tmenu_tooltipinvert, .vmenu').length) {
			closeAll();
		}
	});
});
