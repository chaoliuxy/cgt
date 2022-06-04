define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'activityorders/index' + location.search,
                    add_url: 'activityorders/add',
                    edit_url: 'activityorders/edit',
                    del_url: 'activityorders/del',
                    multi_url: 'activityorders/multi',
                    import_url: 'activityorders/import',
                    table: 'signup',
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
                        // {field: 'activity_id', title: __('Activity_id')},
                        {field: 'venue.name', title: __('所属体育馆'), operate: 'LIKE'},
                        {field: 'order.order_no', title: __('Order.order_no'), operate: 'LIKE'},
                        {field: 'activity.name', title: __('Activity.name'), operate: 'LIKE'},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'gender', title: __('Gender'), searchList: {"10":__('Gender 10'),"20":__('Gender 20')}, formatter: Table.api.formatter.normal},
                        {field: 'user.nickname', title: __('User.nickname'), operate: 'LIKE'},
                        // {field: 'venue_id', title: __('Venue_id')},
                        {field: 'order.discount_price', title: __('Order.discount_price'), operate:'BETWEEN', operate: false},
                        {field: 'order.discount_vip_price', title: __('Order.discount_vip_price'), operate:'BETWEEN', operate: false},
                        {field: 'order.total_discount_price', title: __('Order.total_discount_price'), operate:'BETWEEN', operate: false},
                        {field: 'order.pay_price', title: __('Order.pay_price'), operate:'BETWEEN', operate: false},
                        {field: 'status', title: __('Status'), searchList: {"10":__('Status 10'),"20":__('Status 20'),"30":__('Status 30'),"40":__('Status 40'),"50":__('Status 50')}, formatter: Table.api.formatter.status},
                        {field: 'type', title: __('Type'), searchList: {"10":__('Type 10'),"20":__('Type 20')}, formatter: Table.api.formatter.normal},
                        // {field: 'user_id', title: __('User_id')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'order.pay_status', title: __('Order.pay_status'), formatter: Table.api.formatter.status},
                        {field: 'order.pay_time', title: __('Order.pay_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'order.pay_type', title: __('Order.pay_type')},
                        {field: 'order.createtime', title: __('Order.createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, operate: false},
                        {field: 'order.updatetime', title: __('Order.updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, operate: false},
                        // {field: 'order.order_type', title: __('Order.order_type')},
                        // {field: 'order.groupbuying', title: __('Order.groupbuying')},
                        // {field: 'order.groupbuying_status', title: __('Order.groupbuying_status'), formatter: Table.api.formatter.status},

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