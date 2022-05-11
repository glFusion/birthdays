<?php
/**
 * glFusion API functions for the Birthdays plugin.
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

spl_autoload_register(function($class)
{
    // project-specific namespace prefix
    $prefix = 'Birthdays\\';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/classes/';

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.class.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
use glFusion\Database\Database;
use Birthdays\Config;
use Birthdays\Logger;
use Birthdays\MO;
MO::init();
require_once __DIR__ . '/language/lang_config.php';

require_once __DIR__ . '/birthdays.php';

// Include the user-supplied configuration file, if any
if (is_file(__DIR__ . '/config.php')) {
    include_once __DIR__ . '/config.php';
}

/**
 * Returns the items for this plugin that should appear on the main menu.
 * NOTE: this MUST return the url/value pairs in the following format
 *       label => url
 *
 * @return  array   Associative array of menu items to be added to menu
 */
function plugin_getmenuitems_birthdays()
{
    $menuitems = array();
    if (\Birthdays\Birthday::canView()) {
        $menuitems[MO::_('Birthdays')] = Config::get('url');
    }
    return $menuitems;
}


/**
 * Returns the administrative option for this plugin.
 *
 * @return  mixed   array(title, url, num. astore), or false when not allowed
 */
function plugin_getadminoption_birthdays()
{
    if (plugin_ismoderator_birthdays()) {
        return array(
            MO::_('Birthdays'),
            Config::get('admin_url') . '/index.php'
        );
    } else {
        return false;
    }
}


/**
 * This will put an option for astore in the command and control block on moderation.php.
 *
 * @return  mixed   array(title, url, icon), or boolean false when not allowed
 */
function plugin_cclabel_birthdays()
{
    if (plugin_ismoderator_birthdays()) {
        return array(
            MO::_('Birthdays'),
            Config::get('admin_url') . '/index.php',
            plugin_geticon_birthdays()
        );
    } else {
        return false;
    }
}


/**
 * Check to see if this Plugin uses comments. It does not.
 *
 * @return  boolean     True or False
 */
function plugin_commentsupport_birthdays()
{
    return false;
}


/**
 * Get the current code version of the plugin.
 *
 * @return  string  version number
 */
function plugin_chkVersion_birthdays()
{
    return Config::get('pi_version');
}


/**
 * Returns the URL of the plugin's icon.
 *
 * @return  string      URL of the icon
 */
function plugin_geticon_birthdays()
{
    global $_CONF;
    return Config::get('url') . '/images/birthdays.png';
}


/**
 * Upgrade the plugin.
 *
 * @return  mixed   True for success, False or error message number otherwise
 */
function plugin_upgrade_birthdays()
{
    require_once __DIR__ . '/upgrade.php';
    return BIRTHDAYS_do_upgrade();
}


/**
 * Get additional CSS into the site header.
 * Allows for custom CSS to be placed by the site admin.
 *
 * @return  array   List of paths to CSS files
 */
function plugin_getheadercss_birthdays()
{
    global $_CONF;

    $styles = array();
    $files = array(
        'style.css',
    );
    $pi_css = __DIR__ . '/css/';
    $layout_css = "{$_CONF['path_layout']}plugins/" . Config::PI_NAME . '/';
    foreach ($files as $file) {
        if (@file_exists($layout_css . 'custom/' . $file)) {
            $styles[] = $layout_css . 'custom/' . $file;
        } elseif (@file_exists($layout_css . $file)) {
            $styles[] = $layout_css . $file;
        } elseif (@file_exists($pi_css . 'custom/' . $file)) {
            $styles[] = $pi_css . 'custom/' . $file;
        } elseif (@file_exists($pi_css . $file)) {
            $styles[] = $pi_css . $file;
        }
    }
    return $styles;
}


/**
 * Get additional Javascript into the site header.
 *
 * @return  array   List of paths to Javascript files
 */
function plugin_getheaderjs_birthdays()
{
    global $_CONF;

    $js_dir = __DIR__ . '/js';
    $files = array(
        "$js_dir/bday_misc.js",
    );
    return $files;
}


/**
 * Create an edit form in the Account Settings section.
 *
 * @param   integer $uid        User ID
 * @param   string  $panel      Panel being populated
 * @param   string  $fieldset   Name of fieldset being populated
 * @return  string              HTML for edit form. Never NULL.
 */
function plugin_profileedit_birthdays($uid, $panel, $fieldset)
{
    $retval = '';
    if ($panel == 'userinfo' && $fieldset == 'personalinfo') {
        $retval = \Birthdays\Birthday::getInstance($uid)->editForm();
    }
    return $retval;
}


/**
 * Save the profile information when edited via Account Settings.
 *
 * @param   integer $uid    User ID being saved
 */
function plugin_profilesave_birthdays($uid = 0)
{
    global $_USER;

    if (COM_isAnonUser()) {
        return;
    }

    if ($uid == 0) {
        $uid = (int)$_USER['uid'];
    }

    $bday = \Birthdays\Birthday::getInstance($uid);
    $bday->Save(array(
        'month' => $_POST['birthday_month'],
        'day' => $_POST['birthday_day'],
        'sendcards' => isset($_POST['birthday_sendcards']) ? 1 : 0,
        //'call_itemsaved' => false,
    ) );
}


/**
 * Show the user's birthday in the public profile.
 *
 * @param   integer $uid    User ID
 * @return  string      HTML to show on profile page.
 */
function plugin_profileblocksdisplay_birthdays($uid)
{
    // Make sure that a valid UID was passed in.
    if (empty($uid) || !\Birthdays\Birthday::canView()) {
        return '';
    }

    $LT = new Template(Config::path_template());
    $LT->set_file(array(
        'block' => 'profileblock.thtml',
    ));
    $bday = \Birthdays\Birthday::getInstance($uid);
    if (empty($bday)) return;
    $LT->set_var('birthday', Birthdays\Birthday::formatDate($bday));
    $LT->set_var('lang_birthday', MO::_('Birthday'));
    $LT->parse('output', 'block');
    return $LT->finish($LT->get_var('output'));
}


/**
 * Delete user-specific data when a user account is deleted.
 *
 * @param   integer $uid    User ID being deleted
 */
function plugin_user_delete_birthdays($uid)
{
    \Birthdays\Birthday::Delete($uid);
}


/**
 * Create the sideblock showing upcoming birthdays by month.
 * The plugin installs a block with no arguments but the site admin can
 * create or edit the block with an argument for the number of months to show.
 *
 * @param   array   $blkinfo    Block Info
 * @param   integer $months     Number of months to show
 * @return  string      HTML for the block
 */
function phpblock_birthdays_month($blkinfo=array(), $months=2)
{
    global $_CONF, $LANG_MONTH;

    $M = \Birthdays\Birthday::currentDate()['month'];  // starting month
    $have_data = false;
    $retval = '';

    $T = new Template(Config::path_template());
    $T->set_file('block', 'phpblock.thtml');
    $T->set_block('block', 'month', 'mon');
    $T->set_var('lang_view_all', MO::_('View All'));
    for ($i = 0; $i < $months; $i++) {
        $thismonth = \Birthdays\Birthday::getAll($M);
        if (empty($thismonth)) {
            continue;
        } else {
            $have_data = true;
        }
        $T->set_var('period', $LANG_MONTH[(int)$M]);
        $T->set_block('block', 'bdays', 'bd');
        foreach($thismonth as $bday) {
            $T->set_var('username', COM_getDisplayName($bday->getUid()));
            $T->set_var('birthday', \Birthdays\Birthday::formatDate($bday));
            $T->parse('bd', 'bdays', true);
        }
        $T->parse('mon', 'month', true);
        $T->clear_var('bd');
        // Get the next month
        if ((int)$M < 12) {
            $M++;
        } else {
            $M = 1;
        }
        $M = sprintf('%02d', $M);
    }
    if ($have_data) {
        $T->parse('output', 'block');
        $retval = $T->finish($T->get_var('output'));
    }
    return $retval;
}


/**
 * Create the sideblock showing upcoming birthdays by week.
 * The plugin installs the function with no arguments but the site admin
 * can add functions with an argument for the desired number of weeks.
 *
 * @param   array   $blkinfo    All block information
 * @param   integer $weeks      Number of weeks
 * @return  string      HTML for the block
 */
function phpblock_birthdays_week($blkinfo=array(), $weeks=2)
{
    global $_CONF;

    $s_dt = new \Date('now', $_CONF['timezone']);
    $e_dt = new \Date();      // Just create a date object for later
    $have_data = false;

    $T = new Template(Config::path_template());
    $T->set_file('block', 'phpblock.thtml');
    $T->set_block('block', 'month', 'mon');
    $T->set_var('lang_view_all', MO::_('View All'));
    for ($i = 0; $i < $weeks; $i++) {
        // Set ending date to today + 6 days
        $e_dt->setTimestamp($s_dt->toUnix() + 518400);
        $start = $s_dt->format('Y-m-d');
        $end = $e_dt->format('Y-m-d');
        $thisweek = \Birthdays\Birthday::getRange($start, $end);
        // Change starting date here in case the continue clause is invoked
        $s_dt->setTimestamp($s_dt->toUnix() + 604800);
        if (empty($thisweek)) {
            continue;
        } else {
            $have_data = true;
        }
        $T->set_var(array(
            'period' => \Birthdays\Birthday::formatDate($start) . ' - ' .
                        \Birthdays\Birthday::formatDate($end),
        ) );
        $T->set_block('block', 'bdays', 'bd');
        foreach($thisweek as $bday=>$bdays) {
            foreach ($bdays as $uid) {
                $T->set_var('username', COM_getDisplayName($uid));
                $T->set_var('birthday', \Birthdays\Birthday::formatDate($bday));
                $T->parse('bd', 'bdays', true);
            }
        }
        $T->parse('mon', 'month', true);
        $T->clear_var('bd');
    }
    if ($have_data) {
        $T->parse('output', 'block');
        $retval = $T->finish($T->get_var('output'));
    } else {
        $retval = '';
    }
    return $retval;
}


/**
 * Get the URL to the configuration help file.
 *
 * @param   string  $option     Config option selected
 * @param   string  $doclang    Document language
 * @return  string              URL to document
 */
function plugin_getconfigelementhelp_birthdays($option, $doclang='english')
{
    global $_CONF;

    $file = 'config.html';
    $doc_path = Config::get('url') . "/docs/$doclang/";
    $doc_url = '';

    if (!is_file($doc_path . $file)) {
        $doclang = 'english';
    }
    $doc_url = Config::get('url') . "/docs/$doclang/$file";
    if (!empty($option)) {
        $doc_url .= '#desc_' . $option;
    }
    return array($doc_url, 0);
}


/**
 * Disable this plugin's blocks when the plugin is disabled.
 *
 * @param   boolean $enabled    Plugin's "enabled" state
 */
function plugin_enablestatechange_birthdays($enabled)
{
    global $_TABLES, $_PLUGINS;

    $enabled = $enabled == true ? 1 : 0;
    $db = Database::getInstance();

    // If trying to enable the plugin but lgLib is not available,
    // take no action and return
    if ($enabled && !in_array('lglib', $_PLUGINS)) {
        // Re-disable the plugin
        try {
            $db->conn->executeUpdate(
                "UPDATE {$_TABLES['plugins']} SET pi_enabled = 0 WHERE pi_name = ?",
                array(Config::PI_NAME),
                array(Database::STRING)
            );
        } catch (\Exception $e) {
            Logger::System(__FUNCTION__ . ': ' . $e->getMessage());
        }
        $enabled = 0;
    }

    if (!$enabled) {
        $blocks = array('birthdays', 'birthdays_week');
        foreach ($blocks as $block) {
            try {
                $db->conn->executeUpdate(
                    "UPDATE {$_TABLES['blocks']} SET is_enabled = ? WHERE name = ?",
                    array($enabled, $block),
                    array(Database::INTEGER, Database::STRING)
                );
            } catch (\Exception $e) {
                Logger::System(__FUNCTION__ . ': ' . $e->getMessage());
            }
        }
    }
}


/**
 * Dispaly a Happy Birthday message when a user logs in.
 *
 * @param   integer $uid    User ID
 * @return  void
 */
function plugin_user_login_birthdays($uid)
{
    global $_CONF;

    // Show nothing if disabled
    if (!Config::get('login_greeting')) return;

    $month = \Birthdays\Birthday::currentDate()['month'];
    $day = \Birthdays\Birthday::currentDate()['day'];
    // Get all user birthdays for today. This allows the request
    // to go through caching, and the result set should be small anyway.
    $x = \Birthdays\Birthday::getAll($month, $day);
    if ($x) {
        foreach ($x as $data) {
            if ($data->getUid() == $uid) {
                COM_setMsg(sprintf(MO::_('Happy Birthday, %s!'), COM_getDisplayName($uid)));
                break;
            }
        }
    }
    return;
}


/**
 * Check if the current is a moderator.
 *
 * @return  boolean     True if a moderator, False if not
 */
function plugin_ismoderator_birthdays()
{
    static $isMod = NULL;
    if ($isMod === NULL ) {
        $isMod = SEC_hasRights('birthdays.admin');
    }
    return $isMod;
}


/**
 * Provides the getItemInfo function for birthdays.
 *
 * @param   string  $id         Product ID
 * @param   string  $what       Comma-separated list of fields to return
 * @param   integer $uid        User ID
 * @param   array   $options    Not used
 * @return  array           Array of product information
 */
function plugin_getiteminfo_birthdays($id, $what, $uid=0, $options=array())
{
    global $_TABLES, $_CONF;

    $properties = explode(',', $what);
    $props = array();
    $retval = array();

    if (in_array('search_index', $properties)) {
        return array();
    }

    if ($id == '*') {
        $bdays = Birthdays\Birthday::getAll();
    } else {
        $bdays = array(Birthdays\Birthday::getInstance($id));
    }

    foreach ($bdays as $bday) {
        $username = COM_getDisplayName($bday->getUid());
        foreach ($properties as $p) {
            switch ($p) {
            case 'group_id':
                $props[$p] = Config::get('grp_access');
                break;
            case 'id':
            case 'author':
            case 'owner_id':
                $props[$p] = $bday->getUid();
                break;
            case 'month':
                $props[$p] = $bday->getMonth();
                break;
            case 'day':
                $props[$p] = $bday->getDay();
                break;
            case 'recur_type':
                $props[$p] = 3;     // Evlist code for Yearly
                break;
            case 'recur_interval':
                $props[$p] = 1;     // Recurs every one year
                break;
            case 'content':
                $props[$p] = \Birthdays\Birthday::formatDate($bday->getMonth(), $bday->getDay());
                break;
            case 'date':
                // Use 2016 as year since it's a leap year, allow for 29 Feb.
                $props[$p] = sprintf('2016-%02d-%02d', $bday->getMonth(), $bday->getDay());
                break;
            case 'title':
            case 'excerpt':
                $props[$p] = sprintf(MO::_('%s\'s Birthday'), $username);
                break;
            case 'summary':
                $props[$p] = sprintf(MO::_("It's %s's birthday today!"), $username);
                break;
            case 'description':
                $props[$p] = '';
                if (Config::get('zodiac_in_dscp')) {
                    $props[$p] .= MO::_('Astrological Sign') . ': ' .
                        Birthdays\Models\Zodiac::getSign($bday->getMonth(), $bday->getDay());
                }
                break;
            case 'author_name':
                $props[$p] = COM_getDisplayName($bday->getUid());
                break;
            case 'is_event':
                $props[$p] = true;
                break;
            case 'url':
                $props[$p] = $_CONF['site_url'] . '/users.php?mode=profile&uid=' . $bday->getUid();
                break;
            case 'show_upcoming':
                if (Config::get('show_upcoming')) {
                    $props[$p] = 1;
                } else {
                    $props[$p] = 0;
                }
                break;
            case 'perms':
                $props['perms'] = array(
                    'owner_id' => $bday->getUid(),
                    'group_id' => 1,
                    'perm_owner' => 2,
                    'perm_group' => 2,
                    'perm_members' => 1,
                    'perm_anon' => 1,
                );
                $Grps = Group::withFeature('birthdays.view');
                foreach ($Grps as $gid) {
                    if ($gid == 13) {
                        $props['perms']['perm_members'] = 2;
                    } elseif ($gid == 2) {
                        $props['perms']['perm_members'] = 2;
                        $props['perms']['perm_anon'] = 2;
                    } else {
                        $props['perms']['group_id'] = $gid;
                    }
                }
                break;
            case 'search_index':
                return array();
                break;
            default:
                $props[$p] = NULL;
                break;
            }
        }
        $mapped = array();
        foreach ($props as $key => $value) {
            if ($id == '*') {
                if ($value != '') {
                    $mapped[$key] = $value;
                }
            } else {
                $mapped[$key] = $value;
            }
        }

        if ($id == '*') {
            $retval[] = $mapped;
        } else {
            $retval = $mapped;
            break;
        }
    }
    if (($id != '*') && (count($retval) == 1)) {
        $tRet = array_values($retval);
        $retval = $tRet[0];
    }
    if ($retval === '' || (is_array($retval) && count($retval) == 0)) {
        return NULL;
    } else {
        return $retval;
    }
}


/**
 * Perform scheduled functions, such as notifications and cancellations.
 */
function plugin_runScheduledTask_birthdays()
{
    global $_TABLES, $_VARS, $_USER, $_CONF;

    if (!Config::get('enable_subs') && !Config::get('enable_cards')) {
        return;     // don't go through the loop if nothing will be sent
    }

    $db = Database::getInstance();
    $today = $_CONF['_now']->format('Ymd', true);
    $lastrun = isset($_VARS['birthdays_lastrun']) ? $_VARS['birthdays_lastrun'] : '';
    if ($lastrun == $today) {
        return;     // run once daily only
    }
    try {
        $db->conn->executeUpdate(
            "INSERT INTO {$_TABLES['vars']} VALUES('birthdays_lastrun', ?)
            ON DUPLICATE KEY UPDATE value = ?",
            array($today, $today),
            array(Database::STRING, Database::STRING)
        );
    } catch (\Exception $e) {
        Logger::System(__FUNCTION__ . ': ' . $e->getMessage());
    }

    $BDays = Birthdays\Birthday::getAll($dt['month'], $dt['day']);
    foreach ($BDays as $BDay) {
        if (Config::get('enable_subs')) {
            PLG_sendSubscriptionNotification(
                'birthdays', 'birthday_sub',
                $BDay->getUid(), $BDay->getUid(), 0
            );
        }

        $U = new Birthdays\Models\User($BDay->getUid());
        if (Config::get('grp_cards') > 0) {
            $hasRight = SEC_inGroup(Config::get('grp_cards'), $BDay->getUid());
        } else {
            $hasRight = $U->hasRight('birthdays.card');
        }
        if (
            $hasRight &&
            (!Config::get('cards_only_active') || $U->isActive())
        ) {
            $BDay->sendCard();
        }
    }
}


/**
 * Set the email format for birthday reminders.
 *
 * @param   string  $category   Notification type, e.g. birthday_sub
 * @param   string  $track_id   Item being tracked (not used)
 * @param   integer $uid        Item ID (ID of birthday user in this case)
 * @param   integer $poster_id  Poster ID (not used)
 * @return  array       Array(HTML message, Text message, Subject)
 */
function plugin_subscription_email_format_birthdays($category, $track_id, $uid, $poster_id)
{
    global $_CONF, $_VARS;

    $uid = (int)$uid;
    $name = COM_getDisplayName($uid);
    $name = @htmlspecialchars($name,ENT_QUOTES, COM_getEncodingt(),true);
    Birthdays\MO::initUser($uid);
    $fullname = $name;
    $nameparts = PLG_callFunctionForOnePlugin(
        'plugin_parseName_lglib',
        array(
            1 => $name,
        )
    );
    if ($nameparts !== false) {
        $fname = $nameparts['fname'];
        $lname = $nameparts['lname'];
        $name = $fname;
    } else {
        $fname = '';
        $lname = '';
    }

    $T = new Template(array(
        $_CONF['path_layout'] . 'email/',
        __DIR__ . '/templates/notify/' . MO::getLanguage(),
        __DIR__ . '/templates/notify/english_utf-8',    // default language
        __DIR__ . '/templates/notify', // catch templates using language strings
    ) );
    $T->set_file (array(
        'header_tpl' => 'header.thtml',     // from templates/notify
        'text_outer' => 'mailtemplate_text.thtml',  // from layout
        'html_outer' => 'mailtemplate_html.thtml',  // from layout
        'htmlcard'  => 'html_card.thtml',   // from templates/notify/lang
        'htmlsub'   => 'html_sub.thtml',    // from templates/notify/lang
    ));

    $T->set_var(array(          // common text elements
        'uid'           => $uid,
        'uid_code'      => urlencode(Birthdays\Models\User::encrypt($uid)),
        'firstname'     => $fname,
        'lastname'      => $lname,
        'fullname'      => $fullname,
        'lang_unsub'    => MO::_('Unsubscribe'),
        'lang_to_unsub' => MO::_('To unsubscribe from these notifications, please click'),
        'lang_this_link' => MO::_('this link'),
        'lang_noreply'  => MO::_('This email was generated automatically. Please do not reply to this email.'),
        'site_name'     => $_CONF['site_name'],
        'site_email'    => $_CONF['site_mail'],
    ) );

    if ($category == 'birthday_sub') {
        // Subscription notification
        $subject = sprintf(MO::_("It's %s's birthday today!"), $name);
        $T->parse('htmlmessage', 'htmlsub');
    } else {
        $B = new Birthdays\Birthday($uid);
        // Happy Birthday message to users.
        $subject = sprintf(MO::_('Happy Birthday from %s'), $_CONF['site_name']);
        $T->parse('htmlmessage', 'htmlcard');
    }
    $html_content = $T->finish($T->get_var('htmlmessage'));
    $header = $T->parse('', 'header_tpl');
    $html_content = $header . $html_content;
    $T->set_block('html_outer', 'content', 'contentblock');
    $T->set_var('content_text', $html_content);
    $T->parse('contentblock', 'content', true);

    $html2TextConverter = new \Html2Text\Html2Text($html_content);
    $text_content = $html2TextConverter->getText();
    $T->set_block('text_outer', 'contenttext', 'contenttextblock');
    $T->set_var('content_text', $text_content);
    $T->parse('contenttextblock', 'contenttext',true);

    $T->parse('output', 'html_outer');
    $html_msg = $T->finish($T->get_var('output'));
    $T->parse('textoutput', 'text_outer');
    $text_msg = $T->finish($T->get_var('textoutput'));
    $retval = array(
        'msghtml' => $html_msg,
        'msgtext' => $text_msg,
        'subject' => $subject,
    );
    Birthdays\MO::reset();
    return $retval;
}


/**
 * Service function to get birthdays for a time period.
 * Args can be an array of start and end dates, or a month and day.
 *
 * @param   array   $args       Array of arguments
 * @param   array   $output     Array of output data
 * @param   string  $svc_msg    Unused
 * @return  integer     Return value
 */
function service_getBirthdays_birthdays($args, &$output, &$svc_msg)
{
    $output = array();
    if (!\Birthdays\Birthday::canView()) {
        // Not an error, but return nothing
        return PLG_RET_OK;
    }

    // Args must be an array
    if (!is_array($args)) $args = array($args);

    if (isset($args['start'])) {
        // Get birthdays within a date range, one day by default
        $start = $args['start'];
        $end = isset($args['end']) ? $args['end'] : $start;
        $output = \Birthdays\Birthday::getRange($start, $end);
    } elseif (isset($args['month'])) {
        // Get birthdays for a single month or month/day
        $day = isset($args['day']) ? (int)$args['day'] : 0; // optional day
        $output = \Birthdays\Birthday::getAll($args['month'], $day);
    } else {
        // Get all birthdays in the table
        $output = \Birthdays\Birthday::getAll();
    }
    return PLG_RET_OK;
}


/**
 * Get a group list for the configuration elements.
 * Skip `All Users` since only registered users can submit birthdays.
 * A value of `0` indicates that the features will be used for access instead.
 *
 * @return  arrya       Array of name=>id pairs
 */
function BD_configlist_groups() : array
{
    global $_TABLES;

    static $A = NULL;
    if ($A === NULL) {
        $A = array(
            '-- ' . MO::_('Feature-Controlled') . ' --' => 0,
        );

        try {
            $stmt = $db->conn->executeQuery(
                "SELECT grp_id, grp_name FROM {$_TABLES['groups']}
                ORDER BY grp_name ASC"
            );
        } catch (\Exception $e) {
            Logger::System(__FUNCTION__ . ': ' . $e->getMessage());
            $stmt = NULL;
        }
        if ($stmt) {
            while ($row = $stmt->fetch(Database::ASSOCIATIVE)) {
                if ($row['grp_id'] == 2) {
                    // All Users group is meaningless, only registered users
                    // can submit birthdays
                    continue;
                }
                $row['grp_name'] = htmlspecialchars($row['grp_name']);
                $A[$row['grp_name']] = $row['grp_id'];
            }
        }
    }
    return $A;
}


/**
 * Create an array of groups for the configmanager.
 *
 * @return  array               Array of groups as name=>ID
 */
function plugin_configmanager_select_grp_access_birthdays()
{
    return BD_configlist_groups();
}


/**
 * Create an array of groups for the configmanager.
 *
 * @return  array               Array of groups as name=>ID
 */
function plugin_configmanager_select_grp_cards_birthdays()
{
    return BD_configlist_groups();
}


/**
 * Export user data when requested by the privacy plugin.
 *
 * @param   integer $uid        User ID
 * @param   string  $email      User Email Address (not used)
 * @param   string  $username   User Name (not used)
 * @param   string  $ip         IP address (not used)
 * @return  string              XML-formatted data
 */
function plugin_privacy_export_birthdays($uid, $email='', $username='', $ip='')
{
    $retval = '';
    $bday = \Birthdays\Birthday::getInstance($uid);
    if ($bday !== NULL) {
        $retval .= "<birthdays>\n";
        $retval .= '<birthday>' . \Birthdays\Birthday::formatDate($bday) . '</birthday>' . "\n";
        $retval .= "</birthdays>\n";
        if (function_exists('tidy_repair_string')) {
            $retval = tidy_repair_string($retval, array('input-xml' => 1));
        }
    }
    return $retval;
}

require_once __DIR__ . '/services.inc.php';

