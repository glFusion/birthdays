<?php
/**
 * Public entry point for the Birthdays plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @author      Mike Lynn <mike@mlynn.com>
 * @copyright   Copyright (c) 2018-2020 Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2002 Mike Lynn <mike@mlynn.com>
 * @package     birthdays
 * @version     v1.0.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
require_once('../lib-common.php');

if (!\Birthdays\Birthday::canView()) {
    COM_404();
    exit;
}

USES_lib_admin();

// MAIN

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
$curmonth = \Birthdays\Birthday::currentDate()['month'];
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

/*$domain = 'birthdays';
        $results = setlocale(LC_MESSAGES, 'es_MX');
if ($results) {
            $dom = bind_textdomain_codeset($domain, 'UTF-8');
            $dom = bindtextdomain($domain, __DIR__ . "/locale");
        }

 */


$display = COM_siteHeader('menu');
$T = new Template($_CONF['path'] . 'plugins/birthdays/templates');
$T->set_file('header', 'index.thtml');
$T->set_var(array(
    'header'    => $_BD_CONF['pi_display_name'],
    'pi_name'   => $_BD_CONF['pi_name'],
    'logo'      => plugin_geticon_birthdays(),
    'my_form'   => COM_isAnonUser() ? '' : \Birthdays\Birthday::editForm($_USER['uid'], 'edit_index'),
    'month_select' => Birthdays\Birthday::selectMonth($filter_month),
    'lang_sel_month' => dgettext('birthdays', 'Select Month'),
    'lang_pi_title' => dgettext('birthdays', 'Birthdays'),
) );

$T->parse('output','header');
$display .= $T->finish($T->get_var('output'));
$display .= Birthdays\Birthday::publicList($filter_month);
$display .= COM_siteFooter();
echo $display;
exit;

?>
