<?php
/**
 * Class to manage locale settings.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2021 Lee Garner <lee@leegarner.com>
 * @package     birthdays
 * @version     v1.1.0
 * @since       v1.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Birthdays;
use Birthdays\phpGettext\phpGettext;


/**
 * Manage locale settings for the Birthdays plugin.
 * @package birthdays
 */
class MO
{
    /** Language string.
     * @var string */
    private static $lang = 'english_utf-8';

    /** Locale string.
     * @var string */
    private static $locale = 'en_US';

    /** Language domain, e.g. plugin name.
     * @var string */
    private static $domain = NULL;

    /** Variable to save the original locale if changed by init().
     * @var string */
    private static $old_locale = NULL;

    /** Supported language name=>locale mapping.
     * @var array */
    private static $lang2locale = array(
        'dutch_utf-8' => 'nl_NL',
        'finnish_utf-8' => 'fi_FI',
        'german_utf-8' => 'de_DE',
        'polish_utf-8' => 'pl_PL',
        'czech_utf-8' => 'cs_CZ',
        'english_utf-8' => 'en_US',
        'french_canada_utf-8' => 'fr_CA',
        'spanish_colombia_utf-8' => 'es_CO',
    );


    /**
     * Initialize a language.
     * Sets the language domain and checks the requested language
     *
     * @access  public  so that notifications may set the language as needed.
     * @param   string  $lang   Language name, default is set by lib-common.php
     */
    public static function init($lang = NULL)
    {
        global $_CONF, $LANG_LOCALE;

        // Set the language domain to separate strings from the global
        // namespace.
        self::$domain = Config::PI_NAME;

        if (empty($lang)) {
            self::$lang = $_CONF['language'];
        } else {
            self::$lang = $lang;
        }

        // If not using the system language, then the locale
        // hasn't been determined yet.
        //if (!empty($lang) && $lang != $_CONF['language']) {

            // Save the current locale for reset()
            //self::$old_locale = setlocale(LC_MESSAGES, "0");

            // Validate and use the appropriate locale code.
            // Tries to look up the locale for the language first.
            // Then uses the global locale (ignoring the requested language).
            // Defaults to 'en_US' if a supportated locale wasn't found.
            if (isset(self::$lang2locale[self::$lang])) {
                self::$locale = self::$lang2locale[self::$lang];
            } elseif (
                isset($LANG_LOCALE) &&
                !empty($LANG_LOCALE) &&
                in_array($LANG_LOCALE, self::$lang2locale)
            ) {
                // Not found, try the global variable
                self::$locale = $LANG_LOCALE;
            } else {
                // global not set, fall back to US english
                self::$locale = 'en_US';
                self::$lang = 'english_utf-8';
            }
        //}

        $results = phpGettext::_setlocale(LC_MESSAGES, self::$locale);
        if ($results) {
            phpGettext::_bindtextdomain(self::$domain, __DIR__ . "/../locale");
            phpGettext::_bind_textdomain_codeset(self::$domain, 'UTF-8');
            phpGettext::_textdomain(self::$domain);
        }
    }


    /**
     * Reset the locale back to the previously-defined value.
     * Called after processes that change the locale for a specific user,
     * such as system-generated notifications.
     */
    public static function reset()
    {
        global $_CONF;

        self::init($_CONF['language']);
        /*if (self::$old_locale !== NULL) {
            phpGettext::_setlocale(LC_MESSAGES, self::$old_locale);
        }*/
    }


    /**
     * Initialize the locale for a specific user ID.
     *
     * @uses    self::init()
     * @param   integer $uid    User ID
     */
    public static function initUser($uid=0)
    {
        global $_USER, $_TABLES;

        if ($uid == 0) {
            $uid = $_USER['uid'];
        }
        $lang = DB_getItem($_TABLES['users'], 'language', 'uid = ' . (int)$uid);
        self::init($lang);
    }


    /**
     * Get the currently-used language string, e.g. `english_utf-8`.
     *
     * @return  string      Language string
     */
    public static function getLanguage() : string
    {
        return self::$lang;
    }


    /**
     * Get the locale code, e.g. `en_US`.
     *
     * @return  string      Locale code
     */
    public static function getLocale() : string
    {
        return self::$locale;
    }


    /**
     * Get a singular or plural language string as needed.
     *
     * @param   string  $single     Singular language string
     * @param   string  $plural     Plural language string
     * @return  string      Appropriate language string
     */
    public static function dngettext($single, $plural, $number)
    {
        if (!self::$domain) self::init();
        return phpGettext::_dngettext(self::$domain, $single, $plural, $number);
        //return \dngettext(self::$domain, $single, $plural, $number);
    }
    public static function _n($single, $plural, $number)
    {
        return self::dngettext($single, $plural, $number);
    }


    /**
     * Get a normal, singular language string.
     *
     * @param   string  $txt        Text string
     * @return  string      Translated text
     */
    public static function dgettext($txt)
    {
        if (!self::$domain) {
            self::init();
        }
        return phpGettext::_dgettext(self::$domain, $txt);
        //return \dgettext(self::$domain, $txt);
    }
    public static function _($txt)
    {
        return self::dgettext($txt);
    }

}


/**
 * Get a single or plural text string as needed.
 *
 * @param   string  $single     Text when $number is singular
 * @param   string  $plural     Text when $number is plural
 * @param   float   $number     Number used in the string
 * @return  string      Appropriate text string
 */
function _n($single, $plural, $number)
{
    return MO::dngettext($single, $plural, $number);
}


/**
 * Get a single text string, automatically applying the domain.
 *
 * @param   string  $txt    Text to be translated
 * @return  string      Translated string
 */
function _($txt)
{
    return MO::dgettext($txt);
}

