define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    if ($('input[name="session"]').val()) {
        $("#c-reservation_id").data("params", function () {
          return {custom: {venue_id: $('input[name="session"]').val()}};
        });
      }else{
        $("#c-reservation_id").data("params", function () {
          return {custom: {venue_id: $('input[name="row[venue_id]"]').val()}};
        });
      }
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'box/index' + location.search,
                    add_url: 'box/add',
                    edit_url: 'box/edit',
                    del_url: 'box/del',
                    multi_url: 'box/multi',
                    import_url: 'box/import',
                    qrcode_url: 'box/qrcode',
                    table: 'box',
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
                        {field: 'venue.name', title: __('Venue_id'), operate: 'LIKE'},
                        // {field: 'reservation_id', title: __('Reservation_id')},
                        {field: 'case_id', title: __('Case_id')},
                        {field: 'type', title: __('Type'), searchList: {"10":__('type 10'),"20":__('type 20')}, formatter: Table.api.formatter.normal},
                        // {field: 'opentime', title: __('Opentime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'qrcode', title: __('Qrcode'), operate: 'LIKE', formatter: Table.api.formatter.url},
                        // {field: 'tel', title: __('Tel'), operate: 'LIKE'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                        {field: 'operate', title: __('Operate'), table: table, buttons: [
                            {name: 'send', text: __('小程序码'), icon: 'fa fa-eye', classname: 'btn btn-xs btn-warning btn-dialog chakan', url: 'box/qrcode'},
                            {name: 'send', text: __('箱格管理'), icon: 'fa fa-eye', classname: 'btn btn-xs btn-warning btn-dialog chakan', url: '/KIldJsDvMC.php/boxlattices?ref=addtabs'},
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
        qrcode: function () {
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