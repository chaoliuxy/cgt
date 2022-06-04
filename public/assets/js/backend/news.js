define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
  if ($('input[name="session"]').val()==0) {
    $("#c-label_id").data("params", function (obj) {
      return {custom: {venue_id: $('input[name="row[venue_id]"]').val()}};
    });
  }else{
    $("#c-label_id").data("params", function (obj) {
      return {custom: {venue_id: $('input[name="session"]').val()}};
    });
  }
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'news/index' + location.search,
                    add_url: 'news/add',
                    edit_url: 'news/edit',
                    del_url: 'news/del',
                    multi_url: 'news/multi',
                    import_url: 'news/import',
                    table: 'news',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                searchFormVisible:true,
                search:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        // {field: 'label_id', title: __('Label_id')},
                        {field: 'label.label_name', title: __('Label.label_name'), operate: 'LIKE'},
                        {field: 'venue.name', title: __('Venue.name'), operate: 'LIKE'},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        // {field: 'content', title: __('Content')},
                        // {field: 'venue_id', title: __('Venue_id')},
                        {field: 'status', title: __('Status'), searchList: {"10":__('Status 10'),"20":__('Status 20')}, formatter: Table.api.formatter.status},
                        {field: 'weigh', title: __('Weigh'), operate: false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, operate: false},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,buttons: [
                          {name: 'send', text: __('点赞列表'), icon: 'fa fa-eye', classname: 'btn btn-xs btn-warning btn-dialog', url: '/KIldJsDvMC.php/likes?ref=addtabs',
                        },
                          {name: 'send', text: __('评论列表'), icon: 'fa fa-eye', classname: 'btn btn-xs btn-warning btn-dialog', url: '/KIldJsDvMC.php/comment?ref=addtabs',
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