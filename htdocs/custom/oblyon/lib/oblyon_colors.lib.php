<?php
	/************************************************
	* Copyright (C) 2026   Sylvain Legrand   <contact@infras.fr>   InfraS - <https://www.infras.fr>
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
	* 	\file		../oblyon/lib/oblyon_colors.lib.php
	* 	\ingroup	oblyon
	* 	\brief		Colours per user (3.6.0) : one reading point for the theme, list of the colour constants, helpers of the user tab
	*
	*	Storage : llx_user_param (dol_set_user_param) with the flag OBLYON_USER_COLORS = 1 and one row per colour constant
	*	(full snapshot of the instance colours at the time the user ticks the box, then edited by him). The theme reads every
	*	colour through oblyon_color_setting() : the personal value when the flag is set and the value looks like a colour,
	*	else the instance value (llx_const). Pages without a user (login, password) always get the instance values.
	************************************************/

	/**
	*	Are the personal colours of a user active ? (flag OBLYON_USER_COLORS, or the historical THEME_ELDY_ENABLE_PERSONALIZED user param)
	*
	*	@param		User|null	$tmpuser	User (null = current user)
	*	@return		bool
	**/
	function oblyon_user_colors_enabled($tmpuser = null)
	{
		if (empty($tmpuser)) {
			global $user;
			$tmpuser	= $user;
		}
		if (empty($tmpuser) || empty($tmpuser->id) || empty($tmpuser->conf))	return false;	// no user loaded (NOLOGIN pages) : instance colours
		return (getDolUserInt('OBLYON_USER_COLORS', 0, $tmpuser) > 0 || getDolUserString('THEME_ELDY_ENABLE_PERSONALIZED', '', $tmpuser) !== '');
	}

	/**
	*	Constants the theme normalizes with colorStringToArray() (style.css.php) : they also accept the 'r,g,b' form
	*	(written by the core user/param_ihm.php for THEME_ELDY_TOPMENU_BACK1 / THEME_ELDY_BACKTITLE1). Every other constant is printed as is in the CSS.
	*
	*	@return		array		Constant names
	**/
	function oblyon_colors_rgb_allowed()
	{
		return array('THEME_ELDY_TOPMENU_BACK1', 'THEME_ELDY_VERMENU_BACK1', 'THEME_ELDY_TOPBORDER_TITLE1', 'THEME_ELDY_BACKTITLE1', 'THEME_ELDY_BACKTABCARD1', 'THEME_ELDY_BACKTABACTIVE',
					'THEME_ELDY_LINEIMPAIR1', 'THEME_ELDY_LINEIMPAIR2', 'THEME_ELDY_LINEPAIR1', 'THEME_ELDY_LINEPAIR2', 'THEME_ELDY_LINEBREAK', 'THEME_ELDY_BACKBODY',
					'THEME_ELDY_TEXTTITLENOTAB', 'THEME_ELDY_TEXTTITLE', 'THEME_ELDY_TEXTTITLELINK', 'THEME_ELDY_TEXT', 'THEME_ELDY_TEXTLINK', 'THEME_ELDY_USE_HOVER', 'THEME_ELDY_USE_CHECKED',
					'OBLYON_COLOR_LOGIN_BCKGRD');
	}

	/**
	*	Is a value a colour the theme can use for this constant ? '#RRGGBB' (6 digits : the core colorStringToArray() does not read 3 digits), '#' alone
	*	(inherit convention of the menu texts), or 'r,g,b' (0-255) for the constants the theme normalizes (oblyon_colors_rgb_allowed()).
	*	The core also writes THEME_ELDY_USE_HOVER / THEME_ELDY_USE_CHECKED = 0 / 1 in llx_user_param : those are not colours for Oblyon and are ignored.
	*
	*	@param		string	$value		Value
	*	@param		string	$name		Constant name ('' = unknown : only the '#' forms are accepted)
	*	@return		bool
	**/
	function oblyon_color_is_valid($value, $name = '')
	{
		if (preg_match('/^#([0-9a-f]{6})?$/i', $value))	return true;
		if ($name !== '' && in_array($name, oblyon_colors_rgb_allowed()) && preg_match('/^(\d{1,3}),(\d{1,3}),(\d{1,3})$/', $value, $reg)) {
			return ((int) $reg[1] <= 255 && (int) $reg[2] <= 255 && (int) $reg[3] <= 255);
		}
		return false;
	}

	/**
	*	Colour setting : the personal value of the user when his personal colours are on and the value looks like a colour, else the instance value
	*
	*	@param		string		$name		Constant name
	*	@param		string		$default	Default when neither the user nor the instance defines it
	*	@param		User|null	$tmpuser	User (null = current user)
	*	@return		string
	**/
	function oblyon_color_setting($name, $default = '', $tmpuser = null)
	{
		if (empty($tmpuser)) {
			global $user;
			$tmpuser	= $user;
		}
		if (oblyon_user_colors_enabled($tmpuser) && isset($tmpuser->conf->$name)) {
			$value	= (string) $tmpuser->conf->$name;
			if (oblyon_color_is_valid($value, $name))	return $value;
		}
		return getDolGlobalString($name, $default);
	}

	/**
	*	Colour constants offered on the user tab, grouped like the Colors tab of the module (admin/colors.php) then the dashboard tiles.
	*	Group key = lang key. The top / left menu groups are swapped when the menus are inverted (same as admin/colors.php).
	*
	*	@return		array		group lang key => array of constant names
	**/
	function oblyon_user_colors_list()
	{
		$top	= array('OBLYON_COLOR_TOPMENU_BCKGRD', 'OBLYON_COLOR_TOPMENU_BCKGRD_HOVER', 'OBLYON_COLOR_TOPMENU_TXT', 'OBLYON_COLOR_TOPMENU_TXT_ACTIVE', 'OBLYON_COLOR_TOPMENU_TXT_HOVER');
		$left	= array('OBLYON_COLOR_LEFTMENU_BCKGRD', 'OBLYON_COLOR_LEFTMENU_BCKGRD_HOVER', 'OBLYON_COLOR_LEFTMENU_TXT', 'OBLYON_COLOR_LEFTMENU_TXT_ACTIVE', 'OBLYON_COLOR_LEFTMENU_TXT_HOVER');
		$list	= array();
		if (!getDolGlobalString('MAIN_MENU_INVERT')) {
			$list['TopMenu']	= $top;
			$list['LeftMenu']	= $left;
		} else {
			$list['LeftMenu']	= $top;
			$list['TopMenu']	= $left;
		}
		$list['Buttons']						= array('THEME_ELDY_BTNACTION', 'OBLYON_COLOR_BUTTON_ACTION2', 'THEME_ELDY_TEXTBTNACTION', 'OBLYON_COLOR_BUTTON_DELETE1', 'OBLYON_COLOR_BUTTON_DELETE2');
		$list['OblyonColorGrpMessages']			= array('OBLYON_COLOR_INFO_BORDER', 'OBLYON_COLOR_INFO_BCKGRD', 'OBLYON_COLOR_INFO_TEXT', 'OBLYON_COLOR_WARNING_BORDER', 'OBLYON_COLOR_WARNING_BCKGRD', 'OBLYON_COLOR_WARNING_TEXT',
														'OBLYON_COLOR_ERROR_BORDER', 'OBLYON_COLOR_ERROR_BCKGRD', 'OBLYON_COLOR_ERROR_TEXT', 'OBLYON_COLOR_NOTIF_INFO_BCKGRD', 'OBLYON_COLOR_NOTIF_INFO_TEXT',
														'OBLYON_COLOR_NOTIF_WARNING_BCKGRD', 'OBLYON_COLOR_NOTIF_WARNING_TEXT', 'OBLYON_COLOR_NOTIF_ERROR_BCKGRD', 'OBLYON_COLOR_NOTIF_ERROR_TEXT');
		$list['OblyonColorGrpBackgrounds']		= array('OBLYON_COLOR_MAIN', 'OBLYON_COLOR_BCKGRD', 'OBLYON_COLOR_INPUT_BCKGRD', 'OBLYON_COLOR_INPUT_ADD_BCKGRD', 'OBLYON_COLOR_LOGO_BCKGRD', 'OBLYON_COLOR_LOGIN_BCKGRD');
		$list['OblyonColorGrpText']				= array('THEME_ELDY_TEXT', 'THEME_ELDY_TEXTLINK');
		$list['OblyonColorGrpTitles']			= array('OBLYON_COLOR_BTITLE', 'OBLYON_COLOR_STITLE', 'THEME_ELDY_TEXTTITLE', 'THEME_ELDY_TEXTTITLENOTAB', 'THEME_ELDY_TOPBORDER_TITLE1', 'THEME_ELDY_BACKTITLE1');
		$list['OblyonColorGrpTabs']				= array('THEME_ELDY_BACKTABACTIVE', 'THEME_ELDY_BACKTABCARD1', 'OBLYON_COLOR_TEXTTABACTIVE');
		$list['OblyonColorGrpLines']			= array('OBLYON_COLOR_BLINE', 'OBLYON_COLOR_FLINE', 'THEME_ELDY_USE_HOVER', 'THEME_ELDY_USE_CHECKED', 'OBLYON_COLOR_FLINE_HOVER',
														'THEME_ELDY_LINEIMPAIR1', 'THEME_ELDY_LINEIMPAIR2', 'THEME_ELDY_LINEPAIR1', 'THEME_ELDY_LINEPAIR2', 'THEME_ELDY_LINEBREAK');
		$list['OblyonColorGrpTotal']			= array('OBLYON_COLOR_BTOTAL', 'OBLYON_COLOR_FTOTAL');
		$list['OblyonColorGrpDate']				= array('OBLYON_COLOR_FDATE_DEFAULT', 'OBLYON_COLOR_FDATE_SELECTED');
		$list['OblyonColorGrpNatures']			= array('THEME_ELDY_PROSPECTBACK', 'THEME_ELDY_CUSTOMERBACK', 'THEME_ELDY_VENDORBACK', 'THEME_ELDY_USERBACK', 'THEME_ELDY_COLORNATURE');
		$list['OblyonColorGrpMembers']			= array('THEME_ELDY_MEMBER_COMPANYBACK', 'THEME_ELDY_MEMBER_INDIVIDUALBACK', 'THEME_ELDY_COLORMEMBER');
		$list['OblyonColorGrpDashboard']		= array('OBLYON_COLOR_BOX_SHADOW', 'OBLYON_COLOR_INFOBOX_BCKGRD1', 'OBLYON_COLOR_INFOBOX_BCKGRD2', 'OBLYON_COLOR_BORDER_ACTIONCOLUMN');
		$list['OblyonColorGrpAmounts']			= array('OBLYON_COLOR_AMOUNT_REMAIN', 'OBLYON_COLOR_AMOUNT_PAID', 'OBLYON_COLOR_AMOUNT_UNPAID');
		$list['OblyonColorGrpStatus']			= array('OBLYON_COLOR_STATUS_SUCCESS', 'OBLYON_COLOR_STATUS_INFO', 'OBLYON_COLOR_STATUS_WARNING', 'OBLYON_COLOR_STATUS_DANGER', 'OBLYON_COLOR_STATUS_PRIMARY', 'OBLYON_COLOR_PROGRESSBAR', 'OBLYON_COLOR_TIMELINEITEM');
		$list['OblyonColorGrpWeather']			= array('OBLYON_COLOR_WEATHER_LEVEL0', 'OBLYON_COLOR_WEATHER_LEVEL1', 'OBLYON_COLOR_WEATHER_LEVEL2', 'OBLYON_COLOR_WEATHER_LEVEL3', 'OBLYON_COLOR_WEATHER_LEVEL4', 'OBLYON_COLOR_INFOBOX_UPDATE');
		$list['OblyonColorGrpAutocomplete']		= array('OBLYON_COLOR_AUTOCOMPLETE_BCKGRD', 'OBLYON_COLOR_AUTOCOMPLETE_TEXT');
		$list['OblyonColorGrpMultiselect']		= array('OBLYON_COLOR_CHIP_BCKGRD', 'OBLYON_COLOR_CHIP_TEXT', 'OBLYON_COLOR_RESULT_BCKGRD', 'OBLYON_COLOR_RESULT_TEXT');
		$list['OblyonColorGrpInfobox']			= array('OBLYON_INFOXBOX_BACKGROUND', 'OBLYON_INFOXBOX_WEATHER_COLOR', 'OBLYON_INFOXBOX_ACTION_COLOR', 'OBLYON_INFOXBOX_PROJECT_COLOR',
														'OBLYON_INFOXBOX_CUSTOMER_PROPAL_COLOR', 'OBLYON_INFOXBOX_CUSTOMER_ORDER_COLOR', 'OBLYON_INFOXBOX_CUSTOMER_INVOICE_COLOR',
														'OBLYON_INFOXBOX_SUPPLIER_PROPAL_COLOR', 'OBLYON_INFOXBOX_SUPPLIER_ORDER_COLOR', 'OBLYON_INFOXBOX_SUPPLIER_INVOICE_COLOR',
														'OBLYON_INFOXBOX_CONTRAT_COLOR', 'OBLYON_INFOXBOX_BANK_COLOR', 'OBLYON_INFOXBOX_ADHERENT_COLOR', 'OBLYON_INFOXBOX_EXPENSEREPORT_COLOR',
														'OBLYON_INFOXBOX_HOLIDAY_COLOR', 'OBLYON_INFOXBOX_TICKET_COLOR', 'OBLYON_INFOXBOX_MRP_COLOR');
		$list['OblyonColorGrpEldyOther']		= array('THEME_ELDY_TOPMENU_BACK1', 'THEME_ELDY_VERMENU_BACK1', 'THEME_ELDY_BACKBODY', 'THEME_ELDY_TEXTTITLELINK');
		return $list;
	}

	/**
	*	Flat list of the colour constants of the user tab (keys of the personal snapshot)
	*
	*	@return		array		Constant names
	**/
	function oblyon_user_colors_keys()
	{
		$keys	= array();
		foreach (oblyon_user_colors_list() as $group => $names)	$keys	= array_merge($keys, $names);
		return $keys;
	}

	/**
	*	Label of a colour constant on the user tab : the lang key is the constant name ; top / left labels are swapped when the menus are inverted (as admin/colors.php)
	*
	*	@param		string	$name		Constant name
	*	@return		string				Translated label
	**/
	function oblyon_user_color_label($name)
	{
		global $langs;

		$transkey	= $name;
		if (getDolGlobalString('MAIN_MENU_INVERT')) {
			if (strpos($name, 'OBLYON_COLOR_TOPMENU_') === 0)		$transkey	= str_replace('TOP', 'LEFT', $name);
			elseif (strpos($name, 'OBLYON_COLOR_LEFTMENU_') === 0)	$transkey	= str_replace('LEFT', 'TOP', $name);
		}
		return $langs->trans($transkey);
	}

	/**
	*	Small colour swatch + value for the read mode of the user tab
	*
	*	@param		string	$value		Colour ('#RRGGBB', '#', '' ...)
	*	@param		string	$name		Constant name (for the 'r,g,b' tolerance)
	*	@return		string				HTML
	**/
	function oblyon_color_swatch($value, $name = '')
	{
		$value	= (string) $value;
		if ($value === '' || $value === '#')	return '<span class="opacitymedium">-</span>';
		if (!oblyon_color_is_valid($value, $name))	return dol_escape_htmltag($value);
		$css	= (strpos($value, ',') !== false ? 'rgb('.$value.')' : $value);
		return '<span class="oblyon-color-swatch" style="background:'.dol_escape_htmltag($css).'"></span> <span class="opacitymedium">'.dol_escape_htmltag(strtoupper($value)).'</span>';
	}
