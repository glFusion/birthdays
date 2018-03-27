/*  Miscellaneous javascript AJAX functions
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

function BDAY_updateDay(value)
{
    if (value == 2) {
        document.getElementById("bday_day_30").style.display = "none";
        document.getElementById("bday_day_31").style.display = "none";
    } else if (value == 4 || value == 6 || value == 9 || value == 11) {
        document.getElementById("bday_day_30").style.display = "";
        document.getElementById("bday_day_31").style.display = "none";
    } else {
        document.getElementById("bday_day_30").style.display = "";
        document.getElementById("bday_day_31").style.display = "";
    }
}

