/************************************************
* Copyright (C) 2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
*
* This file is part of Multismtp.
* File added by InfraS (2026-08) : SMTP2GO API helper functions.
*
* Multismtp is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Multismtp is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Multismtp.  If not, see <http://www.gnu.org/licenses/>.
************************************************/

/************************************************
*	\file		./custom/multismtp/js/smtp2go_password_strength.js
*	\ingroup	Multismtp
*	\brief		Functions used by Multismtp module
************************************************/
(function () {
	'use strict';

	// InfraS change : read from the config printed by admin/smtp2go.php instead of a hardcoded duplicate, so PHP and JS can never drift apart
	var config      = (typeof Smtp2goPasswordConfig !== 'undefined') ? Smtp2goPasswordConfig : {};
	var MIN_BITS    = config.minBits || 64;
	var MIN_CLASSES = config.minClasses || 3;

	function Translate(key) {
		var str = (typeof Smtp2goPasswordLangs !== 'undefined' && Smtp2goPasswordLangs[key]) ? Smtp2goPasswordLangs[key] : key;
		for (var i = 1; i < arguments.length; i++) {
			str = str.replace('%s', arguments[i]);
		}
		return str;
	}
	function mixColor(c1, c2, t) {
		return [
			Math.round(c1[0] + (c2[0] - c1[0]) * t),
			Math.round(c1[1] + (c2[1] - c1[1]) * t),
			Math.round(c1[2] + (c2[2] - c1[2]) * t)
		];
	}

	function bitsToColor(bits) {
		var ratio  = Math.max(0, Math.min(1, bits / MIN_BITS));
		var red    = [0xc6, 0x28, 0x28]; // faible
		var yellow = [0xcc, 0xbe, 0x00]; // moyen
		var green  = [0x2e, 0x7d, 0x32]; // robuste
		var rgb    = (ratio < 0.5) ? mixColor(red, yellow, ratio / 0.5) : mixColor(yellow, green, (ratio - 0.5) / 0.5);
		return 'rgb(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ')';
	}
	function distinctCount(str) {
		return new Set(str.split('')).size;
	}
	function estimateBits(pwd) {
		if (!pwd.length) {
			return 0;
		}
		var d = Math.max(distinctCount(pwd), 2); // avoid log2(1) = 0 for a repeated-char string
		return pwd.length * (Math.log(d) / Math.log(2));
	}
	function classesPresent(pwd) {
		return {
			lower: /[a-z]/.test(pwd),
			upper: /[A-Z]/.test(pwd),
			digit: /[0-9]/.test(pwd),
			symbol: /[^a-zA-Z0-9]/.test(pwd)
		};
	}
	// SMTP2GO never returns a self-generated password back through the API, so leaving the
	// field empty (letting SMTP2GO generate one) can no longer be mirrored/saved locally ; offer a client-side
	// generator instead, built to always satisfy MIN_BITS / MIN_CLASSES.
	function secureRandomInt(max) {
		var range = Math.floor(0x100000000 / max) * max;
		var buf   = new Uint32Array(1);
		var val;
		do {
			window.crypto.getRandomValues(buf);
			val = buf[0];
		} while (val >= range);
		return val % max;
	}
	function generatePassword() {
		var length  = 20; // well above what MIN_BITS=64 typically needs with a full charset, comfortable margin
		var classes = ['abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', '0123456789', '!@#$%^&*()-_=+[]{}'];
		var all     = classes.join('');
		var chars   = [];
		classes.forEach(function (set) {
			chars.push(set.charAt(secureRandomInt(set.length))); // guarantee at least one of each of the 4 classes
		});
		while (chars.length < length) {
			chars.push(all.charAt(secureRandomInt(all.length)));
		}
		for (var i = chars.length - 1; i > 0; i--) { // Fisher-Yates shuffle, same secure RNG
			var j       = secureRandomInt(i + 1);
			var tmp     = chars[i];
			chars[i]    = chars[j];
			chars[j]    = tmp;
		}
		return chars.join('');
	}
	// InfraS add end
	function buildFeedbackEl() {
		var el                  = document.createElement('div');
		el.className            = 'smtp2go-pwd-feedback';
		el.style.marginTop      = '4px';
		el.style.marginBottom   = '6px';
		el.style.fontSize       = '0.85em';
		el.style.maxWidth       = '420px';
		el.style.display        = 'none';
		return el;
	}
	function renderFeedback(el, pwd) {
		if (!pwd.length) {
			// Empty is allowed here (e.g. the Edit form: leaving it blank means "keep the current password"). The separate risk of leaving it blank on CREATION (SMTP2GO
			el.style.display = 'none';
			el.dataset.valid = '1';
			return;
		}
		el.style.display = 'block';

		var bits        = estimateBits(pwd);
		var classes     = classesPresent(pwd);
		var classCount  = (classes.lower ? 1 : 0) + (classes.upper ? 1 : 0) + (classes.digit ? 1 : 0) + (classes.symbol ? 1 : 0);	// InfraS add
		var missing     = [];
		if (!classes.lower) {
			missing.push(Translate('Smtp2goPasswordMissLower'));
		}
		if (!classes.upper) {
			missing.push(Translate('Smtp2goPasswordMissUpper'));
		}
		if (!classes.digit) {
			missing.push(Translate('Smtp2goPasswordMissDigit'));
		}
		if (!classes.symbol) {
			missing.push(Translate('Smtp2goPasswordMissSpecial'));
		}

		var ok = bits >= MIN_BITS && classCount >= MIN_CLASSES;	// also require a minimum character-class diversity, not just entropy
		el.dataset.valid = ok ? '1' : '0';
		var color   = bitsToColor(bits);
		var barPct  = Math.max(2, Math.min(100, Math.round((bits / MIN_BITS) * 100)));
		var html    = '';
		html += '<div style="background:#e0e0e0;border-radius:3px;height:6px;overflow:hidden;margin-bottom:3px;">'
			 + '	<div style="height:100%;width:' + barPct + '%;background:' + color + ';transition:background-color .25s ease,width .25s ease;"></div></div>';
		html += '<span style="color:' + color + ';font-weight:600;transition:color .25s ease;">' + Math.round(bits) + ' / ' + MIN_BITS + ' ' + Translate('Smtp2goPasswordBitsLabel') + '</span>';
		if (ok) {
			html += ' — <span style="color:#2e7d32;">' + Translate('Smtp2goPasswordStrongEnough') + ' ✓</span>';
		} else {
			var tips = [];
			if (missing.length) {
				tips.push(Translate('Smtp2goPasswordAddHint', missing.join(', ')));
			}
			// Rough hint on how much longer the password should be, based on the character
			// diversity used so far (approximate - actual requirement depends on what you add).
			var d               = Math.max(distinctCount(pwd), 2);
			var neededLength    = Math.ceil(MIN_BITS / (Math.log(d) / Math.log(2)));
			var extra           = Math.max(0, neededLength - pwd.length);
			if (extra > 0) {
				tips.push(Translate('Smtp2goPasswordCharsNeeded', extra));
			}
			html += ' — <span style="color:' + color + ';">' + Translate('Smtp2goPasswordTooWeak') + (tips.length ? (', ' + tips.join(' ' + Translate('Smtp2goPasswordAnd') + ' ')) : '') + '</span>';
		}

		el.innerHTML = html;
	}
	function attach(input) {
		if (input.dataset.smtp2goPwdAttached) {
			return;
		}
		input.dataset.smtp2goPwdAttached = '1';

		// "Generate" button, inserted right after the input (and its show/hide eye span if any)
		var generateBtn                  = document.createElement('span');
		generateBtn.className            = 'fa fa-refresh paddingleft paddingright';
		generateBtn.style.cursor         = 'pointer';
		generateBtn.title                = Translate('Smtp2goPasswordGenerate');
		generateBtn.addEventListener('click', function () {
			input.value = generatePassword();
			input.type  = 'text'; // reveal it immediately : the admin did not type it and cannot recall it from memory
			input.dispatchEvent(new Event('input'));
			input.focus();
		});
		input.insertAdjacentElement('afterend', generateBtn);

		var feedback = buildFeedbackEl();
		generateBtn.insertAdjacentElement('afterend', feedback);	// keep it right after the Generate button, not sandwiched between input and button
		renderFeedback(feedback, input.value);
		input.addEventListener('input', function () {
			renderFeedback(feedback, input.value);
		});
		var form = input.closest('form');
		if (form) {
			form.addEventListener('submit', function (evt) {
				if (input.value.length && feedback.dataset.valid !== '1') {
					evt.preventDefault();
					input.focus();
					if (typeof input.reportValidity === 'function') {
						input.setCustomValidity(Translate('Smtp2goPasswordWeakForSmtp2go', MIN_BITS));
						input.reportValidity();
						input.setCustomValidity(''); // clear it so a later valid resubmit isn't blocked by a stale message
					}
				}
			});
		}
	}
	function init() {
		document.querySelectorAll('input[name="smtp2go_password"]').forEach(attach);
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();