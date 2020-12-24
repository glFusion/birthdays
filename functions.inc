<?php
/**
 * glFusion API functions for the Birthdays plugin.
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

/** Import configuration-handling class */
$config = \config::get_instance();
$_BD_CONF = $config->get_config('birthdays');

require_once __DIR__ . '/birthdays.php';

/**
 * Include the plugin-specific language file.
 * Also includes the corresponding admin-created custom language file, if any.
 * Looks for the site language first, then defaults to english.php if nothing else found.
 */
$langpath = __DIR__ . '/language';
if (is_file("$langpath/{$_CONF['language']}.php")) {
    $langfile = "{$_CONF['language']}.php";
} else {
    $langfile = 'english.php';
}
require_once "$langpath/$langfile";

if (is_file("$langpath/custom/$langfile")) {
    /** Include any custom language file, if found */
    include_once "$langpath/custom/$langfile";
}

// Set commonly-used variables from the config
$_BD_CONF['url'] = "{$_CONF['site_url']}/{$_BD_CONF['pi_name']}";
$_BD_CONF['admin_url'] = "{$_CONF['site_admin_url']}/plugins/{$_BD_CONF['pi_name']}";
$_BD_CONF['pi_path'] = __DIR__;

// Include the user-supplied configuration file, if any
if (is_file(__DIR__ . '/config.php')) {
    include_once __DIR__ . '/config.php';
}

// Disable features that don't work in older glFusion versions
if (version_compare(GVERSION, '1.7.4', '<')) {
    // Cards work, but Subscriptions require an update to
    // PLG_sendSubscriptionNotification()
    $_BD_CONF['enable_subs'] = 0;
}

// Use the global dateonly format as a default if the format isn't defined
if (!isset($_BD_CONF['format']) || empty($_BD_CONF['format'])) {
    if ($_CONF['dateonly'] != '') {
        $_BD_CONF['format'] = $_CONF['dateonly'];
    } else {
        $_BD_CONF['format'] = 'M d';    // final fallback
    }
}

spl_autoload_register(function($class)
{
    if (strpos($class, 'Birthdays\\') === 0) {
        $class = str_replace('Birthdays\\', '', $class);
        $path = __DIR__ . '/classes/' . $class . '.class.php';
        if (file_exists($path)) {
            include $path;
        }
    }
});


/**
 * Returns the items for this plugin that should appear on the main menu.
 * NOTE: this MUST return the url/value pairs in the following format
 *       label => url
 *
 * @return  array   Associative array of menu items to be added to menu
 */
function plugin_getmenuitems_birthdays()
{
    global $_BD_CONF, $LANG_BD00;

    $menuitems = array();
    if (\Birthdays\Birthday::canView()) {
        $menuitems[$LANG_BD00['pi_title']] = $_BD_CONF['url'];
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
    global $LANG_BD00, $_BD_CONF;

    if (plugin_ismoderator_birthdays()) {
        return array ($LANG_BD00['pi_title'],
                      $_BD_CONF['admin_url'] . '/index.php'
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
    global $LANG_BD00, $_BD_CONF;

    if (plugin_ismoderator_birthdays()) {
        return array($LANG_BD00['pi_title'],
                      $_BD_CONF['admin_url'] . '/index.php',
                      plugin_geticon_birthdays());
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
    global $_BD_CONF;
    return $_BD_CONF['pi_version'];
}


/**
 * Returns the URL of the plugin's icon.
 *
 * @return  string      URL of the icon
 */
function plugin_geticon_birthdays()
{
    global $_CONF, $_BD_CONF;
    return "{$_CONF['site_url']}/{$_BD_CONF['pi_name']}/images/birthdays.png";
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
    global $_CONF, $_BD_CONF;

    $styles = array();
    $files = array(
        'style.css',
    );
    $pi_css = __DIR__ . '/css/';
    $layout_css = "{$_CONF['path_layout']}plugins/{$_BD_CONF['pi_name']}/";
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
        $retval = \Birthdays\Birthday::editForm($uid);
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
    ) );
}


/**
 * Show the user's birthday in the public profile.
 *
 * @param   integer $uid    User ID
 * @param   object  $T      Template object
 */
function plugin_profilevariablesdisplay_birthdays($uid, &$T)
{
    global $_BD_CONF;

    $retval = '';

    // Make sure that a valid UID was passed in.
    if (empty($uid) || !\Birthdays\Birthday::canView()) {
        return '';
    }

    $LT = new Template(__DIR__ . '/templates');
    $LT->set_file(array(
        'block' => 'profileblock.thtml',
    ));
    $bday = \Birthdays\Birthday::getUser($uid);
    if (empty($bday)) return;
    $LT->set_var('birthday', \Birthdays\Birthday::formatDate($bday));
    $LT->parse('output', 'block');
    $existingFields = $T->get_var('plugin_fields');
    $T->set_var('plugin_fields',
                $existingFields . $T->finish($LT->get_var('output')));
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
    global $_BD_CONF, $LANG_BD00, $_CONF, $LANG_MONTH;

    $M = \Birthdays\Birthday::currentDate()['month'];  // starting month
    $have_data = false;
    $retval = '';

    $T = new Template(__DIR__ . '/templates');
    $T->set_file('block', 'phpblock.thtml');
    $T->set_block('block', 'month', 'mon');
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
    global $_BD_CONF, $LANG_BD00, $_CONF;

    $s_dt = new \Date('now', $_CONF['timezone']);
    $e_dt = new \Date();      // Just create a date object for later
    $have_data = false;

    $T = new Template(__DIR__ . '/templates');
    $T->set_file('block', 'phpblock.thtml');
    $T->set_block('block', 'month', 'mon');
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
    global $_CONF, $_BD_CONF;

    $file = 'config.html';
    $pi_name = $_BD_CONF['pi_name'];
    //$doc_url = SRCH_getDocURL($file, $_SRCH_CONF['pi_name'], $doclang);
    $doc_path = "{$_CONF['path_html']}$pi_name/docs/$doclang/";
    $doc_url = '';

    if (!is_file($doc_path . $file)) {
        $doclang = 'english';
    }
    $doc_url = "{$_CONF['site_url']}/$pi_name/docs/$doclang/$file";
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
    global $_TABLES, $_PLUGINS, $_BD_CONF;

    $enabled = $enabled == true ? 1 : 0;

    // If trying to enable the plugin but lgLib is not available,
    // take no action and return
    if ($enabled && !in_array('lglib', $_PLUGINS)) {
        // Re-disable the plugin
        DB_change($_TABLES['plugins'], 'pi_enabled', 0, 'pi_name', $_BD_CONF['pi_name']);
        $enabled = 0;
    }

    if (!$enabled) {
        $blocks = array('birthdays', 'birthdays_week');
        foreach ($blocks as $block) {
            DB_change($_TABLES['blocks'], 'is_enabled', $enabled, 'name', $block);
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
    global $_CONF, $LANG_BD00, $_BD_CONF;

    // Show nothing if disabled
    if (!$_BD_CONF['login_greeting']) return;

    $month = \Birthdays\Birthday::currentDate()['month'];
    $day = \Birthdays\Birthday::currentDate()['day'];
    // Get all user birthdays for today. This allows the request
    // to go through caching, and the result set should be small anyway.
    $x = \Birthdays\Birthday::getAll($month, $day);
    if ($x) {
        foreach ($x as $data) {
            if ($data->getUid() == $uid) {
                COM_setMsg(sprintf($LANG_BD00['msg_happy_birthday'], COM_getDisplayName($uid)));
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
    return SEC_hasRights('birthdays.admin');
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
    global $_TABLES, $_BD_CONF, $LANG_BD00, $_CONF;

    $properties = explode(',', $what);
    $retval = array();
    if ($id == '*') {
        $bdays = \Birthdays\Birthday::getAll();
    } else {
        $bdays = array(\Birthdays\Birthday::getInstance($id));
    }

    foreach ($bdays as $bday) {
        foreach ($properties as $p) {
            switch ($p) {
            case 'group_id':
                $props[$p] = $_BD_CONF['grp_access'];
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
            case 'description':
            case 'excerpt':
            case 'summary':
                $props[$p] = sprintf($LANG_BD00['dscp'], COM_getDisplayName($bday->getUid()));
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
                $props[$p] = isset($_BD_CONF['show_upcoming']) && $_BD_CONF['show_upcoming'] ? 1 : 0;
                break;
            case 'search_index':
                return NULL;        // Birthdays does not support searching
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
    global $_TABLES, $_VARS, $_BD_CONF, $_USER;

    if (!$_BD_CONF['enable_subs'] && !$_BD_CONF['enable_cards']) {
        return;     // don't go through the loop if nothing will be sent
    }

    $dt = \Birthdays\Birthday::currentDate();
    $today = sprintf("%4d%02d%02d", $dt['year'], $dt['month'], $dt['day']);
    $lastrun = isset($_VARS['birthdays_lastrun']) ? $_VARS['birthdays_lastrun'] : '';
    if ($lastrun == $today) {
        return;     // run once daily only
    }
    DB_query(
        "INSERT INTO {$_TABLES['vars']} VALUES('birthdays_lastrun', '$today')
        ON DUPLICATE KEY UPDATE value = '$today'"
    );
    $bdays = \Birthdays\Birthday::getAll($dt['month'], $dt['day']);
    foreach ($bdays as $bday) {
        $uid = $bday->getUid();

        if ($_BD_CONF['enable_subs']) {
            PLG_sendSubscriptionNotification('birthdays', 'birthday_sub', '', $uid, 0);
        }

        if ($_BD_CONF['grp_cards'] > 0) {
            // Check if the user can receive birthday cards.
            
            if (!SEC_inGroup($_BD_CONF['grp_cards'], $uid)) {
                continue;
            }
            // Borrow the email format function to create the message
            $msg = plugin_subscription_email_format_birthdays('birthday_card', '', $uid, '');
            $name = COM_getDisplayName($uid);
            $email = DB_getItem($_TABLES['users'], 'email', "uid = $uid");
            if (empty($email)) continue;    // need a valid email
            $msgData = array(
                'htmlmessage'   => $msg['msghtml'],
                'textmessage'   => $msg['msgtext'],
                'subject'       => $msg['subject'],
                'from'          => NULL,
                'to'            => array(
                    'name'  => $name,
                    'email' => $email,
                ),
            );
            COM_emailNotification($msgData);
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
    global $_CONF, $_BD_CONF;

    $uid = (int)$uid;
    $name = COM_getDisplayName($uid);
    $name = @htmlspecialchars($name,ENT_QUOTES, COM_getEncodingt(),true);
    $lang = BIRTHDAYS_loadLanguage($uid);
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
        __DIR__ . '/templates/notify', // catch templates using language strings
    ) );
    $T->set_file (array(
        'text_outer' => 'mailtemplate_text.thtml',
        'html_outer' => 'mailtemplate_html.thtml',
        'htmlcard'  => 'html_card.thtml',
        'htmlsub'   => 'html_sub.thtml',
    ));
    $T->set_var(array(          // common text elements
        'uid'           => $uid,
        'firstname'     => $fname,
        'lastname'      => $lname,
        'fullname'      => $fullname,
        'lang_unsub'    => $lang['unsubscribe'],
        'lang_to_unsub' => $lang['sub_unsub'],
        'lang_noreply'  => $lang['email_autogen'],
        'site_name'     => $_CONF['site_name'],
    ) );

    if ($category == 'birthday_sub') {
        // Subscription notification
        $subject = sprintf($lang['sub_title'], $def_name);
        /*$T->set_var(array(
            'sub_title'     => sprintf($lang['sub_title'], $name),
            'sub_message'   => sprintf($lang['sub_message'], $name),
            'sub_reason'    => sprintf($lang['sub_reason'], $name),
            'lang_birthday_notif' => $lang['sub_title'],
        ));*/
        $T->parse('htmlmessage', 'htmlsub');
    } else {
        // Happy Birthday message to users.
        $T->parse('htmlmessage', 'htmlcard');
        $subject = $lang['card_title'];
    }
    $html_content = $T->finish($T->get_var('htmlmessage'));
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


function BD_configlist_groups()
{
    global $_TABLES, $LANG_BD00;

    static $A = NULL;
    if ($A === NULL) {
        $A = array(
            '-- ' . $LANG_BD00['none'] . ' --' => 0,
        );

        $sql = "SELECT grp_id, grp_name FROM {$_TABLES['groups']}
            ORDER BY grp_name ASC";
        $res = DB_query($sql);
        if ($res && DB_numRows($res) > 0) {
            while ($row = DB_fetchArray($res, false)) {
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
    $bday = \Birthdays\Birthday::getUser($uid);
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


/**
 * Get a language file for a specific specific user ID.
 * This is the same as the code at the top of the file, but the language
 * strings are saved in a local array so the global $LANG_BD00 is unaffected.
 *
 * @param   integer $uid    User ID
 * @return  array           Language array
 */
function BIRTHDAYS_loadLanguage($uid)
{
    global $_CONF, $_TABLES;

    static $users = array();
    static $langs = array();

    $uid = (int)$uid;
    if (!isset($users[$uid])) {
        $users[$uid] = DB_getItem($_TABLES['users'], 'language', "uid = $uid");
        if (!$users[$uid]) $users[$uid] = $_CONF['language'];
    }
    $lang = $users[$uid];
    if (!isset($langs[$lang])) {
        $langpath = __DIR__ . '/language';
        if (is_file("{$langpath}/{$lang}.php")) {
            $langfile = "{$lang}.php";
        } else {
            $langfile= 'english_utf-8.php';
        }
        include "$langpath/$langfile";

        if (is_file("$langpath/custom/$langfile")) {
            /** Include any custom language file, if found */
            include "$langpath/custom/$langfile";
        }

        // Now we have a $LANG_BD00 variable with the language,
        // set it into the local static array
        $langs[$lang] = $LANG_BD00;
    }
    return $langs[$lang];
}

require_once __DIR__ . '/services.inc.php';

?>
