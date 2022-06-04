define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'goodsgroupon/index' + location.search,
                    // add_url: 'goodsgroupon/add',
                    // edit_url: 'goodsgroupon/edit',
                    // del_url: 'goodsgroupon/del',
                    multi_url: 'goodsgroupon/multi',
                    import_url: 'goodsgroupon/import',
                    table: 'goods_groupon',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('团ID')},
                        {field: 'user.nickname', title: __('User_id'), operate: 'LIKE'},
                        {field: 'goods_id', title: __('Goods_id'), operate: false},
                        {field: 'order_id', title: __('Order_id')},
                        {field: 'num', title: __('Num'), operate: false},
                        {field: 'current_num', title: __('Current_num'), operate: false},
                        {field: 'status', title: __('Status'), searchList: {"invalid":__('Status invalid'),"ing":__('Status ing'),"finish":__('Status finish'),"finish-fictitious":__('Status finish-fictitious')}, formatter: Table.api.formatter.status},
                        {field: 'groupbuy_status', title: __('Groupbuy_status'), searchList: {"10":__('Groupbuy_status 10'),"20":__('Groupbuy_status 20')}, formatter: Table.api.formatter.status},
                        {field: 'finishtime', title: __('Finishtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, operate: false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, operate: false},
                        {field: 'expiretime', title: __('Expiretime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                        {field: 'operate', title: __('Operate'), table: table, buttons: [
                            {name: 'send', text: __('查看团购成员'), icon: 'fa fa-eye', classname: 'btn btn-xs btn-warning btn-dialog chakan', url: 'goodsgrouponlog?ref=addtabs'},
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