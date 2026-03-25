<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand 		- <contact@infras.fr>	InfraS - <https://www.infras.fr>
	* Copyright (C) 2025-2026	Lucky Ranasolonirina	- <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	************************************************/


	/************************************************
	*	Class TCPDF_InfraS - extends TCPDF with ColorFlag fix and page buffer reorder
	************************************************/
	class TCPDF_InfraS extends TCPDF
	{
		/**
		*	Override setColor to always force ColorFlag = true
		*	@see TCPDF::setColor()
		**/
		public function setColor($type, $col1 = 0, $col2 = -1, $col3 = -1, $col4 = -1, $ret = false, $name = '')
		{
			$result = parent::setColor($type, $col1, $col2, $col3, $col4, $ret, $name);
			$this->ColorFlag = true;
			return $result;
		}

		/**
		*	Override setSpotColor to always force ColorFlag = true
		*	@see TCPDF::setSpotColor()
		**/
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
		**/
		protected function setGraphicVars($gvars, $extended = false)
		{
			parent::setGraphicVars($gvars, $extended);
			$this->ColorFlag = true;
		}

		/**
		*	Public wrapper for getGraphicVars (protected in TCPDF).
		*	Permet au code externe de sauvegarder l'état graphique complet.
		*
		*	@return	array	Tableau de variables graphiques
		**/
		public function saveGraphicVars()
		{
			return $this->getGraphicVars();
		}

		/**
		*	Public wrapper for setGraphicVars (protected in TCPDF).
		*	Permet au code externe de restaurer un état graphique sauvegardé via saveGraphicVars().
		*
		*	@param	array	$gvars			Tableau de variables graphiques obtenu via saveGraphicVars()
		*	@param	bool	$extended		Mode étendu
		*	@return	void
		**/
		public function restoreGraphicVars($gvars, $extended = false)
		{
			$this->setGraphicVars($gvars, $extended);
		}

		/**
		*	Extraire le contenu texte du buffer de la page courante.
		*	Lors d'un saut de page automatique dans writeHTMLCell, TCPDF écrit le texte
		*	de débordement AVANT que le code appelant puisse dessiner le filigrane et
		*	l'en-tête. Cette méthode extrait ce contenu prématuré (situé après intmrk)
		*	afin de pouvoir insérer filigrane/en-tête au bon endroit dans le z-order.
		*
		*	@return string Le contenu texte extrait du buffer
		**/
		public function liftPageContent()
		{
			$page = $this->page;
			$mark = isset($this->intmrk[$page]) ? $this->intmrk[$page] : 0;
			$buffer = $this->getPageBuffer($page);
			// Extraire uniquement le contenu après intmrk (texte du writeHTMLCell)
			$content = ($buffer !== false) ? substr($buffer, $mark) : '';
			// Tronquer le buffer en conservant le setup initial de startPage (avant intmrk)
			$this->setPageBuffer($page, ($buffer !== false) ? substr($buffer, 0, $mark) : '');
			// intmrk, bordermrk, cntmrk restent à leur position (fin du setup initial)
			return $content;
		}

		/**
		*	Réinjecter le contenu texte sauvegardé après filigrane et en-tête.
		*	Met à jour les marqueurs internes pour que les opérations Cell suivantes
		*	s'insèrent correctement après l'en-tête (entre en-tête et contenu texte).
		*
		*	@param	string	$content	Le contenu sauvegardé par liftPageContent()
		*	@return void
		**/
		public function dropPageContent($content)
		{
			if ($content === '') {
				return;
			}
			$page = $this->page;
			// Avancer les marqueurs à la fin du buffer actuel (après filigrane + en-tête)
			$currentLen = $this->pagelen[$page];
			$this->intmrk[$page] = $currentLen;
			$this->bordermrk[$page] = $currentLen;
			$this->cntmrk[$page] = $currentLen;
			// Ajouter le contenu texte après en-tête/filigrane
			$this->setPageBuffer($page, $content, true);
		}
	}


	/************************************************
	*	Class TCPDI_InfraS - extends TCPDI with the same ColorFlag fix
	************************************************/
	if (class_exists('TCPDI')) {
		class TCPDI_InfraS extends TCPDI
		{
			/**
			*	Override setColor to always force ColorFlag = true
			*	@see TCPDF::setColor()
			**/
			public function setColor($type, $col1 = 0, $col2 = -1, $col3 = -1, $col4 = -1, $ret = false, $name = '')
			{
				$result = parent::setColor($type, $col1, $col2, $col3, $col4, $ret, $name);
				$this->ColorFlag = true;
				return $result;
			}

			/**
			*	Override setSpotColor to always force ColorFlag = true
			*	@see TCPDF::setSpotColor()
			**/
			public function setSpotColor($type, $name, $tint = 100)
			{
				$result = parent::setSpotColor($type, $name, $tint);
				$this->ColorFlag = true;
				return $result;
			}

			/**
			*	Override setGraphicVars to always force ColorFlag = true after restoring state
			*	@see TCPDF::setGraphicVars()
			**/
			protected function setGraphicVars($gvars, $extended = false)
			{
				parent::setGraphicVars($gvars, $extended);
				$this->ColorFlag = true;
			}

			/**
			*	Public wrapper for getGraphicVars (protected in TCPDF).
			*	@see TCPDF_InfraS::saveGraphicVars()
			*	@return	array	Tableau de variables graphiques
			**/
			public function saveGraphicVars()
			{
				return $this->getGraphicVars();
			}

			/**
			*	Public wrapper for setGraphicVars (protected in TCPDF).
			*	@see TCPDF_InfraS::restoreGraphicVars()
			*	@param	array	$gvars			Tableau de variables graphiques obtenu via saveGraphicVars()
			*	@param	bool	$extended		Mode étendu
			*	@return	void
			**/
			public function restoreGraphicVars($gvars, $extended = false)
			{
				$this->setGraphicVars($gvars, $extended);
			}

			/**
			*	Extraire le contenu texte du buffer de la page courante.
			*	@see TCPDF_InfraS::liftPageContent()
			*	@return string Le contenu texte extrait du buffer
			**/
			public function liftPageContent()
			{
				$page = $this->page;
				$mark = isset($this->intmrk[$page]) ? $this->intmrk[$page] : 0;
				$buffer = $this->getPageBuffer($page);
				// Extraire uniquement le contenu après intmrk (texte du writeHTMLCell)
				$content = ($buffer !== false) ? substr($buffer, $mark) : '';
				// Tronquer le buffer en conservant le setup initial de startPage (avant intmrk)
				$this->setPageBuffer($page, ($buffer !== false) ? substr($buffer, 0, $mark) : '');
				// intmrk, bordermrk, cntmrk restent à leur position (fin du setup initial)
				return $content;
			}

			/**
			*	Réinjecter le contenu texte sauvegardé après filigrane et en-tête.
			*	@see TCPDF_InfraS::dropPageContent()
			*	@param	string	$content	Le contenu sauvegardé par liftPageContent()
			*	@return void
			**/
			public function dropPageContent($content)
			{
				if ($content === '') {
					return;
				}
				$page = $this->page;
				// Avancer les marqueurs à la fin du buffer actuel (après filigrane + en-tête)
				$currentLen = $this->pagelen[$page];
				$this->intmrk[$page] = $currentLen;
				$this->bordermrk[$page] = $currentLen;
				$this->cntmrk[$page] = $currentLen;
				// Ajouter le contenu texte après en-tête/filigrane
				$this->setPageBuffer($page, $content, true);
			}
		}
	}
