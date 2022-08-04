var header_Receive = function () {
    var menuName = "Receive_", fd = "Receiving/" + menuName + "data.php";

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
        ajax(fd, {}, 4, function (json) {
            if (json.data.header.length > 0) {
                ele('create_grn').disable();
                ele('DN_Number').disable();
                ele('GRN_Number').disable();
                ele('form1').setValues(json.data.header[0]);
                ele('form2').setValues(json.data.header[0]);
                setTable('dataT1', json.data.body);
            }

        }, null,
            function (json) {
                //ele('find').callEvent("onItemClick", []);
            }, btn);

    };



    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_Receive",
        body:
        {
            id: "Receive_id",
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
                                            vw1("text", 'DN_Number', "DN Number", {
                                                required: true, suggest: fd + "?type=1", width: 250
                                            }),
                                            {
                                                rows: [
                                                    {},
                                                    vw1('button', 'create_grn', 'Create GRN', {
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
                                                                                        //ele('find').callEvent("onItemClick", []);
                                                                                    });
                                                                            }
                                                                            else {
                                                                                ele('create_grn').enable();
                                                                                ele('DN_Number').enable();
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

                                if (view.config.name == 'FG_Serial_Number') {

                                    view.blur();
                                    var obj1 = ele('form1').getValues();
                                    var obj2 = ele('form2').getValues();
                                    var obj3 = { ...obj1, ...obj2 };

                                    ajax(fd, obj3, 12, function (json) {
                                        loadData();
                                        ele('Package_Number').setValue('');
                                        ele('FG_Serial_Number').setValue('');
                                        webix.UIManager.setFocus(ele('Package_Number'));

                                    }, null,

                                        function (json) {
                                            ele('Package_Number').setValue('');
                                            ele('FG_Serial_Number').setValue('');
                                            webix.UIManager.setFocus(ele('Package_Number'));
                                        });


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
                                            vw1("text", 'GRN_Number', "GRN Number", { width: 250 }),
                                            vw2("text", 'Package_Number', 'Package_Number', "Package Number", { width: 250 }),
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
                                                                        var obj3 = { ...obj1, ...obj2 };
                                                                        webix.confirm(
                                                                            {
                                                                                title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                                callback: function (res) {
                                                                                    if (res) {
                                                                                        ajax(fd, obj3, 41, function (json) {
                                                                                            setTable('dataT1', json.data);
                                                                                            ele('create_grn').enable();
                                                                                            ele('DN_Number').enable();
                                                                                            ele('GRN_Number').enable();
                                                                                            ele('DN_Number').setValue('');
                                                                                            ele('GRN_Number').setValue('');
                                                                                            ele('Package_Number').setValue('');
                                                                                            ele('FG_Serial_Number').setValue('');
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
                    {
                        padding: 3,
                        cols: [
                            {
                                view: "datatable", id: $n("dataT1"), navigation: true, select: "row", editaction: "custom",
                                resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                                threeState: true, rowLineHeight: 25, rowHeight: 25,
                                datatype: "json", headerRowHeight: 25, leftSplit: 2, editable: true,
                                scheme:
                                {
                                    $change: function (obj) {
                                        var css = {};
                                        obj.$cellCss = css;
                                    }
                                },
                                columns: [
                                    {
                                        id: $n("icon_cancel"), header: "&nbsp;", width: 40, template: function (row) {
                                            return "<span style='cursor:pointer' class='webix_icon fa-trash'></span>";
                                        }
                                    },
                                    { id: "NO", header: "No.", css: "rank", width: 50, sort: "int" },
                                    { id: "DN_Number", header: ["DN Number", { content: "textFilter" }], width: 140 },
                                    { id: "Package_Number", header: ["Package Number", { content: "textFilter" }], width: 150 },
                                    { id: "FG_Serial_Number", header: ["Serial Number", { content: "textFilter" }], width: 200 },
                                    { id: "Part_No", header: ["Part No.", { content: "textFilter" }], width: 140 },
                                    { id: "Part_Name", header: ["Part Name", { content: "textFilter" }], width: 220 },
                                    { id: "Qty", header: ["Qty", { content: "textFilter" }], width: 100 },
                                ],
                                onClick:
                                {
                                    "fa-trash": function (e, t) {
                                        var row = this.getItem(t), datatable = this;
                                        var obj = row.GRN_Number.concat("/", row.FG_Serial_Number);
                                        console.log('obj : ', obj);
                                        msBox('ลบ', function () {
                                            ajax(fd, obj, 31, function (json) {
                                                loadData();
                                                //webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ลบสำเร็จ', callback: function () { } });

                                            }, null,
                                                function (json) {
                                                });
                                        }, row);
                                    },
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