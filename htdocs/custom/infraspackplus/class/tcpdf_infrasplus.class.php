<?php
/* Copyright (C) 2025-2026	Lucky Ranasolonirina	- <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
 *	\file		infraspackplus/class/tcpdf_infrasplus.class.php
 *	\ingroup	InfraS
 *	\brief		TCPDF/TCPDI subclasses that fix the ColorFlag bug.
 *
 *	TCPDF uses an internal flag (ColorFlag) that is set to (FillColor != TextColor).
 *	When ColorFlag is false, TCPDF does not emit q/Q color operators around text,
 *	so text on new pages after automatic page breaks renders in default black instead
 *	of the configured text color.
 *
 *	This happens when HTML content contains a background-color CSS that, once parsed
 *	by TCPDF, produces a FillColor string identical to TextColor. The corrupted
 *	ColorFlag is then saved/restored across page breaks via getGraphicVars/setGraphicVars.
 *
 *	Fix: force ColorFlag to always be true so color operators are always emitted.
 */


/**
 *	Class TCPDF_InfraS - extends TCPDF with ColorFlag fix and page buffer reorder
 */
class TCPDF_InfraS extends TCPDF
{
	/**
	 *	Override setColor to always force ColorFlag = true
	 *	@see TCPDF::setColor()
	 */
	public function setColor($type, $col1 = 0, $col2 = -1, $col3 = -1, $col4 = -1, $ret = false, $name = '')
	{
		$result = parent::setColor($type, $col1, $col2, $col3, $col4, $ret, $name);
		$this->ColorFlag = true;
		return $result;
	}

	/**
	 *	Override setSpotColor to always force ColorFlag = true
	 *	@see TCPDF::setSpotColor()
	 */
	public function setSpotColor($type, $name, $tint = 100)
	{
		$result = parent::setSpotColor($type, $name, $tint);
		$this->ColorFlag = true;
		return $result;
	}

	/**
	 *	Override setGraphicVars to always force ColorFlag = true after restoring state
	 *	This is critical for automatic page breaks where saved state may have ColorFlag = false
	 *	@see TCPDF::setGraphicVars()
	 */
	protected function setGraphicVars($gvars, $extended = false)
	{
		parent::setGraphicVars($gvars, $extended);
		$this->ColorFlag = true;
	}

	/**
	 *	Save and clear the current page content buffer.
	 *	When TCPDF auto-breaks to a new page, it writes overflow text BEFORE the
	 *	calling code can draw the background watermark.  This method saves that
	 *	premature content so the watermark can be drawn first (correct z-order).
	 *
	 *	@return string The saved page content
	 */
	public function liftPageContent()
	{
		$page = $this->page;
		$content = isset($this->pages[$page]) ? $this->pages[$page] : '';
		$this->pages[$page] = '';
		$this->pagelen[$page] = 0;
		$this->intmrk[$page] = 0;
		$this->bordermrk[$page] = 0;
		$this->cntmrk[$page] = 0;
		return $content;
	}

	/**
	 *	Append previously saved page content back after watermark and header.
	 *
	 *	@param	string	$content	The saved content to append
	 *	@return void
	 */
	public function dropPageContent($content)
	{
		if ($content === '') {
			return;
		}
		$page = $this->page;
		$this->pages[$page] .= $content;
		$this->pagelen[$page] = strlen($this->pages[$page]);
	}
}


/**
 *	Class TCPDI_InfraS - extends TCPDI with the same ColorFlag fix
 */
if (class_exists('TCPDI')) {
	class TCPDI_InfraS extends TCPDI
	{
		/**
		 *	Override setColor to always force ColorFlag = true
		 *	@see TCPDF::setColor()
		 */
		public function setColor($type, $col1 = 0, $col2 = -1, $col3 = -1, $col4 = -1, $ret = false, $name = '')
		{
			$result = parent::setColor($type, $col1, $col2, $col3, $col4, $ret, $name);
			$this->ColorFlag = true;
			return $result;
		}

		/**
		 *	Override setSpotColor to always force ColorFlag = true
		 *	@see TCPDF::setSpotColor()
		 */
		public function setSpotColor($type, $name, $tint = 100)
		{
			$result = parent::setSpotColor($type, $name, $tint);
			$this->ColorFlag = true;
			return $result;
		}

		/**
		 *	Override setGraphicVars to always force ColorFlag = true after restoring state
		 *	@see TCPDF::setGraphicVars()
		 */
		protected function setGraphicVars($gvars, $extended = false)
		{
			parent::setGraphicVars($gvars, $extended);
			$this->ColorFlag = true;
		}

		/**
		 *	Save and clear the current page content buffer.
		 *	@see TCPDF_InfraS::liftPageContent()
		 *	@return string The saved page content
		 */
		public function liftPageContent()
		{
			$page = $this->page;
			$content = isset($this->pages[$page]) ? $this->pages[$page] : '';
			$this->pages[$page] = '';
			$this->pagelen[$page] = 0;
			$this->intmrk[$page] = 0;
			$this->bordermrk[$page] = 0;
			$this->cntmrk[$page] = 0;
			return $content;
		}

		/**
		 *	Append previously saved page content back after watermark and header.
		 *	@see TCPDF_InfraS::dropPageContent()
		 *	@param	string	$content	The saved content to append
		 *	@return void
		 */
		public function dropPageContent($content)
		{
			if ($content === '') {
				return;
			}
			$page = $this->page;
			$this->pages[$page] .= $content;
			$this->pagelen[$page] = strlen($this->pages[$page]);
		}
	}
}
