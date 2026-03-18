<?php
	/************************************************
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
	*	\file		./dolinfras/css/dolinfras.css.php
	*	\ingroup	InfraS
	*	\brief		CSS for the module InfraS
	************************************************/

	// Dolibarr environment *************************
	if (!defined('NOREQUIRESOC')) {
		define('NOREQUIRESOC', '1');
	}
	if (!defined('NOCSRFCHECK')) {
		define('NOCSRFCHECK', 1);
	}
	if (!defined('NOTOKENRENEWAL')) {
		define('NOTOKENRENEWAL', 1);
	}
	if (!defined('NOLOGIN')) {
		define('NOLOGIN', 1);	// File must be accessed by logon page so without login
	}
	if (!defined('NOREQUIREHTML')) {
		define('NOREQUIREHTML', 1);
	}
	if (!defined('NOREQUIREAJAX')) {
		define('NOREQUIREAJAX', '1');
	}
	if (!defined('ISLOADEDBYSTEELSHEET')) {
		define('ISLOADEDBYSTEELSHEET', '1');
	}
	session_cache_limiter('public');

	require '../config.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

	// Define css type
	header('Content-type: text/css');
	// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
	// You can use CTRL+F5 to refresh your browser cache.
	if (empty($dolibarr_nocache)) {
		header('Cache-Control: max-age=3600, public, must-revalidate');
	} else {
		header('Cache-Control: no-cache');
	}
?>

@font-face {
	font-family: 'puentebold';
	src: url('<?php print dol_buildpath('/dolinfras/css/puentebold.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

@font-face {
	font-family: 'NeuropolRegular';
	src: url('<?php print dol_buildpath('/dolinfras/css/NeuropolRegular.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

.dolinfrasneuropolinfras {
	font-family: NeuropolRegular, sans-serif;
	font-weight: bold;
	font-style: italic;
	color: #19052d;
}

.dolinfraspuentedolibarr {
	font-family: puentebold, sans-serif;
	color: #027991;
	font-size-adjust:0.6;
}

.dolinfrasCaution {
	color: red;
}

.dolinfraswidth180 {
	width: 180px;
}

.dolinfraswidth220 {
	width: 220px;
}

.dolinfraswidth270 {
	width: 270px;
}

.dolinfrasheight32 {
	height: 32px;
}

.dolinfrasheight75 {
	height: 75px;
}

.dolinfrasheight50 {
	height: 50px;
}

.dolinfrasheight25 {
	height: 25px;
}

.dolinfrasnoborder {
	border: none !important;
}

.dolinfraswidthtrentepercent {
	width: 30%;
}

.dolinfrasminwidth700imp {
	min-width: 700px !important;
}

.dolinfrasmargintop10imp {
	margin-top: 10px !important;
}

.dolinfrasnopaddingvert {
	padding-top: 0;
	padding-bottom: 0;
}

.dolinfrasslogan {
	font-size: 14px;
	color: white;
}

.dolinfrascolor {
	color: #19052d;
}

.dolinfrastitleparam {
	font-size: 16px;
	font-weight: bold;
}

/* Changelog table */
.dolinfraschangelogbase {
	padding: 2px 5px;
	font-size: 12px;
}

.dolinfraschangefix {
	color: red;
}

.dolinfraschangeadd {
	color: green;
}

.dolinfraschangechg {
	color: blue;
}

.dolinfraschangedefault {
	color: #333;
}

.dolinfrasbgorange {
	background-color: #ffcc80;
}

.dolinfrasbggreen {
	background-color: #c8e6c9;
}

/* Dark background overrides for changelog */
.dolinfras-dark-bg .dolinfraschangedefault {
	color: #e0e0e0;
}

.dolinfras-dark-bg .dolinfrasslogan {
	color: #c8b0e0;
}

.dolinfras-dark-bg .dolinfrascolor {
	color: #c8b0e0;
}

/* Dark background overrides (class set by dolinfras.js) */
.dolinfras-dark-bg .dolinfrasneuropolinfras {
	color: #c8b0e0;
}

.dolinfras-dark-bg .dolinfrascolor {
	color: #c8b0e0;
}

.dolinfras-dark-bg .dolinfrasblack {
	color: #e0e0e0;
}
