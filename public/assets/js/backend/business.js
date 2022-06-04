define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'business/index' + location.search,
                    add_url: 'business/add',
                    edit_url: 'business/edit',
                    del_url: 'business/del',
                    multi_url: 'business/multi',
                    import_url: 'business/import',
                    table: 'business',
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
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'images', title: __('Images'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.images, operate: false},
                        // {field: 'business_label_ids', title: __('Business_label_ids'), operate: 'LIKE'},
                        {field: 'score', title: __('Score'), operate:'BETWEEN'},
                        {field: 'starting_price', title: __('Starting_price'), operate:'BETWEEN'},
                        {field: 'packing_fee', title: __('Packing_fee'), operate:'BETWEEN'},
                        {field: 'distribution_fee', title: __('Distribution_fee'), operate:'BETWEEN'},
                        {field: 'type', title: __('Type'), searchList: {"10":__('Type 10'),"20":__('Type 20'),"30":__('Type 30')}, formatter: Table.api.formatter.normal},
                        {field: 'is_recommend', title: __('Is_recommend'), searchList: {"10":__('Is_recommend 10'),"20":__('Is_recommend 20')}, formatter: Table.api.formatter.normal},
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