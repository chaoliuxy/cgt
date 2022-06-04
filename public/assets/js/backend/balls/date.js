define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'balls/date/index' + location.search,
                    add_url: 'balls/date/add',
                    edit_url: 'balls/date/edit',
                    del_url: 'balls/date/del',
                    multi_url: 'balls/date/multi',
                    import_url: 'balls/date/import',
                    table: 'ball_date',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'reservation_id', title: __('Reservation_id')},
                        {field: 'venue_id', title: __('Venue_id')},
                        {field: 'time', title: __('Time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'big_num', title: __('Big_num')},
                        {field: 'time_num', title: __('Time_num')},
                        {field: 'open_small', title: __('Open_small')},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1')}, formatter: Table.api.formatter.status},
                        {field: 'weigh', title: __('Weigh'), operate: false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'reservation.id', title: __('Reservation.id')},
                        {field: 'reservation.type_id', title: __('Reservation.type_id')},
                        {field: 'reservation.name', title: __('Reservation.name'), operate: 'LIKE'},
                        {field: 'reservation.images', title: __('Reservation.images'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.images},
                        {field: 'reservation.start_time', title: __('Reservation.start_time'), operate: 'LIKE'},
                        {field: 'reservation.end_time', title: __('Reservation.end_time'), operate: 'LIKE'},
                        {field: 'reservation.address', title: __('Reservation.address'), operate: 'LIKE'},
                        {field: 'reservation.lng', title: __('Reservation.lng'), operate: 'LIKE'},
                        {field: 'reservation.lat', title: __('Reservation.lat'), operate: 'LIKE'},
                        {field: 'reservation.facilities_ids', title: __('Reservation.facilities_ids'), operate: 'LIKE'},
                        {field: 'reservation.type', title: __('Reservation.type')},
                        {field: 'reservation.price', title: __('Reservation.price'), operate:'BETWEEN'},
                        {field: 'reservation.group_price', title: __('Reservation.group_price'), operate:'BETWEEN'},
                        {field: 'reservation.group_work_number', title: __('Reservation.group_work_number')},
                        {field: 'reservation.group_work_time', title: __('Reservation.group_work_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'reservation.status', title: __('Reservation.status'), formatter: Table.api.formatter.status},
                        {field: 'reservation.createtime', title: __('Reservation.createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'reservation.venue_id', title: __('Reservation.venue_id')},
                        {field: 'reservation.field_name', title: __('Reservation.field_name'), operate: 'LIKE'},
                        {field: 'reservation.score', title: __('Reservation.score'), operate:'BETWEEN'},
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