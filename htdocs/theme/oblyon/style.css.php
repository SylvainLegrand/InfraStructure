<?php
	/************************************************
	* Copyright (C) 2015-2024	Alexandre Spangaro	<alexandre@inovea-conseil.com>
	* Copyright (C) 2022-2026	Sylvain Legrand		<contact@infras.fr>
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
	************************************************/

	/************************************************
	* 	\file		./theme/oblyon/style.css.php
	* 	\brief		CSS for Oblyon theme
	************************************************/

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
		define('NOLOGIN', 1);		 // File must be accessed by logon page so without login
	}
	if (! defined('NOREQUIREHTML')) {
		define('NOREQUIREHTML', 1);
	}
	if (! defined('NOREQUIREAJAX')) {
		define('NOREQUIREAJAX', '1');
	}
	define('ISLOADEDBYSTEELSHEET', '1');
	session_cache_limiter('public');
	require_once __DIR__.'/../../main.inc.php'; // __DIR__ allow this script to be included in custom themes
	require __DIR__.'/theme_vars.inc.php';
	if (defined('THEME_ONLY_CONSTANT'))	return;
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	dol_include_once('/oblyon/backport/v21/core/lib/functions.lib.php');

	/************************************************
	*	Select text color from background values
	*
	*	@param	string		$bgcolor			RGB value for background color
	* 	@return	string							'FFFFFF' or '000000' for white or black
	************************************************/
	function txt_color(&$bgcolor)
	{
		global $conf;

		$tmppart	= explode(',', $bgcolor);
		$tmpvalr	= (! empty($tmppart[0]) ? $tmppart[0] : 0) * 0.3;
		$tmpvalg	= (! empty($tmppart[1]) ? $tmppart[1] : 0) * 0.59;
		$tmpvalb	= (! empty($tmppart[2]) ? $tmppart[2] : 0) * 0.11;
		$tmpval		= $tmpvalr + $tmpvalg + $tmpvalb;
		$txtcolor	= $tmpval <= 128 ? 'FFFFFF' : '000000';
		return $txtcolor;
	}

	// Load user to have $user->conf loaded (not done into main because of NOLOGIN constant defined) and permission, so we can later calculate number of top menu ($nbtopmenuentries) according to user profile.
	if (empty($user->id) && ! empty($_SESSION['dol_login'])) {
		$user = new User($db);
		$user->fetch(0, $_SESSION['dol_login'], '', 1);
		$user->getrights();
		// Reload menu now we have the good user (and we need the good menu to have ->showmenu('topnb') correct.
		$menumanager	= new MenuManager($db, empty($user->socid) ? 0 : 1);
		$menumanager->loadMenu();
	}
	// Define css type
	top_httphead('text/css');
	// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
	if (empty($dolibarr_nocache)) {
		header('Cache-Control: max-age=10800, public, must-revalidate');
	} else {
		header('Cache-Control: no-cache');
	}
	if (GETPOST('theme', 'aZ09')) {
		$conf->theme=GETPOST('theme', 'alpha'); // If theme was forced on URL
	}
	if (GETPOST('lang', 'aZ09')) {
		$langs->setDefaultLang(GETPOST('lang', 'aZ09'));	// If language was forced on URL
	}
	if (GETPOST('THEME_DARKMODEENABLED', 'int')) {
		$conf->global->THEME_DARKMODEENABLED	= GETPOST('THEME_DARKMODEENABLED', 'int');  // If darkmode was forced on URL
	}
	// Define fontawesome family
	$path		= dol_buildpath('/theme/common/', 0);
	$listdir	= dol_dir_list($path, 'directories', 0, '^fontawesome-', null, 'name', SORT_ASC, 0, 0, '', 0);
	$listFamily	= array();
	foreach ($listdir as $dir) {
		if (empty($dir['name']))	continue;
		if (preg_match('/^fontawesome-([0-9])$/', $dir['name'], $reg)) {
			$version	= $reg[1];
			$lines		= file(dol_buildpath('/theme/common/'.$dir['name'].'/scss', 0).'/_variables.scss');	// Fetch variables scss file in variable
			foreach($lines as $line) {
				// Discard any black line or anything without :
				if (strpos($line, ':') !== false && preg_match('/\$fa-style-family/', $line, $reg)) {
					$t						= explode('"', $line);
					$listFamily[$version]	= $t[1];
					break;
				}
			}
		}
	}
	$fontawesomeFamily	= count($listFamily) > 1 ? $listFamily[max(array_keys($listFamily))] : (!empty($listFamily) ? reset($listFamily) : 'Font Awesome 5 Free');
	$fontawesomeFamily	= getDolGlobalString('MAIN_FONTAWESOME_FAMILY', $fontawesomeFamily);
	// Vérifie que la famille font awesome est bien enegistré
	if (!getDolGlobalString('MAIN_FONTAWESOME_FAMILY', '')) {
		dolibarr_set_const($db, 'MAIN_FONTAWESOME_FAMILY', $fontawesomeFamily, 'chaine', 0, 'module Oblyon', 0);
	}
	$fontawesomeBrands	= explode(' ', $fontawesomeFamily);
	$fontawesomeBrands	= $fontawesomeBrands[0].' '.$fontawesomeBrands[1].' '.$fontawesomeBrands[2].' Brands';
	$langs->load('main', 0, 1);
	$right				= ($langs->trans('DIRECTION') == 'rtl' ? 'left' : 'right');
	$left				= ($langs->trans('DIRECTION') == 'rtl' ? 'right' : 'left');
	$path				= '';		// This value may be used in future for external module to overwrite theme
	$theme				= 'oblyon';	// Value of theme
	if (!empty(getDolGlobalString('MAIN_OVERWRITE_THEME_RES'))) {
		$path	= '/'.getDolGlobalString('MAIN_OVERWRITE_THEME_RES');
		$theme	= getDolGlobalString('MAIN_OVERWRITE_THEME_RES');
	}
	// Define image path files and other constants
	$img_button					= dol_buildpath($path.'/theme/'.$theme.'/img/button_bg.png', 1);
	$dol_hide_topmenu			= $conf->dol_hide_topmenu;
	$dol_hide_leftmenu			= $conf->dol_hide_leftmenu;
	$dol_optimize_smallscreen	= $conf->dol_optimize_smallscreen;
	$dol_no_mouse_hover			= $conf->dol_no_mouse_hover;
	dolibarr_set_const($db, 'THEME_ELDY_ENABLE_PERSONALIZED', 1, 'chaine', 0, 'OblyonTheme', $conf->entity);
	$useboldtitle				= getDolGlobalInt('THEME_ELDY_USEBOLDTITLE', 0);

	// ===================== Couleurs Oblyon (défauts dans theme_vars, surcharge par constantes OBLYON_COLOR_*) =====================
	$maincolor					= getDolGlobalString('OBLYON_COLOR_MAIN');								// default value: #0083a2
	$navlinkcolor				= '#f4f4f4';															// default value: #eee
	$topmenu_hover				= $maincolor;															// default value: #
	$bgnavtop					= getDolGlobalString('OBLYON_COLOR_TOPMENU_BCKGRD', $bgnavtop);			// default value: #333		//	for main navigation
	$bgnavtop_txt				= getDolGlobalString('OBLYON_COLOR_TOPMENU_TXT', $bgnavtop_txt);			// default value: #f4f4f4	//	for main navigation
	$bgnavtop_txt_active		= getDolGlobalString('OBLYON_COLOR_TOPMENU_TXT_ACTIVE', $bgnavtop_txt_active);		// default value: #f4f4f4	//	for main navigation
	$bgnavtop_txt_hover			= getDolGlobalString('OBLYON_COLOR_TOPMENU_TXT_HOVER', $bgnavtop_txt_hover);		// default value: #f4f4f4	//	for main navigation
	$bgnavtop_hover				= getDolGlobalString('OBLYON_COLOR_TOPMENU_BCKGRD_HOVER', $bgnavtop_hover);		// default value: #444		//	for main navigation
	$bgnavleft					= getDolGlobalString('OBLYON_COLOR_LEFTMENU_BCKGRD', $bgnavleft);			// default value: #333		//	for left navigation
	$bgnavleft_txt				= getDolGlobalString('OBLYON_COLOR_LEFTMENU_TXT', $bgnavleft_txt);			// default value: #f4f4f4	//	for left navigation
	$bgnavleft_txt_active		= getDolGlobalString('OBLYON_COLOR_LEFTMENU_TXT_ACTIVE', $bgnavleft_txt_active);	// default value: #f4f4f4	//	for left navigation
	$bgnavleft_txt_hover		= getDolGlobalString('OBLYON_COLOR_LEFTMENU_TXT_HOVER', $bgnavleft_txt_hover);		// default value: #f4f4f4	//	for left navigation
	$bgnavleft_hover			= getDolGlobalString('OBLYON_COLOR_LEFTMENU_BCKGRD_HOVER', $bgnavleft_hover);		// default value: #444		//	for left navigation
	$colorButtonAction1			= getDolGlobalString('THEME_ELDY_BTNACTION', $colorButtonAction1);			// default value: #0088cc
	$colorButtonAction2			= getDolGlobalString('OBLYON_COLOR_BUTTON_ACTION2', $colorButtonAction2);			// default value: #0044cc
	$colorTextButtonAction		= getDolGlobalString('THEME_ELDY_TEXTBTNACTION', $colorTextButtonAction);
	$colorButtonDelete1			= getDolGlobalString('OBLYON_COLOR_BUTTON_DELETE1', $colorButtonDelete1);			// default value: #cc8800
	$colorButtonDelete2			= getDolGlobalString('OBLYON_COLOR_BUTTON_DELETE2', $colorButtonDelete2);			// default value: #cc4400
	$colorInfoBorder			= getDolGlobalString('OBLYON_COLOR_INFO_BORDER', $colorInfoBorder);			// default value: #87cfd2
	$colorInfoBg				= getDolGlobalString('OBLYON_COLOR_INFO_BCKGRD', $colorInfoBg);			// default value: #eff8fc
	$colorInfoTxt				= getDolGlobalString('OBLYON_COLOR_INFO_TEXT', $colorInfoTxt);						// default value: #
	$colorWarningBorder			= getDolGlobalString('OBLYON_COLOR_WARNING_BORDER', $colorWarningBorder);			// default value: #f2cf87
	$colorWarningBg				= getDolGlobalString('OBLYON_COLOR_WARNING_BCKGRD', $colorWarningBg);			// default value: #fcf8e3
	$colorWarningTxt			= getDolGlobalString('OBLYON_COLOR_WARNING_TEXT', $colorWarningTxt);					// default value: #
	$colorErrorBorder			= getDolGlobalString('OBLYON_COLOR_ERROR_BORDER', $colorErrorBorder);			// default value: #e0796e
	$colorErrorBg				= getDolGlobalString('OBLYON_COLOR_ERROR_BCKGRD', $colorErrorBg);			// default value: #f07b6e
	$colorErrorTxt				= getDolGlobalString('OBLYON_COLOR_ERROR_TEXT', $colorErrorTxt);					// default value: #
	$colorNotifInfoBg			= getDolGlobalString('OBLYON_COLOR_NOTIF_INFO_BCKGRD', $colorNotifInfoBg);		// default value: #d9e5d1
	$colorNotifInfoTxt			= getDolGlobalString('OBLYON_COLOR_NOTIF_INFO_TEXT', $colorNotifInfoTxt);		// default value: #446548
	$colorNotifWarningBg		= getDolGlobalString('OBLYON_COLOR_NOTIF_WARNING_BCKGRD', $colorNotifWarningBg);	// default value: #fff7d1
	$colorNotifWarningTxt		= getDolGlobalString('OBLYON_COLOR_NOTIF_WARNING_TEXT', $colorNotifWarningTxt);		// default value: #a28918
	$colorNotifErrorBg			= getDolGlobalString('OBLYON_COLOR_NOTIF_ERROR_BCKGRD', $colorNotifErrorBg);		// default value: #d79eac
	$colorNotifErrorTxt			= getDolGlobalString('OBLYON_COLOR_NOTIF_ERROR_TEXT', $colorNotifErrorTxt);		// default value: #a72947
	$colorTextTabActive			= getDolGlobalString('OBLYON_COLOR_TEXTTABACTIVE', $colorTextTabActive);			// default value: #222222
	$colorBckgrdInput			= getDolGlobalString('OBLYON_COLOR_INPUT_BCKGRD', $colorBckgrdInput);			// default value: #DEDEDE
	$color1BckgrdInfobox		= getDolGlobalString('OBLYON_COLOR_INFOBOX_BCKGRD1', $color1BckgrdInfobox);		// default value: #a2e0b8
	$color2BckgrdInfobox		= getDolGlobalString('OBLYON_COLOR_INFOBOX_BCKGRD2', $color2BckgrdInfobox);		// default value: #E4EFE8
	$colorBorderActionColumn	= getDolGlobalString('OBLYON_COLOR_BORDER_ACTIONCOLUMN', $colorBorderActionColumn);	// default value: #BBBBBB
	$bgotherbox					= '#f4f4f4';															// default value: #E6E6E6	//	Other information boxes on home page
	$bgbutton_hover				= '#197489';															// default value: #197489
	if (!empty($maincolor)) {
		$colorlength	= strlen($maincolor);
		$matches		= array();
		if ($colorlength == 4) {
			preg_match('/([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})/', $maincolor, $matches);	// Format #RGB
		} elseif ($colorlength == 7) {
			preg_match('/([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})/', $maincolor, $matches);	// Format #RRGGBB
		}
		if (!empty($matches)) {
			$maincolor_variant	= array();
			$variation			= -50;	// 20% darker
			for ($i=1; $i < sizeof($matches); $i++) {
				$maincolor_variant[$i-1]	= max(0 , min(hexdec($matches[$i]) + $variation, 255));
			}
			$bgbutton_hover = '#'.colorArrayToHex($maincolor_variant);
		}
	}
	$logo_background_color		= getDolGlobalString('OBLYON_COLOR_LOGO_BCKGRD', $logo_background_color);	// default value : #FFFFFF
	$bgcolor					= getDolGlobalString('OBLYON_COLOR_BCKGRD', $bgcolor);			// default value : #F4F4F4
	$login_bgcolor				= getDolGlobalString('OBLYON_COLOR_LOGIN_BCKGRD', $login_bgcolor);	// default value : #F4F4F4
	$colorbtitle				= getDolGlobalString('OBLYON_COLOR_BTITLE', $colorbtitle);			// default value : #E09430
	$colorstitle				= getDolGlobalString('OBLYON_COLOR_STITLE', $colorstitle);			// default value : #F4F4F4
	$colorbline					= getDolGlobalString('OBLYON_COLOR_BLINE', $colorbline);			// default value : #FFFFFF
	$colorbline_hover			= getDolGlobalString('THEME_ELDY_USE_HOVER', $colorbline_hover);
	$colorbline_checked			= getDolGlobalString('THEME_ELDY_USE_CHECKED', $colorbline_checked);
	$colorfline					= getDolGlobalString('OBLYON_COLOR_FLINE', $colorfline);			// default value : #444444
	$colorfline_hover			= getDolGlobalString('OBLYON_COLOR_FLINE_HOVER', $colorfline_hover);	// default value : #222222
	$colorbtotal				= getDolGlobalString('OBLYON_COLOR_BTOTAL', $colorbtotal);
	$colorftotal				= getDolGlobalString('OBLYON_COLOR_FTOTAL', $colorftotal);
	$colorfdatedefault			= getDolGlobalString('OBLYON_COLOR_FDATE_DEFAULT', $colorfdatedefault);	// default value : #FF0000
	$colorfdateselected			= getDolGlobalString('OBLYON_COLOR_FDATE_SELECTED', $colorfdateselected);	// default value : #FF0000
	$invertratiofilter			= getDolGlobalString('THEME_INVERT_RATIO_FILTER', 0);			// default value : 0
	$prospectback				= getDolGlobalString('THEME_ELDY_PROSPECTBACK', $prospectback);	// default value : #A7C5B0
	$customerback				= getDolGlobalString('THEME_ELDY_CUSTOMERBACK', $customerback);	// default value : #55955D
	$vendorback					= getDolGlobalString('THEME_ELDY_VENDORBACK', $vendorback);	// default value : #599CAF
	$userback					= getDolGlobalString('THEME_ELDY_USERBACK', $userback);	// default value : #79633F
	$colornature				= getDolGlobalString('THEME_ELDY_COLORNATURE', $colornature);	// default value : #FFFFFF
	$member_companyback			= getDolGlobalString('THEME_ELDY_MEMBER_COMPANYBACK', $member_companyback);	// default value : #E4E4E4
	$member_individualback		= getDolGlobalString('THEME_ELDY_MEMBER_INDIVIDUALBACK', $member_individualback);	// default value : #E4E4E4
	$colormember				= getDolGlobalString('THEME_ELDY_COLORMEMBER', $colormember);	// default value : #666666
	$colorAutocompleteBg		= getDolGlobalString('OBLYON_COLOR_AUTOCOMPLETE_BCKGRD', $colorAutocompleteBg);
	$colorAutocompleteText		= getDolGlobalString('OBLYON_COLOR_AUTOCOMPLETE_TEXT', $colorAutocompleteText);
	$colorChipBg				= getDolGlobalString('OBLYON_COLOR_CHIP_BCKGRD', $colorChipBg);
	$colorChipText				= getDolGlobalString('OBLYON_COLOR_CHIP_TEXT', $colorChipText);
	$colorResultBg				= getDolGlobalString('OBLYON_COLOR_RESULT_BCKGRD', $colorResultBg);
	$colorResultText			= getDolGlobalString('OBLYON_COLOR_RESULT_TEXT', $colorResultText);

	// ===================== Couleurs Eldy (défauts theme_vars + personnalisation utilisateur) =====================
	$colorbackhmenu1			= getDolGlobalString('THEME_ELDY_TOPMENU_BACK1', $colorbackhmenu1);
	$colorbackvmenu1			= getDolGlobalString('THEME_ELDY_VERMENU_BACK1', $colorbackvmenu1);
	$colortopbordertitle1		= getDolGlobalString('THEME_ELDY_TOPBORDER_TITLE1', $colortopbordertitle1);
	$colorbacktitle1			= getDolGlobalString('THEME_ELDY_BACKTITLE1', $colorbacktitle1);
	$colorbacktabcard1			= !getDolUserString('THEME_ELDY_ENABLE_PERSONALIZED', '', $user) ? getDolGlobalString('THEME_ELDY_BACKTABCARD1', $colorbacktabcard1) : getDolUserString('THEME_ELDY_BACKTABCARD1', $colorbacktabcard1, $user);
	$colorbacktabactive			= !getDolUserString('THEME_ELDY_ENABLE_PERSONALIZED', '', $user) ? getDolGlobalString('THEME_ELDY_BACKTABACTIVE', $colorbacktabactive) : getDolUserString('THEME_ELDY_BACKTABACTIVE', $colorbacktabactive, $user);
	$colorbacklineimpair1		= !getDolUserString('THEME_ELDY_ENABLE_PERSONALIZED', '', $user) ? getDolGlobalString('THEME_ELDY_LINEIMPAIR1', $colorbacklineimpair1) : getDolUserString('THEME_ELDY_LINEIMPAIR1', $colorbacklineimpair1, $user);
	$colorbacklineimpair2		= !getDolUserString('THEME_ELDY_ENABLE_PERSONALIZED', '', $user) ? getDolGlobalString('THEME_ELDY_LINEIMPAIR2', $colorbacklineimpair2) : getDolUserString('THEME_ELDY_LINEIMPAIR2', $colorbacklineimpair2, $user);
	$colorbacklinepair1			= !getDolUserString('THEME_ELDY_ENABLE_PERSONALIZED', '', $user) ? getDolGlobalString('THEME_ELDY_LINEPAIR1', $colorbacklinepair1) : getDolUserString('THEME_ELDY_LINEPAIR1', $colorbacklinepair1, $user);
	$colorbacklinepair2			= !getDolUserString('THEME_ELDY_ENABLE_PERSONALIZED', '', $user) ? getDolGlobalString('THEME_ELDY_LINEPAIR2', $colorbacklinepair2) : getDolUserString('THEME_ELDY_LINEPAIR2', $colorbacklinepair2, $user);
	$colorbacklinebreak			= !getDolUserString('THEME_ELDY_ENABLE_PERSONALIZED', '', $user) ? getDolGlobalString('THEME_ELDY_LINEBREAK', $colorbacklinebreak) : getDolUserString('THEME_ELDY_LINEBREAK', $colorbacklinebreak, $user);
	$colorbackbody				= !getDolUserString('THEME_ELDY_ENABLE_PERSONALIZED', '', $user) ? getDolGlobalString('THEME_ELDY_BACKBODY', $colorbackbody) : getDolUserString('THEME_ELDY_BACKBODY', $colorbackbody, $user);
	$colortexttitlenotab		= !getDolUserString('THEME_ELDY_ENABLE_PERSONALIZED', '', $user) ? getDolGlobalString('THEME_ELDY_TEXTTITLENOTAB', $colortexttitlenotab) : getDolUserString('THEME_ELDY_TEXTTITLENOTAB', $colortexttitlenotab, $user);
	$colortexttitle				= !getDolUserString('THEME_ELDY_ENABLE_PERSONALIZED', '', $user) ? getDolGlobalString('THEME_ELDY_TEXTTITLE', $colortexttitle) : getDolUserString('THEME_ELDY_TEXTTITLE', $colortexttitle, $user);
	$colortexttitlelink			= !getDolUserString('THEME_ELDY_ENABLE_PERSONALIZED', '', $user) ? getDolGlobalString('THEME_ELDY_TEXTTITLELINK', $colortexttitlelink) : getDolUserString('THEME_ELDY_TEXTTITLELINK', $colortexttitlelink, $user);
	$colortext					= !getDolUserString('THEME_ELDY_ENABLE_PERSONALIZED', '', $user) ? getDolGlobalString('THEME_ELDY_TEXT', $colortext) : getDolUserString('THEME_ELDY_TEXT', $colortext, $user);
	$colortextlink				= !getDolUserString('THEME_ELDY_ENABLE_PERSONALIZED', '', $user) ? getDolGlobalString('THEME_ELDY_TEXTLINK', $colortextlink) : getDolUserString('THEME_ELDY_TEXTLINK', $colortextlink, $user);

	// ===================== Normalisation + couleurs calculées (contrastes) =====================
	// Hover color
	$colorbacklinepairhover		= colorStringToArray($colorbline_hover);
	$colorbacklinepairchecked	= colorStringToArray($colorbline_checked);
	$colortopckeditor			= colorArrayToHex(colorStringToArray($colorbackhmenu1));
	setcookie('colortopckeditor', $colortopckeditor, time() + (86400 * 30), "/"); // 86400 = 1 day
	// Set text color to black or white
	$colorbackhmenu1			= join(',', colorStringToArray($colorbackhmenu1));	// Normalize value to 'x,y,z'
	$colortextbackhmenu			= txt_color($colorbackhmenu1);
	$colorbackvmenu1			= join(',', colorStringToArray($colorbackvmenu1));	// Normalize value to 'x,y,z'
	$colortextbackvmenu			= txt_color($colorbackvmenu1);
	$colorbacktitle1			= join(',', colorStringToArray($colorbacktitle1));	// Normalize value to 'x,y,z'
	$autocolorshadow			= txt_color($colorbacktitle1);	// $colorshadowtitle : contraste sur le fond des filtres (comportement d'origine, inchangé)
	$colorshadowtitle			= ($autocolorshadow == 'FFFFFF') ? '888888' : 'FFFFFF';
	if (!getDolGlobalString('THEME_ELDY_TEXTTITLE') && !getDolUserString('THEME_ELDY_TEXTTITLE', '', $user)) {
		// contraste auto calculé sur le VRAI fond des titres = $colorbtitle (OBLYON_COLOR_BTITLE), pas sur le fond des filtres
		$autocolortexttitle	= txt_color(join(',', colorStringToArray($colorbtitle)));
		$colortexttitle		= ($autocolortexttitle == '000000') ? '101010' : $autocolortexttitle;
	}
	$colorbacktabcard1	= join(',', colorStringToArray($colorbacktabcard1));	// Normalize value to 'x,y,z'
	$colortextbacktab	= txt_color($colorbacktabcard1);
	if ($colortextbacktab == '000000') {
		$colortextbacktab	= '111111';
	}
	// Format color value to match expected format (may be 'FFFFFF' or '255,255,255')
	$colortopbordertitle1	= join(',', colorStringToArray($colortopbordertitle1));
	$colorbacktabactive		= join(',', colorStringToArray($colorbacktabactive));
	$colorbacklineimpair1	= join(',', colorStringToArray($colorbacklineimpair1));
	$colorbacklineimpair2	= join(',', colorStringToArray($colorbacklineimpair2));
	$colorbacklinepair1		= join(',', colorStringToArray($colorbacklinepair1));
	$colorbacklinepair2		= join(',', colorStringToArray($colorbacklinepair2));
	if ($colorbacklinepairhover != '') {
		$colorbacklinepairhover	= join(',', colorStringToArray($colorbacklinepairhover));
	}
	if ($colorbacklinepairchecked != '') {
		$colorbacklinepairchecked	= join(',', colorStringToArray($colorbacklinepairchecked));
	}
	$colorbackbody			= join(',', colorStringToArray($colorbackbody));
	$colortexttitlenotab	= join(',', colorStringToArray($colortexttitlenotab));
	$colortexttitle			= join(',', colorStringToArray($colortexttitle));
	$colortext				= join(',', colorStringToArray($colortext));
	$colortextlink			= join(',', colorStringToArray($colortextlink));
	// ===================== Métriques du menu haut =====================
	$nbtopmenuentries		= $menumanager->showmenu('topnb');
	if ($conf->browser->layout == 'phone') {
		$nbtopmenuentries	= max($nbtopmenuentries, 10);
	}
	$minwidthtmenu		= 66;	/* minimum width for one top menu entry */
	$heightmenu			= 50;	/* height of top menu, part with image */
	$heightmenu2		= 49;	/* height of top menu, part with login  */
	$disableimages		= 0;
	$maxwidthloginblock	= 180;
	if (getDolGlobalString('THEME_TOPMENU_DISABLE_IMAGE')) {
		$disableimages		= 1;
		$maxwidthloginblock	= $maxwidthloginblock + 50;
		$minwidthtmenu		= 0;
	}
	if (getDolGlobalString('MAIN_USE_TOP_MENU_QUICKADD_DROPDOWN')) {
		$maxwidthloginblock = $maxwidthloginblock + 55;
	}
	if (getDolGlobalString('MAIN_USE_TOP_MENU_SEARCH_DROPDOWN')) {
		$maxwidthloginblock	= $maxwidthloginblock + 55;
	}
	if (isModEnabled('bookmark')) {
		$maxwidthloginblock	= $maxwidthloginblock + 55;
	}
	if (isModEnabled('multicompany')) {
		$maxwidthloginblock	= $maxwidthloginblock + 55;
	}

	// Rayon des arrondis (variable CSS --infras_radius, utilisee des le bloc :root de global.inc.php)
	$infras_radius	= getDolGlobalInt('THEME_ELDY_BORDER_RADIUS', 6);
	if ($infras_radius <= 0)	$infras_radius	= 6;	// valeur nulle => rayon visible par defaut

	require __DIR__.'/global.inc.php';

	if (is_object($db))	$db->close();