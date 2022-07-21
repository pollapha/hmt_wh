var header_Ship = function () {
    var menuName = "Ship_", fd = "Shipping/" + menuName + "data.php";

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
                ele('create_gtn').disable();
                ele('Ship_Date').disable();
                ele('GTN_Number').disable();
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
        id: "header_Ship",
        body:
        {
            id: "Ship_id",
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
                                            vw1("datepicker", 'Ship_Date', "Ship Date", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, required: false, width: 250 }),
                                            {
                                                rows: [
                                                    {},
                                                    vw1('button', 'create_gtn', 'Create GTN', {
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
                                                                                    //console.log(json.data);
                                                                                }, null,
                                                                                    function (json) {
                                                                                        //ele('find').callEvent("onItemClick", []);
                                                                                    });
                                                                            }
                                                                            else {
                                                                                ele('create_gtn').enable();
                                                                                ele('Ship_Date').enable();
                                                                                ele('GTN_Number').enable();
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
                                    console.log(obj2);

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
                                            vw1("text", 'GTN_Number', "GTN Number", { width: 250 }),
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
                                                                        var obj3 = { ...obj1, ...obj2 };
                                                                        webix.confirm(
                                                                            {
                                                                                title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                                callback: function (res) {
                                                                                    if (res) {
                                                                                        ajax(fd, obj3, 41, function (json) {
                                                                                            ele('create_gtn').enable();
                                                                                            ele('Ship_Date').enable();
                                                                                            ele('GTN_Number').enable();
                                                                                            ele('Ship_Date').setValue('');
                                                                                            ele('GTN_Number').setValue('');
                                                                                            ele('Package_Number').setValue('');
                                                                                            ele('FG_Serial_Number').setValue('');
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
                    {
                        padding: 3,
                        cols: [
                            {
                                view: "datatable", id: $n("dataT1"), navigation: true, select: "row", editaction: "custom",
                                resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                                threeState: true, rowLineHeight: 25, rowHeight: 25,
                                datatype: "json", headerRowHeight: 25, leftSplit: 2, editable: true,
                                // scheme:
                                // {
                                //     $change: function (obj) {
                                //         var css = {};
                                //         obj.$cellCss = css;
                                //         if (obj.Pick_Qty != 0) {
                                //             obj.$css = { "background": "#ffffb2", "font-weight": "bold" };
                                //         }
                                //     }
                                // },
                                columns: [
                                    { id: "NO", header: "No.", css: "rank", width: 50, sort: "int" },
                                    { id: "Package_Number", header: ["Package Number", { content: "textFilter" }], width: 150 },
                                    { id: "FG_Serial_Number", header: ["Serial Number", { content: "textFilter" }], width: 200 },
                                    { id: "Part_No", header: ["Part No.", { content: "textFilter" }], width: 140 },
                                    { id: "Part_Name", header: ["Part Name", { content: "textFilter" }], width: 220 },
                                    { id: "Qty", header: ["Qty", { content: "textFilter" }], width: 100 },
                                ],
                                // columns: [
                                //     { id: "NO", header: "No.", css: "rank", width: 50, sort: "int" },
                                //     { id: "Customer", header: ["Customer", { content: "textFilter" }], width: 100 },
                                //     { id: "Dock", header: ["Dock", { content: "textFilter" }], width: 100 },
                                //     { id: "Weld_On_No", header: ["Weld on No.", { content: "textFilter" }], width: 200 },
                                //     { id: "Delivery_DateTime", header: ["Delivery DateTime", { content: "textFilter" }], width: 150 },
                                //     { id: "MMTH_Part_No", header: ["Part No.", { content: "textFilter" }], width: 150 },
                                //     { id: "Part_No", header: ["Part No.", { content: "textFilter" }], width: 200 },
                                //     { id: "Part_Descri", header: ["Part Description", { content: "textFilter" }], width: 400 },
                                //     { id: "Qty", header: ["Qty", { content: "textFilter" }], width: 70 },
                                //     { id: "SNP", header: ["SNP", { content: "textFilter" }], width: 70 },
                                //     { id: "PS_No", header: ["PS No.", { content: "textFilter" }], width: 150 },
                                //     { id: "Package_Type", header: ["Package Type", { content: "textFilter" }], width: 120 },
                                //     { id: "Pick_Qty", header: ["Pick Qty", { content: "textFilter" }], width: 100 },
                                //     { id: "Pick_Status", header: ["Pick Status", { content: "textFilter" }], width: 150 },
                                //     { id: "Ship_Qty", header: ["Ship Qty", { content: "textFilter" }], width: 100 },
                                //     { id: "Ship_Status", header: ["Ship Status", { content: "textFilter" }], width: 120 },
                                //     { id: "Slide_Status", header: ["Slide Status", { content: "textFilter" }], width: 120 },
                                // ],
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