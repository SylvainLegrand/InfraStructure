<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infraspackplus/css/infraspackplus.css.php
	* 	\ingroup	InfraS
	* 	\brief		CSS for the module InfraS
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
	src: url('<?php print dol_buildpath('/infraspackplus/css/puentebold.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

@font-face {
	font-family: 'NeuropolRegular';
	src: url('<?php print dol_buildpath('/infraspackplus/css/NeuropolRegular.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

.infrasplusneuropolinfras {
	font-family: NeuropolRegular, sans-serif;
	font-weight: bold;
	font-style: italic;
	color: #19052d;
}

.infraspluspuentedolibarr {
	font-family: puentebold, sans-serif;
	color: #027991;
	font-size-adjust: 0.6;
}

.infrasplusNoBCollapse {
	border-collapse: separate;
}

.infrasplusModal {
	margin: -3px -0 0 -2px;
    width: calc(100% + 4px) !important;
    height: 30px;
}

.infrasplusModalTitle {
	padding-left: 10px;
	line-height: 30px;
}

img.infraspluswidthpictotitle {
	max-width: 48px;
}

.infrasplusDivTitre {
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

.infrasplusHR {
    text-align: center;
    margin: 5px 0px !important;
	padding: 0px !important;
}

.infrasplusFinal {
    line-height: 1px;
	border: none !important;
}

.infrasplusScrollUp {
	position: fixed;
	bottom : 30px;
	right: -100px;
	/*color: #19052d;*/
	font-size: xx-large;
}

.infrasplusModal
{
	margin: -3px -0 0 -2px;
    width: calc(100% + 4px) !important;
    height: 30px;
}

.infrasplusModalTitle
{
	padding-left: 10px;
	line-height: 30px;
}

.infraspluscaution {
	color: red;
}

.infrasplusbgtrans {
	background: transparent !important;
}

.infrasplusbggreen {
	background: lightgreen;
}

.infrasplusbgorange {
	background: orange;
}

.infrasplusbgred {
	background: red;
}

.infrasplusgreen {
	color: green;
}

.infrasplusblue {
	color: #0088ff;
}

.infrasplusblack {
	color: black;
}

.infrasplusslogan {
	color: white;
	font-size: 16px;
}

.infrasplustitleparam {
	font-size: 14px;
}

.infrasplussubtitleparam {
	font-size: 12px;
}

.infraspluscolor {
	color: #19052d;
}

.infrasplusformabout {
	/*background-color: var(--colorbacktabcard1);*/
	padding: 20px;
}

.infraspluschangelogbase {
	border: none;
	padding-top: 0;
	padding-bottom: 0
}

.tablelayoutfixed {
	table-layout: fixed;
}

.widthquatrevingtdixpercent {
	width: 90%;
}

.infraspluswidth90 {
	width: 90px;
}

.infraspluswidth110 {
	width: 110px;
}

.infraspluswidth120 {
	width: 120px;
}

.infraspluswidth180 {
	width: 180px;
}

.infraspluswidth220 {
	width: 220px;
}

.infraspluswidth270 {
	width: 270px;
}

.infrasplusminwidth700imp {
	min-width: 700px !important;
}

.infraspluswidthquinzepercent {
	width: 15%;
}

.infraspluswidthtrentepercent {
	width: 30%;
}

.infraspluswidthtrentetroispercent {
	width: 33%;
}

.infrasplusheight75 {
	height: 75px;
}

.infrasplusheight32 {
	height: 32px;
}

.infrasplusheight50 {
	height: 50px;
}

.infrasplusheight25 {
	height: 25px;
}

.infrasplusheight20 {
	height: 20px;
}

.lineinfrasplusheight200percent {
	line-height: 200%;
}

.infrasplusnomargin {
	margin: 0px;
}

.infrasplusnopadding {
	padding: 0px !important;
}

.infrasplusnoborder {
	border: none;
}

.infrasplusnopaddingvert {
	padding-top: 0;
	padding-bottom: 0;
}

.infrasplusmargintop10imp {
	margin-top: 10px !important;
}

.infrasplusfontsizeinherit {
	font-size: inherit;
}
button.infraspluscopyParamsBtn, .infraspluscopyParamsBtn:hover {
	padding: 8px 25px 8px 25px;
}