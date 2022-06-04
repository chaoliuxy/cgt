define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'goodsgrouponlog/index' + location.search,
                    add_url: 'goodsgrouponlog/add',
                    edit_url: 'goodsgrouponlog/edit',
                    del_url: 'goodsgrouponlog/del',
                    multi_url: 'goodsgrouponlog/multi',
                    import_url: 'goodsgrouponlog/import',
                    table: 'goods_groupon_log',
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
                        // {field: 'user_id', title: __('User_id')},
                        {field: 'user_nickname', title: __('User_nickname'), operate: 'LIKE'},
                        {field: 'user_avatar', title: __('User_avatar'), operate: 'LIKE', events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'groupon_id', title: __('团ID')},
                        // {field: 'goods_id', title: __('Goods_id')},
                        // {field: 'goods_sku_price_id', title: __('Goods_sku_price_id')},
                        {field: 'is_leader', title: __('Is_leader')},
                        // {field: 'order_id', title: __('Order_id')},
                        {field: 'is_refund', title: __('Is_refund'), searchList: {"0":__('Is_refund 0'),"1":__('Is_refund 1')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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