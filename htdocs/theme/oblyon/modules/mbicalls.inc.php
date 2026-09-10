/* InfraS add : fichier ajouté par InfraS (2026-07) */
/* Copyright (C) 2026  Sylvain Legrand  InfraS - <contact@infras.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/**
 * \file		htdocs/theme/oblyon/modules/mbicalls.inc.php
 * \ingroup		oblyon
 * \brief		Lisibilité du dropdown historique d'appels MBI Calls sous le thème Oblyon (mode sombre)
 */
<?php if (! defined('ISLOADEDBYSTEELSHEET')) die('Must be call by steelsheet'); ?>
/* <style type="text/css" > */

<?php if (isModEnabled('mbicalls')) {
	$mbiRow    = getDolGlobalString('OBLYON_COLOR_BLINE', '#2d2d2d');
	$mbiHover  = getDolGlobalString('THEME_ELDY_USE_HOVER', getDolGlobalString('OBLYON_COLOR_BLINE_HOVER', '#4d4d4d'));
	$mbiText   = getDolGlobalString('OBLYON_COLOR_FLINE', '#f3f2f4');
	$mbiInput  = getDolGlobalString('OBLYON_COLOR_INPUT_BCKGRD', '#404040');
	$mbiLink   = '#9db8e6';
?>

/* --- Bouton téléphone de la barre du haut : centré sur la hauteur du bandeau ---
   Oblyon fixe une hauteur au bloc (.login_block_elem : 54 px, ou 40 px en menu
   inversé) et centrait son contenu par le line-height hérité. Le bloc du module est
   passé en conteneur flex centré (style.css) ; on neutralise ici le line-height du
   bandeau, qui sinon ajoute un décalage vertical au-dessus de l'icône. */
#topmenu-calls-dropdown,
#topmenu-calls-dropdown .calls-dropdown-a {
	line-height: 1 !important;
}
.login_block_other .mbicalls-topbar-block {
<?php if (getDolGlobalString('MAIN_MENU_INVERT')) { ?>
	height: 40px;
<?php } else { ?>
	height: 54px;
<?php } ?>
}

/* --- Lignes du tableau : fond sombre lisible, texte clair (palette Oblyon) --- */
#topmenu-calls-dropdown .mbicalls-dropdown-table tr {
	background: <?php echo $mbiRow; ?> !important;
	box-shadow: none !important;
}
#topmenu-calls-dropdown .mbicalls-dropdown-table tr:hover {
	background: <?php echo $mbiHover; ?> !important;
}
#topmenu-calls-dropdown .mbicalls-dropdown-table td,
#topmenu-calls-dropdown .mbicalls-dropdown-table td:first-child {
	color: <?php echo $mbiText; ?> !important;
}

/* --- Liens : numéros (click-to-dial) et tiers, lisibles sur fond sombre --- */
/* font-size forcé : le thème Oblyon agrandit les liens du dropdown, le numéro paraissait trop gros */
#topmenu-calls-dropdown .mbicalls-dropdown-table a.mbicalls-clicktodial {
	color: <?php echo $mbiText; ?> !important;
	font-size: 10px !important;
	font-weight: 600 !important;
}
#topmenu-calls-dropdown .mbicalls-dropdown-table a {
	color: <?php echo $mbiLink; ?> !important;
	font-size: 10px !important;
}

/* --- Sélecteur « personne » accordé au thème sombre --- */
#topmenu-calls-dropdown .mbicalls-filter-select {
	background: <?php echo $mbiInput; ?> !important;
	border-color: rgba(255, 255, 255, 0.16) !important;
	color: <?php echo $mbiText; ?> !important;
}

/* --- Pills de filtre d'état inactives : sombres ; l'état actif garde sa couleur de statut (style.css) --- */
#topmenu-calls-dropdown .mbicalls-state-pill {
	background: <?php echo $mbiInput; ?> !important;
	border-color: rgba(255, 255, 255, 0.14) !important;
	color: <?php echo $mbiText; ?> !important;
}
#topmenu-calls-dropdown .mbicalls-state-pill:hover {
	background: <?php echo $mbiHover; ?> !important;
}
#topmenu-calls-dropdown .mbicalls-state-pill.on {
	color: #fff !important;
}

<?php } ?>
