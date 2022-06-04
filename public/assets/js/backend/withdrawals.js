define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'withdrawals/index' + location.search,
                    add_url: 'withdrawals/add',
                    // edit_url: 'withdrawals/edit',
                    // del_url: 'withdrawals/del',
                    multi_url: 'withdrawals/multi',
                    import_url: 'withdrawals/import',
                    table: 'withdrawal',
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
                        // {field: 'venue_id', title: __('Venue_id')},
                        {field: 'venue.name', title: __('Venue_id'), operate: 'LIKE'},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'actualamount', title: __('Actualamount'), operate:'BETWEEN'},
                        {field: 'servicecharge', title: __('Servicecharge'), operate:'BETWEEN'},
                        {field: 'payeename', title: __('Payeename'), operate: 'LIKE'},
                        {field: 'account', title: __('Account'), operate: 'LIKE'},
                        {field: 'bank', title: __('Bank'), operate: 'LIKE'},
                        {field: 'subbranch', title: __('Subbranch'), operate: 'LIKE'},
                        {field: 'status', title: __('Status'), searchList: {"10":__('Status 10'),"20":__('Status 20'),"30":__('Status 30')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                        {
                            field: "operate",
                            title: __("Operate"),
                            table: table,
                            buttons: [
                                {
                                    name: 'see',
                                    title: __('查看'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-eye',
                                    url: 'withdrawals/edit',
                                    callback: function (data) {
                                        // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                        Layer.alert(ret.msg);
                                        location.reload();
                                        //如果需要阻止成功提示，则必须使用return false;
                                        return false;
                                    },
                                },
                            ],
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                        },
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