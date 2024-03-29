<?php
/**
 * Public entry point for the Birthdays plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @author      Mike Lynn <mike@mlynn.com>
 * @copyright   Copyright (c) 2018-2022 Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2002 Mike Lynn <mike@mlynn.com>
 * @package     birthdays
 * @version     v1.2.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
require_once('../lib-common.php');
use Birthdays\Config;

if (!\Birthdays\Birthday::canView()) {
    COM_404();
    exit;
}
use Birthdays\MO;
use glFusion\Database\Database;
use glFusion\Log\Log;
USES_lib_admin();

// MAIN

$expected = array('list', 'addbday', 'mode', 'nocards', 'unsub');
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
            'sendcards' => isset($_POST['sendcards']) ? 1 : 0,
        ) );
        echo COM_refresh($_CONF['site_url'] . '/birthdays/index.php');
    }
    break;
case 'nocards':
    $uid = (int)Birthdays\Models\User::decrypt($actionval);
    if ($uid > 1) {
        Birthdays\Birthday::getInstance($uid)->toggleCard(0);
    }
    COM_setMsg(MO::_('You will no longer receive birthday cards'));
    COM_refresh($_CONF['site_url'] . '/index.php');
    break;
case 'unsub':
    // Unsubscribe from all birthday notifications
    $uid = Birthdays\Models\User::decrypt($actionval);
    if ($uid > 1) {
        try {
            $db->conn->delete(
                $_TABLES['subscriptions'],
                array(
                    'type' => 'birthdays',
                    'category' => 'birthdays_sub',
                    'uid' => '?'
                ),
                array(Database::INTEGER)
            );
        } catch (\Exception $e) {
            Birthdays\Logger::logException($e);
        }
        Birthdays\Logger::audit("User {$uid} unsubscribed from birthday notifications");
    }
    COM_setMsg(MO::_('You have been unsubscribed from birthday notifications'));
    COM_refresh($_CONF['site_url'] . '/index.php');
    break;
}

$display = COM_siteHeader('menu');
$T = new Template(Config::path_template());
$T->set_file('header', 'index.thtml');
$T->set_var(array(
    'header'    => Config::get('pi_display_name'),
    'pi_name'   => Config::PI_NAME,
    'logo'      => plugin_geticon_birthdays(),
    'lang_sel_month' => MO::_('Select Month'),
    'lang_pi_title' => MO::_('Birthdays'),
    'my_form'   => COM_isAnonUser() ? '' : \Birthdays\Birthday::getInstance($_USER['uid'])->editForm('edit_index'),
    'month_select' => \Birthdays\Birthday::selectMonth($filter_month),
) );

$T->parse('output','header');
$display .= $T->finish($T->get_var('output'));
$display .= Birthdays\Birthday::publicList($filter_month);
$display .= COM_siteFooter();
echo $display;
exit;
