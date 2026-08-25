--	/************************************************
--	* Copyright (C) 2026	Sylvain Legrand - <contact@infras.fr>   InfraS - <https://www.infras.fr>
--	*
--	* This program is free software: you can redistribute it and/or modify
--	* it under the terms of the GNU General Public License as published by
--	* the Free Software Foundation, either version 3 of the License, or
--	* (at your option) any later version.
--	*
--	* This program is distributed in the hope that it will be useful,
--	* but WITHOUT ANY WARRANTY; without even the implied warranty of
--	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
--	* GNU General Public License for more details.
--	*
--	* You should have received a copy of the GNU General Public License
--	* along with this program.  If not, see <http://www.gnu.org/licenses/>.
--	************************************************/

--	/************************************************
--	* 	\file		./multismtp/sql/llx_user_smtp2go.sql
--	* 	\ingroup	MultiSMTP
--	* 	\brief		SQL data for module MultiSMTP
--	************************************************/
CREATE TABLE `llx_user_smtp2go` (
  `rowid`                   int(11) NOT NULL AUTO_INCREMENT,
  `username`                varchar(100) NOT NULL,
  `description`             varchar(255) DEFAULT NULL,
  `password`                varchar(255) DEFAULT NULL,
  `fk_user`                 int(11) DEFAULT NULL,
  `subaccount_id`           varchar(64) DEFAULT NULL,
  `custom_ratelimit`        tinyint(1) NOT NULL DEFAULT 0,
  `custom_ratelimit_value`  int(11) DEFAULT NULL,
  `custom_ratelimit_period` varchar(50) DEFAULT NULL,
  `ratelimit_default`       tinyint(1) NOT NULL DEFAULT 0,
  `enforce_2fa`             tinyint(1) DEFAULT NULL,
  `enable_sms`              tinyint(1) DEFAULT NULL,
  `sms_limit`               int(11) DEFAULT NULL,
  `archive_enabled`         tinyint(1) DEFAULT NULL,
  `open_tracking_enabled`   tinyint(1) DEFAULT NULL,
  `click_tracking_enabled`  tinyint(1) DEFAULT NULL,
  `audit_email`             varchar(255) DEFAULT NULL,
  `feedback_enabled`        tinyint(1) DEFAULT NULL,
  `feedback_domain`         varchar(255) DEFAULT NULL,
  `feedback_html`           text,
  `feedback_text`           text,
  `bounce_notifications`    varchar(255) DEFAULT NULL,
  `entity`                  int(11) NOT NULL DEFAULT 1,
  `tms`                     timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;