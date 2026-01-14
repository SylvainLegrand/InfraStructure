<?php

/**
 * Copyright © 2015-2016 Marcos García de La Fuente <hola@marcosgdf.com>
 *
 * This file is part of Multismtp.
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
 */

class ActionsMultismtp
{

	/**
	 * Catches updateSession hook to replace SMTP configuration
	 *
	 * @return int
	 */
	public function updateSession()
	{
		global $conf;

		if (!$conf->global->MULTISMTP_SMTP_ENABLED) {
			return 0;
		}

		//We do not want to replace constants when navigating to admin/mails.php page
		if (strpos('/admin/mails.php', $_SERVER['PHP_SELF']) !== false) {
			return 0;
		}

		global $db, $user;

		require __DIR__.'/../lib/multismtp.php';

		if (!replaceConfiguration($db, $user, $conf)) {
			global $langs;

			$langs->load('multismtp@multismtp');

			setEventMessage($langs->trans('SMTPInjectionError'), 'errors');
		}

		return 1;
	}

}