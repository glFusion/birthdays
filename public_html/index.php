<?php
/**
*   glFusion API functions for the Birthdays plugin.
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
USES_lib_admin();

global $T, $_TABLES, $PHP_SELF, $_CONF, $HTTP_POST_VARS, $_USER, $LANG_STATIC,$_SP_CONF;

/*
* Main Function
*/
$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
$filter_month = isset($_REQUEST['filter_month']) ? $_REQUEST['filter_month'] : 0;

$display = COM_siteHeader('menu');
$T = new Template($_CONF['path'] . 'plugins/birthdays/templates');
$T->set_file('header', 'index.thtml');
$T->set_var(array(
    'header'    =>$_BD_CONF['pi_display_name'],
    'logo'      => plugin_geticon_birthdays(),
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

    $dt = new \Date('now', $_CONF['timezone']);
    $curmonth = $dt->Format('n', true);
    $year = $dt->Format('Y', true);     // just to have a value for later
    if (empty($filter_month)) {
        $filter_month = $curmonth;
    }
    $filter = $filter_month == 'all' ? '' : " AND month = $filter_month";
    $text_arr = array(
        'form_url' => $_BD_CONF['url'] . '/index.php',
    );
    /*$query_arr = array('table' => 'birthdays',
        'sql' => "SELECT * FROM {$_TABLES['birthdays']}
                WHERE 1=1 $filter",
        'query_fields' => array(),
        'default_filter' => ''
    );*/
    $defsort_arr = array('field' => 'day', 'direction' => 'ASC');
    $data_arr = Birthdays\Birthday::getAll($filter_month);
    $form_arr = array(
        'top' => select_month($filter_month),
    );
    $extra = array(
        'dt'    => $dt,
    );
    $retval .= ADMIN_listArray('birthdays', 'getField_bday_list', $header_arr,
                $text_arr, $data_arr, $defsort_arr, '', $extra, '', $form_arr);
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
function getField_bday_list($fieldname, $fieldvalue, $A, $icon_arr, $extra)
{
    global $_CONF, $_BD_CONF;

    $retval = '';

    switch($fieldname) {
    case 'fullname':
        $retval .= COM_getDisplayName($A['uid']);
        break;

    case 'birthday':
        $extra['dt']->setDate($A['year'], $A['month'], $A['day']);
        $retval .= $extra['dt']->Format($_BD_CONF['format'], true);
        break;
    }
    return $retval;
}


/**
*   Create a month selector
*
*   @param  integer $month  Currently-selected month
*   @return string      HTML for month selection
*/
function select_month($month)
{
    global $_CONF, $LANG_MONTH, $LANG_ADMIN, $LANG_BD00;

    if(empty($month)) {
        $month=strftime("%m")+0;
    }
    $str = " <input type=hidden name=mode value='list'> " . $LANG_BD00['sel_month'] .
        ": <select name=\"filter_month\" onChange='javascript:this.form.submit();return true'><option value=\"all\">ALL";
    for ($i = 1; $i < 13; $i++) {
        $sel = $i == $month ? 'selected="selected"' : '';
        $str .= "<option value=\"$i\" $sel>{$LANG_MONTH[$i]}</option>";
    }
    $str.='</select>';
    return $str;
}

?>
