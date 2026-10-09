<?php
	/************************************************
	* Copyright (C) 2026	Fallinah Ranasolonirina	- <contact@infras.fr>	InfraS - <https://www.infras.fr>
	*
	* This program is free software: you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation, either version 3 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program.	If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	* 	\file		./infrashelpdesk/class/actions_infrashelpdesk.class.php
	* 	\ingroup	InfraS
	* 	\brief		Hook handler class for module InfraSHelpdesk
	*
	*	SKELETON: This class provides hook method examples.
	*	Add your hook contexts in the module descriptor (module_parts => hooks).
	*	Then implement the corresponding methods here.
	*	See https://wiki.dolibarr.org/index.php/Hooks
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	dol_include_once('/infrashelpdesk/core/lib/infrashelpdeskAdmin.lib.php');

	/************************************************
	* Class Actionsinfrashelpdesk - Hook handler
	*
	************************************************/
	class Actionsinfrashelpdesk
	{
		/** @var DoliDB Database handler */
		public $db;
		/** @var array Hook results. Propagated to $hookmanager->resArray for later reuse */
		public $results = array();
		/** @var string String displayed by executeHook() immediately after return */
		public $resprints;
		/** @var array Errors */
		public $errors = array();

		/**
		* Constructor
		*
		* @param	DoliDB		$db		Database handler
		* @return	void
		*/
		public function __construct($db)
		{
			$this->db = $db;
		}

		/**
		* When login (../main.inc.php)
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error
		**/
		public function afterLogin($parameters, &$object, &$action, $hookmanager)
		{
			global $langs;

			$currentversion	= array();
			$currentversion	= infrashelpdesk_getLocalVersionMinDoli('infrashelpdesk');
			if (!getDolGlobalString('INFRASHELPDESK_DISABLE_CHECK_VERSION_MAX', '') && version_compare(DOL_VERSION, $currentversion[4], '>')) {
				setEventMessages($langs->trans('InfraSHelpdeskWarningMaxVersion', DOL_VERSION, $currentversion[0], $currentversion[4]), null, 'warnings');
			}
			return 0;
		}

		/**
		* Get label of a hook context, loaded by the current page (ex : interventiondao -> intervention -> key 'Intervention').
		*
		* @param	string		$context	Hook context name
		* @return	string					Translated label or raw context name
		**/
		private function getContextLabel($context)
		{
			global $langs;

			static $lowerkeys	= null;
			if ($lowerkeys === null) {	// case-insensitive index of the translation keys loaded by the current page
				$lowerkeys	= array();
				foreach ((array) $langs->tab_translate as $key => $value) {
					$lowerkeys[strtolower($key)]	= $key;
				}
			}
			$roots		= array($context);
			$stripped	= $context;
			$suffixes	= array('dao', 'card', 'list', 'index', 'agenda', 'note', 'document', 'contact', 'ldap', 'partnership', 'stats');
			$changed	= true;
			while ($changed) {	// progressively strip technical suffixes (ex : membertypeldapcard -> membertypeldap -> membertype)
				$changed	= false;
				foreach ($suffixes as $suffix) {
					if (strlen($stripped) > strlen($suffix) && substr($stripped, -strlen($suffix)) === $suffix) {
						$stripped	= substr($stripped, 0, -strlen($suffix));
						$roots[]	= $stripped;
						$changed	= true;
					}
				}
			}
			foreach ($roots as $root) {
				if (isset($lowerkeys[strtolower($root)])) {
					return $langs->transnoentities($lowerkeys[strtolower($root)]);
				}
			}
			return $context;
		}

		/**
		* Get display label of a wiki URL from its chapter or sub-chapter slug (ex : /chapter/08-commerce -> COMMERCE, /page/82-propositions-commerciales -> Propositions commerciales).
		*
		* @param	string		$url		Wiki URL (relative or absolute)
		* @param	string		$context	Hook context name (fallback label)
		* @return	string					Chapter label (uppercase), sub-chapter label, or context label
		**/
		private function getWikiLabel($url, $context)
		{
			if (preg_match('#/chapter/([^/?\#]+)#', $url, $reg)) {
				return dol_strtoupper(str_replace('-', ' ', preg_replace('/^\d+-/', '', $reg[1])));
			}
			if (preg_match('#/page/([^/?\#]+)#', $url, $reg)) {
				return ucfirst(str_replace('-', ' ', preg_replace('/^\d+-/', '', $reg[1])));
			}
			return $this->getContextLabel($context);
		}

		/**
		* Common footer of all pages (../core/lib/functions.lib.php). Injects the configuration of the floating context button : current page hook
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error
		**/
		public function printCommonFooter($parameters, &$object, &$action, $hookmanager)
		{
			global $conf, $langs, $user;

			if (empty($user->id)) {	// public pages or not logged
				return 0;
			}
			if (empty($user->admin) && !getDolGlobalInt('INFRASHELPDESK_BUTTON_FOR_ALL_USERS', 0) && empty($user->hasRight('infrashelpdesk', 'paramInfraSHelpDeskBtn'))) {	// floating button restricted to users with the dedicated permission
				return 0;
			}
			$langs->load('infrashelpdesk@infrashelpdesk');
			$contexts		= is_array($hookmanager->contextarray) ? array_values($hookmanager->contextarray) : array();
			// Récupère la liste des contextes de hook actifs sur la page courante
			$pagefunctions	= array();
			$scriptfile		= empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
			if (!empty($scriptfile) && is_readable($scriptfile)) {
				$pagecontent		= file_get_contents($scriptfile);
				if ($pagecontent !== false && preg_match_all('/executeHooks\(\s*[\'"]([A-Za-z0-9_]+)[\'"]/', $pagecontent, $reg)) {
					$pagefunctions	= array_values(array_unique($reg[1]));
				}
			}
			$modules		= array();
			$hookinstances	= array();
			// Recherche des modules externes qui s'accrochent à ces contextes
			if (!empty($conf->modules_parts['hooks']) && is_array($conf->modules_parts['hooks'])) {
				foreach ($conf->modules_parts['hooks'] as $module => $hookcontexts) {
					if ($module == 'infrashelpdesk') {
						continue;
					}
					if (!is_dir(DOL_DOCUMENT_ROOT.'/custom/'.$module)) {
						continue;
					}
					$hookcontexts	= is_array($hookcontexts) ? $hookcontexts : explode(':', $hookcontexts);
					$isall			= in_array('all', $hookcontexts) ? 1 : 0;
					$matched		= $isall ? $contexts : array_values(array_intersect($hookcontexts, $contexts));
					if (empty($matched)) {	// Aucun hooks déclarées ne correspondent aux contextes de la page
						continue;
					}
					$instance	= null;
					foreach ($hookmanager->hooks as $hookedcontext => $modulehooks) {
						if (!empty($modulehooks[$module]) && is_object($modulehooks[$module])) {
							$instance	= $modulehooks[$module];
							break;
						}
					}
					if (is_object($instance)) {
						$hookinstances[$module]	= $instance;	// Récupère l'instance PHP réelle du module et ajoute le module à la liste des candidats.
					}
					$modules[]	= array('name' => $module, 'all' => $isall, 'contexts' => $matched);
				}
			}
			// Construction des URL de page/wiki pour la recherche en base
			$wiki			= array();
			$dicthooks		= array();
			$moduleurls		= array();
			$wikiprefix		= '';
			$relpage		= $_SERVER['PHP_SELF'];
			if (defined('DOL_URL_ROOT') && DOL_URL_ROOT != '' && strpos($relpage, DOL_URL_ROOT) === 0) {	// chemin relatif propre
				$relpage	= substr($relpage, strlen(DOL_URL_ROOT));
			}
			$relpage		= ltrim($relpage, '/');
			$params			= array();
			if (!empty($relpage)) {
				$wikiprefix	= getDolGlobalString('INFRASHELPDESK_PREFIX_LINK_WIKI_DOC', '');
				$bases		= array($relpage);
				if (strpos($relpage, 'custom/') !== 0) {
					$bases[]	= 'custom/'.$relpage;
				}
				$leftmenu		= GETPOST('leftmenu', 'aZ09');
				$contextpage	= GETPOST('contextpage', 'aZ09');
				foreach ($bases as $base) {
					if (!empty($contextpage)) {
						$params[]	= $base.'?contextpage='.$contextpage;
					}
					if (!empty($leftmenu)) {
						$params[]	= $base.'?leftmenu='.$leftmenu;
					}
					$params[]	= $base;
				}
			}
			// Requête SQL unique pour récupérer les liens wiki/doc
			$modulenames	= array_column($modules, 'name');
			$whereparts		= array();
			if (!empty($params)) {
				$sqlpages	= array();
				foreach ($params as $param) {
					$sqlpages[]	= "'".$this->db->escape($param)."'";
				}
				$whereparts[]	= 'page IN ('.implode(', ', $sqlpages).')';
			}
			if (!empty($modulenames)) {
				$likes	= array();
				foreach ($modulenames as $modulename) {
					$likes[]	= "page LIKE 'custom/".$this->db->escape($this->db->escapeforlike($modulename))."/%'";
				}
				$whereparts[]	= '('.implode(' OR ', $likes).')';
			}
			if (!empty($whereparts)) {
				$sql	= 'SELECT page, context, hooks, url_page, url_wiki FROM '.$this->db->prefix().'c_infrashelpdesk_ctxurl';
				$sql	.= ' WHERE active = 1 AND ('.implode(' OR ', $whereparts).')';
				$sql	.= ' AND entity IN ('.getEntity('c_infrashelpdesk_ctxurl').')';
				$resql	= $this->db->query($sql);
				if ($resql) {
					$rowsbypage	= array();
					while ($obj = $this->db->fetch_object($resql)) {
						if (in_array($obj->page, $params)) {	// ligne = page/contexte courant -> alimente wiki + dicthooks
							$rowsbypage[$obj->page][]	= $obj;
						}
						foreach ($modulenames as $modulename) {	// ligne = page propre à un module name -> alimente son url de doc
							if (isset($moduleurls[$modulename]) || strpos($obj->page, 'custom/'.$modulename.'/') !== 0) {
								continue;
							}
							foreach (array($obj->url_wiki, $obj->url_page) as $url) {	// book (url_wiki) d'abord, sous-page (url_page) en repli
								if (!empty($url) && preg_match('/^https?:\/\//i', $url)) {
									$moduleurls[$modulename]	= $url;
									break;
								}
							}
						}
					}
					$this->db->free($resql);
					$pagerows	= array();
					foreach ($params as $param) {	// le param le plus spécifique gagne : page?leftmenu=xxx avant la page nue
						if (!empty($rowsbypage[$param])) {
							$pagerows	= $rowsbypage[$param];
							break;
						}
					}
					foreach ($pagerows as $obj) {
						foreach (explode(',', (string) $obj->hooks) as $function) {
							$function	= trim($function);
							if ($function !== '' && ! in_array($function, $dicthooks)) {
								$dicthooks[]	= $function;
							}
						}
					}
					// Récupère la liste des noms de fonctions de hook déclarées en base, sans doublons.
					$seen	= array();
					foreach ($pagerows as $obj) {
						$url	= !empty($obj->url_page) ? $obj->url_page : $obj->url_wiki;
						if (empty($url) || isset($seen[$url])) {
							continue;
						}
						$seen[$url]	= 1;
						if (! preg_match('/^https?:\/\//i', $url)) {	// Préfixe réservé aux pages du cœur Dolibarr (URLs relatives) ; les pages des modules InfraS sont stockées en URLs absolues et ne sont pas modifiées
							$url	= $wikiprefix.$url;
						}
						$wiki[]	= array('url' => $url, 'label' => $this->getWikiLabel($url, $obj->context));
					}
				} else {	// Si la requête échoue (table absente, par exemple module pas encore réactivé)
					dol_syslog('Actionsinfrashelpdesk::printCommonFooter ctxurl dictionary not available : '.$this->db->lasterror(), LOG_WARNING);
				}
			}
			// Fonctions de hook utilisé par la page courante : executeHooks() + colonne hook pour le dictionnaire
			$allfunctions	= array_values(array_unique(array_merge($pagefunctions, $dicthooks)));
			sort($allfunctions);
			$functionhooks	= array();
			foreach ($allfunctions as $function) {
				$implementers	= array();
				foreach ($hookinstances as $modulename => $instance) {
					if (method_exists($instance, $function)) {
						$implementers[]	= $modulename;
					}
				}
				sort($implementers);
				$functionhooks[]	= array('name' => $function, 'modules' => $implementers);
			}
			// Verifie les modules qui possèdent réellement une méthode portant le nom du fonction de hook
			$implementernames	= array();
			foreach ($functionhooks as $functionhook) {
				foreach ($functionhook['modules'] as $modulename) {
					$implementernames[$modulename]	= 1;
				}
			}
			// Récupère l'ensemble des noms de modules qui implémentent au moins une des fonctions utilisées par la page.
			$modules	= array_values(array_filter($modules, function ($module) use ($implementernames) {
				return isset($implementernames[$module['name']]);
			}));
			usort($modules, function ($a, $b) {
				return strcmp($a['name'], $b['name']);
			});
			foreach ($modules as $key => $module) {
				$modules[$key]['url']	= !empty($moduleurls[$module['name']]) ? $moduleurls[$module['name']] : 'https://wiki.infras.fr/shelves/modules-infras';
			}
			// Construction et affichage du JSON pour le JavaScript
			$jsconf	= array('page'			=> $_SERVER['PHP_SELF'],
							'contexts'		=> $contexts,
							'functionHooks'	=> $functionhooks,
							'modules'		=> $modules,
							'wiki'			=> $wiki,
							'devmode'		=> getDolGlobalInt('INFRASHELPDESK_ENABLE_DEV_MODE'),
							'langs'			=> array('title'	=> $langs->transnoentities('InfraSHelpdeskCtxTitle'),
													'page'		=> $langs->transnoentities('InfraSHelpdeskCtxPage'),
													'contexts'	=> $langs->transnoentities('InfraSHelpdeskCtxContexts'),
													'functions' => $langs->transnoentities('InfraSHelpdeskCtxFunctions'),
													'modules'	=> $langs->transnoentities('InfraSHelpdeskCtxModules'),
													'nomodule'	=> $langs->transnoentities('InfraSHelpdeskCtxNoModule'),
													'allctx'	=> $langs->transnoentities('InfraSHelpdeskCtxAll'),
													'wiki'		=> $langs->transnoentities('InfraSHelpdeskCtxWiki')
													)
							);
			print "\n".'<!-- infrashelpdesk floating context button configuration -->'."\n";
			print '<script nonce="'.getNonce().'">var infrashelpdeskCtxConf = '.json_encode($jsconf, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP).';</script>'."\n";
			return 0;
		}
	}
