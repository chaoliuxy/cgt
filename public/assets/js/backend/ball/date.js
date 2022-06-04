define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template'], function ($, undefined, Backend, Table, Form, Template) {
  if ($('input[name="session"]').val()==0) {
    $("#c-reservation_id").data("params", function (obj) {
      return {custom: {venue_id: $('input[name="row[venue_id]"]').val()}};
    });
  }else{
    $("#c-reservation_id").data("params", function (obj) {
      return {custom: {venue_id: $('input[name="session"]').val()}};
    });
  }
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'ball/date/index' + location.search,
                    add_url: 'ball/date/add',
                    edit_url: 'ball/date/edit',
                    del_url: 'ball/date/del',
                    multi_url: 'ball/date/multi',
                    table: 'ball_date',
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
                        {field: 'time', title: __('Time')},
                        {field: 'reservation.name', title: __('Reservation_id'), operate: 'LIKE'},
                        // {field: 'open_small', title: __('Open_small'), searchList: {"0":"关闭","1":"开启"}, formatter: Table.api.formatter.label},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime, operate: false},
                        {field: 'weigh', title: __('Weigh'), operate: false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            //当内容渲染完成给编辑按钮添加`data-area`属性
            table.on('post-body.bs.table', function (e, settings, json, xhr) {
                $(".btn-editone").data("area", ["100%", "100%"]);
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            $("#csh").click(function(){
                var big_num = $("#c-big_num").val();
                var time_num = $("#c-time_num").val();
                var open_small = $("#c-open_small").val();

                if(!big_num || !time_num){
                    layer.alert("请完善数据");
                }

                var content = Template("createtpl",{big_num:big_num,time_num:time_num,open_small:open_small})

                $("#creat").html(content)

                Form.api.bindevent($("form[role=form]"));
            })
            Controller.api.bindevent();
        },
        edit: function () {
            $("#csh").click(function(){
                var big_num = $("#c-big_num").val();
                var time_num = $("#c-time_num").val();
                var open_small = $("#c-open_small").val();

                if(!big_num || !time_num){
                    layer.alert("请完善数据");
                }

                var content = Template("createtpl",{big_num:big_num,time_num:time_num,open_small:open_small})

                $("#creat").html(content)

                Form.api.bindevent($("form[role=form]"));
            })
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