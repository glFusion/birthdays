<?php
/**
 * Functions to log and display messages.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2021 Lee Garner <lee@leegarner.com>
 * @package     birthdays
 * @version     v1.0.2
 * @since       v1.0.2
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Birthdays;
use glFusion\Log\Log;


/**
 * Class to log and display messages.
 * @package birthdays
 */
class Logger
{
    /**
     * Log activity to a plugin-specific log file.
     *
     * @param   string  $logentry   Text to log
     * @param   string  $logfile    Log filename
     */
    private static function write(string $logentry, string $logfile) : void
    {
        global $_CONF, $_USER, $LANG01;

        if ($logentry == '') {
            return;
        }

        // A little sanitizing
        $logentry = str_replace(
            array('<\?', '\?\>'),
            array('(@', '@)'),
            $logentry
        );

        $timestamp = strftime( '%c' );

        // Can't open the log file?  Return an error
        if (!$file = fopen($logfile, 'a')) {
            Log::write('system', Log::ERROR,
                "Birthdays Logger: " . $LANG01[33] . $logfile . ' (' . $timestamp . ')'
            );
            return;
        }

        // Get the user name if it's not anonymous
        if (isset($_USER['uid'])) {
            $byuser = $_USER['uid'] . '-'.
                COM_getDisplayName(
                    $_USER['uid'],
                    $_USER['username'],
                    $_USER['fullname']
                );
        } else {
            $byuser = 'anon';
        }
        $byuser .= '@' . $_SERVER['REMOTE_ADDR'];

        // Write the log entry to the file
        fputs($file, "$timestamp ($byuser) - $logentry\n");
        fclose($file);
    }


    /**
     * Write an entry to the Audit log.
     *
     * @param   string  $msg        Message to log
     * @return  void
     */
    public static function audit(string $msg) : void
    {
        global $_CONF;

        $logfile = $_CONF['path_log'] . Config::PI_NAME . '.log';
        self::write($msg, $logfile);
    }


    /**
     * Write an entry to the system log.
     *
     * @param   string  $msg        Message to log
     * @return  void
     */
    public static function System(string $msg) : void
    {
        Log::write('system', Log::ERROR, $msg);
    }


    /**
     * Write a debug log message.
     * Uses the System() function if debug logging is enabled.
     *
     * @param   string  $msg        Message to log
     * @return  void
     */
    public static function Debug(string $msg) : void
    {
        if ((int)Config::get('log_level') <= 100) {
            self::System('DEBUG: ' . $msg);
        }
    }


    /**
     * Log a thrown exception.
     *
     * @param   Exception   $e      PHP Exception object
     */
    public static function logException(\Exception $e) : void
    {
        $msg = $e->getFile() . ': ' . $e->getLine() . ':: ' . $e->getMessage();
        self::System($msg);
    }

}
