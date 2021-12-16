<?php
/**
 * Language file for the Birthdays plugin for glFusion.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @author      Mike Lynn <mike@mlynn.com>
 * @copyright   Copyright (c) 2018 Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2002 Mike Lynn <mike@mlynn.com>
 * @package     birthdays
 * @version     v0.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
use Birthdays\MO;

// Localization of the Admin Configuration UI
$LANG_configsections['birthdays'] = array(
    'label' => MO::_('Birthdays'),
    'title' => MO::_('Birthdays Plugin Configuration'),
);

$LANG_configsubgroups['birthdays'] = array(
    'sg_main' => MO::_('Main Settings'),
);

$LANG_fs['birthdays'] = array(
    'fs_main' => MO::_('Main Settings'),
);

$LANG_confignames['birthdays'] = array(
    'format'   => MO::_('Date Display Format'),
    'login_greeting' => MO::_('Greeting message upon login?'),
    'enable_subs' => MO::_('Allow subscriptions to birthday announcements?'),
    'enable_cards' => MO::_('Enable birthday cards?'),
    'grp_access' => MO::_('Group allowed to view birthdays'),
    'grp_cards' => MO::_('Group allowed to receive cards'),
    'show_upcoming' => MO::_('Show in upcoming events (evList)?'),
    'zodiac_in_dscp' => MO::_('Astrological sign in calendar description?'),
    'cards_only_active' => MO::_('Send cards only to active accounts?'),
);

$LANG_configselects['birthdays'] = array(
    0 => array(
        MO::_('True') => 1,
        MO::_('False') => 0,
    ),
);
