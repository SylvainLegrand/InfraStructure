<?php
/* Copyright (C) 2018 		Netlogic			<dolibarr@netlogic.fr>
 * Copyright (C) 2018-2021	Frédéric France		<frederic.france@netlogic.fr>
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
 */

/**
 * \file    uptosign/css/uptosign.css.php
 * \ingroup uptosign
 * \brief   CSS file for module UptoSign.
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled. Language code is found on url.
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN', '1');	// Not disabled because need to do translations
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);          // File must be accessed by logon page so without login
}
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU', 1);  // We need top menu content
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

//session_cache_limiter('public');

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

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
div.mainmenu.uptosign {
	background-image: url('../img/uptosign.png' );
}

.uptosignFinal
{
	line-height: 1px;
	border: none !important;
}

.uptosignHistory{
	clear: both;
}
.uptosignHistory ol li ul{
	list-style-type:none;
	padding-top:5px;
}
.uptosignHistory ol li ul li{
	padding-left:10px;
	position:relative;
	cursor:pointer;
	transition:.5s;
}
.uptosignHistory ol li ul li span{
	background-color: #1685b8;
	color: #fff;
	border-radius: 10px;
	display:inline-block;
	padding:2px 5px;
	font-size:15px;
	text-align:center;
}
.uptosignHistory ol li ul li .content{
	margin-bottom: 15px;
}
.uptosignHistory ol li ul li .content h3{
	color: #34ace0;
	font-size: 17px;
	padding: 5px 0px;
	margin: 0px;
}
.uptosignHistory ol li ul li .content p{
	padding: 5px 0px 0px 0px;
	margin: 0px;
	font-size:15px;
}
.uptosignHistory ol li ul li:before{
	position:absolute;
	content:'';
	width:10px;
	height:10px;
	background-color:#34ace0;
	border-radius:50%;
	left:-11px;
	top:5px;
	transition:.5s;
}
.uptosignHistory ol li ul li:hover{
	background-color:#eee;
	border-radius: 10px;
}
.uptosignHistory ol li ul li:hover:before{
	background-color:#0F0;
	box-shadow:0px 0px 10px 2px #0F0;
}

/* Modal hosting the isolated position wizard (js/uptosign-modal.js) */
.uptosign-modal-overlay {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 100000;
	background: rgba(20, 28, 36, 0.72);
	/* Top band wide enough for the close button to sit outside of the frame */
	padding: 38px 24px 24px 24px;
	box-sizing: border-box;
}

.uptosign-modal-close {
	position: absolute;
	top: 4px;
	right: 24px;
	width: 28px;
	height: 28px;
	padding: 0;
	border: 0;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.15);
	color: #ffffff;
	font-size: 20px;
	line-height: 26px;
	cursor: pointer;
}

.uptosign-modal-close:hover {
	background: rgba(255, 255, 255, 0.3);
}

.uptosign-modal-frame {
	width: 100%;
	height: 100%;
	border: 0;
	border-radius: 8px;
	background: #ffffff;
	box-shadow: 0 10px 40px rgba(0, 0, 0, 0.35);
}

body.uptosign-modal-open {
	overflow: hidden;
}

@media only screen and (max-width: 800px) {
	.uptosign-modal-overlay {
		padding: 34px 0 0 0;
	}
	.uptosign-modal-close {
		right: 4px;
	}
	.uptosign-modal-frame {
		border-radius: 0;
	}
}
