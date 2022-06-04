define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'boxlattice/index' + location.search,
                    add_url: 'boxlattice/add',
                    edit_url: 'boxlattice/edit',
                    del_url: 'boxlattice/del',
                    multi_url: 'boxlattice/multi',
                    import_url: 'boxlattice/import',
                    table: 'boxlattice',
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
                        {field: 'box_id', title: __('Box_id')},
                        {field: 'venue_id', title: __('Venue_id')},
                        {field: 'type', title: __('Type'), searchList: {"10":__('Type 10'),"20":__('Type 20'),"30":__('Type 30')}, formatter: Table.api.formatter.normal},
                        {field: 'number', title: __('Number'), operate: 'LIKE'},
                        {field: 'state', title: __('State'), searchList: {"1":__('State 1'),"3":__('State 3'),"5":__('State 5')}, formatter: Table.api.formatter.normal},
                        {field: 'goodsState', title: __('Goodsstate'), searchList: {"1":__('Goodsstate 1'),"0":__('Goodsstate 0')}, formatter: Table.api.formatter.normal},
                        {field: 'doorState', title: __('Doorstate'), searchList: {"1":__('Doorstate 1'),"0":__('Doorstate 0')}, formatter: Table.api.formatter.normal},
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