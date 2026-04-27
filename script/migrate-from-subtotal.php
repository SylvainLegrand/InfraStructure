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
	* along with this program.  If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	* 	\file		./infrastructure/script/migrate-from-subtotal.php
	* 	\ingroup	InfraS
	* 	\brief		Wrapper CLI / web pour exécuter la migration des données
	*				subtotal → infrastructure et, optionnellement, désactiver
	*				+ nettoyer le module subtotal.
	*
	*	Usage :
	*		- Simulation (par défaut)		: accès web (admin requis)
	*		- Exécution réelle				: ?confirm=yes
	*		- Exécution + cleanup subtotal	: ?confirm=yes&cleanup=yes
	*		- CLI							: php migrate-from-subtotal.php confirm [cleanup]
	************************************************/

	require __DIR__.'/../config.php';

	// Libraries ************************************
	dol_include_once('/infrastructure/core/lib/infrastructureMigrateSubtotal.lib.php');

	// Init *****************************************
	$isCli		= (php_sapi_name() === 'cli');
	if ($isCli) {
		$confirm	= (! empty($argv[1]) && $argv[1] === 'confirm');
		$cleanup	= (! empty($argv[2]) && $argv[2] === 'cleanup');
	} else {
		if (empty($user->admin)) {
			accessforbidden();
		}
		$confirm	= (GETPOST('confirm', 'aZ09') === 'yes');
		$cleanup	= (GETPOST('cleanup', 'aZ09') === 'yes');
	}

	$eol		= $isCli ? "\n" : "<br>\n";

	$logger	= function ($msg) use ($eol) {
		echo $msg.$eol;
		flush();
	};

	// Actions **************************************
	if (! $isCli) {
		echo '<pre>';
	}

	call_user_func($logger, 'Migration subtotal → infrastructure — entity '.((int) $conf->entity).' — mode : '.($confirm ? 'EXECUTION' : 'SIMULATION (ajouter ?confirm=yes pour exécuter)'));
	call_user_func($logger, str_repeat('-', 80));

	$res	= infrastructure_migrateFromSubtotal($db, $conf, !$confirm, $logger);

	call_user_func($logger, '');
	call_user_func($logger, str_repeat('-', 80));
	if (! $res['success']) {
		call_user_func($logger, 'ÉCHEC MIGRATION : '.count($res['errors']).' erreur(s) — rollback effectué.');
		foreach ($res['errors'] as $e) {
			call_user_func($logger, '  - '.$e);
		}
	} elseif (! $confirm) {
		call_user_func($logger, 'SIMULATION terminée sans erreur — aucune modification écrite.');
		call_user_func($logger, 'Pour exécuter réellement : ajouter ?confirm=yes (+ ?cleanup=yes pour désactiver subtotal).');
	} else {
		call_user_func($logger, 'SUCCÈS : migration appliquée.');
		if ($cleanup) {
			call_user_func($logger, '');
			call_user_func($logger, 'Nettoyage du module subtotal...');
			$resClean	= infrastructure_cleanupSubtotal($db, $conf, $logger);
			if ($resClean) {
				call_user_func($logger, 'Nettoyage terminé.');
			} else {
				call_user_func($logger, 'ÉCHEC du nettoyage.');
			}
		}
	}

	if (! $isCli) {
		echo '</pre>';
	}
