<?php
/**
 * Service functions for the Birthdays plugin.
 * This file provides functions to be called by other plugins, such
 * as the Custom Profile plugin for profile lists.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2020 Lee Garner <lee@leegarner.com>
 * @package     birthdays
 * @version     1.0.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own!');
}


/**
 * Get the query element needed when collecting data for the Profile plugin.
 * The $output array contains the field names, the SELECT and JOIN queries,
 * and the search fields for the ADMIN_list function.
 *
 * @param   array   $args       Post, Get, incl_exp_stat and incl_user_stat
 * @param   array   &$output    Pointer to output array
 * @param   array   &$svc_msg   Unused
 * @return  integer             Status code
 */
function service_profilefields_birthdays($args, &$output, &$svc_msg)
{
    global $LANG_BD00, $_BD_CONF, $_TABLES;

    $pi = $_BD_CONF['pi_name'];
    $tbl = $_TABLES['birthdays'];

    // Does not support remote web services, must be local only.
    if ($args['gl_svc'] !== false) {
        return PLG_RET_PERMISSION_DENIED;
    }

    $output = array(
        'names' => array(
            $pi . '_birthday' => array(
                'field' => "{$tbl}.day",
                'title' => $LANG_BD00['birthday'],
            ),
        ),

        'query' => "{$tbl}.month as {$pi}_month,
                    {$tbl}.day as {$pi}_day",

        'join' => "LEFT JOIN {$tbl} ON u.uid = {$tbl}.uid",

        'where' => '',

        'search' => array(),

        'f_info' => array(
            $pi . '_birthday' => array(
                'disp_func' => 'birthdays_profilefield_birthday',
            ),
        ),
    );

    return PLG_RET_OK;
}


/**
 * Callback to display the expiration date in profile listings.
 * Same parameters as the normal field display functions.
 * Expects $A['membership_exp_days'] to contain the number of days that this
 * membership has expired, with a negative number indicating that the
 * membership has not yet expired.
 *
 * @param   string  $fieldname  Name of field
 * @param   mixed   $fieldvalue Value of field
 * @param   array   $A          Array of all field name=>value
 * @param   array   $icon_arr   Array of icons
 * @param   array   $extras     Possible extra pass-through values
 * @return  string      HTML for field display
 */
function birthdays_profilefield_birthday(
    $fieldname, $fieldvalue, $A, $icon_arr, $extras
) {
    global $LANG_MONTH;

    $retval = '';
    switch ($fieldname) {
    case 'birthdays_birthday':
        $month = (int)$A['birthdays_month'];
        if ($month > 0) {
            $retval = $LANG_MONTH[$month] . ' ' . $A['birthdays_day'];
        }
        break;
    }
    return $retval;
}

?>
