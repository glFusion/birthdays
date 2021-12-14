<?php
/**
 * Global configuration items for the Birthdays plugin.
 * These are either static items, such as the plugin name and table
 * definitions, or are items that don't lend themselves well to the
 * glFusion configuration system, such as allowed file types.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2021 Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2002 Mike Lynn <mike@mlynn.com>
 * @package     birthdays
 * @version     v1.1.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}
use Birthdays\Config;
global $_DB_table_prefix, $_TABLES;

Config::set('pi_version', '1.1.1');
Config::set('gl_version', '1.7.8');

$_TABLES['birthdays']      = $_DB_table_prefix . 'birthdays';

