define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'venuevip/index' + location.search,
                    add_url: 'venuevip/add',
                    edit_url: 'venuevip/edit',
                    del_url: 'venuevip/del',
                    multi_url: 'venuevip/multi',
                    import_url: 'venuevip/import',
                    table: 'venue_vip',
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
                        {field: 'id', title: __('Id'),operate:false},
                        // {field: 'venue_id', title: __('Venue_id')},
                        {field: 'venue.name', title: __('Venue.name'), operate: 'LIKE'},
                        // {field: 'paytype_ids', title: __('Paytype_ids'), operate: false},
                        {field: 'month_cost', title: __('Month_cost'),  operate: false},
                        {field: 'quarter_cost', title: __('Quarter_cost'),  operate: false},
                        {field: 'year_cost', title: __('Year_cost'),  operate: false},
                        {field: 'discount', title: __('Discount')},
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