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
 * \file    scaninvoices/lib/scaninvoices_compat.lib.php
 * \ingroup scaninvoices
 * \brief   Compatibility helpers for the Dolibarr releases predating some core functions.
 *
 * isModEnabled(), getDolGlobalString() and getDolGlobalInt() do not exist on the
 * oldest Dolibarr releases the module still supports. Declaring them here under a
 * function_exists() guard is not an option: the module files are loaded through
 * dol_include_once(), so this file can be reached before a core file that declares
 * the very same function, and PHP then aborts with a "Cannot redeclare" fatal error.
 *
 * Each helper below therefore carries the module prefix, and delegates to the core
 * function when the running Dolibarr provides it. Module code must call these
 * helpers, never the raw core ones.
 */

if (!function_exists('scaninvoicesIsModEnabled')) {
	/**
	 * Is a Dolibarr module enabled.
	 *
	 * @param  string $module Module name to check (example: 'facture', 'memcached')
	 * @return bool           True when the module is enabled
	 */
	function scaninvoicesIsModEnabled($module)
	{
		global $conf;

		if (function_exists('isModEnabled')) {
			// cast: the helper of the oldest releases answers an int
			return (bool) isModEnabled($module);
		}

		// Fix special cases, same mapping as the core helper
		$arrayconv = [
			'bank' => 'banque',
			'category' => 'categorie',
			'contract' => 'contrat',
			'project' => 'projet',
			'delivery_note' => 'expedition',
		];
		if (empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) {
			$arrayconv['supplier_order'] = 'fournisseur';
			$arrayconv['supplier_invoice'] = 'fournisseur';
		}
		if (!empty($arrayconv[$module])) {
			$module = $arrayconv[$module];
		}

		// Releases without isModEnabled() do not fill $conf->modules
		if (isset($conf->modules) && is_array($conf->modules)) {
			return !empty($conf->modules[$module]);
		}

		return !empty($conf->$module->enabled);
	}
}

if (!function_exists('scaninvoicesUserHasRight')) {
	/**
	 * Does the user hold the given permission.
	 *
	 * User::hasRight() only exists since Dolibarr 15, older releases read the
	 * permission tree directly in $user->rights. The mapping tables and the
	 * fallbacks on the historical permission names (lire, creer, supprimer) are
	 * those of the core method, so both paths answer the same thing.
	 *
	 * @param  User   $user       User to check
	 * @param  string $module     Module name (example: 'scaninvoices', 'societe')
	 * @param  string $permlevel1 First level of permission (example: 'read', 'lire')
	 * @param  string $permlevel2 Second level of permission, when the module has one
	 * @return int                Positive value when the permission is granted, 0 otherwise
	 */
	function scaninvoicesUserHasRight($user, $module, $permlevel1, $permlevel2 = '')
	{
		if (is_object($user) && method_exists($user, 'hasRight')) {
			return $user->hasRight($module, $permlevel1, $permlevel2);
		}

		if (!is_object($user)) {
			dol_syslog('scaninvoicesUserHasRight called without a user object, permission denied for ' . $module . '/' . $permlevel1, LOG_WARNING);
			return 0;
		}

		// For compatibility with bad naming permissions on module
		$moduletomoduletouse = [
			'compta' => 'comptabilite',
			'contract' => 'contrat',
			'member' => 'adherent',
			'mo' => 'mrp',
			'order' => 'commande',
			'produit' => 'product',
			'productlot' => 'produit',
			'project' => 'projet',
			'propale' => 'propal',
			'shipping' => 'expedition',
			'task' => 'task@projet',
			'fichinter' => 'ficheinter',
			'inventory' => 'stock',
			'invoice' => 'facture',
			'invoice_supplier' => 'fournisseur',
			'order_supplier' => 'fournisseur',
			'facturerec' => 'facture',
			'margins' => 'margin',
		];
		if (!empty($moduletomoduletouse[$module])) {
			$module = $moduletomoduletouse[$module];
		}

		// In $conf->modules we have 'product', in $user->rights we have 'produit'
		$moduleRightsMapping = [
			'product' => 'produit',
			'margin' => 'margins',
			'comptabilite' => 'compta',
		];
		$rightsPath = $module;
		if (!empty($moduleRightsMapping[$rightsPath])) {
			$rightsPath = $moduleRightsMapping[$rightsPath];
		}

		// If module is abc@module, permission is user->rights->module->abc->permlevel1
		$tmp = explode('@', $rightsPath, 2);
		if (!empty($tmp[1])) {
			if (strpos($module, '@') !== false) {
				$module = $tmp[1];
			}
			$rightsPath = $tmp[1];
			$permlevel2 = $permlevel1;
			$permlevel1 = $tmp[0];
		}

		if (!scaninvoicesIsModEnabled($module)) {
			return 0;
		}

		// For compatibility with bad naming permissions on permlevel1
		if ($permlevel1 == 'propale') {
			$permlevel1 = 'propal';
		}
		if ($permlevel1 == 'member') {
			$permlevel1 = 'adherent';
		}
		if ($permlevel1 == 'recruitmentcandidature') {
			$permlevel1 = 'recruitmentjobposition';
		}

		if (empty($rightsPath) || empty($user->rights) || empty($user->rights->$rightsPath) || empty($permlevel1)) {
			return 0;
		}

		// Historical permission names, kept as fallback like the core method does
		$oldnames = [
			'read' => ['lire'],
			'write' => ['creer', 'create'],
			'delete' => ['supprimer'],
		];

		if ($permlevel2) {
			if (!empty($user->rights->$rightsPath->$permlevel1)) {
				if (!empty($user->rights->$rightsPath->$permlevel1->$permlevel2)) {
					return $user->rights->$rightsPath->$permlevel1->$permlevel2;
				}
				if (!empty($oldnames[$permlevel2])) {
					foreach ($oldnames[$permlevel2] as $oldname) {
						if (!empty($user->rights->$rightsPath->$permlevel1->$oldname)) {
							return $user->rights->$rightsPath->$permlevel1->$oldname;
						}
					}
				}
			}

			return 0;
		}

		if (!empty($user->rights->$rightsPath->$permlevel1)) {
			return $user->rights->$rightsPath->$permlevel1;
		}
		if (!empty($oldnames[$permlevel1])) {
			foreach ($oldnames[$permlevel1] as $oldname) {
				if (!empty($user->rights->$rightsPath->$oldname)) {
					return $user->rights->$rightsPath->$oldname;
				}
			}
		}

		return 0;
	}
}

if (!function_exists('scaninvoicesGetDolGlobalString')) {
	/**
	 * Return a Dolibarr global constant as a string.
	 *
	 * @param  string $key     Key of the constant to read in $conf->global
	 * @param  string $default Value returned when the constant is not set
	 * @return string          Value of the constant, or $default
	 */
	function scaninvoicesGetDolGlobalString($key, $default = '')
	{
		global $conf;

		// The core helper only accepts a $default argument since Dolibarr 15,
		// so older releases are served by reading $conf->global directly.
		if (function_exists('getDolGlobalString') && ((int) DOL_VERSION) >= 15) {
			return getDolGlobalString($key, $default);
		}

		return (string) (isset($conf->global->$key) ? $conf->global->$key : $default);
	}
}

if (!function_exists('scaninvoicesGetDolGlobalInt')) {
	/**
	 * Return a Dolibarr global constant as an integer.
	 *
	 * @param  string $key     Key of the constant to read in $conf->global
	 * @param  int    $default Value returned when the constant is not set
	 * @return int             Value of the constant, or $default
	 */
	function scaninvoicesGetDolGlobalInt($key, $default = 0)
	{
		global $conf;

		// The core helper only accepts a $default argument since Dolibarr 15,
		// so older releases are served by reading $conf->global directly.
		if (function_exists('getDolGlobalInt') && ((int) DOL_VERSION) >= 15) {
			return getDolGlobalInt($key, $default);
		}

		return (int) (isset($conf->global->$key) ? $conf->global->$key : $default);
	}
}
