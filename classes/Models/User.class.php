<?php
/**
 * Handle user account and privileges.
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
namespace Birthdays\Models;
use Birthdays\MO;
use Birthdays\Config;


/**
 * Handle checking user account information.
 * @package birthdays
 */
class User
{
    const STATUS_ACTIVE = 3;

    private $uid = 0;
    private $username = '';
    private $fullname = '';
    private $status = 0;
    private $email = '';
    private $_rights = NULL;

    public function __construct(?int $uid)
    {
        global $_USER, $_TABLES;

        if ($uid === NULL) {
            $uid = (int)$_USER['uid'];
        }

        $sql = "SELECT uid, username, fullname, status, email
            FROM `{$_TABLES['users']}`
            WHERE uid = $uid";
        $res = DB_query($sql);
        if ($res && DB_numRows($res) == 1) {
            $A = DB_fetchArray($res, false);
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

}
