var header_ConfirmShipping = function () {
    var menuName = "ConfirmShipping_", fd = "Shipping/" + menuName + "data.php";

    function init() {

    };

    function ele(name) {
        return $$($n(name));
    };

    function $n(name) {
        return menuName + name;
    };

    function focus(name) {
        setTimeout(function () { ele(name).focus(); }, 100);
    };

    function setView(target, obj) {
        var key = Object.keys(obj);
        for (var i = 0, len = key.length; i < len; i++) {
            target[key[i]] = obj[key[i]];
        }
        return target;
    };

    function vw1(view, name, label, obj) {
        var v = { view: view, required: true, label: label, id: $n(name), name: name, labelPosition: "top" };
        return setView(v, obj);
    };

    function vw2(view, id, name, label, obj) {
        var v = { view: view, required: true, label: label, id: $n(id), name: name, labelPosition: "top" };
        return setView(v, obj);
    };

    function setTable(tableName, data) {
        if (!ele(tableName)) return;
        ele(tableName).clearAll();
        ele(tableName).parse(data);
        ele(tableName).filterByAll();
    };

    function loadData(btn) {
        var obj = ele('form1').getValues();
        ajax(fd, obj, 1, function (json) {
            if (json.data.header.length > 0) {
                ele('GTN_Number').disable();

                ele('form1').setValues(json.data.header[0]);
                setTable('dataT1', json.data.body);
            }
        }, btn);
    };

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_ConfirmShipping",
        body:
        {
            id: "ConfirmShipping_id",
            type: "clean",
            rows:
                [
                    {
                        view: "form", scroll: false, id: $n('form1'),
                        on:

                        {

                            "onSubmit": function (view, e) {

                                if (view.config.name == 'FG_Serial_Number') {

                                    view.blur();
                                    var obj = ele('form1').getValues();
                                    ajax(fd, obj, 11, function (json) {
                                        loadData();
                                        webix.UIManager.setFocus(ele('Package_Number'));
                                        ele('Package_Number').setValue('');
                                        ele('FG_Serial_Number').setValue('');

                                    }, null,

                                        function (json) {
                                            webix.UIManager.setFocus(ele('Package_Number'));
                                            ele('Package_Number').setValue('');
                                            ele('FG_Serial_Number').setValue('');
                                        });

                                }
                                else if (webix.UIManager.getNext(view).config.type == 'line') {

                                    //webix.UIManager.setFocus(webix.UIManager.getNext(webix.UIManager.getNext(view)));
                                    //view.disable();
                                    webix.UIManager.setFocus(ele('Package_Number'));

                                }

                                else {

                                    webix.UIManager.setFocus(ele('Package_Number'));
                                }


                                if (view.config.name == 'GTN_Number') {

                                    view.blur();
                                    var obj = ele('form1').getValues();
                                    console.log(obj);
                                    ajax(fd, obj, 2, function (json) {
                                        setTable('dataT1', json.data);
                                        webix.UIManager.setFocus(ele('Package_Number'));
                                        ele('GTN_Number').disable();

                                    }, null,

                                        function (json) {
                                            ele('GTN_Number').enable();
                                            ele('GTN_Number').setValue('');
                                            webix.UIManager.setFocus(ele('GTN_Number'));
                                        });


                                }

                                else if (webix.UIManager.getNext(view).config.type == 'line') {

                                    //webix.UIManager.setFocus(webix.UIManager.getNext(webix.UIManager.getNext(view)));
                                    //view.disable();
                                    webix.UIManager.setFocus(ele('FG_Serial_Number'));

                                }

                                else {

                                    webix.UIManager.setFocus(ele('FG_Serial_Number'));

                                }
                            },
                        },
                        elements: [
                            {
                                rows: [
                                    {
                                        cols: [
                                            vw1("text", 'GTN_Number', "GTN Number", {
                                                //required: true, suggest: fd + "?type=1", 
                                                width: 250
                                            },
                                            ),
                                            vw1("text", 'Package_Number', "Package Number", { width: 250 }),
                                            vw1("text", 'FG_Serial_Number', "Serial Number", { width: 250 }),

                                            {
                                                rows: [
                                                    {},
                                                    vw1('button', 'confirm', 'Confirm Shipping', {
                                                        type: 'form',
                                                        width: 150,
                                                        on: {
                                                            onItemClick: function () {
                                                                var obj = ele('form1').getValues();
                                                                webix.confirm(
                                                                    {
                                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                        callback: function (res) {
                                                                            if (res) {
                                                                                ajax(fd, obj, 41, function (json) {
                                                                                    ele('GTN_Number').enable();
                                                                                    ele('GTN_Number').setValue('');
                                                                                    setTable('dataT1', json.data);

                                                                                }, null,
                                                                                    function (json) {
                                                                                        //loadData();
                                                                                        //ele('GTN_Number').setValue('');
                                                                                        //ele('confirm').hide();
                                                                                        //ele('dataT1').clearAll();
                                                                                    });
                                                                            }
                                                                            ele('confirm').show();
                                                                        }
                                                                    });
                                                            }
                                                        }
                                                    }),
                                                ]
                                            },
                                            {}
                                        ],
                                    },

                                ]
                            },
                        ]
                    },
                    {
                        padding: 3,
                        cols: [
                            {
                                view: "datatable", id: $n("dataT1"), navigation: true, select: true, editaction: "custom",
                                resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                                threeState: true, rowLineHeight: 25, rowHeight: 25,
                                datatype: "json", headerRowHeight: 25, leftSplit: 2, editable: true,
                                scheme:
                                {
                                    $change: function (item) {
                                        if (item.Confirm_Shipping_DateTime == null && item.Status_Shipping == 'PENDING' && item.Ship_Number != null) {
                                            item.$css = { "background": "#afeac8", "font-weight": "bold" };
                                        }
                                    }
                                },
                                columns: [
                                    { id: "NO", header: "No.", css: "rank", width: 50, sort: "int" },
                                    { id: "Ship_Date", header: ["Ship Date", { content: "textFilter" }], width: 200 },
                                    //{ id: "DN_Number", header: ["DN Number", { content: "textFilter" }], width: 150 },
                                    { id: "Package_Number", header: ["Package Number", { content: "textFilter" }], width: 150 },
                                    { id: "FG_Serial_Number", header: ["Serial Number", { content: "textFilter" }], width: 200 },
                                    { id: "Qty", header: ["Qty", { content: "textFilter" }], width: 100 },
                                    //{ id: "Ship_Number", header: ["Ship_Number", { content: "textFilter" }], width: 100 },
                                    //{ id: "Status_Shipping", header: ["Status_Shipping", { content: "textFilter" }], width: 100 },
                                    { id: "Confirm_Shipping_DateTime", header: ["Confirm Shipping DateTime", { content: "textFilter" }], width: 200 },
                                ],
                                onClick:
                                {
                                },
                                on: {
                                    // "onEditorChange": function (id, value) {
                                    // }
                                    "onItemClick": function (id) {
                                        this.editRow(id);
                                    }
                                }
                            },
                        ]
                    },
                ], on:
            {
                onHide: function () {

                },
                onShow: function () {

                },
                onAddView: function () {
                    init();
                }
            }
        }
    };
};