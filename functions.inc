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

/** Import configuration-handling class */
$config = \config::get_instance();
$_BD_CONF = $config->get_config('birthdays');

// TODO: Load from configuration system
require_once __DIR__ . '/birthdays.php';

/**
*   Include the plugin-specific language file, and the corresponding
*   admin-created custom language file, if any.  Looks for the site
*   language first, then defaults to english.php if nothing else found.
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

/** Include the user-supplied configuration file, if any */
if (is_file(__DIR__ . '/config.php')) {
    include_once __DIR__ . '/config.php';
}

// Use the global dateonly format as a default if the format isn't defined
if (isset($_BD_CONF['format']) && $_BD_CONF['format'] == '') {
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


// +---------------------------------------------------------------------------+
// | Geeklog Plugin API Implementations                                        |
// +---------------------------------------------------------------------------+

/**
*   Returns the items for this plugin that should appear on the main menu
*   NOTE: this MUST return the url/value pairs in the following format
*       label => url
*
*   @return array   Associative array of menu items to be added to menu
*/
function plugin_getmenuitems_birthdays()
{
    global $_BD_CONF;

    $menuitems = array();
    if (SEC_hasRights('birthdays.view')) {
        $menuitems[$_BD_CONF['pi_display_name']] = $_BD_CONF['url'];
    }
    return $menuitems;
}


/**
*   Check to see if this Plugin uses comments. It does not.
*
*   @return boolean     True or False
*/
function plugin_commentsupport_birthdays()
{
    return false;
}


/**
*   Called by the plugin Editor to display the current plugin code version
*   This may be different than the version installed and registered currently.
*   If newer then you may want to run the update
*
*   @return string  version number
*/
function plugin_chkVersion_birthdays()
{
    global $_BD_CONF;
    return $_BD_CONF['pi_version'];
}


/**
*   Returns the URL of the plugin's icon.
*
*   @return string      URL of the icon
*/
function plugin_geticon_birthdays()
{
    global $_CONF, $_BD_CONF;
    return "{$_CONF['site_url']}/{$_BD_CONF['pi_name']}/images/birthdays.gif";
}


/**
*   Get additional CSS into the site header.
*   Allows for custom CSS to be placed by the site admin.
*
*   @return array   List of paths to CSS files
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
*   Create an edit form in the Account Settings section.
*
*   @param  integer $uid        User ID
*   @param  string  $panel      Panel being populated
*   @param  string  $fieldset   Name of fieldset being populated
*   @return string              HTML for edit form. Never NULL.
*/
function plugin_profileedit_birthdays($uid, $panel, $fieldset)
{
    $retval = '';

    if ($panel == 'userinfo' && $fieldset == 'personalinfo') {
        $retval = Birthdays\Birthday::editForm($uid);
    //} elseif ($panel == '' && $fieldset == '') {
    //    $retval = Birthdays\Birthday::editForm($uid);
    }
    return $retval;
}


/**
*   Save the profile information when edited via Account Settings.
*   Used with glFusion 1.1.5 and above
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

    $bday = Birthdays\Birthday::getInstance($uid);
    $bday->Save(array(
            'month' => $_POST['birthday_month'],
            'day' => $_POST['birthday_day'],
    ) );
}


/**
*   Delete user-specific data when a user account is deleted.
*
*   @param  integer $uid    User ID being deleted
*/
function plugin_user_delete_birthdays($uid)
{
    \Birthdays\Birthday::Delete($uid);
}


/**
*   Create the sideblock showin upcoming birthdays
*
*   @return string      HTML for the block
*/
function phpblock_birthdays()
{
    global $_BD_CONF, $LANG_BD00, $_CONF;

    $T = new Template(__DIR__ . '/templates');
    $T->set_file('block', 'phpblock.thtml');
    $dt = new Date('now', $_CONF['timezone']);
    $year = $dt->Format('Y', true);
    $month = $dt->Format('n', true);
    $day = $dt->Format('d', true);
    $thismonth = Birthdays\Birthday::getAll($month);

    $T->set_block('block', 'thismonth', 'thism');
    foreach($thismonth as $bday) {
        $T->set_var('username', COM_getDisplayName($bday['uid']));
        $dt->setDate($year, $bday['month'], $bday['day']);
        $T->set_var('bday', $dt->Format($_BD_CONF['format'], true));
        $T->parse('thism', 'thismonth', true);
    }
    $T->set_var('thismonth_count', count($thismonth));

    ++$month;
    if ($month > 12) {
        $month = 1;
    }
    $nextmonth = Birthdays\Birthday::getAll($month);
    if (count($thismonth) == 0 && count($nextmonth) == 0) return '';

    $T->set_block('block', 'nextmonth', 'nextm');
    foreach ($nextmonth as $dt_str=>$bday) {
        $dt->setDate($year, $bday['month'], $bday['day']);
        $T->set_var('username', COM_getDisplayName($bday['uid']));
        $T->set_var('bday', $dt->Format($_BD_CONF['format'], true));
        $T->parse('nextm', 'nextmonth', true);
    }
    $T->set_var('nextmonth_count', count($nextmonth));

    $T->parse('output', 'block');
    $retval = $T->finish($T->get_var('output'));
    return $retval;
}


/**
*   Get the URL to the configuration help file.
*
*   @param  string  $option     Config option selected
*   @param  string  $doclang    Document language
*   @return string              URL to document
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


function BIRTHDAYS_canView()
{
    global $_BD_CONF;

    if (COM_isAnonUser() && $_BD_CONF['login_required']) {
        return false;
    } else {
        return true;
    }
}


function service_getBirthdays_birthdays($args, &$output, &$svc_msg)
{
    if (!BIRTHDAYS_canView()) {
        // Not an error, but return nothing
        $output = array();
        return PLG_RET_OK;
    }

    if (isset($args['start'])) {
        $start = $args['start'];
        $end = isset($args['end']) ? $args['end'] : $start;
        $output = \Birthdays\Birthday::getRange($start, $end);
    } elseif (isset($args['month'])) {
        $day = isset($args['day']) ? (int)$args['day'] : 0; // optional day
        $output = \Birthdays\Birthday::getAll($args['month'], $day);
    } else {
        $output = \Birthdays\Birthday::getAll();
    }
    return PLG_RET_OK;
}

?>
