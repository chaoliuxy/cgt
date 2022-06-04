define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
  if ($('input[name="session"]').val()==0) {
    $("#c-type_id").data("params", function (obj) {
      return {custom: {venue_id: $('input[name="row[venue_id]"]').val()}};
    });
    $("#c-facilities_ids").data("params", function (obj) {
      return {custom: {venue_id: $('input[name="row[venue_id]"]').val()}};
    });
    $("#c-balltemplate_id").data("params", function (obj) {
      return {custom: {venue_id: $('input[name="row[venue_id]"]').val()}};
    });
  }else{
    $("#c-type_id").data("params", function (obj) {
      return {custom: {venue_id: $('input[name="session"]').val()}};
    });
    $("#c-facilities_ids").data("params", function (obj) {
      return {custom: {venue_id: $('input[name="session"]').val()}};
    });
    $("#c-balltemplate_id").data("params", function (obj) {
      return {custom: {venue_id: $('input[name="session"]').val()}};
    });
  }
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'reservation/index' + location.search,
                    add_url: 'reservation/add',
                    edit_url: 'reservation/edit',
                    del_url: 'reservation/del',
                    multi_url: 'reservation/multi',
                    import_url: 'reservation/import',
                    table: 'reservation',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                searchFormVisible:true,
                search:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'type.type_name', title: __('Type_id'), operate: 'LIKE'},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'images', title: __('Images'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.images, operate: false},
                        {field: 'start_time', title: __('Start_time'), autocomplete:false},
                        {field: 'end_time', title: __('End_time'), autocomplete:false},
                        {field: 'address', title: __('Address'), operate: 'LIKE'},
                        // {field: 'lng', title: __('Lng'), operate: 'LIKE'},
                        // {field: 'lat', title: __('Lat'), operate: 'LIKE'},
                        // {field: 'facilities_ids', title: __('Facilities_ids'), operate: 'LIKE'},
                        // {field: 'time', title: __('Time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        // {field: 'big_num', title: __('Big_num')},
                        // {field: 'time_num', title: __('Time_num')},
                        // {field: 'open_small', title: __('Open_small'), searchList: {"10":__('Open_small 10'),"20":__('Open_small 20')}, formatter: Table.api.formatter.normal},
                        {field: 'status', title: __('Status'), searchList: {"10":__('Status 10'),"20":__('Status 20')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,buttons: [
													{
															name: 'detail',
															text: __('自动生成7天预定信息'),
															title: __('自动生成7天预定信息'),
															classname: 'btn btn-xs btn-success btn-magic btn-ajax',
															icon: 'fa fa-magic',
															// url: 'ball/template/create_date',
															url: 'reservation/create_date',
															confirm: "确认发送",
															extend:'data-confirm="确认发送"',
															success: function (data, ret) {
																	Layer.alert(ret.msg);
																	//如果需要阻止成功提示，则必须使用return false;
																	return false;
															},
															error: function (data, ret) {
																	Layer.alert(ret.msg);
																	return false;
															}
													},
                          {name: 'send', text: __('灯控列表'), icon: 'fa fa-eye', classname: 'btn btn-xs btn-warning btn-dialog', url: '/KIldJsDvMC.php/lamplist?ref=addtabs',
                        },
                        ]
                      }
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