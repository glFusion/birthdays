<?php
/**
 * Class to read and manipulate configuration values.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2020 Lee Garner <lee@leegarner.com>
 * @package     birthdays
 * @version     v1.0.2
 * @since       v1.0.2
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Birthdays;


/**
 * Class to get plugin configuration data.
 * @package birthdays
 */
final class Config
{
    /** Plugin Name.
     */
    public const PI_NAME = 'birthdays';

    /** Array of config items (name=>val).
     * @var array */
    private $properties = NULL;

    /** Config class singleton instance.
     * @var object */
    static private $instance = NULL;


    /**
     * Get the configuration object.
     * Creates an instance if it doesn't already exist.
     *
     * @return  object      Configuration object
     */
    public static function getInstance()
    {
        if (self::$instance === NULL) {
            self::$instance = new self;
        }
        return self::$instance;
    }


    /**
     * Create an instance of the configuration object.
     */
    private function __construct()
    {
        global $_CONF;

        $cfg = \config::get_instance();
        $this->properties = $cfg->get_config(self::PI_NAME);

        $this->properties['pi_name'] = self::PI_NAME;
        $this->properties['pi_display_name'] = 'Birthdays';
        $this->properties['pi_url'] = 'https://glfusion.org';
        $this->properties['url'] = $_CONF['site_url'] . '/' . self::PI_NAME;
        $this->properties['admin_url'] = $_CONF['site_admin_url'] . '/plugins/' . self::PI_NAME;
        $this->properties['pi_path'] = $_CONF['path'] . 'plugins/' . self::PI_NAME . '/';
        // Use the global dateonly format as a default if the format isn't defined
        if (
            !isset($this->properties['format']) ||
            empty($this->properties['format'])
        ) {
            if ($_CONF['dateonly'] != '') {
                $this->properties['format'] = $_CONF['dateonly'];
            } else {
                $this->properties['format'] = 'M d';    // final fallback
            }
        }
    }


    /**
     * Returns a configuration item.
     * Returns all items if `$key` is NULL.
     *
     * @param   string|NULL $key        Name of item to retrieve
     * @param   mixed       $default    Default value if item is not set
     * @return  mixed       Value of config item
     */
    private function _get($key=NULL, $default=NULL)
    {
        if ($key === NULL) {
            return $this->properties;
        } elseif (array_key_exists($key, $this->properties)) {
            return $this->properties[$key];
        } else {
           return $default;
        }
    }


    /**
     * Set a configuration value.
     * Unlike the root glFusion config class, this does not add anything to
     * the database. It only adds temporary config vars.
     *
     * @param   string  $key    Configuration item name
     * @param   mixed   $val    Value to set
     */
    private function _set($key, $val)
    {
        $this->properties[$key] = $val;
        return $this;
    }


    /**
     * Set a configuration value.
     * Unlike the root glFusion config class, this does not add anything to
     * the database. It only adds temporary config vars.
     *
     * @param   string  $key    Configuration item name
     * @param   mixed   $val    Value to set, NULL to unset
     */
    public static function set($key, $val=NULL)
    {
        return self::getInstance()->_set($key, $val);
    }


    /**
     * Returns a configuration item.
     * Returns all items if `$key` is NULL.
     *
     * @param   string|NULL $key        Name of item to retrieve
     * @param   mixed       $default    Default value if item is not set
     * @return  mixed       Value of config item
     */
    public static function get($key=NULL, $default=NULL)
    {
        return self::getInstance()->_get($key);
    }


    /**
     * Convenience function to get the base plugin path.
     *
     * @return  string      Path to main plugin directory.
     */
    public static function path()
    {
        return self::_get('pi_path');
    }


    /**
     * Convenience function to get the path to plugin templates.
     *
     * @return  string      Template path
     */
    public static function path_template()
    {
        return self::get('pi_path') . 'templates/';
    }

}
