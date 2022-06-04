define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'totalorder/index' + location.search,
                    add_url: 'totalorder/add',
                    // edit_url: 'totalorder/edit',
                    del_url: 'totalorder/del',
                    multi_url: 'totalorder/multi',
                    import_url: 'totalorder/import',
                    table: 'order',
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
                        {field: 'venue.name', title: __('所属体育馆'), operate: 'LIKE'},
                        {field: 'business.name', title: __('所属商家'), operate: 'LIKE'},
                        {field: 'order_no', title: __('Order_no'), operate: 'LIKE'},
                        {field: 'litestoreordergoods.goods_name', title: __('Litestoreordergoods.goods_name'), operate: 'LIKE'},
                        {field: 'litestoreordergoods.goods_attr', title: __('Litestoreordergoods.goods_attr'), operate: 'LIKE', operate: false},
                        // {field: 'user.nickname', title: __('下单人昵称'), operate: 'LIKE'},
                        {field: 'litestoreorderaddress.name', title: __('Litestoreorderaddress.name'), operate: 'LIKE'},
                        {field: 'litestoreorderaddress.phone', title: __('Litestoreorderaddress.phone'), operate: 'LIKE'},
                        {field: 'litestoreorderaddress.detail', title: __('Litestoreorderaddress.detail'), operate: 'LIKE'},
                        // {field: 'coupons_id', title: __('Coupons_id')},
                        {field: 'discount_price', title: __('Discount_price'), operate:'BETWEEN', operate: false},
                        {field: 'discount_vip_price', title: __('Discount_vip_price'), operate:'BETWEEN', operate: false},
                        {field: 'total_discount_price', title: __('Total_discount_price'), operate:'BETWEEN', operate: false},
                        {field: 'pay_price', title: __('Pay_price'), operate:'BETWEEN', operate: false},
                        {field: 'packing_fee', title: __('Packing_fee'), operate:'BETWEEN', operate: false},
                        {field: 'distribution_fee', title: __('Distribution_fee'), operate:'BETWEEN', operate: false},
                        {field: 'order_type', title: __('Order_type'), searchList: {"30":__('Order_type 30'),"50":__('Order_type 50'),"60":__('Order_type 60')}, formatter: Table.api.formatter.normal},
                        // {field: 'shop_id', title: __('Shop_id')},

                        // {field: 'captcha', title: __('Captcha'), operate: 'LIKE'},
                        {field: 'groupbuying', title: __('Groupbuying'), searchList: {"10":__('Groupbuying 10'),"20":__('Groupbuying 20')}, formatter: Table.api.formatter.normal},
                        {field: 'groupbuying_status', title: __('Groupbuying_status'), searchList: {"10":__('Groupbuying_status 10'),"20":__('Groupbuying_status 20'),"30":__('Groupbuying_status 30'),"40":__('Groupbuying_status 40')}, formatter: Table.api.formatter.status},
                        {field: 'pay_status', title: __('Pay_status'), searchList: {"10":__('Pay_status 10'),"20":__('Pay_status 20'),"50":__('Pay_status 50'),"60":__('Pay_status 60'),"70":__('Pay_status 70'),"80":__('Pay_status 80'),"90":__('Pay_status 90'),"100":__('Pay_status 100'),"110":__('Pay_status 110')}, formatter: Table.api.formatter.status},
                        {field: 'pay_time', title: __('Pay_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'user_id', title: __('User_id')},
                        {field: 'pay_type', title: __('Pay_type'), searchList: {"10":__('Pay_type 10'),"20":__('Pay_type 20')}, formatter: Table.api.formatter.normal, operate: false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, operate: false},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, operate: false},
                        // {field: 'order_ids', title: __('Order_ids'), operate: 'LIKE'},
                        // {field: 'collage_sign', title: __('Collage_sign')},
                        // {field: 'is_head', title: __('Is_head'), searchList: {"10":__('Is_head 10'),"20":__('Is_head 20')}, formatter: Table.api.formatter.normal},
                        // {field: 'group_buy_time', title: __('Group_buy_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'group_work_number', title: __('Group_work_number')},
                        // {field: 'group_work_numbers', title: __('Group_work_numbers')},
                        // {field: 'groupon_id', title: __('Groupon_id')},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                        {field: 'operate', title: __('Operate'), table: table, buttons: [
                            {name: 'send', text: __('view'), icon: 'fa fa-eye', classname: 'btn btn-xs btn-warning btn-dialog chakan', url: 'totalorder/detail'},
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
        detail: function(){
            $("#send").on('click', function() {
                var sn = $("#c-virtual_sn").val();
                var name = $("#c-virtual_name").val();
                if(sn == '' || name == '')
                {
                    layer.msg("请填写正确的快递信息");
                    return false;
                }
                $("#send-form").attr("action","totalorder/detail").submit();
            });
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