<?php
	/************************************************
	* Copyright (C) 2026-2026	Lucky Ranasolonirina - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	*	\file		./infrasfiles/css/infrasfiles.css.php
	*	\ingroup	InfraS
	*	\brief		CSS for the module InfraSFiles
	************************************************/

	// Dolibarr environment *************************
	if (! defined('NOREQUIRESOC')) {
		define('NOREQUIRESOC', '1');
	}
	if (! defined('NOCSRFCHECK')) {
		define('NOCSRFCHECK', 1);
	}
	if (! defined('NOTOKENRENEWAL')) {
		define('NOTOKENRENEWAL', 1);
	}
	if (! defined('NOLOGIN')) {
		define('NOLOGIN', 1);	// File must be accessed by logon page so without login
	}
	if (! defined('NOREQUIREHTML')) {
		define('NOREQUIREHTML', 1);
	}
	if (! defined('NOREQUIREAJAX')) {
		define('NOREQUIREAJAX', '1');
	}
	if (! defined('ISLOADEDBYSTEELSHEET')) {
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
	src: url('<?php print dol_buildpath('/infrasfiles/css/puentebold.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

@font-face {
	font-family: 'NeuropolRegular';
	src: url('<?php print dol_buildpath('/infrasfiles/css/NeuropolRegular.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

.infrasfilesneuropolinfras {
	font-family: NeuropolRegular, sans-serif;
	font-weight: bold;
	font-style: italic;
	color: #19052d;
}

.infrasfilespuentedolibarr {
	font-family: puentebold, sans-serif;
	color: #027991;
	font-size-adjust: 0.6;
}

img.infrasfileswidthpictotitle {
	max-width: 48px;
}

.infrasfilesDivTitre {
	<?php if (isModEnabled('oblyon')) { ?>
		color: var(--colorftitle) !important;
	<?php } else { ?>
		color: var(--colortexttitle) !important;
	<?php } ?>
	font-weight: bold;
	font-size: 1.1em;
	text-decoration: none;
	padding-top: 5px;
	padding-bottom: 5px;
}

.infrasfilesHR {
	text-align: center;
	margin: 5px 0px !important;
	padding: 0px !important;
}

.infrasfilesFinal {
	line-height: 1px;
	border: none !important;
}

.infrasfilesScrollUp {
	position: fixed;
	bottom : 30px;
	right: -100px;
	color: #19052d;
	font-size: xx-large;
}

.infrasfilescaution,
.infrasfilesCaution {
	color: red;
}

.infrasfilesbggreen {
	background: lightgreen;
}

.infrasfilesbgorange {
	background: orange;
}

.infrasfilesbgred {
	background: red;
}

.infrasfilesgreen {
	color: green;
}

.infrasfilesblue {
	color: #0088ff;
}

.infrasfilesblack {
	color: black;
}

.infrasfilesslogan {
	color: #ffffff;
	font-size: 16px;
}

.infrasfilesTitleparam,
.infrasfilestitleparam {
	font-size: 14px;
}

.infrasfilesSubtitleparam,
.infrasfilessubtitleparam {
	font-size: 12px;
}

.infrasfilesColor {
	color: #19052d;
}

.login_block .classfortooltip:hover {
	background-color: transparent;
}

.infrasfilesformabout {
	padding: 20px;
}

.infrasfileschangelogbase {
	border: none;
	padding-top: 0;
	padding-bottom: 0
}

.infrasfileswidthquatrevingtdixpercent {
	width: 90%;
}

.infrasfileswidth110 {
	width: 110px;
}

.infrasfileswidth120 {
	width: 120px;
}

.infrasfileswidth180 {
	width: 180px
}

.infrasfileswidth220 {
	width: 220px;
}

.infrasfileswidth270 {
	width: 270px;
}

.infrasfilesminwidth700imp {
	min-width: 700px !important;
}

.infrasfileswidthtrentepercent {
	width: 30%;
}

.infrasfilesheight75 {
	height: 75px;
}

.infrasfilesheight32 {
	height: 32px;
}

.infrasfilesheight50 {
	height: 50px;
}

.infrasfilesheight25 {
	height: 25px;
}

.infrasfilesheight20 {
	height: 20px;
}

.infrasfilesnomargin {
	margin: 0px;
}

.infrasfilesnopadding {
	padding: 0px !important;
}

.infrasfilesnoborder {
	border: none;
}

.infrasfilesnopaddingvert {
	padding-top: 0;
	padding-bottom: 0;
}

.infrasfilesmargintop10imp {
	margin-top: 10px !important;
}

.infrasfilesfontsizeinherit {
	font-size: inherit;
}

/* Setup page, "supported documents" table : visible zebra rows (the oblyon theme uses 255 / 251 : invisible) and a band per document */
table[name="tblObjects"] tr.oddeven td {
	border-bottom: 1px solid rgba(100, 130, 160, 0.18);
	padding-top: 4px;
	padding-bottom: 4px;
}

table[name="tblObjects"] tr.oddeven:nth-of-type(even) td {
	background-color: rgba(100, 130, 160, 0.09);
}

table[name="tblObjects"] tr.infrasfilesobjecttitle td {
	padding-top: 6px;
	padding-bottom: 6px;
	font-weight: bold;
}

table[name="tblObjects"] .infrasfileseditorlabel {
	margin-bottom: 8px;
}
