<?php
/**
*   Configuration Defaults for the Birthdays plugin for glFusion.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2018 Lee Garner
*   @package    birthdays
*   @version    0.1.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

// This file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}


/** Utility plugin default configurations
*   @global array */
global $_BD_DEFAULTS;
$_BD_DEFAULTS = array(
    'format' => 'M d',
    'login_greeting'    => 1,
    'enable_subs'       => 0,
    'enable_cards'      => 0,
);

/**
*   Initialize Birthdays plugin configuration
*
*   @return boolean             true: success; false: an error occurred
*/
function plugin_initconfig_birthdays()
{
    global $_CONF, $_BD_CONF, $_BD_DEFAULTS;

    $c = config::get_instance();
    if (!$c->group_exists($_BD_CONF['pi_name'])) {

        $c->add('sg_main', NULL, 'subgroup', 0, 0, NULL, 0, true,
                $_BD_CONF['pi_name']);
        $c->add('fs_main', NULL, 'fieldset', 0, 0, NULL, 0, true,
                $_BD_CONF['pi_name']);

        $c->add('format', $_BD_DEFAULTS['format'],
                'text', 0, 0, NULL, 10, true, $_BD_CONF['pi_name']);
        $c->add('login_greeting', $_BD_DEFAULTS['login_greeting'],
                'select', 0, 0, 0, 20, true, $_BD_CONF['pi_name']);
        $c->add('enable_subs', $_BD_DEFAULTS['enable_subs'],
                'select', 0, 0, 0, 30, true, $_BD_CONF['pi_name']);
        $c->add('enable_cards', $_BD_DEFAULTS['enable_cards'],
                'select', 0, 0, 0, 40, true, $_BD_CONF['pi_name']);
     }
     return true;
}

?>
