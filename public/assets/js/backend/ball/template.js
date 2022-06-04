define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template'], function ($, undefined, Backend, Table, Form, Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'ball/template/index' + location.search,
                    add_url: 'ball/template/add',
                    edit_url: 'ball/template/edit',
                    del_url: 'ball/template/del',
                    multi_url: 'ball/template/multi',
                    table: 'ball_template',
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
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'title', title: __('Title')},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1')}, formatter: Table.api.formatter.status},
                        {field: 'weigh', title: __('Weigh'), operate: false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime, operate: false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,buttons: [
                                {
                                    name: 'detail',
                                    text: __('自动生成7天预定信息'),
                                    title: __('自动生成7天预定信息'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-magic',
                                    url: 'ball/template/create_date',
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
                                }]}
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