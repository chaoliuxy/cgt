define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/index' + location.search,
                    add_url: 'order/add',
                    edit_url: 'order/edit',
                    del_url: 'order/del',
                    multi_url: 'order/multi',
                    import_url: 'order/import',
                    table: 'order',
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
                        {field: 'order_no', title: __('Order_no'), operate: 'LIKE'},
                        {field: 'pay_price', title: __('Pay_price'), operate:'BETWEEN'},
                        {field: 'pay_status', title: __('Pay_status'), searchList: {"10":__('Pay_status 10'),"20":__('Pay_status 20')}, formatter: Table.api.formatter.status},
                        {field: 'pay_time', title: __('Pay_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'pay_type', title: __('Pay_type'), searchList: {"10":__('Pay_type 10'),"20":__('Pay_type 20')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'order_ids', title: __('Order_ids'), operate: 'LIKE'},
                        {field: 'order_type', title: __('Order_type'), searchList: {"10":__('Order_type 10'),"20":__('Order_type 20'),"30":__('Order_type 30'),"40":__('Order_type 40'),"50":__('Order_type 50'),"60":__('Order_type 60')}, formatter: Table.api.formatter.normal},
                        {field: 'shop_id', title: __('Shop_id')},
                        {field: 'total_price', title: __('Total_price'), operate:'BETWEEN'},
                        {field: 'discount_price', title: __('Discount_price'), operate:'BETWEEN'},
                        {field: 'coupons_id', title: __('Coupons_id')},
                        {field: 'packing_fee', title: __('Packing_fee'), operate:'BETWEEN'},
                        {field: 'distribution_fee', title: __('Distribution_fee'), operate:'BETWEEN'},
                        {field: 'captcha', title: __('Captcha'), operate: 'LIKE'},
                        {field: 'groupbuying', title: __('Groupbuying'), searchList: {"10":__('Groupbuying 10'),"20":__('Groupbuying 20')}, formatter: Table.api.formatter.normal},
                        {field: 'groupbuying_status', title: __('Groupbuying_status'), searchList: {"10":__('Groupbuying_status 10'),"20":__('Groupbuying_status 20'),"30":__('Groupbuying_status 30'),"40":__('Groupbuying_status 40')}, formatter: Table.api.formatter.status},
                        {field: 'collage_sign', title: __('Collage_sign')},
                        {field: 'is_head', title: __('Is_head'), searchList: {"10":__('Is_head 10'),"20":__('Is_head 20')}, formatter: Table.api.formatter.normal},
                        {field: 'group_buy_time', title: __('Group_buy_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'group_work_number', title: __('Group_work_number')},
                        {field: 'group_work_numbers', title: __('Group_work_numbers')},
                        {field: 'groupon_id', title: __('Groupon_id')},
                        {field: 'discount_vip_price', title: __('Discount_vip_price'), operate:'BETWEEN'},
                        {field: 'total_discount_price', title: __('Total_discount_price'), operate:'BETWEEN'},
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