define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'venuemonetlog/index' + location.search,
                    add_url: 'venuemonetlog/add',
                    edit_url: 'venuemonetlog/edit',
                    del_url: 'venuemonetlog/del',
                    multi_url: 'venuemonetlog/multi',
                    import_url: 'venuemonetlog/import',
                    table: 'venue_money_log',
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
                        // {field: 'venue_id', title: __('Venue_id')},
                        {field: 'venue.name', title: __('Venue.name'), operate: 'LIKE'},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'before', title: __('Before'), operate:'BETWEEN', operate: false},
                        {field: 'after', title: __('After'), operate:'BETWEEN', operate: false},
                        {field: 'memo', title: __('Memo'), operate: 'LIKE'},
                        {field: 'type', title: __('Type'), searchList: {"10":__('Type 10'),"20":__('Type 20'),"30":__('Type 30'),"40":__('Type 40'),"50":__('Type 50'),"60":__('Type 60'),"70":__('Type 70')}, formatter: Table.api.formatter.normal},
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