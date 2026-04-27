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
	* 	\file		../infrastructure/css/infrastructure.css.php
	* 	\ingroup	InfraS
	* 	\brief		CSS file for the Infrastructure module
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
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

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
	src: url('<?php print dol_buildpath('/infrastructure/css/puentebold.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

@font-face {
	font-family: 'NeuropolRegular';
	src: url('<?php print dol_buildpath('/infrastructure/css/NeuropolRegular.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

.infrastructureneuropolinfras {
	font-family: NeuropolRegular, sans-serif;
	font-weight: bold;
	font-style: italic;
	color: #19052d;
}

.infrastructurepuentedolibarr {
	font-family: puentebold, sans-serif;
	color: #027991;
	font-size-adjust: 0.6;
}

#Infrastructure {
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

.formInfrastructure {
	text-align: center;
}

.formInfrastructure input {
	margin-left: 2px;
}

label.titre {
	font-family: roboto,arial,tahoma,verdana,helvetica;
	font-weight: bold;
	color: rgb(90,90,90);
	text-decoration: none;
}

.infrastructureNoBCollapse {
	border-collapse: separate;
}

.infrastructureModal {
	margin: -3px -0 0 -2px;
    width: calc(100% + 4px) !important;
    height: 30px;
}

.infrastructureModalTitle {
	padding-left: 10px;
	line-height: 30px;
}

img.infrastructurewidthpictotitle {
	max-width: 48px;
}

.infrastructureDivTitre {
	color: var(--colortexttitle) !important;
	font-weight: bold;
	font-size: 1.1em;
	text-decoration: none;
	padding-top: 5px;
	padding-bottom: 5px;
}

.infrastructureHR {
    text-align: center;
    margin: 5px 0px !important;
	padding: 0px !important;
}

.infrastructureFinal {
    line-height: 1px;
	border: none !important;
}

.infrastructureScrollUp {
	position: fixed;
	bottom : 30px;
	right: -100px;
	/*color: #19052d;*/
	font-size: xx-large;
}

.infrastructurecaution {
	color: red;
}

.infrastructurebgtrans {
	background: transparent !important;
}

.infrastructurebggreen {
	background: lightgreen;
}

.infrastructurebgorange {
	background: orange;
}

.infrastructurebgred {
	background: red;
}

.infrastructuregreen {
	color: green;
}

.infrastructureblue {
	color: #0088ff;
}

.infrastructureblack {
	color: black;
}

.infrastructureslogan {
	color: #ffffff;
	font-size: 16px;
}

.infrastructuretitleparam {
	font-size: 14px;
}

.infrastructuresubtitleparam {
	font-size: 12px;
}

.infrastructurecolor {
	color: #19052d;
}

.infrastructureformabout {
	/*background-color: var(--colorbacktabcard1);*/
	padding: 20px;
}

.infrastructurechangelogbase {
	border: none;
	padding-top: 0;
	padding-bottom: 0
}

.infrastructurewidth110 {
	width: 110px;
}

.infrastructurewidth120 {
	width: 120px;
}

.infrastructurewidth180 {
	width: 180px;
}

.infrastructurewidth220 {
	width: 220px;
}

.infrastructurewidth270 {
	width: 270px;
}

.infrastructureminwidth700imp {
	min-width: 700px !important;
}

.infrastructurewidthtrentepercent {
	width: 30%;
}

.infrastructureheight75 {
	height: 75px;
}

.infrastructureheight32 {
	height: 32px;
}

.infrastructureheight50 {
	height: 50px;
}

.infrastructureheight25 {
	height: 25px;
}

.infrastructureheight20 {
	height: 20px;
}

.infrastructurenomargin {
	margin: 0px;
}

.infrastructurenopadding {
	padding: 0px !important;
}

.infrastructurenoborder {
	border: none;
}

.infrastructurenopaddingvert {
	padding-top: 0;
	padding-bottom: 0;
}

.infrastructuremargintop10imp {
	margin-top: 10px !important;
}

.infrastructurefontsizeinherit {
	font-size: inherit;
}