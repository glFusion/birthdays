<?php
/**
 * MySQL table definitions to be used in installing the Photocomp plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2017 Lee Garner <lee@leegarner.com>
 * @package     photocomp
 * @version     v1.4.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
$_SQL = array(
'birthdays' => "CREATE TABLE {$_TABLES['birthdays']} (
  uid int(10) NOT NULL,
  month int(2) default NULL,
  day int(2) default NULL,
  PRIMARY KEY (`uid`),
  KEY `mon_day` (`month`,`day`)
) TYPE=MyISAM;",
);

?>
