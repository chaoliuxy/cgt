define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'signup/index' + location.search,
                    add_url: 'signup/add',
                    edit_url: 'signup/edit',
                    del_url: 'signup/del',
                    multi_url: 'signup/multi',
                    import_url: 'signup/import',
                    table: 'signup',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        // {field: 'activity_id', title: __('Activity_id')},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'gender', title: __('Gender'), searchList: {"10":__('Gender 10'),"20":__('Gender 20')}, formatter: Table.api.formatter.normal},
                        // {field: 'venue_id', title: __('Venue_id')},
                        {field: 'status', title: __('Status'), searchList: {"10":__('Status 10'),"20":__('Status 20'),"30":__('Status 30'),"40":__('Status 40'),"50":__('Status 50')}, formatter: Table.api.formatter.status},
                        {field: 'type', title: __('Type'), searchList: {"10":__('Type 10'),"20":__('Type 20')}, formatter: Table.api.formatter.normal},
                        // {field: 'user_id', title: __('User_id')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'activity.name', title: __('Activity.name'), operate: 'LIKE'},
                        {field: 'user.nickname', title: __('User.nickname'), operate: 'LIKE'},
                        {field: 'venue.name', title: __('Venue.name'), operate: 'LIKE'},
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