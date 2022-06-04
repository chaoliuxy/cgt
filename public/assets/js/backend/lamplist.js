define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'lamplist/index' + location.search,
                    add_url: 'lamplist/add',
                    edit_url: 'lamplist/edit',
                    del_url: 'lamplist/del',
                    multi_url: 'lamplist/multi',
                    import_url: 'lamplist/import',
                    table: 'lamplist',
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
                        {field: 'field_name', title: __('Field_name'), operate: 'LIKE'},
                        {field: 'lamp_id', title: __('Lamp_id')},
                        {field: 'reservation_id', title: __('Reservation_id')},
                        {field: 'number', title: __('Number')},
                        {field: 'status', title: __('Status'), searchList: {"10":__('Status 10'),"20":__('Status 20')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                        {
							field: "operate",
							title: __("Operate"),
							table: table,
							buttons: [
								{
									name: "ajax",
									title: __("开灯"),
									classname: "btn btn-xs btn-success btn-magic btn-ajax",
									// icon: "fa fa-check",
									icon: "fa fa-bell-o",
									confirm: "确认开灯？",
									url: "lamplist/open",
									success: function (data, ret) {
										Layer.alert(ret.msg);
										//如果需要阻止成功提示，则必须使用return false;
                                        location.reload();
										return false;
									},
									error: function (data, ret) {
										Layer.alert(ret.msg);
                                        location.reload();
										return false;
									},
								},
                                {
									name: "ajax",
									title: __("关灯"),
									classname: "btn btn-xs btn-success btn-magic btn-ajax",
									// icon: "fa fa-times",
									icon: "fa fa-bell-slash-o",
									confirm: "确认关灯？",
									url: "lamplist/close",
									success: function (data, ret) {
										Layer.alert(ret.msg);
                                        location.reload();
										//如果需要阻止成功提示，则必须使用return false;
										return false;
									},
									error: function (data, ret) {
										Layer.alert(ret.msg);
                                        location.reload();
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