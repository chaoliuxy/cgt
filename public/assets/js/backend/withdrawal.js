define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'withdrawal/index' + location.search,
                    add_url: 'withdrawal/add',
                    // edit_url: 'withdrawal/edit',
                    // del_url: 'withdrawal/del',
                    remark_url:'withdrawal/remark',
                    examine_url:'withdrawal/examine',
                    multi_url: 'withdrawal/multi',
                    import_url: 'withdrawal/import',
                    table: 'withdrawal',
                }
            });

            var table = $("#table");
						var venue_id = 0;
            table.on('load-success.bs.table', function (e, data) {
							//这里可以获取从服务端获取的JSON数据
							//这里我们手动设置底部的值
							$("#venue_id").text(data.extend.venue_id);
							venue_id = data.extend.venue_id;
					  });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
								search:false,
                searchFormVisible:true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate: false},
                        // {field: 'venue_id', title: __('Venue_id')},
                        {field: 'venue.name', title: __('Venue_id'), operate: 'LIKE'},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'actualamount', title: __('Actualamount'), operate:'BETWEEN', operate: false},
                        {field: 'servicecharge', title: __('Servicecharge'), operate:'BETWEEN', operate: false},
                        {field: 'payeename', title: __('Payeename'), operate: 'LIKE', operate: false},
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
															name: 'remark',
															title: __('打款'),
															classname: 'btn btn-xs btn-success btn-magic btn-dialog',
															icon: 'fa fa-check',
															url: 'withdrawal/examine',
															callback: function (data) {
																// Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
																Layer.alert(ret.msg);
																location.reload();
																//如果需要阻止成功提示，则必须使用return false;
																return false;
															},
															hidden:function(row){
																if(row.status != 10){
																		return true;
																}else{
																	if (venue_id) {
																		return true;
																	}else{
																		return false;
																	}
																}
															}
														},
														{
															name: 'remark',
															title: __('驳回'),
															classname: 'btn btn-xs btn-primary btn-dialog',
															icon: 'fa fa-times',
															url: 'withdrawal/remark',
															callback: function (data) {
																// Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
																Layer.alert(ret.msg);
																location.reload();
																//如果需要阻止成功提示，则必须使用return false;
																return false;
															},
															hidden:function(row){
																if(row.status != 10){
																		return true;
																}else{
																	if (venue_id) {
																		return true;
																	}else{
																		return false;
																	}
																}
															}
														},
														{
															name: 'see',
															title: __('查看'),
															classname: 'btn btn-xs btn-success btn-dialog',
															icon: 'fa fa-eye',
															url: 'withdrawal/edit',
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
        remark_url: function () {
            Controller.api.bindevent();
        },
        examine_url: function () {
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