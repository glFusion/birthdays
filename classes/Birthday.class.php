<?php
/**
*   Class to manage birthdays
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2018 Lee Garner <lee@leegarner.com>
*   @package    birthdays
*   @version    0.1.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Birthdays;

/**
*   Class for ad type
*   @package classifieds
*/
class Birthday
{
    /** Properties array
     *  @var array */
    var $properties;


    /**
    *   Constructor.
    *   Reads in the specified class, if $id is set.  If $id is zero,
    *   then a new entry is being created.
    *
    *   @param integer $id Optional type ID
    */
    public function __construct($uid=0)
    {
        $uid = (int)$uid;
        if ($uid < 1) {
            $this->uid = 0;
            $this->month = 0;
            $this->day = 0;
            $this->year = 0;
        } else {
            $this->uid = $uid;
            $this->Read();
        }
    }


    public static function getInstance($uid)
    {
        static $bdays = array();
        if (!array_key_exists($uid, $bdays)) {
            $bdays[$uid] = new self($uid);
        }
        return $bdays[$uid];
    }


    public function __set($key, $value)
    {
        switch ($key) {
        case 'uid':
        case 'year':
        case 'month':
        case 'day':
            $this->properties[$key] = (int)$value;
            break;
        }
    }

    public function __get($key)
    {
        if (isset($this->properties[$key]))
            return $this->properties[$key];
        else
            return NULL;
    }


    /**
    *   Sets all variables to the matching values from $rows
    *
    *   @param array $row Array of values, from DB or $_POST
    */
    public function SetVars($row)
    {
        if (!is_array($row)) return;

        foreach (array('uid', 'month', 'day') as $key) {
            if (isset($row[$key])) {
                $this->$key = $row[$key];
            }
        }
    }


    /**
    *   Read one as type from the database and populate the local values.
    *
    *   @param integer $id Optional ID.  Current ID is used if zero
    */
    public function Read($uid = 0)
    {
        global $_TABLES;

        $uid = (int)$uid;
        if ($uid == 0) $uid = $this->uid;
        if ($uid == 0) {
            return false;
        }

        $result = DB_query("SELECT * from {$_TABLES['birthdays']}
                            WHERE uid = $uid");
        $row = DB_fetchArray($result, false);
        $this->SetVars($row);
    }


    /**
    *   Adds the current values to the databae as a new record
    *
    *   @param  array   $vals   Optional array of values to set
    *   @return boolean     True on success, False on error
    */
    public function Save($vals = NULL)
    {
        global $_TABLES;

        if (is_array($vals)) {
            $this->SetVars($vals);
        }

        // If "0" is entered for either month or day, consider this
        // as a deletion request
        if ($this->month == 0 || $this->day == 0) {
            self::Delete($this->uid);
            return true;
        }

        $sql = "INSERT INTO {$_TABLES['birthdays']} SET
                    uid = {$this->uid},
                    month = {$this->month},
                    day = {$this->day}
                ON DUPLICATE KEY UPDATE
                    month = {$this->month},
                    day = {$this->day}";
        //echo $sql;die;
        $res = DB_query($sql);
        return DB_error() ? false : true;
    }


    /**
    *   Get all birthdays for one month, or for all.
    *
    *   @param  mixed   $month  Month number, or "all"
    *   @param  mixed   $day    Optional day number
    *   @return array           Array of birthday records
    */
    public static function getAll($month = 'all', $day = 0)
    {
        global $_TABLES, $_CONF;

        $dt = new \Date('now', $_CONF['timezone']);
        $year = $dt->Format('Y', true);

        $where = '';
        if ($month == 'all') {
            $where .= ' AND b.month > 0';
        } else {
            $where .= ' AND b.month = ' . (int)$month;
        }
        if ($day == 0) {
            $where .= ' AND b.day > 0';
        } else {
            $where .= ' AND b.day = ' . (int)$day;
        }
        $sql = "SELECT 2016 as year, b.* FROM {$_TABLES['birthdays']} b
                WHERE 1=1 $where
                ORDER BY b.month, b.day";
        //echo $sql;die;
        $res = DB_query($sql);
        $retval = DB_fetchAll($res, false);
        return $retval;
    } 


    /**
    *   Get a range of birthdays.
    *   This is intended to be used with other plugins and returns only user
    *   IDs indexed by date.
    *
    *   @param  string  $start  Starting date, YYYY-MM-DD
    *   @param  string  $end    Ending date, YYYY-MM-DD
    *   @return array           Array of date =>array(userids)
    */
    public static function getRange($start, $end)
    {
        global $_TABLES, $_CONF;

        $dt_s = new \Date($start, $_CONF['timezone']);
        $dt_e = new \Date($end, $_CONF['timezone']);
        // Find the months to retrieve
        $s_year = $dt_s->Format('Y');
        $s_month = $dt_s->Format('n');
        //$s_day = $dt_s->Format('d');
        $e_year = $dt_e->Format('Y');
        $e_month = $dt_e->Format('n');
        //$e_day = $dt_e->Format('d');
        $months = array();

        if ($e_month < $s_month) {
            // End month is less than start, must be wrapping the year
            for ($i = $e_month; $i < 13; $i++) {
                $months[] = $i;
            }
            for ($i = 1; $i < $s_month; $i++) {
                $months[] = $i;
            }
        } else {
            for ($i = $s_month; $i <= $e_month; $i++) {
                $months[] = (int)$i;
            }
        }
        $retval = array();
        $month_str = implode(',', $months);
        $sql = "SELECT * FROM {$_TABLES['birthdays']}
                WHERE month IN ($month_str)
                ORDER BY month, day";
        //echo $sql;die;
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            $year = $A['month'] < $s_month ? $e_year : $s_year;
            $key1 = sprintf('%d-%02d-%02d', $year, $A['month'], $A['day']);
            if (!isset($retval[$key1])) $retval[$key1] = array();
            $retval[$key1][] = $A['uid'];
        }
        return $retval;
    }


    /**
    *   Display the edit form within the profile editing screen
    *
    *   @param  integer $uid    User ID
    *   @return string      HTML for edit form
    */
    public static function editForm($uid)
    {
        global $LANG_MONTH, $LANG_BD00;

        $bday = self::getInstance($uid);
        $T = new \Template(__DIR__ . '/../templates');
        $T->set_file('edit', 'edit.thtml');
        $opt = '';
        for ($i = 0; $i < 13; $i++) {
            $sel = $bday->month == $i ? 'selected="selected"' : '';
            if ($i > 0) {
                $opt .= "<option $sel value=\"$i\">{$LANG_MONTH[$i]}</option>";
            } else {
                $opt .= "<option $sel value=\"$i\">{$LANG_BD00['none']}</option>";
            }
        }
        $T->set_var('month_select', $opt);
        $opt = '';
        for ($i = 0; $i < 32; $i++) {
            $sel = $bday->day == $i ? 'selected="selected"' : '';
            if ($i > 0) {
                $opt .= "<option $sel value=\"$i\">$i</option>";
            } else {
                $opt .= "<option $sel value=\"$i\">{$LANG_BD00['none']}</option>";
            }
        }
        $T->set_var('day_select', $opt);
        //$T->set_var('year', $bday->year);
        $T->parse('output', 'edit');
        return $T->finish($T->get_var('output'));
    }


    /**
    *   Delete a birthday record.
    *   Used if the user submits "0" for month or day
    *
    *   @param  integer $uid    User ID
    */
    public static function Delete($uid)
    {
        global $_TABLES;

        DB_delete($_TABLES['birthdays'], 'uid', $uid);
    }

}

?>
