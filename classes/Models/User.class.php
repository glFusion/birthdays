<?php
/**
 * Handle user account and privileges.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner <lee@leegarner.com>
 * @package     birthdays
 * @version     v1.2.0
 * @since       v1.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Birthdays\Models;
use Birthdays\MO;
use Birthdays\Config;
use glFusion\Database\Database;


/**
 * Handle checking user account information.
 * @package birthdays
 */
class User
{
    const STATUS_REGISTERED = 1;
    const STATUS_ACTIVE = 3;

    /** User ID.
     * @var integer */
    private $uid = 0;

    /** Login name.
     * @var string */
    private $username = '';

    /** Full name.
     * @var string */
    private $fullname = '';

    /** Account status.
     * @var integer */
    private $status = 0;

    /** Email address.
     * @var string */
    private $email = '';

    /* Array of privileges (features).
     * @var array */
    private $_rights = NULL;


    /**
     * Read the pertinent user information from the database.
     *
     * @param   integer $uid    Optional user ID, current user if empty
     */
    public function __construct(?int $uid)
    {
        global $_USER, $_TABLES;

        if ($uid === NULL) {
            $uid = (int)$_USER['uid'];
        }
        $db = Database::getInstance();
        try {
            $A = $db->conn->executeQuery(
                "SELECT uid, username, fullname, status, email
                FROM `{$_TABLES['users']}`
                WHERE uid = ?",
                array($uid),
                array(Database::INTEGER)
            )->fetch(Database::ASSOCIATIVE);
        } catch (\Exception $e) {
            Logger::logException($e);
            $A = NULL;
        }
        if (is_array($A) && !empty($A)) {
            $this->uid = (int)$A['uid'];
            $this->username = $A['username'];
            $this->fullname = $A['fullname'];
            $this->status = (int)$A['status'];
            $this->email = (int)$A['email'];
        }
    }


    /**
     * Get the user ID.
     *
     * @return  integer     User ID
     */
    public function getUid() : int
    {
        return (int)$this->uid;
    }


    /**
     * Get the user login name.
     * 
     * @return  string      User Name
     */
    public function getUsername() : string
    {
        return $this->username;
    }


    /**
     * Get the user's full name.
     *
     * @return  string      Full name
     */
    public function getFullname() : string
    {
        return $this->fullname;
    }


    /**
     * Get the email address.
     *
     * @return  string      Email address
     */
    public function getEmail() : string
    {
        return $this->email;
    }


    /**
     * Get the user's account status.
     *
     * @return  integer     Account status
     */
    public function getStatus() : int
    {
        return (int)$this->status;
    }


    /**
     * Check if this user is active.
     *
     * @return  bool        True if active
     */
    public function isActive() : bool
    {
        return $this->status == self::STATUS_ACTIVE || $this->status == self::STATUS_REGISTERED;
    }


    /**
     * Get all the privileges that this user has.
     *
     * @return  array       Array of privilege names
     */
    public function getRights() : array
    {
        if ($this->_rights === NULL) {
            if ($this->uid == 0) {
                $this->_rights = array();
            } else {
                $this->_rights = explode(',', SEC_getUserPermissions('', $this->uid));
            }
        }
        return $this->_rights;
    }


    /**
     * See if this user has a particular privliege.
     *
     * @return  boolean     True if user has the right
     */
    public function hasRight($right) : bool
    {
        if ($this->uid == 0) {
            return false ;
        } else {
            return in_array($right, $this->getRights());
        }
    }


    /**
     * Utility function to decrypt a string using an internal salt.
     *
     * @param   string  $str    String to decrypt
     * @return  string      Decrypted value
     */
    public static function decrypt(string $str) : string
    {
        global $_VARS;

        return COM_decrypt($str, $_VARS['guid']);
    }


    /**
     * Utility function to encrypt the user ID using an internal salt.
     * Used to create a query string authorizing (un)subscribe requests.
     *
     * @param   integer $uid    User ID
     * @return  string      Encrypted string
     */
    public static function encrypt(int $uid) : string
    {
        global $_VARS;

        return COM_encrypt($uid, $_VARS['guid']);
    }

}
