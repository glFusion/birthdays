<?php
/**
 * MySQL table definitions to be used in installing the Birthdays plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018-2021 Lee Garner <lee@leegarner.com>
 * @package     birthdays
 * @version     v1.1.2
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

global $_TABLES, $_SQL, $BD_UPGRADE;

$_SQL = array(
'birthdays' => "CREATE TABLE {$_TABLES['birthdays']} (
  `uid` int(10) NOT NULL,
  `month` int(2) DEFAULT NULL,
  `day` int(2) DEFAULT NULL,
  `sendcards` tinyint(1) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`uid`),
  KEY `mon_day` (`month`,`day`)
) TYPE=MyISAM;",
);

$BD_UPGRADE = array(
    '1.1.0' => array(
        "ALTER TABLE {$_TABLES['birthdays']} ADD `sendcards` tinyint(1) unsigned NOT NULL DEFAULT 1 AFTER `day`",
    ),
);

