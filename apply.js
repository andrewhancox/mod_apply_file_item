function set_item_focus(itemid) {
    var item = document.getElementById(itemid);
    if(item){
        item.focus();
    }
}

/*
function applyGo2delete(form) {
    form.action = M.cfg.wwwroot+'/mod/apply/delete_submit.php';
    form.submit();
}

function setcourseitemfilter(item, item_typ) {
    document.report.courseitemfilter.value = item;
    document.report.courseitemfiltertyp.value = item_typ;
    document.report.submit();
}


M.mod_apply = {};

M.mod_apply.init_sendmessage = function(Y) {
    Y.on('click', function(e) {
        Y.all('input.usercheckbox').each(function() {
            this.set('checked', 'checked');
        });
    }, '#checkall');

    Y.on('click', function(e) {
        Y.all('input.usercheckbox').each(function() {
            this.set('checked', '');
        });
    }, '#checknone');
};
*/
