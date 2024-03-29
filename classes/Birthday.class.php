<?php
/**
 * Class to manage birthdays.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018-2022 Lee Garner <lee@leegarner.com>
 * @package     birthdays
 * @version     v1.2.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Birthdays;
use Birthdays\Models\Month;
use Birthdays\Models\Zodiac;
use glFusion\FieldList;
use glFusion\Database\Database;


/**
 * Class for birthday events.
 * @package birthdays
 */
class Birthday
{
    /** Base cache tag added to all items.
     * @const string */
    const TAG = 'birthdays';

    /** Minimum glFusion version that supports caching.
     * @const string */
    const CACHE_GVERSION = '2.0.0';

    /** Year to used to create date objects.
     * Using 2016 as it is a leap year.
     * The actual birth year is not included in the saved birthdays.
     * @const integer */
    CONST YEAR = 2016;

    /** User ID.
     * @var integer */
    private $uid = 0;

    /** Birth Month.
     * @var integer */
    private $month = 0;

    /** Birth Day.
     * @var integer */
    private $day = 0;

    /** Flag to indicate that cards should be sent to this user.
     * @var boolean */
    private $sendcards = 1;


    /**
     * Instantiate an object for the specified user, or a new entry.
     *
     * @param   integer $uid    Optional type ID, zero indicates a new record
     */
    public function __construct($uid=0)
    {
        if (is_array($uid)) {
            $this->setVars($uid);
        } else {
            $uid = (int)$uid;
            if ($uid > 0) {
                $this->uid = (int)$uid;
                $this->Read();
            }
        }
    }


    /**
     * Get an instance of a birthday object.
     * Saves objects in a static variable for subsequent use.
     *
     * @param   integer $uid    User ID
     * @return  object          Birthday object for the user
     */
    public static function getInstance($uid)
    {
        return new self($uid);
    }


    /**
     * Sets all variables to the matching values from `$rows`.
     *
     * @param   array   $row    Array of values, from DB or $_POST
     */
    public function setVars($row)
    {
        if (!is_array($row)) return;

        foreach (array('uid', 'month', 'day', 'sendcards') as $key) {
            if (isset($row[$key])) {
                $this->$key = (int)$row[$key];
            }
        }
    }


    /**
     * Read one as type from the database and populate the local values.
     *
     * @param   integer $uid    Optional user ID.  Current user if zero.
     */
    public function Read($uid = 0)
    {
        global $_TABLES;

        $uid = (int)$uid;
        if ($uid == 0) $uid = $this->uid;
        if ($uid == 0) {
            return false;
        }
        $db = Database::getInstance();
        try {
            $data = $db->conn->executeQuery(
                "SELECT * from {$_TABLES['birthdays']}
                WHERE uid = ?",
                array($uid),
                array(Database::INTEGER)
            )->fetch(Database::ASSOCIATIVE);
        } catch (\Exception $e) {
            Logger::logException($e);
            $data = NULL;
        }
        if (is_array($data)) {
            $this->setVars($data);
        }
    }


    /**
     * Adds the current values to the databae as a new record.
     *
     * @param   array   $vals   Optional array of values to set
     * @return  boolean     True on success, False on error
     */
    public function Save(?array $vals = NULL) : bool
    {
        global $_TABLES;

        $orig_month = $this->month;
        $orig_day = $this->day;
        $orig_sendcards = $this->sendcards;

        if (is_array($vals)) {
            $this->setVars($vals);
        }

        // If "0" is entered for either month or day, consider this
        // as a deletion request
        if ($this->month == 0 || $this->day == 0) {
            self::Delete($this->uid);
            PLG_itemDeleted($this->uid, Config::PI_NAME);
            return true;
        }

        // If the date wasn't changed, nothing to do.
        if ($this->month == $orig_month && $this->day == $orig_day) {
            if ($this->sendcards == $orig_sendcards) {
                return true;
            }
            $vals['call_itemsaved'] = true;
        }

        $qb = Database::getInstance()->conn->createQueryBuilder();
        $qb->setParameter('uid', $this->uid, Database::INTEGER)
           ->setParameter('month', $this->month, Database::INTEGER)
           ->setParameter('day', $this->day, Database::INTEGER)
           ->setParameter('sendcards', $this->sendcards, Database::INTEGER);
        $error = false;
        try {
            $qb->insert($_TABLES['birthdays'])
               ->setValue('uid', ':uid')
               ->setValue('month', ':month')
               ->setValue('day', ':day')
               ->setValue('sendcards', ':sendcards')
               ->execute();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $k) {
            try {
                $qb->update($_TABLES['birthdays'])
                   ->set('month', ':month')
                   ->set('day', ':day')
                   ->set('sendcards', ':sendcards')
                   ->where('uid = :uid')
                   ->execute();
            } catch (\Exception $e) {
                Logger::logException($e);
                $error = true;
            }
        } catch (\Exception $e) {
            Logger::logException($e);
            $error = true;
        }
        if (!$error) {
            self::clearCache('range');
            // Put this in cache to save a lookup in plugin_getiteminfo
            self::setCache('uid_' . $this->uid, $this);
            if (!isset($vals['call_itemsaved']) || !$vals['call_itemsaved']) {
                PLG_itemSaved($this->uid, Config::PI_NAME);
            }
            return true;
        } else {
            return false;
        }
    }


    /**
     * Get all birthdays for one month, one day, or all..
     *
     * @param   mixed   $month  Month number, or "all"
     * @param   mixed   $day    Optional day number
     * @return  array           Array of birthday records
     */
    public static function getAll($month = 0, $day = 0)
    {
        global $_TABLES, $_CONF;

        $month = (int)$month;
        $day = (int)$day;
        $cache_key = $month . '_' . $day;
        $retval = self::getCache($cache_key);
        if ($retval !== NULL) {
           return $retval;
        }

        $qb = Database::getInstance()->conn->createQueryBuilder();
        if ($month > 0) {
            $qb->andWhere('b.month = :month');
        }
        if ($day > 0) {
           $qb->andWhere('b.day = :day');
        }
        // The year isn't stored, so use a bogus leap year.
        try {
            $data = $qb->select(
                self::YEAR . ' as year',
                'b.*', 'u.username', 'u.fullname'
            )
                       ->from($_TABLES['birthdays'], 'b')
                       ->leftJoin('b', $_TABLES['users'], 'u', 'u.uid=b.uid')
                       ->setParameter('month', $month, Database::INTEGER)
                       ->setParameter('day', $day, Database::INTEGER)
                       ->execute()
                       ->fetchAll(Database::ASSOCIATIVE);
        } catch (\Exception $e) {
            Logger::logException($e);
            $data = NULL;
        }
        $retval = array();
        if (is_array($data)) {
            foreach ($data as $A) {
                $retval[$A['uid']] = new self($A);
            }
            self::setCache($cache_key, $retval, 'range');
        }
        return $retval;
    }


    /**
     * Get a range of birthdays.
     * This is intended to be used with other plugins and returns only user
     * IDs indexed by date.
     *
     * @param   string  $start  Starting date, YYYY-MM-DD
     * @param   string  $end    Ending date, YYYY-MM-DD
     * @return  array           Array of date =>array(userids)
     */
    public static function getRange($start, $end)
    {
        global $_TABLES, $_CONF;

        $db = Database::getInstance();
        $cache_key = $start . '_' . $end;
        $retval = self::getCache($cache_key);
        if ($retval !== NULL) {
            return $retval;
        }

        $dt_s = new \Date($start, $_CONF['timezone']);
        $dt_e = new \Date($end, $_CONF['timezone']);
        // Find the months to retrieve
        $s_year = $dt_s->format('Y');
        $s_month = $dt_s->format('n');
        $e_year = $dt_e->format('Y');
        $e_month = $dt_e->format('n');
        $months = array();

        if ($e_month < $s_month) {
            // End month is less than start, must be wrapping the year
            // First get ending months up to the end of the year
            for ($i = $e_month; $i < 13; $i++) {
                $months[] = $i;
            }
            // Then get ending months in the next year, up to starting month
            for ($i = 1; $i < $s_month; $i++) {
                $months[] = $i;
            }
        } else {
            // Ending > starting, just get the months between them (inclusive)
            for ($i = $s_month; $i <= $e_month; $i++) {
                $months[] = (int)$i;
            }
        }
        $retval = array();
        try {
            $stmt = $db->conn->executeQuery(
                "SELECT * FROM {$_TABLES['birthdays']}
                WHERE month IN (?)
                ORDER BY month, day",
                array($months),
                array(Database::PARAM_INT_ARRAY)
            );
        } catch (\Exception $e) {
            Logger::logException($e);
            $stmt = NULL;
        }
        if ($stmt) {
            while ($A = $stmt->fetch(Database::ASSOCIATIVE)) {
                $year = $A['month'] < $s_month ? $e_year : $s_year;
                $key1 = sprintf('%d-%02d-%02d', $year, $A['month'], $A['day']);
                // Return only dates within the range
                if ($key1 < $dt_s->format('Y-m-d') || $key1 > $dt_e->format('Y-m-d')) {
                    continue;
                }
                if (!isset($retval[$key1])) $retval[$key1] = array();
                $retval[$key1][] = $A['uid'];
            }
        }
        self::setCache($cache_key, $retval, 'range');
        return $retval;
    }


    /**
     * Display the edit form within the profile editing screen.
     *
     * @param  integer $uid    User ID
     * @param  string  $tpl    Template name, default="edit"
     * @return string      HTML for edit form
     */
    public function editForm($tpl = 'edit')
    {
        global $_USER;

        $bday = self::getInstance($this->uid);
        $opt = self::selectMonth($this->month, _('None'));
        $T = new \Template(Config::path_template());
        $T->set_file('edit', $tpl . '.thtml');
        $T->set_var('month_select', $opt);
        $opt = '';
        for ($i = 0; $i < 32; $i++) {
            $sel = $this->day == $i ? 'selected="selected"' : '';
            if ($i > 0) {
                $opt .= "<option id=\"bday_day_$i\" $sel value=\"$i\">$i</option>";
            } else {
                $opt .= "<option $sel value=\"$i\">" . _('None') . "</option>";
            }
        }
        $T->set_var(array(
            'day_select' => $opt,
            'month' => $this->month,
            'lang_my_birthday' => _('My Birthday'),
            'lang_today' => _('Today'),
            'rnd' => rand(1,1000),
            'lang_send_cards' => _('Send birthday cards'),
            'cards_chk' => $this->sendcards ? 'checked="checked"' : '',
            'is_current_user' => $this->uid == $_USER['uid'] || plugin_ismoderator_birthdays(),
        ) );
        $T->parse('output', 'edit');
        return $T->finish($T->get_var('output'));
    }


    /**
     * Delete a birthday record.
     * Used if the user submits "0" for month or day
     *
     * @param   integer $uid    User ID
     */
    public static function Delete(int $uid) : void
    {
        global $_TABLES;

        $db = Database::getInstance();
        $db->conn->delete(
            $_TABLES['birthdays'],
            array('uid' => $uid),
            array(Database::INTEGER)
        );
        PLG_itemDeleted($uid, 'birthdays');
        self::clearCache('range');
        self::deleteCache('uid_' . $uid);
    }


    /**
     * Create the option elements for a month selection.
     * This allows for a common function used for the month selection on the
     * home page and for the user-supplied birthday.
     *
     * @param   integer $thismonth      Currently-selected month
     * @param   string  $all_prompt     String to show for "all" or "none"
     * @return  string                  Option elements
     */
    public static function selectMonth($thismonth = 0, $all_prompt = '')
    {
        if ($all_prompt == '') $all_prompt = _('All');
        $opt = '';
        for ($i = 0; $i < 13; $i++) {
            $sel = $thismonth == $i ? 'selected="selected"' : '';
            if ($i > 0) {
                $opt .= "<option $sel value=\"$i\">" . Month::getName($i) . "</option>";
            } else {
                $opt .= "<option $sel value=\"$i\">{$all_prompt}</option>";
            }
        }
        return $opt;
    }


    /**
     * Format a date according to the configured format.
     * Allows a single array parameter, or individual values.
     *
     * @param   mixed   $month  Month number or array of all values
     * @param   integer $day    Day value, if $month is an integer
     * @param   integer $year   Optional year value
     * @return  string          Formatted date string
     */
    public static function formatDate($month, $day = '', $year = '')
    {
        global $_CONF;
        static $dt = NULL;

        if (is_array($month)) {
            // For an array, load the vaues into individual fields
            $day = isset($month['day']) ? $month['day'] : '';
            $year = isset($month['year']) ? $month['year'] : '';
            $month = isset($month['month']) ? $month['month'] : '';
        } elseif (is_object($month)) {  // a Birthday object received
            $day = $month->getDay();
            $year = self::YEAR;
            $month = $month->getMonth();
        } elseif (is_string($month) && strpos($month, '-')) {
            // YYYY-MM-DD format, separate into component parts
            $A = explode('-', $month);
            if (count($A) == 3) {
                $year = $A[0];
                $month = $A[1];
                $day = $A[2];
            } else {
                $month = $A[0];
                $day = $A[1];
            }
        }

        // At least month and day are required
        if (empty($month) || empty($day)) {
            return 'n/a';
        }
        // If the year is undefined, use the current year
        if (empty($year)) $year = self::currentDate()['year'];

        // Create a date object, if not already done.
        if ($dt === NULL) {
            $dt = new \Date('now', $_CONF['timezone']);
        }

        // Format the date
        $dt->setDate($year, $month, $day);
        return $dt->Format(Config::get('format'), true);
    }


    /**
     * Helper function to determine if the current user can view all birthdays.
     *
     * @return  boolean     True if access is allowed, False if not.
     */
    public static function canView() : bool
    {
        static $canView = NULL;

        if ($canView === NULL) {
            if (plugin_ismoderator_birthdays()) {
                $canView = true;
            } elseif (Config::get('grp_access') > 0) {
                $canView = SEC_inGroup(Config::get('grp_access'));
            } else {
                $canView = SEC_hasRights('birthdays.view,birthdays.admin');
            }
        }
        return $canView;
    }


    /**
     * Get an array of values for the current date.
     * Uses a static variable for repeated calls.
     *
     * @return  array   Array of year, month, day
     */
    public static function currentDate()
    {
        global $_CONF;
        static $retval = NULL;

        if ($retval === NULL) {
            $dt = new \Date('now', $_CONF['timezone']);
            $retval = array(
                'dt'    => $dt,
                'year'  => $dt->Format('Y', true),
                'month' => $dt->Format('m', true),
                'day'   => $dt->Format('d', true),
            );
        }
        return $retval;
    }


    /**
     * Update the cache.
     *
     * @param   string  $key    Item key
     * @param   mixed   $data   Data, typically an array
     * @param   mixed   $tag    Single or array of tags to apply
     * @return  boolean     True on success, False on error
     */
    public static function setCache($key, $data, $tag='')
    {
        if (version_compare(GVERSION, self::CACHE_GVERSION, '<')) return NULL;

        if ($tag == '') {
            $tag = array(self::TAG);
        } else {
            $tag = array($tag, self::TAG);
        }
        $key = self::_makeKey($key);
        return \glFusion\Cache\Cache::getInstance()->set($key, $data, $tag);
    }


    /**
     * Clear the cache by tag. By default all plugin entries are removed.
     * Entries matching all tags, including default tag, are removed.
     *
     * @param   mixed   $tag    Single or array of tags
     * @return  boolean     True on success, False on error
     */
    public static function clearCache($tag = '')
    {
        if (version_compare(GVERSION, self::CACHE_GVERSION, '<')) return;

        $tags = array(self::TAG);
        if (!empty($tag)) {
            if (!is_array($tag)) $tag = array($tag);
            $tags = array_merge($tags, $tag);
        }
        return \glFusion\Cache\Cache::getInstance()->deleteItemsByTagsAll($tags);
    }


    /**
     * Delete a single item from the cache
     *
     * @param   string  $key    Item key to delete
     * @return  boolean     True on success, False on error
     */
    public static function deleteCache($key)
    {
        if (version_compare(GVERSION, self::CACHE_GVERSION, '<')) return;

        $key = self::_makeKey($key);
        return \glFusion\Cache\Cache::getInstance()->delete($key);
    }


    /**
     * Create a unique cache key.
     *
     * @param   string  $key    Unique cache key
     * @return  string          Encoded key string to use as a cache ID
     */
    private static function _makeKey($key)
    {
        return self::TAG . '_' . $key;
    }


    /**
     * Get an entry from cache, if available.
     *
     * @param   string  $key    Cache key
     * @return  mixed           Cache contents, NULL if not found
     */
    public static function getCache($key)
    {
        if (version_compare(GVERSION, self::CACHE_GVERSION, '<')) {
            return NULL;
        }
        $key = self::_makeKey($key);
        if (\glFusion\Cache\Cache::getInstance()->has($key)) {
            return \glFusion\Cache\Cache::getInstance()->get($key);
        } else {
            return NULL;
        }
    }


    /**
     * Get the user ID.
     *
     * @return  integer     User ID
     */
    public function getUid()
    {
        return (int)$this->uid;
    }


    /**
     * Get the birth month value.
     *
     * @return  integer     Birth month (1 - 12)
     */
    public function getMonth()
    {
        return (int)$this->month;
    }


    /**
     * Get the birth day value.
     *
     * @return  integer     Birth day (1 - 31)
     */
    public function getDay()
    {
        return (int)$this->day;
    }


    /**
     * Present the list of birthdays.
     *
     * @param   integer $filter_month   Month to show, 0 for "all"
     * @return  string      HTML for the list
     */
    public static function publicList($filter_month=0)
    {
        global $_TABLES;

        $retval = '';

        $header_arr = array(
            array(
                'text' => _('Name'),
                'field' => 'fullname',
                'sort' => false,
                'align' => '',
            ),
            array(
                'text' => _('Birthday'),
                'field' => 'birthday',
                'sort' => true,
                'align' => 'center',
            ),
        );
        if (Config::get('zodiac_in_dscp')) {
            $header_arr[] = array(
                'text' => _('Astrological Sign'),
                'field' => 'zodiac',
                'sort' => false,
                'align' => 'left',
            );
        }
        if (Config::get('enable_subs')) {
            $header_arr[] =  array(
                'text' => _('Subscribe'),
                'field' => 'subscribe',
                'sort' => false,
                'align' => 'center',
            );
        }

        $defsort_arr = array('field' => 'month,day', 'direction' => 'ASC');
        $text_arr = array(
            'has_menu'     => false,
            'has_extras'   => false,
            'title'        => _('Birthdays'),
            'form_url'     => Config::get('url') . '/index.php?filter_month=' . $filter_month,
            'help_url'     => ''
        );
        $filter = $filter_month == 0 ? '' : " AND month = $filter_month";
        $sql = "SELECT " . self::YEAR . " as year, b.*, u.username, u.fullname
                FROM {$_TABLES['birthdays']} b
                LEFT JOIN {$_TABLES['users']} u
                    ON u.uid = b.uid
                WHERE 1=1 $filter";
        $query_arr = array(
            'table' => 'birthdays',
            'sql' => $sql,
            'query_fields' => array(),
        );
        $text_arr = array(
            'form_url' => Config::get('url') . '/index.php?filter_month=' . $filter_month,
            'has_search' => false,
            'has_limit'     => true,
            'has_paging'    => true,
        );

        $retval .= ADMIN_list(
            'birthdays_publiclist',
            array(__CLASS__, 'getListField'),
            $header_arr, $text_arr, $query_arr, $defsort_arr
        );
        return $retval;
    }


    /**
     * Show the admin list.
     *
     * @return string  HTML for item list
     */
    public static function adminList()
    {
        global $LANG_ADMIN, $_TABLES, $_CONF;

        $retval = '';
        $form_arr = array();

        $header_arr = array(
            array(
                'text' => _('User ID'),
                'field' => 'uid',
                'sort' => true,
            ),
            array(
                'text' => _('Name'),
                'field' => 'fullname',
                'sort' => false,
            ),
            array(
                'text'  => _('Birthday'),
                'field' => 'birthday',
                'sort'  => false,
            ),
            array(
                'text' => _('Send Cards'),
                'field' => 'sendcards',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => _('Delete'),
                'field' => 'delete',
                'sort' => false,
                'align' => 'center',
            ),
        );

        $sql = "SELECT " . self::YEAR . " as year, b.*, u.username, u.fullname
                FROM {$_TABLES['birthdays']} b
                LEFT JOIN  {$_TABLES['users']} u
                    ON u.uid = b.uid";
        $text_arr = array(
            'has_extras' => false,
            'form_url' => Config::get('admin_url') . '/index.php',
        );
        $options = array(
            'chkdelete' => 'true',
            'chkfield' => 'uid',
        );
        $defsort_arr = array(
            'field' => 'uid',
            'direction' => 'asc',
        );
        $query_arr = array(
            'table' => 'birthdays',
            'sql' => $sql,
        );

        $retval = ADMIN_list(
            'birthdays_adminlist',
            array(__CLASS__, 'getListField'),
            $header_arr,
            $text_arr, $query_arr, $defsort_arr, '', '', $options, $form_arr
        );
        return $retval;
    }


    /**
     * Determine what to display in the birthday lists.
     * Serves both publicList() and adminList().
     *
     * @param   string  $fieldname  Name of the field, from database
     * @param   mixed   $fieldvalue Value of the current field
     * @param   array   $A          Array of all name/field pairs
     * @param   array   $icon_arr   Array of system icons
     * @return  string              HTML for the field cell
     */
    public static function getListField($fieldname, $fieldvalue, $A, $icon_arr)
    {
        global $_CONF, $_USER;

        $retval = '';

        switch($fieldname) {
        case 'fullname':
            $retval .= COM_getDisplayName($A['uid'], $A['username'], $A['fullname']);
            break;

        case 'birthday':
            $retval .= \Birthdays\Birthday::formatDate($A);
            break;

        case 'zodiac':
            $retval .= Zodiac::getSign($A['month'], $A['day']);
            break;

        case 'subscribe':
            if (PLG_isSubscribed('birthdays', 'birthday_sub', $A['uid'], $_USER['uid'])) {
                $tooltip = _('Click to Unsubscribe');
                $current_val = 1;
                $chk = 'checked="checked"';
            } else {
                $tooltip = _('Click to Subscribe');
                $current_val = 0;
                $chk = '';
            }
            $retval = '<input type="checkbox" value="1" ' . $chk .
                ' data-uk-tooltip title="' . $tooltip .
                '" onclick="javascript:BDAY_toggleSub(this, ' . $A['uid'] . ', ' . $current_val . ');" />';
            break;

        case 'sendcards':   // administrator toggle of user's subscription
            $fieldvalue = (int)$fieldvalue;
            $retval = FieldList::checkbox(array(
                'checked' => $fieldvalue == 1,
                'id' => "togcards{$A['uid']}",
                'onclick' => "javascript:BDAY_toggleCards(this, '{$A['uid']}', '{$fieldvalue}');",
            ) );
            break;

        case 'delete':
            $retval = FieldList::delete(array(
                'url' => Config::get('admin_url') . "/index.php?delitem={$A['uid']}",
                'attr' => array(
                     'onclick' => "return confirm('" . _('Do you really want to delete this item?') . "');",
                ),
            ) );
            break;

        default:
            $retval = $fieldvalue;
            break;
        }
        return $retval;
    }


    /**
     * Send a card to the current birthday user.
     *
     * @return  void
     */
    public function sendCard() : void
    {
        global $_TABLES;

        if (!$this->sendcards) {
            // User has unsubscribed, do nothing.
            return;
        }

        $db = Database::getInstance();

        // Borrow the email format function to create the message
        $msg = plugin_subscription_email_format_birthdays('birthday_card', '', $this->uid, '');
        $name = COM_getDisplayName($this->uid);
        $email = $db->getItem(
            $_TABLES['users'],
            'email',
            array('uid', $this->uid),
            array(Database::INTEGER)
        );
        if (empty($email)) {
            return;    // need a valid email
        }
        $msgData = array(
            'htmlmessage'   => $msg['msghtml'],
            'textmessage'   => $msg['msgtext'],
            'subject'       => $msg['subject'],
            'from'          => NULL,
            'to'            => array(
                'name'  => $name,
                'email' => $email,
            ),
        );
        COM_emailNotification($msgData);
        Logger::audit("Sent card to user {$this->uid} ({$name})");
    }


    /**
     * Toggle this user's subscription to receive birthday cards.
     *
     * @param   integer $oldval Original field value
     * @return  integer     New field value
     */
    public function toggleCard(int $oldval) : int
    {
        global $_TABLES;

        $newval = $oldval ? 0 : 1;  // toggle to opposite
        COM_errorLog("changing to $newval");
        $db = Database::getInstance();
        try {
            $db->conn->executeUpdate(
                "UPDATE {$_TABLES['birthdays']} SET sendcards = ? WHERE uid = ?",
                array($newval, $this->uid),
                array(Database::INTEGER, Database::INTEGER)
            );
            return $newval;
        } catch (\Exception $e) {
            Logger::logException($e);
            return $oldval;
        }
    }

}
