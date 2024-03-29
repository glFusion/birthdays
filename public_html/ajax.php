<?php
/**
 * Common AJAX functions.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018-2022 Lee Garner <lee@leegarner.com>
 * @package     birthdays
 * @version     v1.1.2
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Include required glFusion common functions */
require_once '../lib-common.php';
use Birthdays\MO;
use Birthdays\Config;

$retval = '';

switch ($_POST['action']) {
case 'toggleSub':
    if (
        !Config::get('enable_subs') ||
        COM_isAnonUser() ||
        !isset($_POST['oldval'])
    ) {
        break;
    }

    switch ($_POST['oldval']) {
    case 0:         // was unsubscribed, now subscribing
        $newval = PLG_subscribe(
            'birthdays',
            'birthday_sub',
            $_POST['uid'],
            $_USER['uid'],
            Config::get('pi_display_name'),
            sprintf(MO::_('Description'), COM_getDisplayName($_POST['uid']))
        ) ? 1 : 0;
        break;
    case 1:         // was subscribed, now unsubscribing
        $newval = PLG_unsubscribe('birthdays', 'birthday_sub', $_POST['uid'], $_USER['uid']) ? 0 : 1;
        break;
    default:
        COM_errorLog("oldval is {$_POST['oldval']}");
    }
    $retval = array(
        'uid'    => $_POST['uid'],
        'newval' => $newval,
        'statusMessage' => $newval != $_POST['oldval'] ? 
            MO::_('Subscription has been updated') :
            MO::_('Error updating subscription'),
    );
    break;

case 'toggleCards':
    $oldval = (int)$_POST['oldval'];
    $Bday = Birthdays\Birthday::getInstance($_POST['uid']);
    $newval = $Bday->toggleCard($oldval);
    COM_errorLog("value is now $newval");
    $retval = array(
        'uid'    => $_POST['uid'],
        'newval' => $newval,
        'statusMessage' => $newval != $oldval ? 
            MO::_('Subscription has been updated') :
            MO::_('Error updating subscription'),
    );
    COM_errorLog(var_export($retval,true));
    break;
}

if (is_array($retval) && !empty($retval)) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    //A date in the past
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    echo json_encode($retval);
}

