define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'banner/index' + location.search,
                    add_url: 'banner/add',
                    edit_url: 'banner/edit',
                    del_url: 'banner/del',
                    multi_url: 'banner/multi',
                    import_url: 'banner/import',
                    table: 'banner',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                search:false,
				searchFormVisible: true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'position_status', title: __('Position_status'), searchList: {"homepage":__('Position_status homepage'),"explain":__('Position_status explain'),"business":__('Position_status business')}, formatter: Table.api.formatter.status},
                        {field: 'venue.name', title: __('Venue_id'), operate: 'LIKE'},
                        {field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'type', title: __('Type'), searchList: {"10":__('Type 10'),"20":__('Type 20'),"30":__('Type 30'),"40":__('Type 40')}, formatter: Table.api.formatter.normal},
                        // {field: 'position_id', title: __('Position_id')},
                        // {field: 'title', title: __('Title'), operate: 'LIKE'},
                        // {field: 'content', title: __('Content')},
                        {field: 'status', title: __('Status'), searchList: {"10":__('Status 10'),"20":__('Status 20')}, formatter: Table.api.formatter.status},
                        {field: 'weigh', title: __('Weigh'), operate: false},
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
            $('#c-type').change(function(){
          //后续操作10=活动详情,20=课程详情,30=场馆介绍,40=自定义页面
            // var type = $('#c-type').find("option:selected").val();
            var type = $(this).val();
            if (type=='10') {
                document.getElementById("activity_ids").style.display="block";//隐藏
                document.getElementById("goods_ids").style.display="none";//隐藏
                $(function(){
                //请求参数
                    $.post("https://cgt.qifudaren.net/api/index/getactivitylist",{data:$('input[id="c-venue_id"]').val()},function(result){
                        if (JSON.stringify(result.data)) {
                            var data = result.data;
                            var activity = $('#c-activity_id');
                            $("#c-activity_id").empty();
                            if (data.length > 0) {
                                for (var i = 0; i < data.length; i++) {
                                    activity.append('<option value="' + data[i].id + '">' + data[i].name + '</option>');
                                }
                            }
                        }
                    });
                });
            }else if(type=='20'){
                document.getElementById("goods_ids").style.display="block";//隐藏
                document.getElementById("activity_ids").style.display="none";//显示
                $(function(){
                //请求参数
                    $.post("https://cgt.qifudaren.net/api/index/goodslist",{data:$('input[id="c-venue_id"]').val()},function(result){
                        if (JSON.stringify(result.data)) {
                            var data = result.data;
                            var venue = $('#c-goods_id');
                            $("#c-goods_id").empty();
                            if (data.length > 0) {
                                for (var i = 0; i < data.length; i++) {
                                    venue.append('<option value="' + data[i].goods_id + '">' + data[i].goods_name + '</option>');
                                }
                            }
                        }
                    });
                });
            }else{
                document.getElementById("goods_ids").style.display="none";//隐藏
                document.getElementById("activity_ids").style.display="none";//显示
            }
            });
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