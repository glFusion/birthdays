<?php
/**
 * Class to get the Zodiac sign from a given date.
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
class Zodiac
{
    /** Array of month names.
     * @var array */
    private static $SIGNS = NULL;


    /**
     * Create the language array from language strings.
     */
    private static function init()
    {
        if (self::$SIGNS === NULL) {
            self::$SIGNS = array(
                '0120' => MO::_('Capricorn'),
                '0218' => MO::_('Aquarius'),
                '0320' => MO::_('Pisces'),
                '0419' => MO::_('Aries'),
                '0520' => MO::_('Taurus'),
                '0620' => MO::_('Gemini'),
                '0722' => MO::_('Cancer'),
                '0822' => MO::_('Leo'),
                '0922' => MO::_('Virgo'),
                '1022' => MO::_('Libra'),
                '1121' => MO::_('Scorpio'),
                '1221' => MO::_('Sagittarius'),
                '1232' => MO::_('Capricorn'),  // since this wraps new year
            );
        }
    }


    /**
     * Get the Zodiac sign for a given month and day
     *
     * @param   integer $month  Month, 01 - 12
     * @param   integer $day    Day, 01 - 31
     * @return  string      Zodiac sign name
     */
    public static function getSign(int $month, int $day) : string
    {
        self::init();
        $key = sprintf('%02d%02d', $month, $day);
        foreach (self::$SIGNS as $ends=>$sign) {
            if ($key <= $ends) { 
                return $sign;
            }
        }
        return MO::_('Unknown');
    }

}
