<?php
/**
 * Admin entry point for the Birthdays plugin.
 *
 * @author     Lee Garner <lee@leegarner.com>
 * @author     Mike Lynn <mike@mlynn.com>
 * @copyright  Copyright (c) 2018-2020 Lee Garner <lee@leegarner.com>
 * @package    birthdays
 * @version    v1.0/0
 * @license    http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
require_once('../../../lib-common.php');

if (!plugin_ismoderator_birthdays()) {
    COM_404();
}
USES_lib_admin();

$expected = array(
    'syncall', 'delitem',
);
$action = '';
$content = BIRTHDAYS_adminMenu();

foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
        $actionval = $_POST[$provided];
        break;
    } elseif (isset($_GET[$provided])) {
        $action = $provided;
        $actionval = $_GET[$provided];
        break;
    }
}

switch ($action) {
case 'syncall':
    // Re-save all items to sync with other plugins
    $Birthdays= \Birthdays\Birthday::getAll();
    foreach ($Birthdays as $B) {
        PLG_itemSaved($B->uid, $_BD_CONF['pi_name']);
    }
    break;

case 'delitem':
    // Delete one or more items
    if (!is_array($actionval)) {
        $actionval = array($actionval);
    }
    foreach ($actionval as $val) {
        \Birthdays\Birthday::Delete($val);
    }
    echo COM_refresh($_BD_CONF['admin_url']);
    break;

default:
    break;
}
$content .= \Birthdays\Birthday::adminList();

echo COM_siteHeader();
echo $content;
echo COM_siteFooter();


/**
 * Create the admin menu at the top of the list and form pages.
 *
 * @return  string      HTML for admin menu section
 */
function BIRTHDAYS_adminMenu()
{
    global $_CONF, $_BD_CONF, $LANG01;

    $menu_arr = array (
        array(
            'url' => $_BD_CONF['admin_url'] . '/index.php?syncall=x',
            'text' => dgettext('birthdays', 'Sync All'),
        ),
        array(
            'url' => $_CONF['site_admin_url'],
            'text' => $LANG01[53]       // Admin Home,
        ),
    );
    $T = new \Template($_BD_CONF['pi_path'] . '/templates');
    $T->set_file('title', 'admin.thtml');
    $T->set_var(array(
        'version'   => $_BD_CONF['pi_version'],
        'logo_url' => plugin_geticon_birthdays(),
        'lang_pi_title' => dgettext('birthdays', 'Birthdays'),
    ) );
    $retval = $T->parse('', 'title');
    $retval .= ADMIN_createMenu(
        $menu_arr,
        dgettext('birthdays', 'Sync All: Re-saves all birthday entries so other plugins will update their records if they use data from this plugin.'),
        plugin_geticon_birthdays()
    );
    return $retval;
}

?>
