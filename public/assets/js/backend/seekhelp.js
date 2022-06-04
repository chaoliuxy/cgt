define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
  if ($('input[name="session"]').val()) {
    $("#c-helptype_id").data("params", function (obj) {
      return {custom: {venue_id: $('input[name="session"]').val()}};
    });
  }else{
    $("#c-helptype_id").data("params", function (obj) {
      return {custom: {venue_id: $('input[name="row[venue_id]"]').val()}};
    });
  }
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'seekhelp/index' + location.search,
                    add_url: 'seekhelp/add',
                    edit_url: 'seekhelp/edit',
                    del_url: 'seekhelp/del',
                    multi_url: 'seekhelp/multi',
                    import_url: 'seekhelp/import',
                    table: 'seekhelp',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search:false,
				searchFormVisible: true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'venue.name', title: __('Venue_id'), operate: 'LIKE'},
                        {field: 'helptype.name', title: __('Helptype_id')},
                        {field: 'phone', title: __('Phone'), operate: 'LIKE'},
                        {field: 'user.nickname', title: __('User_id'), operate: 'LIKE'},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, operate: false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});