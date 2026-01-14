<?php
	/************************************************
	* Copyright (C) 2016-2025	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infrascusprice/css/infrascusprice.css.php
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

img.infrascuspwidthpictotitle {
	max-width: 48px;
}

.infrascuspDivTitre {
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

.infrascuspHR {
    text-align: center;
    margin: 5px 0px !important;
	padding: 0px !important;
}

.infrascuspFinal {
    line-height: 1px;
	border: none !important;
}

.infrascuspScrollUp {
	position: fixed;
	bottom : 30px;
	right: -100px;
	color: #19052d;
	font-size: xx-large;
}

.infrascuspCaution {
	color: red;
}

.infrascuspbggreen {
	background: lightgreen;
}

.infrascuspbgorange {
	background: orange;
}

.infrascuspbgred {
	background: red;
}

.infrascuspgreen {
	color: green;
}

.infrascuspblue {
	color: #0088ff;
}

.infrascuspblack {
	color: black;
}

.infrascuspSlogan {
	font-size: 16px;
}

.infrascuspTitleparam {
	font-size: 14px;
}

.infrascuspSubtitleparam {
	font-size: 12px;
}

.infrascuspColor {
	color: #19052d;
}

.login_block .classfortooltip:hover {
	background-color: transparent;
}

.infrascuspformabout {
	/*background-color: var(--colorbacktabcard1);*/
	padding: 20px;
}

.infrascuspchangelogbase {
	border: none;
	padding-top: 0;
	padding-bottom: 0
}

.widthquatrevingtdixpercent {
	width: 90%;
}

.width110 {
	width: 110px;
}

.width120 {
	width: 120px;
}

.width180 {
	width: 180px
}

.width220 {
	width: 220px;
}

.width270 {
	width: 270px;
}

.widthtrentepercent {
	width: 30%;
}

.height75 {
	height: 75px;
}

.height50 {
	height: 50px;
}

.height25 {
	height: 25px;
}

.height20 {
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

.fontsizeinherit {
	font-size: inherit;
}

.dropdown-item a img {
	max-height: 16px;
}
