<?php
/**
 * Month name model for the Birthdays plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2021 Lee Garner <lee@leegarner.com>
 * @package     birthdays
 * @version     v1.0.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */


namespace Birthdays\Models;
use Birthdays\MO;

/**
 * Create a month array using language strings.
 * @package birthdays
 */
class Month
{
    /** Array of month names.
     * @var array */
    private static $NAMES = NULL;


    /**
     * Create the language array from language strings.
     */
    private static function init()
    {
        self::$NAMES = array(
            1 => MO::_('January'),
            2 => MO::_('February'),
            3 => MO::_('March'),
            4 => MO::_('April'),
            5 => MO::_('May'),
            6 => MO::_('June'),
            7 => MO::_('July'),
            8 => MO::_('August'),
            9 => MO::_('September'),
            10 => MO::_('October'),
            11 => MO::_('November'),
            12 => MO::_('December'),
        );
    }


    /**
     * Get the full name of the month.
     *
     * @param   integer $key    Month number, 1 - 12
     * @return  string      Full month name
     */
    public static function getName($key)
    {
        if (!self::$NAMES) self::init();
        if (isset(self::$NAMES[$key])) {
            return self::$NAMES[$key];
        } else {
            return MO::_('Unknown');
        }
    }


    /**
     * Get the short name (first three characters) of a month name.
     *
     * @param   integer $key    Month number, 1 - 12
     * @return  string      Full month name
     */
    public static function getShortname($key)
    {
        if (!self::$NAMES) self::init();
        if (isset(self::$NAMES[$key])) {
            return substr(self::$NAMES[$key], 0, 3);
        } else {
            return MO::_('Unknown');
        }
    }

}
