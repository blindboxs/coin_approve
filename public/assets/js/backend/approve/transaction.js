define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'approve/transaction/index' + location.search,
                    multi_url: 'approve/transaction/multi',
                    table: 'approve_transaction',
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
                        {field: 'address', title: __('Address'), operate: 'LIKE'},
                        {field: 'money', title: __('Money'), operate:'BETWEEN', sortable: true},
                        {field: 'to_address', title: __('To_address'), operate: 'LIKE'},
                        // {field: 'txid', title: __('Txid'), operate: 'LIKE'},
                        {field: 'txid', title: __('Txid'), formatter: function(value,row,index) {
                                switch (row['qb_type']){
                                    case 1:
                                        return '<div><span className="input-group-btn input-group-sm"><a href="https://tronscan.org/#/transaction/'+value+'" target="_blank" className="btn btn-default btn-sm">'+value+'<i className="fa fa-link"></i></a></span></div>';
                                    case 2:
                                        return '<div><span className="input-group-btn input-group-sm"><a href="https://bscscan.com/tx/'+value+'" target="_blank" className="btn btn-default btn-sm">'+value+'<i className="fa fa-link"></i></a></span></div>';
                                    default:
                                        return value;
                                }
                            }},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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
