define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
  if ($('input[name="session"]').val()!=0) {
    $("#c-sporttype_ids").data("params", function (obj) {
      return {custom: {venue_id: $('input[name="session"]').val()}};
    });
  }
  else{
    $("#c-sporttype_ids").data("params", function (obj) {
      return {custom: {venue_id: $('input[name="row[venue_id]"]').val()}};
    });
  }
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'venue/index' + location.search,
                    add_url: 'venue/add',
                    edit_url: 'venue/edit',
                    del_url: 'venue/del',
                    multi_url: 'venue/multi',
                    import_url: 'venue/import',
                    table: 'venue',
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
                        {field: 'star_time', title: __('Star_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, operate: false},
                        {field: 'end_time', title: __('End_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, operate: false},
                        {field: 'money', title: __('Money')},
                        {field: 'address', title: __('Address'), operate: 'LIKE'},
                        {field: 'lng', title: __('Lng'), operate: 'LIKE', operate: false},
                        {field: 'lat', title: __('Lat'), operate: 'LIKE', operate: false},
                        {field: 'status', title: __('Status'), searchList: {"10":__('Status 10'),"20":__('Status 20')}, formatter: Table.api.formatter.status},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, operate: false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
												{field: 'operate', title: __('Operate'), table: table, buttons: [
                          {name: 'send', text: __('余额明'), icon: 'fa fa-eye', classname: 'btn btn-xs btn-warning btn-dialog', url: '/KIldJsDvMC.php/venuemonetlog?ref=addtabs',
                        },
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});