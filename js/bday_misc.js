/*  Miscellaneous javascript AJAX functions
*/

/**
 * Toggle the current user's subscription to receive birthday notifications.
 *
 * @param   object  cbox    Subscription yes/no checkbox
 * @param   integer uid     Current user ID
 * @return  boolean     False
 */
var BDAY_toggleSub = function(cbox, uid) {
    var oldval = cbox.checked ? 0 : 1;
    var dataS = {
        "action" : "toggleSub",
        "uid": uid,
        "oldval": oldval,
    };
    data = $.param(dataS);
    $.ajax({
        type: "POST",
        dataType: "json",
        url: glfusionSiteUrl + "/birthdays/ajax.php",
        data: data,
        success: function(result) {
            cbox.checked = result.newval == 1 ? true : false;
            try {
                $.UIkit.notify("<i class='uk-icon-check'></i>&nbsp;" + result.statusMessage, {timeout: 1000,pos:'top-center'});
            }
            catch(err) {
                alert(result.statusMessage);
            }
        }
    });
    return false;
};

/**
 * Toggle a user's subscription to receive birthday cards.
 *
 * @param   object  cbox    Subscription yes/no checkbox
 * @param   integer uid     User ID
 * @return  boolean     False
 */
var BDAY_toggleCards = function(cbox, uid) {
    var oldval = cbox.checked ? 0 : 1;
    var dataS = {
        "action" : "toggleCards",
        "uid": uid,
        "oldval": oldval,
    };
    data = $.param(dataS);
    $.ajax({
        type: "POST",
        dataType: "json",
        url: glfusionSiteUrl + "/birthdays/ajax.php",
        data: data,
        success: function(result) {
            cbox.checked = result.newval == 1 ? true : false;
            try {
                $.UIkit.notify("<i class='uk-icon-check'></i>&nbsp;" + result.statusMessage, {timeout: 1000,pos:'top-center'});
            }
            catch(err) {
                alert(result.statusMessage);
            }
        }
    });
    return false;
};

/**
 * Update the days selection based on the supplied month.
 *
 * @param   integer value   Month number
 */
function BDAY_updateDay(value)
{
    if (value == 2) {
        // 29-day month (Feb)
        document.getElementById("bday_day_30").style.display = "none";
        document.getElementById("bday_day_31").style.display = "none";
    } else if (value == 4 || value == 6 || value == 9 || value == 11) {
        // 30-day month
        document.getElementById("bday_day_30").style.display = "";
        document.getElementById("bday_day_31").style.display = "none";
    } else {
        // 31-day month
        document.getElementById("bday_day_30").style.display = "";
        document.getElementById("bday_day_31").style.display = "";
    }
}

/**
 * Set today's date as the birthday.
 *
 * @param   string  rnd     Random number string to identify the field
 */
function BDAY_setToday(rnd)
{
    var d = new Date();
    var month = d.getMonth()+1;
    var day = d.getDate();

    var m_sel = "#bday_month_sel_"+rnd ;
    var d_sel = "#bday_day_sel_"+rnd ;
    var curmonth = $(m_sel).val();
    var curday = $(d_sel).val();
    $(m_sel + " option[value=" + curmonth + "]").attr("selected", false);
    $(m_sel + ' option[value=' + month.toString() +']').attr("selected", true);
    $(d_sel + " option[value=" + curday + "]").attr("selected", false);
    $(d_sel + ' option[value=' + day.toString() +']').attr("selected", true);
}

