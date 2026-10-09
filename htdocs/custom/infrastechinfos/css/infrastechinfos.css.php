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
	* 	\file		../infrastechinfos/css/infrastechinfos.css.php
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
	src: url('<?php print dol_buildpath('/infrastechinfos/css/puentebold.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

@font-face {
	font-family: 'NeuropolRegular';
	src: url('<?php print dol_buildpath('/infrastechinfos/css/NeuropolRegular.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

.infrastechneuropolinfras {
	font-family: NeuropolRegular, sans-serif;
	font-weight: bold;
	font-style: italic;
	color: #19052d;
}

.infrastechpuentedolibarr {
	font-family: puentebold, sans-serif;
	color: #027991;
	font-size-adjust: 0.6;
}

#InfraSTechInfos {
	padding: 8px 8px 16px 8px;
	height: 100%;
	width: 50%;
	margin-left: auto;
	margin-right: auto;
	margin-top: 8px;
	margin-bottom: 8px;
	border: solid #ddd 2px;
	background-color: #efefef;
}

.formInfraSTechInfos {
	text-align: center;
}

.formInfraSTechInfos input {
	margin-left: 2px;
}

label.titre {
	font-family: roboto,arial,tahoma,verdana,helvetica;
	font-weight: bold;
	color: rgb(90,90,90);
	text-decoration: none;
}

.infrastechinfosNoBCollapse {
	border-collapse: separate;
}

.infrastechinfosModal {
	margin: -3px -0 0 -2px;
    width: calc(100% + 4px) !important;
    height: 30px;
}

.infrastechinfosModalTitle {
	padding-left: 10px;
	line-height: 30px;
}

img.infrastechinfoswidthpictotitle {
	max-width: 48px;
}

.infrastechinfosDivTitre {
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

.infrastechinfosHR {
    text-align: center;
    margin: 5px 0px !important;
	padding: 0px !important;
}

.infrastechinfosFinal {
    line-height: 1px;
	border: none !important;
}

.infrastechinfosScrollUp {
	position: fixed;
	bottom : 30px;
	right: -100px;
	/*color: #19052d;*/
	font-size: xx-large;
}

.infrastechinfoscaution {
	color: red;
}

.infrastechinfosbgtrans {
	background: transparent !important;
}

.infrastechinfosbggreen {
	background: lightgreen;
}

.infrastechinfosbgorange {
	background: orange;
}

.infrastechinfosbgred {
	background: red;
}

.infrastechinfosgreen {
	color: green;
}

.infrastechinfosblue {
	color: #0088ff;
}

.infrastechinfosblack {
	color: black;
}

.infrastechinfosslogan {
	color: #ffffff;
	font-size: 16px;
}

.infrastechinfostitleparam {
	font-size: 14px;
}

.infrastechinfossubtitleparam {
	font-size: 12px;
}

.infrastechinfoscolor {
	color: #19052d;
}

.infrastechinfosformabout {
	/*background-color: var(--colorbacktabcard1);*/
	padding: 20px;
}

.infrastechinfoschangelogbase {
	border: none;
	padding-top: 0;
	padding-bottom: 0
}

.infrastechwidth110 {
	width: 110px;
}

.infrastechwidth120 {
	width: 120px;
}

.infrastechwidth180 {
	width: 180px;
}

.infrastechwidth220 {
	width: 220px;
}

.infrastechwidth270 {
	width: 270px;
}

.infrastechminwidth700imp {
	min-width: 700px !important;
}

.infrastechwidthtrentepercent {
	width: 30%;
}

.infrastecheight75 {
	height: 75px;
}

.infrastecheight32 {
	height: 32px;
}

.infrastecheight50 {
	height: 50px;
}

.infrastecheight25 {
	height: 25px;
}

.infrastecheight20 {
	height: 20px;
}

.nomargin {
	margin: 0px;
}

.nopadding {
	padding: 0px !important;
}

.noborder {
	border: none;
}

.nopaddingvert {
	padding-top: 0;
	padding-bottom: 0;
}

.margintop10imp {
	margin-top: 10px !important;
}

.fontsizeinherit {
	font-size: inherit;
}