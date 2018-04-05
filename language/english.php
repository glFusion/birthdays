<?php
/**
*   Language file for the Birthdays plugin for glFusion
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
$LANG_BD00 = array (
    'pi_title'      => 'Birthdays',
    'my_birthday'   => 'My Birthday',
    'birthday'      => 'Birthday',
    'sel_month'     => 'Select Month',
    'this_month'    => 'This Month',
    'next_month'    => 'Next Month',
    'none'          => '-- None --',
    'all'           => '-- All --',
    'uid'           => 'uid',
    'view_all'      => 'View All',
    'msg_happy_birthday' => 'Happy Birthday, %s!',
    'sub_title'     => 'Birthday Notification from ' . $_CONF['site_name'],
    'sub_message'   => 'It&apos;s %s&apos;s birthday today. Join us in saying &quot;Happy Birthday!&quot;',
    'sub_reason'   => 'You are receiving this email because you have chosen to be notified when %s has a birthday.',
    'sub_unsub'     => 'To unsubscribe from these notifications, please click this link',
    'card_title'    => 'Happy Birthday from ' . $_CONF['site_name'] . '!',
    'card_message'  => 'Happy Birthday from %s',
    'unsubscribe'   => 'Unsubscribe',
    'email_autogen' => 'This email was generated automatically. Please do not reply to this email.',
    'click_to'      => 'Click to ',
    'subscribe'     => 'Subscribe',
    'subscr_updated' => 'Subscription Updated',
    'subscr_err'    => 'Error updating subscription',
    'dscp'          => '%s&apos;s Birthday',
    'sync_all'      => 'Sync All',
    'conf_del'      => 'Do you really want to delete this item?',
    'name'          => 'Name',
    'user_id'       => 'User ID',
);

// Localization of the Admin Configuration UI
$LANG_configsections['birthdays'] = array(
    'label' => 'Birthdays',
    'title' => 'Birthdays Plugin Configuration',
);

$LANG_fs['birthdays'] = array(
    'fs_main' => 'Main Settings',
);

$LANG_confignames['birthdays'] = array(
    'format'   => 'Date Display Format',
    'login_greeting' => 'Greeting message upon login?',
    'enable_subs' => 'Allow subscripttions to birthday announcements?',
    'enable_cards' => 'Enable birthday cards?',
    'grp_access' => 'Group allowed to view birthdays',
);

$LANG_configselects['birthdays'] = array(
    0 => array('True' => 1, 'False' => 0),
);

?>
