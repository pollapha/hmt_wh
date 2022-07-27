var header_Pick = function () {
    var menuName = "Pick_", fd = "Picking/" + menuName + "data.php";

    function init() {
        loadData();
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
        ajax(fd, {}, 1, function (json) {
            if (json.data.header.length > 0) {
                ele('create_ps').disable();
                ele('Pick_Date').disable();
                ele('PS_Number').disable();
                ele('form1').setValues(json.data.header[0]);
                ele('form2').setValues(json.data.header[0]);
                setTable('dataT1', json.data.body);
            }

        }, null,
            function (json) {
                //ele('find').callEvent("onItemClick", []);
            }, btn);

    };

    var cells =
        [
            {
                header: "Package Number",
                body:
                {
                    view: "form", scroll: false, id: $n('form3'), on:

                    {

                        "onSubmit": function (view, e) {

                            if (view.config.name == 'Package_Number') {

                                view.blur();
                                var obj1 = ele('form1').getValues();
                                var obj2 = ele('form2').getValues();
                                var obj3 = ele('form3').getValues();
                                var obj4 = { ...obj1, ...obj2, ...obj3 };

                                ajax(fd, obj4, 13, function (json) {
                                    loadData();

                                }, null,

                                    function (json) {

                                    });
                                webix.UIManager.setFocus(ele('Package_Number'));

                            }

                            else if (webix.UIManager.getNext(view).config.type == 'line') {

                                webix.UIManager.setFocus(webix.UIManager.getNext(webix.UIManager.getNext(view)));
                                view.disable();

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
                                        vw2("text", 'Package_Number_1', 'Package_Number', "Package Number", { width: 250 }),
                                        {
                                            rows: [
                                                {},
                                                {
                                                    cols: [
                                                        vw2('button', 'save_1', 'save', 'Save (บันทึก)', {
                                                            type: 'form',
                                                            width: 120,
                                                            on: {
                                                                onItemClick: function () {
                                                                    var obj1 = ele('form1').getValues();
                                                                    var obj2 = ele('form2').getValues();
                                                                    var obj3 = ele('form3').getValues();
                                                                    var obj4 = { ...obj1, ...obj2, ...obj3 };
                                                                    webix.confirm(
                                                                        {
                                                                            title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                            callback: function (res) {
                                                                                if (res) {
                                                                                    ajax(fd, obj4, 41, function (json) {
                                                                                        ele('create_ps').enable();
                                                                                        ele('Pick_Date').enable();
                                                                                        ele('PS_Number').enable();
                                                                                        ele('Pick_Date').setValue('');
                                                                                        ele('PS_Number').setValue('');
                                                                                        ele('Package_Number_1').setValue('');
                                                                                        ele('dataT1').clearAll();
                                                                                    }, null,
                                                                                        function (json) {
                                                                                            //ele('find').callEvent("onItemClick", []);
                                                                                        });
                                                                                }
                                                                            }
                                                                        });
                                                                }
                                                            }
                                                        }),
                                                    ]
                                                },
                                            ]
                                        },
                                        {},
                                        {}
                                    ]
                                },
                            ]
                        },
                    ]
                },
            },


            {
                header: "Package Number & Serial_Number",
                body: {
                    view: "form", scroll: false, id: $n('form4'), on:

                    {

                        "onSubmit": function (view, e) {

                            if (view.config.name == 'FG_Serial_Number') {

                                view.blur();
                                var obj1 = ele('form1').getValues();
                                var obj2 = ele('form2').getValues();
                                var obj3 = ele('form4').getValues();
                                var obj4 = { ...obj1, ...obj2, ...obj3 };

                                ajax(fd, obj4, 12, function (json) {
                                    loadData();

                                }, null,

                                    function (json) {

                                    });
                                webix.UIManager.setFocus(ele('Package_Number'));
                            }

                            else if (webix.UIManager.getNext(view).config.type == 'line') {

                                webix.UIManager.setFocus(webix.UIManager.getNext(webix.UIManager.getNext(view)));
                                view.disable();

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
                                        vw1("text", 'Package_Number', "Package Number", { width: 250 }),
                                        vw1("text", 'FG_Serial_Number', "Serial Number", { width: 250 }),
                                        {
                                            rows: [
                                                {},
                                                {
                                                    cols: [
                                                        vw1('button', 'save', 'Save (บันทึก)', {
                                                            type: 'form',
                                                            width: 120,
                                                            on: {
                                                                onItemClick: function () {
                                                                    var obj1 = ele('form1').getValues();
                                                                    var obj2 = ele('form2').getValues();
                                                                    var obj3 = ele('form4').getValues();
                                                                    var obj4 = { ...obj1, ...obj2, ...obj3 };
                                                                    webix.confirm(
                                                                        {
                                                                            title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                            callback: function (res) {
                                                                                if (res) {
                                                                                    ajax(fd, obj4, 41, function (json) {
                                                                                        ele('create_ps').enable();
                                                                                        ele('Pick_Date').enable();
                                                                                        ele('PS_Number').enable();
                                                                                        ele('Pick_Date').setValue('');
                                                                                        ele('PS_Number').setValue('');
                                                                                        ele('Package_Number').setValue('');
                                                                                        ele('FG_Serial_Number').setValue('');
                                                                                        ele('dataT1').clearAll();
                                                                                    }, null,
                                                                                        function (json) {

                                                                                        });
                                                                                }
                                                                            }
                                                                        });
                                                                }
                                                            }
                                                        }),
                                                    ]
                                                },
                                            ]
                                        },
                                        {},
                                        {}
                                    ]
                                },
                            ]
                        },
                    ]
                },
            },
        ]



    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_Pick",
        body:
        {
            id: "Pick_id",
            type: "clean",
            rows:
                [
                    {
                        view: "form", scroll: false, id: $n('form1'),
                        elements: [
                            {
                                rows: [
                                    {
                                        cols: [
                                            vw1("datepicker", 'Pick_Date', "Pick Date", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, required: false, width: 250 }),
                                            {
                                                rows: [
                                                    {},
                                                    vw1('button', 'create_ps', 'Create PS', {
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
                                                                                ajax(fd, obj, 11, function (json) {
                                                                                    loadData();
                                                                                }, null,
                                                                                    function (json) {

                                                                                    });
                                                                            }
                                                                            else {
                                                                                ele('create_ps').enable();
                                                                                ele('Pick_Date').enable();
                                                                                ele('PS_Number').enable();
                                                                            }
                                                                        }
                                                                    });
                                                            },
                                                        }
                                                    })
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
                        view: "form", scroll: false, id: $n('form2'), on:

                        {
                            "onSubmit": function (view, e) {
                                loadData();
                            },
                        },
                        elements: [
                            {
                                rows: [
                                    {
                                        cols: [
                                            vw1("text", 'PS_Number', "PS Number", { width: 250 }),
                                            {},
                                            {}
                                        ]
                                    },
                                ]
                            },
                        ]
                    },
                    { view: "tabview", cells: cells, multiview: { fitBiggest: true } },
                    {
                        padding: 3,
                        cols: [
                            {
                                view: "datatable", id: $n("dataT1"), navigation: true, select: "row", editaction: "custom",
                                resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                                threeState: true, rowLineHeight: 25, rowHeight: 25,
                                datatype: "json", headerRowHeight: 25, leftSplit: 2, editable: true,
                                columns: [
                                    { id: "NO", header: "No.", css: "rank", width: 50, sort: "int" },
                                    { id: "Package_Number", header: ["Package Number", { content: "textFilter" }], width: 150 },
                                    { id: "FG_Serial_Number", header: ["Serial Number", { content: "textFilter" }], width: 200 },
                                    { id: "Part_No", header: ["Part No.", { content: "textFilter" }], width: 140 },
                                    { id: "Part_Name", header: ["Part Name", { content: "textFilter" }], width: 220 },
                                    { id: "Qty", header: ["Qty", { content: "textFilter" }], width: 100 },
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