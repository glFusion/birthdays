<?php


namespace Birthdays\Models;
use Birthdays\MO;

class Month
{
    private static $NAMES = NULL;


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


    public static function getName($key)
    {
        if (!self::$NAMES) self::init();
        return self::$NAMES[$key];
    }


    public static function getShortname($key)
    {
        if (!self::$NAMES) self::init();
        return substr(self::$NAMES[$key], 0, 3);
    }

}


