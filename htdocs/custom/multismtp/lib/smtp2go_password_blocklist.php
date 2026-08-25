<?php
	/************************************************
	* Copyright (C) 2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	************************************************/

	/************************************************
	*	\file		./custom/multismtp/lib/smtp2go_password_blocklist.php
	*	\ingroup	multismtp
	*	\brief		List of common/predictable password bases used by multismtp_smtp2go_password_is_common()
	*				File added by InfraS (2026-08) : NIST SP 800-63B recommends checking candidate passwords
	*				against a blocklist of commonly-used/breached values rather than relying only on entropy
	*				and character-class rules (see lib/smtp2go.lib.php), which do not catch predictable bases
	*				like "Password2026!".
	************************************************/

	if (!function_exists('multismtp_smtp2go_get_password_blocklist')) {
		/**
		* Common/predictable password bases rejected by multismtp_smtp2go_password_is_common().
		* Lowercase letters only : the candidate password is leetspeak-folded and stripped down to letters
		* by the caller before being tested for containment, so entries here never include digits or symbols.
		*
		* @return	string[]	List of blocklisted password bases
		*/
		function multismtp_smtp2go_get_password_blocklist()
		{
			return array(
				// Most common breached passwords (base form, without appended digits/symbols)
				'password', 'letmein', 'welcome', 'monkey', 'dragon', 'master', 'shadow', 'superman', 'batman',
				'trustno', 'iloveyou', 'princess', 'sunshine', 'football', 'baseball', 'basketball', 'soccer',
				'whatever', 'freedom', 'ninja', 'mustang', 'access', 'flower', 'hunter', 'killer', 'jordan',
				'harley', 'ranger', 'buster', 'george', 'michael', 'jennifer', 'jessica', 'michelle', 'daniel',
				'starwars', 'pokemon', 'chelsea', 'liverpool', 'arsenal', 'united',
				// Generic/administrative bases
				'admin', 'administrator', 'root', 'login', 'guest', 'default', 'changeme', 'temp', 'temporary',
				'test', 'demo', 'user', 'hello', 'secret', 'passe', 'motdepasse', 'contrasena',
				// Keyboard walks
				'qwerty', 'qwertyuiop', 'azerty', 'azertyuiop', 'qwertz', 'asdfgh', 'asdfghjkl', 'zxcvbn', 'zxcvbnm',
				// Context-specific : product/vendor names that make an obvious guess for this exact service
				// ("smtpgo", not "smtp2go" : the "2" is stripped by the digit normalization above)
				'smtpgo', 'dolibarr', 'opendsi', 'multismtp', 'infras',
			);
		}
	}
