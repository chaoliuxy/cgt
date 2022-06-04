define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
  if ($('input[name="session"]').val()) {
    $("#c-reservation_ids").data("params", function (obj) {
      return {custom: {venue_id: $('input[name="session"]').val()}};
    });
  }else{
    $("#c-reservation_ids").data("params", function (obj) {
      return {custom: {venue_id: $('input[name="row[venue_id]"]').val()}};
    });
  }
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'coupons/index' + location.search,
                    add_url: 'coupons/add',
                    edit_url: 'coupons/edit',
                    del_url: 'coupons/del',
                    multi_url: 'coupons/multi',
                    import_url: 'coupons/import',
                    table: 'coupons',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                search:false,
				searchFormVisible: true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'venue.name', title: __('Venue_id'), operate: 'Like'},
                        // {field: 'reservation_ids', title: __('Reservation_ids'), operate: 'LIKE'},
                        {field: 'scene_type', title: __('Scene_type'), searchList: {"10":__('Scene_type 10'),"20":__('Scene_type 20'),"30":__('Scene_type 30'),"40":__('Scene_type 40'),"50":__('Scene_type 50')}, formatter: Table.api.formatter.normal},
                        {field: 'coupons_type', title: __('Coupons_type'), searchList: {"10":__('Coupons_type 10'),"20":__('Coupons_type 20'),"30":__('Coupons_type 30')}, formatter: Table.api.formatter.normal},
                        {field: 'coupons_name', title: __('Coupons_name'), operate: 'LIKE'},
                        {field: 'coupons_image', title: __('Coupons_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'coupons_money', title: __('Coupons_money'), operate:'BETWEEN', operate: false},
                        {field: 'use_condition', title: __('Use_condition'), operate:'BETWEEN', operate: false},
                        {field: 'startime', title: __('Startime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, operate: false},
                        {field: 'endtime', title: __('Endtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, operate: false},
                        {field: 'weigh', title: __('Weigh'), operate: false, operate: false},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, operate: false},
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