<?php
/**
 * Upgrade routines for the Birthdays plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018 Lee Garner <lee@leegarner.com>
 * @package     birthdays
 * @version     v0.2.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined('GVERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Upgrade the plugin.
 *
 * @param   boolean $dvlp   True to ignore errors (development upgrade)
 * @return  boolean     True on success, False on failure
 */
function BIRTHDAYS_do_upgrade($dvlp = false)
{
    global $_TABLES, $_CONF, $_PLUGINS, $_BD_CONF, $_PLUGIN_INFO;;

    $installed_ver = $_PLUGIN_INFO[$_BD_CONF['pi_name']]['pi_version'];
    $code_ver = plugin_chkVersion_birthdays();
    $current_ver = $installed_ver;

    // Get the config instance, several upgrades might need it
    $c = config::get_instance();

    if (!COM_checkVersion($current_ver, '0.0.2')) {
        // upgrade to 0.2.2
        $current_ver = '0.2.2';
        if (!BIRTHDAYS_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!BIRTHDAYS_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, $installed_ver)) {
        if (!BIRTHDAYS_do_set_version($installed_ver)) return false;
    }

    // Update any configuration item changes
    USES_lib_install();
    global $birthdaysConfigData;
    require_once __DIR__ . '/install_defaults.php';
    _update_config('birthdays', $birthdaysConfigData);

    // Clear all caches
    \Birthdays\Birthday::clearCache();
    CTL_clearCache();

    // Remove deprecated files
    BIRTHDAYS_remove_old_files();
}


/**
 * Actually perform any sql updates.
 * Gets the sql statements from the $UPGRADE array defined (maybe)
 * in the SQL installation file.
 *
 * @param   string  $version    Version being upgraded TO
 * @param   boolean $ignore_error   True to ignore SQL errors
 * @return  boolean     True on success, False on failure
 */
function BIRTHDAYS_do_upgrade_sql($version, $ignore_error=false)
{
    global $_TABLES, $_BD_CONF, $BD_UPGRADE;

    // If no sql statements passed in, return success
    if (!is_array($BD_UPGRADE[$version]))
        return true;

    // Execute SQL now to perform the upgrade
    COM_errorLog("--- Updating Birthdays to version $version", 1);
    foreach($BD_UPGRADE[$version] as $sql) {
        COM_errorLOG("Birthdays Plugin $version update: Executing SQL => $sql");
        DB_query($sql, '1');
        if (DB_error()) {
            COM_errorLog("SQL Error during Birthdays Plugin update", 1);
            if (!$ignore_error){
                return false;
            }
        }
    }
    COM_errorLog("--- Birthdays plugin SQL update to version $version done", 1);
    return true;
}


/**
 * Update the plugin version number in the database.
 * Called at each version upgrade to keep up to date with
 * successful upgrades.
 *
 * @param   string  $ver    New version to set
 * @return  boolean         True on success, False on failure
 */
function BIRTHDAYS_do_set_version($ver)
{
    global $_TABLES, $_BD_CONF;

    // now update the current version number.
    $sql = "UPDATE {$_TABLES['plugins']} SET
            pi_version = '$ver',
            pi_gl_version = '{$_BD_CONF['gl_version']}',
            pi_homepage = '{$_BD_CONF['pi_url']}'
        WHERE pi_name = '{$_BD_CONF['pi_name']}'";

    $res = DB_query($sql, 1);
    if (DB_error()) {
        COM_errorLog("Error updating the {$_BD_CONF['pi_display_name']} Plugin version to $ver",1);
        return false;
    } else {
        return true;
    }
}


/**
 * Remove deprecated files.
 */
function BIRTHDAYS_remove_old_files()
{
    global $_CONF;

    $paths = array(
        // private/plugins/birthdays
        __DIR__ => array(
            'classes/Date_Calc.class.php',
            'templates/phpblock_week.thtml',
        ),
        // public_html/birthdays
        $_CONF['path_html'] . 'birthdays' => array(
            'images/birthdays.gif',     // 0.0.2
        ),
    );

    foreach ($paths as $path=>$files) {
        foreach ($files as $file) {
            @unlink("$path/$file");
        }
    }
}

?>
