<?php
/**
 * Common AJAX functions.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018 Lee Garner <lee@leegarner.com>
 * @package     birthdays
 * @version     v0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Include required glFusion common functions */
require_once '../lib-common.php';
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
            sprintf($LANG_BD00['dscp'], COM_getDisplayName($_POST['uid']))
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
        'statusMessage' => $newval != $_POST['oldval'] ? $LANG_BD00['subscr_updated'] : $LANG_BD00['subscr_err'],
    );
    break;
}

if (is_array($retval) && !empty($retval)) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    //A date in the past
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    echo json_encode($retval);
}

?>
