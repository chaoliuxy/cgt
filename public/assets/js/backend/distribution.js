define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'distribution/index' + location.search,
                    add_url: 'distribution/add',
                    edit_url: 'distribution/edit',
                    del_url: 'distribution/del',
                    multi_url: 'distribution/multi',
                    import_url: 'distribution/import',
                    table: 'distribution',
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
                        // {field: 'delivery_type', title: __('Delivery_type'), searchList: {"10":__('Delivery_type 10'),"20":__('Delivery_type 20'),"30":__('Delivery_type 30'),"40":__('Delivery_type 40'),"50":__('Delivery_type 50')}, formatter: Table.api.formatter.normal},
                        {field: 'packingfee', title: __('Packingfee'), operate:'BETWEEN'},
                        {field: 'distributionfee', title: __('Distributionfee'), operate:'BETWEEN'},
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