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
