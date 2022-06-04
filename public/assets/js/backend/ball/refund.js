define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'ball/refund/index' + location.search,
                    add_url: 'ball/refund/add',
                    edit_url: 'ball/refund/edit',
                    del_url: 'ball/refund/del',
                    multi_url: 'ball/refund/multi',
                    table: 'ball_refund',
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
                        {field: 'admin_id', title: __('Admin_id')},
                        {field: 'total_fee', title: __('Total_fee')},
                        {field: 'refund_fee', title: __('Refund_fee')},
                        {field: 'refund_desc', title: __('Refund_desc')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'user.nickname', title: __('User.nickname')},
                        {field: 'user.mobile', title: '手机号'},
                        {field: 'ballorderdetail.type', title: __('Ballorderdetail.type'),searchList: {"big":"全场","small":"半场"}, formatter: Table.api.formatter.normal},
                        {field: 'ballorder.date', title: "订场日期"},
                        {field: 'ballorderdetail.time_num', title: __('Ballorderdetail.time_num')},
                        {field: 'ballorderdetail.money_num', title: __('Ballorderdetail.money_num')},
                        {field: 'ballorderdetail.sx', title: __('Ballorderdetail.sx')},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                        {field: 'note', title: __('Note')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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