<?php
/**
*   Public entry point for the Birthdays plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @author     Mike Lynn <mike@mlynn.com>
*   @copyright  Copyright (c) 2018 Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2002 Mike Lynn <mike@mlynn.com>
*   @package    birthdays
*   @version    0.1.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
require_once('../lib-common.php');

if (!BIRTHDAYS_canView()) {
    COM_404();
    exit;
}

USES_lib_admin();

//global $T, $_TABLES, $PHP_SELF, $_CONF, $HTTP_POST_VARS, $_USER, $LANG_STATIC,$_SP_CONF;

/*
* Main Function
*/

$expected = array('list', 'addbday', 'mode');
foreach($expected as $provided) {
    // Get requested action and page from GET or POST variables.
    // Most could come in either way.  They are not sanitized, so they must
    // only be used in switch or other conditions.
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
if (empty($action)) $action = 'list';
$curmonth = BIRTHDAYS_currentDate()['month'];
$filter_month = isset($_REQUEST['filter_month']) ? $_REQUEST['filter_month'] : $curmonth;
if ($filter_month == -1) {
    $filter_month = $curmonth;
}

switch ($action) {
case 'addbday':
    if (!COM_isAnonUser()) {
        $bday = \Birthdays\Birthday::getInstance($_USER['uid']);
        $bday->Save(array(
            'month' => $_POST['birthday_month'],
            'day' => $_POST['birthday_day'],
        ) );
        echo COM_refresh($_CONF['site_url'] . '/birthdays/index.php');
    }
    break;
}

$display = COM_siteHeader('menu');
$T = new Template($_CONF['path'] . 'plugins/birthdays/templates');
$T->set_file('header', 'index.thtml');
$T->set_var(array(
    'header'    => $_BD_CONF['pi_display_name'],
    'pi_name'   => $_BD_CONF['pi_name'],
    'logo'      => plugin_geticon_birthdays(),
    'my_form'   => COM_isAnonUser() ? '' : \Birthdays\Birthday::editForm($_USER['uid'], 'edit_index'),
    'month_select' => \Birthdays\Birthday::selectMonth($filter_month),
) );
$T->parse('output','header');
$display .= $T->finish($T->get_var('output'));
$display .= listbirthdays($filter_month);
$display .= COM_siteFooter();
echo $display;
exit;


/**
*   Present the list of birthdays.
*
*   @param  integer $filter_month   Month to show, or "all"
*   @return string      HTML for the list
*/
function listbirthdays($filter_month)
{
    global $T, $_TABLES, $PHP_SELF, $_CONF, $HTTP_POST_VARS, $_USER, $LANG_STATIC,$_SP_CONF;
    global $LANG_BD00, $_BD_CONF, $LANG04, $LANG_ADMIN;

    $retval = '';

    $header_arr = array(
        array('text' => $LANG04[3],
            'field' => 'fullname',
            'sort' => false,
            'align' => '',
        ),
        array('text' => $LANG_BD00['birthday'],
            'field' => 'birthday',
            'sort' => true,
            'align' => 'center',
        ),
    );
    if ($_BD_CONF['enable_subs']) {
        $header_arr[] =  array('text' => 'Subscribe',
            'field' => 'subscribe',
            'sort' => false,
            'align' => 'center',
        );
    }

    $defsort_arr = array('field' => 'birthday', 'direction' => 'ASC');
    $text_arr = array(
        'has_menu'     => false,
        'has_extras'   => false,
        'title'        => $LANG_BD00['pi_title'],
        'form_url'     => $_BD_CONF['url'] . '/index.php?filter_month=' . $filter_month,
        'help_url'     => ''
    );
    $filter = $filter_month == 0 ? '' : " AND month = $filter_month";
    $sql = "SELECT 2016 as year, CONCAT(
                    LPAD(b.month,2,0),LPAD(b.day,2,0)
                ) as birthday, b.*
                FROM {$_TABLES['birthdays']} b
                WHERE 1=1 $filter";

    $query_arr = array('table' => 'birthdays',
            'sql' => $sql,
            'query_fields' => array(),
    );
    $text_arr = array(
        'form_url' => $_BD_CONF['url'] . '/index.php?filter_month=' . $filter_month,
        'has_search' => false,
        'has_limit'     => true,
        'has_paging'    => true,
    );

    $retval .= ADMIN_list('birthdays', 'getField_bday_list',
            $header_arr, $text_arr, $query_arr, $defsort_arr);
    return $retval;
}


/**
*   Determine what to display in the admin list for each birthday.
*
*   @param  string  $fieldname  Name of the field, from database
*   @param  mixed   $fieldvalue Value of the current field
*   @param  array   $A          Array of all name/field pairs
*   @param  array   $icon_arr   Array of system icons
*   @param  array   $extra      Extra passthrough items (includes date obj)
*   @return string              HTML for the field cell
*/
function getField_bday_list($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $_BD_CONF, $LANG_BD00, $_USER;

    $retval = '';

    switch($fieldname) {
    case 'fullname':
        $retval .= COM_getDisplayName($A['uid']);
        break;

    case 'birthday':
        $retval .= BIRTHDAYS_format($A);
        break;

    case 'subscribe':
        if (PLG_isSubscribed('birthdays', 'birthday_sub', $A['uid'], $_USER['uid'])) {
            $text = $LANG_BD00['unsubscribe'];
            //$icon_cls = 'uk-text-success';
            $current_val = 1;
            $chk = 'checked="checked"';
        } else {
            $text = $LANG_BD00['subscribe'];
            //$icon_cls = '';
            $current_val = 0;
            $chk = '';
        }
        $retval = '<input type="checkbox" value="1" ' . $chk .
                ' data-uk-tooltip title="' . $LANG_BD00['click_to'] . $text .
                    '" onclick="javascript:BDAY_toggleSub(this, ' . $A['uid'] . ', ' . $current_val . ');" />';
        break;
    }
    return $retval;
}

?>
