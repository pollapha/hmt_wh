var header_ConfirmDelivery = function () {
    var menuName = "ConfirmDelivery_", fd = "InventoryManagement/" + menuName + "data.php";

    function init() {
        webix.UIManager.setFocus(ele('GTN_Number'));
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
            setTable('dataT1', json.data);
            webix.UIManager.setFocus(ele('GTN_Number'));
            console.log('1');

        }, null,

            function (json) {
                ele('GTN_Number').enable();
                ele('GTN_Number').setValue('');
                console.log(webix.UIManager.setFocus(ele('GTN_Number')));
                console.log('2');
            }, btn);
    };

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_ConfirmDelivery",
        body:
        {
            id: "ConfirmDelivery_id",
            type: "clean",
            rows:
                [
                    {
                        view: "form", scroll: false, id: $n('form1'),
                        on:

                        {

                            "onSubmit": function (view, e) {

                                if (view.config.name == 'GTN_Number') {

                                    view.blur();
                                    loadData();

                                }

                                else if (webix.UIManager.getNext(view).config.type == 'line') {

                                    webix.UIManager.setFocus(webix.UIManager.getNext(webix.UIManager.getNext(view))); 

                                }

                                else {

                                    webix.UIManager.setFocus(webix.UIManager.getNext(view));

                                }
                            },
                        },
                        elements: [
                            {
                                rows: [
                                    {
                                        cols: [
                                            vw1("text", 'GTN_Number', "GTN Number", { width: 250 },
                                            ),
                                            {
                                                rows: [
                                                    {},
                                                    vw1('button', 'confirm', 'Confirm', {
                                                        type: 'form',
                                                        width: 100,
                                                        on: {
                                                            onItemClick: function () {
                                                                var obj = ele('form1').getValues();
                                                                webix.confirm(
                                                                    {
                                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                        callback: function (res) {
                                                                            if (res) {
                                                                                ajax(fd, obj, 41, function (json) {
                                                                                    setTable('dataT1', json.data);
                                                                                }, null,
                                                                                    function (json) {
                                                                                        ele('GTN_Number').enable();
                                                                                        ele('GTN_Number').setValue('');
                                                                                        setTable('dataT1', json.data).clearAll();
                                                                                    });
                                                                            }
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
                                columns: [
                                    { id: "NO", header: "No.", css: "rank", width: 50, sort: "int" },
                                    { id: "Ship_Date", header: ["Ship Date", { content: "textFilter" }], width: 200 },
                                    { id: "Package_Number", header: ["Package Number", { content: "textFilter" }], width: 150 },
                                    { id: "FG_Serial_Number", header: ["Serial Number", { content: "textFilter" }], width: 200 },
                                    { id: "Qty", header: ["Qty", { content: "textFilter" }], width: 100 },
                                    { id: "Status_Shipping", header: ["Status_Shipping", { content: "textFilter" }], width: 200 },
                                    { id: "Confirm_Delivery_DateTime", header: ["Confirm Delivery Date", { content: "textFilter" }], width: 220 },
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