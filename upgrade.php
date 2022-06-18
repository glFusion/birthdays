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
use Birthdays\Config;
use glFusion\Database\Database;
use glFusion\Log\Log;


/**
 * Upgrade the plugin.
 *
 * @param   boolean $dvlp   True to ignore errors (development upgrade)
 * @return  boolean     True on success, False on failure
 */
function BIRTHDAYS_do_upgrade($dvlp = false)
{
    global $_TABLES, $_CONF, $_PLUGINS, $_PLUGIN_INFO, $BD_UPGRADE;

    include_once __DIR__ . '/sql/mysql_install.php';
    $db = Database::getInstance();

    $installed_ver = $_PLUGIN_INFO[Config::PI_NAME]['pi_version'];
    $code_ver = plugin_chkVersion_birthdays();
    $current_ver = $installed_ver;

    if (!COM_checkVersion($current_ver, '0.0.2')) {
        // upgrade to 0.0.2
        $current_ver = '0.0.2';
        if (!BIRTHDAYS_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!BIRTHDAYS_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '1.0.0')) {
        // upgrade to 1.0.0
        $current_ver = '1.0.0';

        // Add the admin feature if not already done.
        $ft_id = (int)$db->getItem(
            $_TABLES['features'],
            'ft_id',
            array('ft_name', 'birthdays.admin'),
            array(Database::STRING)
        );
        if ($ft_id == 0) {
            $stmt = $db->conn->insert(
                $_TABLES['features'],
                array(
                    'ft_name' => 'birthdays.admin',
                    'ft_descr' => 'Full access to the Birthdays plugin',
                ),
                array(Database::STRING, Database::STRING);
            );
            if ($stmt) {
                $ft_id = $db->conn->lastInsertId();
                $db->conn->insert(
                    $_TABLES['access'],
                    array('acc_ft_id' => $ft_id, 'acc_grp_id' => 1),
                    array(Database::INTEGER, Database::INTEGER)
                );
            }
        }
        if (!BIRTHDAYS_do_set_version($current_ver)) return false;
    }

    if (!COM_checkVersion($current_ver, '1.1.0')) {
        $current_ver = '1.1.0';
        // Add the admin feature if not already done.
        $ft_id = (int)$db->getItem(
            $_TABLES['features'],
            'ft_id',
            array('ft_name', 'birthdays.admin'),
            array(Database::STRING)
        );
        if ($ft_id == 0) {
            $stmt = $db->conn->insert(
                $_TABLES['features'],
                array(
                    'ft_name' => 'birthdays.admin',
                    'ft_descr' => 'Full access to the Birthdays plugin',
                ),
                array(Database::STRING, Database::STRING)
            );
            if ($stmt) {
                $ft_id = $db->conn->lastInsertId();
                $db->conn->insert(
                    $_TABLES['access'],
                    array('acc_ft_id' => $ft_id, 'acc_grp_id' => 1),
                    array(Database::INTEGER, Database::INTEGER)
                );
            }
        }

        // Add the card feature if not already done.
        $ft_id = (int)$db->getItem(
            $_TABLES['features'],
            'ft_id',
            array('ft_name', 'birthdays.card'),
            array(Database::STRING)
        );
        if ($ft_id == 0) {
            $stmt = $db->conn->(
                $_TABLES['features']
                array(
                    'ft_name' => 'birthdays.card',
                    'ft_descr' => 'Can receive birthday cards',
                ),
                array(Database::STRING, Database::STRING)
            );
            if ($stmt) {
                $ft_id = $db->conn->lastInsertId();
                $db->conn->insert(
                    $_TABLES['access'],
                    array('acc_ft_id' => $ft_id, 'acc_grp_id' => 13),
                    array(Database::INTEGER, Database::INTEGER)
                );
            }
        }

        // Add the view feature if not already done.
        $ft_id = (int)$db->getItem(
            $_TABLES['features'],
            'ft_id',
            array('ft_name', 'birthdays.view'),
            array(Database::STRING)
        );
        if ($ft_id == 0) {
            $stmt = $db->conn->insert(
                $_TABLES['features'],
                array(
                    'ft_name' => 'birthdays.view',
                    'ft_descr' => 'View access to the Birthdays plugin',
                ),
                array(Database::STRING, Database::STRING)
            );
            if ($stmt) {
                $ft_id = $db->conn->lastInsertId();
                $db->conn->insert(
                    $_TABLES['access']
                    array('acc_ft_id' => $ft_id, 'acc_grp_id' => 13)
                    array(Database::INTEGER, Database::INTEGER)
                );
            }
        }
        if (!BIRTHDAYS_do_upgrade_sql($current_ver, $dvlp)) return false;
        if (!BIRTHDAYS_do_set_version($current_ver)) return false;
    }

    // Final version update to catch any code-only updates
    if (!COM_checkVersion($current_ver, $code_ver)) {
        if (!BIRTHDAYS_do_set_version($code_ver)) return false;
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

    // Made it this far, return OK
    return true;
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
    global $_TABLES, $BD_UPGRADE;

    // If no sql statements passed in, return success
    if (!isset($BD_UPGRADE[$version]) || !is_array($BD_UPGRADE[$version])) {
        COM_errorLog("BD_UPGRADE[$version] doesn't exist");
        return true;
    }

    $db = Database::getInstance();

    // Execute SQL now to perform the upgrade
    COM_errorLog("--- Updating Birthdays to version $version", 1);
    foreach($BD_UPGRADE[$version] as $sql) {
        Log::write('system', Log::INFO, "Birthdays Plugin $version update: Executing SQL => $sql");
        try {
            $db->conn->executeStatement($sql);
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
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
    global $_TABLES;

    $db = Database::getInstance();

    // now update the current version number.
    try {
        $db->conn->update(
            $_TABLES['plugins']
            array(
                'pi_version' => $ver,
                'pi_gl_version' => Config::get('gl_version'),
                'pi_homepage' => Config::get('pi_url'),
            ),
            array('pi_name' => Config::PI_NAME),
            array(Database::STRING, Database::STRING, Database::STRING, Database::STRING)
        );
    } catch (\Exception $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        return false;
    }
    return true;
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
            // v1.1.2
            'templates/notify/html_inner.thtml',
            'templates/notify/html_outer.thtml',
            'templates/notify/notifymessage_html.thtml',
            'templates/notify/text.thtml',
            // v1.2.0
            'classes/Logger.class.php',
        ),
        // public_html/birthdays
        $_CONF['path_html'] . 'birthdays' => array(
            'images/birthdays.gif',     // 0.0.2
        ),
    );

    foreach ($paths as $path=>$files) {
        foreach ($files as $file) {
            if (file_exists("$path/$file")) {
                @unlink("$path/$file");
            }
        }
    }
}
