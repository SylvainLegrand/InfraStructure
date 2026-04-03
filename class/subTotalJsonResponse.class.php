	<?php
	/**************************************************** 
	* Copyright (C) 2025 ATM Consulting <support@atm-consulting.fr>
	* Copyright (C) 2025-2026 Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
	*
	* This program is free software; you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation; either version 3 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program. If not, see <https://www.gnu.org/licenses/>.
	****************************************************/

	/*****************************************************
	* SPDX-License-Identifier: GPL-3.0-or-later
	* This file is part of Dolibarr module Subtotal
	****************************************************/

	/**
	* Class SubTotalJsonResponse
	*/
	class SubTotalJsonResponse
	{

		public $result = 0;		// @var int $result the call status to determine if success or fail
		public $data;			// @var mixed $data the data to return to call can be all type you want
		public $debug;			// @var mixed $debug the debug data to return to call can be all type you want
		public $msg = '';		// @var string $msg the message to return to call, usually used as set event message
		public $newToken = '';	// @var string $newToken the new token to return to call, used to update token on client side

		/**
		*  Constructor
		*/
		public function __construct()
		{
			$this->newToken = newToken();
		}

		/**
		* return json encoded of object
		* @return string JSON
		*/
		public function getJsonResponse()
		{
			$jsonResponse			= new stdClass();
			$jsonResponse->result	= $this->result;
			$jsonResponse->msg		= $this->msg;
			$jsonResponse->newToken	= $this->newToken;
			$jsonResponse->data		= $this->data;
			$jsonResponse->debug	= $this->debug;
			return json_encode($jsonResponse, JSON_PRETTY_PRINT);
		}
	}
