<?php
/**
 * Admin entry point for the Birthdays plugin.
 *
 * @author     Lee Garner <lee@leegarner.com>
 * @author     Mike Lynn <mike@mlynn.com>
 * @copyright  Copyright (c) 2018 Lee Garner <lee@leegarner.com>
 * @package    birthdays
 * @version    0.1.0
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
    $BD = \Birthdays\Birthday::getAll();
    $B = new \Birthdays\Birthday();
    foreach ($BD as $vals) {
        $B->Save($vals);
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
$content .= BIRTHDAYS_adminList();

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
    global $_CONF, $_BD_CONF, $LANG_BD00, $LANG01;

    $menu_arr = array (
        array(
            'url' => $_BD_CONF['admin_url'] . '/index.php?syncall=x',
            'text' => $LANG_BD00['sync_all'],
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
    ) );
    $retval = $T->parse('', 'title');
    $retval .= ADMIN_createMenu($menu_arr, '',
            plugin_geticon_birthdays());
    return $retval;
}


/**
 * Show the admin list.
 *
 * @return string  HTML for item list
 */
function BIRTHDAYS_adminList()
{
    global $LANG_ADMIN, $LANG_BD00, $_TABLES, $_CONF, $_BD_CONF;

    $retval = '';
    $form_arr = array();

    $header_arr = array(
        array(  'text' => $LANG_BD00['user_id'],
                'field' => 'uid',
                'sort' => true,
        ),
        array(  'text' => $LANG_BD00['name'],
                'field' => 'username',
                'sort' => false,
        ),
        array(  'text'  => $LANG_BD00['birthday'],
                'field' => 'birthday',
                'sort'  => false,
        ),
        array(  'text' => $LANG_ADMIN['delete'],
                'field' => 'delete',
                'sort' => false,
                'align' => 'center',
        ),
    );

    $text_arr = array(
        'has_extras' => false,
        'form_url' => $_BD_CONF['admin_url'] . '/index.php',
    );

    $options = array('chkdelete' => 'true', 'chkfield' => 'uid');
    $defsort_arr = array('field' => 'uid', 'direction' => 'asc');
    $query_arr = array(
        'table' => 'birthdays',
        'sql' => "SELECT * FROM {$_TABLES['birthdays']}",
    );

    $retval = ADMIN_list('birthdays', 'BIRTHDAYS_getAdminField', $header_arr,
                $text_arr, $query_arr, $defsort_arr, '', '', $options, $form_arr);
    return $retval;
}


/**
 * Get the correct display for a single field in the admin list.
 *
 * @param   string  $fieldname  Field variable name
 * @param   string  $fieldvalue Value of the current field
 * @param   array   $A          Array of all field names and values
 * @param   array   $icon_arr   Array of system icons
 * @return  string              HTML for field display within the list cell
 */
function BIRTHDAYS_getAdminField($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $_BD_CONF, $LANG_BD00;

    $retval = '';

    switch($fieldname) {
    case 'username':
        $retval = COM_getDisplayName($A['uid']);
        break;

    case 'delete':
        $retval = COM_createLink('<i class="uk-icon uk-icon-trash uk-text-danger"></i>',
                $_BD_CONF['admin_url'] . "/index.php?delitem={$A['uid']}",
                array(
                     'onclick' => "return confirm('{$LANG_BD00['conf_del']}');",
                ) );
        break;

    case 'birthday':
        $retval = BIRTHDAYS_format($A['month'], $A['day']);
        break;

    default:
        $retval = $fieldvalue;
        break;
    }
    return $retval;
}

?>
