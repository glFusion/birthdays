<?php
/**
 * Configuration Defaults for the Birthdays plugin for glFusion.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018 Lee Garner
 * @package     birthdays
 * @version     v0.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

// This file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/** @var global config data */
global $birthdaysConfigData;
$birthdaysConfigData = array(
    array(
        'name' => 'sg_main',
        'default_value' => NULL,
        'type' => 'subgroup',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'birthdays',
    ),
    array(
        'name' => 'fs_main',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'birthdays',
    ),
    array(
        'name' => 'format',
        'default_value' => 'M d',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 10,
        'set' => true,
        'group' => 'birthdays',
    ),
    array(
        'name' => 'login_greeting',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 20,
        'set' => true,
        'group' => 'birthdays',
    ),
    array(
        'name' => 'enable_subs',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 30,
        'set' => true,
        'group' => 'birthdays',
    ),
    /*array(
        'name' => 'grp_cards',
        'default_value' => 13,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 40,
        'set' => true,
        'group' => 'birthdays',
    ),*/
    array(
        'name' => 'grp_access',
        'default_value' => 13,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 50,
        'set' => true,
        'group' => 'birthdays',
    ),
    array(
        'name' => 'show_upcoming',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 60,
        'set' => true,
        'group' => 'birthdays',
    ),
);

/**
 * Initialize Birthdays plugin configuration.
 *
 * @param   integer $group_id   Not used
 * @return  boolean     True: success; False: an error occurred
 */
function plugin_initconfig_birthdays($group_id = 0)
{
    global $birthdaysConfigData;

    $c = config::get_instance();
    if (!$c->group_exists('birthdays')) {
        USES_lib_install();
        foreach ($birthdaysConfigData AS $cfgItem) {
            _addConfigItem($cfgItem);
        }
    } else {
        COM_errorLog('initconfig error: Paypal config group already exists');
    }
    return true;
}

?>
