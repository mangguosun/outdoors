admin.delField = function (p_field_id, p_alias) {
    if (confirm('是否删除字段' + p_alias + '？')) {
        $.post(U('cat/Admin/doDelField'), {'field_id': p_field_id}, function (msg) {
            location.reload();
        }, 'json');
    }

};
admin.delEntity = function (p_entity_id, p_alias) {
    if (confirm('是否删除实体' + p_alias + '？')) {
        $.post(U('cat/Admin/doDelEntity'), {'entity_id': p_entity_id}, function (msg) {
            location.reload();
        }, 'json');
    }

};
admin.delInfo = function (p_info_id, p_alias) {
    if (confirm('是否删除信息' + p_alias + '？')) {
        $.post(U('cat/Admin/doDelInfo'), {'info_id': p_info_id}, function (msg) {
            location.reload();
        }, 'json');
    }

};
admin.delCom = function (p_com_id) {
    if (confirm('是否删除该评论？')) {
        $.post(U('cat/Admin/doDelCom'), {'com_id': p_com_id}, function (msg) {
            location.reload();
        }, 'json');
    }
};
admin.active = function (p_info_id) {
    if (confirm('通过审核？')) {
        $.post(U('cat/Admin/doActive'), {info_id: p_info_id,active:1}, function (msg) {
            location.reload();
        }, 'json');
    }
};
admin.unactive = function (p_info_id) {
    if (confirm('通过审核？')) {
        $.post(U('cat/Admin/doActive'), {info_id: p_info_id,active:0}, function (msg) {
            location.reload();
        }, 'json');
    }
};