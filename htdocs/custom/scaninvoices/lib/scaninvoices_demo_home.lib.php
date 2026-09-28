<?php
/* Copyright (C) 2026 Éric Seigne <eric.seigne@cap-rel.fr>
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

/**
 * \file    scaninvoices/lib/scaninvoices_demo_home.lib.php
 * \ingroup scaninvoices
 * \brief   Demonstration mode: the flag, and the decision to divert the home page.
 *
 * Kept apart from scaninvoices_demo.lib.php, and deliberately small: the hook
 * that reads it runs on every single page of Dolibarr, and the generator pulls
 * Societe, Product, FactureFournisseur and the classes of the module with it.
 * Nothing here loads anything but the compatibility wrappers.
 */

dol_include_once('/scaninvoices/lib/scaninvoices_compat.lib.php');

/** Constant raised while a demonstration data set is installed. */
if (!defined('SCANINVOICES_DEMO_ACTIVE')) {
	define('SCANINVOICES_DEMO_ACTIVE', 'SCANINVOICES_DEMO_ACTIVE');
}

/**
 * Where the previous MAIN_LANDING_PAGE is parked while the demonstration runs,
 * so the purge can put back exactly what the administrator had configured.
 */
if (!defined('SCANINVOICES_DEMO_LANDING_BACKUP')) {
	define('SCANINVOICES_DEMO_LANDING_BACKUP', 'SCANINVOICES_DEMO_LANDING_BACKUP');
}

/** Page the demonstration mode lands on. */
if (!defined('SCANINVOICES_DEMO_LANDING_PAGE')) {
	define('SCANINVOICES_DEMO_LANDING_PAGE', '/scaninvoices/index.php');
}

/**
 * Query parameter switching the diversion off for the rest of the session.
 * Without it, an administrator who just started the demonstration could no
 * longer reach the dashboard, nor the setup pages behind it.
 */
if (!defined('SCANINVOICES_NODEMO_PARAM')) {
	define('SCANINVOICES_NODEMO_PARAM', 'nodemo');
}

/**
 * Is a demonstration data set currently installed?
 *
 * @return	bool	True while the demonstration mode is on
 */
function scaninvoicesDemoIsActive()
{
	return scaninvoicesGetDolGlobalString(SCANINVOICES_DEMO_ACTIVE) ? true : false;
}

/**
 * Decide whether the Dolibarr home page must be swapped for the welcome page of
 * the module, and remember the opt-out in session.
 *
 * A plain function rather than the body of the hook, so the decision is tested
 * without simulating a whole HTTP cycle.
 *
 * @param	string		$phpSelf	Value of $_SERVER['PHP_SELF'] for the current page
 * @param	int|null	$nodemo		Value of the opt-out parameter, null when absent
 * @param	User|null	$user		Current user, to honour their own landing page
 * @return	string					URL to redirect to, empty string to stay put
 */
function scaninvoicesDemoHomeRedirect($phpSelf, $nodemo, $user = null)
{
	// Sticky opt-out: one visit with ?nodemo=1 and the administrator keeps the
	// real dashboard for the rest of the session.
	if ($nodemo !== null) {
		$_SESSION['scaninvoices_nodemo'] = ((int) $nodemo) ? 1 : 0;
	}
	if (!empty($_SESSION['scaninvoices_nodemo'])) {
		return '';
	}

	if (!scaninvoicesDemoIsActive()) {
		return '';
	}

	// Only the home page of Dolibarr is diverted. Everything else, starting
	// with our own page, is left alone: that is the anti-loop.
	$root = preg_replace('#/+$#', '', DOL_URL_ROOT);
	if ($phpSelf !== $root . '/index.php') {
		return '';
	}

	// A user who set their own landing page decided where they arrive.
	if (!empty($user) && !empty($user->conf->MAIN_LANDING_PAGE)) {
		return '';
	}

	return dol_buildpath(SCANINVOICES_DEMO_LANDING_PAGE, 1);
}

/**
 * Turn the demonstration mode on and point the landing page at the module.
 *
 * @param	DoliDB	$db		Database handler
 * @param	int		$entity	Entity the constants are written for
 * @return	int				Return integer <0 if KO, >0 if OK
 */
function scaninvoicesDemoActivate($db, $entity)
{
	$current = scaninvoicesGetDolGlobalString('MAIN_LANDING_PAGE');

	// The backup is taken at the first activation only: a second one would park
	// our own page over itself, and the restore would then give back nothing.
	if (!scaninvoicesDemoIsActive()) {
		$backup = ($current === SCANINVOICES_DEMO_LANDING_PAGE) ? '' : $current;
		if (dolibarr_set_const($db, SCANINVOICES_DEMO_LANDING_BACKUP, $backup, 'chaine', 0, '', $entity) < 0) {
			dol_syslog('ScanInvoices demo: cannot park MAIN_LANDING_PAGE, the demonstration mode stays off', LOG_ERR);

			return -1;
		}
	}

	if (dolibarr_set_const($db, 'MAIN_LANDING_PAGE', SCANINVOICES_DEMO_LANDING_PAGE, 'chaine', 0, '', $entity) < 0) {
		dol_syslog('ScanInvoices demo: cannot set MAIN_LANDING_PAGE', LOG_ERR);

		return -1;
	}

	if (dolibarr_set_const($db, SCANINVOICES_DEMO_ACTIVE, '1', 'chaine', 0, '', $entity) < 0) {
		dol_syslog('ScanInvoices demo: cannot raise the demonstration flag', LOG_ERR);

		return -1;
	}

	return 1;
}

/**
 * Turn the demonstration mode off and restore the landing page as it was.
 *
 * @param	DoliDB	$db		Database handler
 * @param	int		$entity	Entity the constants were written for
 * @return	int				Return integer <0 if KO, >0 if OK
 */
function scaninvoicesDemoDeactivate($db, $entity)
{
	$error = 0;
	$backup = scaninvoicesGetDolGlobalString(SCANINVOICES_DEMO_LANDING_BACKUP);

	if ($backup !== '') {
		if (dolibarr_set_const($db, 'MAIN_LANDING_PAGE', $backup, 'chaine', 0, '', $entity) < 0) {
			dol_syslog('ScanInvoices demo: cannot restore MAIN_LANDING_PAGE to ' . $backup, LOG_ERR);
			$error++;
		}
	} elseif (scaninvoicesGetDolGlobalString('MAIN_LANDING_PAGE') === SCANINVOICES_DEMO_LANDING_PAGE) {
		// An empty backup means there was none before: remove ours rather than
		// leaving the module page behind on an instance that goes back to work.
		if (dolibarr_del_const($db, 'MAIN_LANDING_PAGE', $entity) < 0) {
			dol_syslog('ScanInvoices demo: cannot remove MAIN_LANDING_PAGE', LOG_ERR);
			$error++;
		}
	}

	if (dolibarr_del_const($db, SCANINVOICES_DEMO_LANDING_BACKUP, $entity) < 0) {
		dol_syslog('ScanInvoices demo: cannot remove the landing page backup', LOG_ERR);
		$error++;
	}
	if (dolibarr_del_const($db, SCANINVOICES_DEMO_ACTIVE, $entity) < 0) {
		dol_syslog('ScanInvoices demo: cannot lower the demonstration flag', LOG_ERR);
		$error++;
	}

	return $error > 0 ? -1 : 1;
}
