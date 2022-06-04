define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'boxlattices/index' + location.search,
                    // add_url: 'boxlattices/add',
                    edit_url: 'boxlattices/edit',
                    // del_url: 'boxlattices/del',
                    multi_url: 'boxlattices/multi',
                    import_url: 'boxlattices/import',
                    table: 'boxlattices',
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
                        {field: 'devId', title: __('Devid')},
                        // {field: 'venue_id', title: __('Venue_id')},
                        {field: 'boxType', title: __('Boxtype'), searchList: {"C":__('Boxtype c'),"S":__('Boxtype s'),"M":__('Boxtype m'),"L":__('Boxtype l'),"X":__('Boxtype x')}, formatter: Table.api.formatter.normal},
                        {field: 'boxId', title: __('Boxid'), operate: 'LIKE'},
                        {field: 'state', title: __('State'), searchList: {"1":__('State 1'),"3":__('State 3'),"5":__('State 5')}, formatter: Table.api.formatter.normal},
                        {field: 'goodsState', title: __('Goodsstate'), searchList: {"1":__('Goodsstate 1'),"0":__('Goodsstate 0')}, formatter: Table.api.formatter.normal},
                        {field: 'doorState', title: __('Doorstate'), searchList: {"1":__('Doorstate 1'),"0":__('Doorstate 0')}, formatter: Table.api.formatter.normal},
                        {field: 'use_status', title: __('Use_status'), searchList: {"10":__('Use_status 10'),"20":__('Use_status 20')}, formatter: Table.api.formatter.status},
                        {field: 'is_use', title: __('Is_use'), searchList: {"10":__('Is_use 10'),"20":__('Is_use 20')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                        {field: 'operate', title: __('Operate'), table: table, buttons: [
                            // {name: 'send', text: __('清箱'), icon: 'fa fa-eye', classname: 'btn btn-xs btn-warning btn-dialog chakan', url: 'boxlattices/clearbox'},
                        ],  events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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