var header_PartMaster = function () {
    var menuName = "PartMaster_", fd = "MasterData/" + menuName + "data.php";

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

    //add
    webix.ui(
        {
            view: "window", id: $n("win_add"), modal: 1,
            head: "Add (เพิ่มข้อมูล)", top: 50, position: "center",
            body:
            {
                view: "form", scroll: false, id: $n("win_add_form"), width: 600,
                elements:
                    [
                        {
                            cols:
                                [
                                    {
                                        rows:
                                            [
                                                {
                                                    cols: [
                                                        vw1('text', 'Part_ID', 'Part ID.', { labelPosition: "top", hidden: 1 }),
                                                        vw1('text', 'Part_No', 'Part No.', { labelPosition: "top" }),
                                                        vw1('text', 'Part_Name', 'Part Name', { labelPosition: "top" }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw1('text', 'MMTH_Part_No', 'MMTH Part No.', { labelPosition: "top" }),
                                                        vw1('text', 'TAST_No', 'TAST No.', { labelPosition: "top", required: false }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw1('text', 'CBM_Per_Package', 'CBM Per Package', { labelPosition: "top" }),
                                                        vw1('text', 'Qty_Per_Package', 'Qty Per Package', { labelPosition: "top" }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw1('text', 'Weight_Package_Part', 'Weight Package Part', { labelPosition: "top", required: false }),
                                                        vw1('text', 'Specification', 'Specification', { labelPosition: "top", required: false }),

                                                    ],
                                                },


                                            ]
                                    }
                                ]
                        },
                        {
                            cols:
                                [
                                    {},
                                    vw1('button', 'save', 'Save (บันทึก)', {
                                        type: 'form', width: 120,
                                        on: {
                                            onItemClick: function () {
                                                console.log(ele('win_add_form').getValues());
                                                var obj = ele('win_add_form').getValues();
                                                webix.confirm(
                                                    {
                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                        callback: function (res) {
                                                            if (res) {
                                                                ajax(fd, obj, 11, function (json) {
                                                                    setTable('dataT1', json.data);
                                                                    console.log(setTable('dataT1', json.data));
                                                                    ele('win_add').hide();
                                                                }, null,
                                                                    function (json) {
                                                                        /* ele('find').callEvent("onItemClick", []); */
                                                                    });
                                                            }
                                                        }
                                                    });
                                            }
                                        }
                                    }),
                                    vw1('button', 'cancel', 'Cancel (ยกเลิก)', {
                                        type: 'danger', width: 120,
                                        on: {
                                            onItemClick: function () {
                                                ele('win_add').hide();
                                                ele('Part_No').setValue('');
                                                ele('Part_Name').setValue('');
                                                ele('MMTH_Part_No').setValue('');
                                                ele('CBM_Per_Package').setValue('');
                                                ele('Qty_Per_Package').setValue('');
                                                ele('Specification').setValue('');
                                                ele('Weight_Package_Part').setValue('');
                                                ele('TAST_No').setValue('');
                                            }
                                        }
                                    }),
                                ]
                        }
                    ],
                rules:
                {
                }
            }
        });


    //edit
    webix.ui(
        {
            view: "window", id: $n("win_edit"), modal: 1,
            head: "Edit (แก้ไขข้อมูล)", top: 50, position: "center",
            body:
            {
                view: "form", scroll: false, id: $n("win_edit_form"), width: 600,
                elements:
                    [
                        {
                            cols:
                                [
                                    {
                                        rows:
                                            [
                                                {
                                                    cols: [
                                                        vw2('text', 'Part_No_edit', 'Part_No', 'Part No.', { labelPosition: "top", disabled: true }),
                                                        vw2('text', 'Part_Name_edit', 'Part_Name', 'Part Name', { labelPosition: "top" }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'MMTH_Part_No_edit', 'MMTH_Part_No', 'MMTH Part No.', { labelPosition: "top" }),
                                                        vw2('text', 'TAST_No_edit', 'TAST_No', 'TAST No.', { labelPosition: "top", required: false }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'CBM_Per_Package_edit', 'CBM_Per_Package', 'CBM Per Package', { labelPosition: "top" }),
                                                        vw2('text', 'Qty_Per_Package_edit', 'Qty_Per_Package', 'Qty Per Package', { labelPosition: "top" }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'Weight_Package_Part_edit', 'Weight_Package_Part', 'Weight Package Part', { labelPosition: "top", required: false }),
                                                        vw2('text', 'Specification_edit', 'Specification', 'Specification', { labelPosition: "top", required: false }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('richselect', 'Active_edit', 'Active', 'Active', {
                                                            labelPosition: "top",
                                                            value: 'Y', options: [
                                                                { id: 'Y', value: "Yes" },
                                                                { id: 'N', value: "No" },
                                                            ]
                                                        }),
                                                        {}
                                                    ],
                                                },

                                            ]
                                    }
                                ]
                        },
                        {
                            cols:
                                [
                                    {},
                                    vw1('button', 'edit', 'Save (บันทึก)', {
                                        type: 'form', width: 120,
                                        on: {
                                            onItemClick: function () {
                                                var obj = ele('win_edit_form').getValues();
                                                console.log(obj);
                                                webix.confirm(
                                                    {
                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                        callback: function (res) {
                                                            if (res) {
                                                                ajax(fd, obj, 21, function (json) {
                                                                    ele('win_edit').hide();
                                                                    setTable('dataT1', json.data);
                                                                    console.log(setTable('dataT1', json.data));
                                                                }, null,
                                                                    function (json) {
                                                                        /* ele('find').callEvent("onItemClick", []); */
                                                                    });
                                                            }
                                                        }
                                                    });

                                            }
                                        }
                                    }),

                                    vw1('button', 'cancel_edit', 'Cancel (ยกเลิก)', {
                                        type: 'danger', width: 120,
                                        on: {
                                            onItemClick: function () {
                                                ele('win_edit').hide();
                                            }
                                        }
                                    }),
                                ]
                        }
                    ],
                rules:
                {
                }
            }
        });



    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_PartMaster",
        body:
        {
            id: "PartMaster_id",
            type: "clean",
            rows:
                [
                    {
                        view: "form", scroll: false, id: $n('form1'),
                        elements: [
                            {
                                cols:
                                    [
                                        vw1('button', 'add', 'Add (เพิ่มข้อมูล)', {
                                            on: {
                                                onItemClick: function () {
                                                    console.log(ele('win_add').show());
                                                }
                                            }
                                        }),
                                        vw1('button', 'find', 'Find (ค้นหา)', {
                                            on: {
                                                onItemClick: function (id, e) {
                                                    console.log(ele("form1").getValues());
                                                    var obj = ele('form1').getValues();

                                                    ajax(fd, obj, 1, function (json) {
                                                        //webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'บันทึกสำเร็จ', callback: function () { } });
                                                        setTable('dataT1', json.data);
                                                    }, null,
                                                        function (json) {
                                                            /* ele('find').callEvent("onItemClick", []); */
                                                        });
                                                }
                                            }
                                        }),
                                    ]
                            },
                            {
                                cols: [
                                    {
                                        view: "datatable", id: $n("dataT1"), navigation: true, select: "row", editaction: "custom",
                                        resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                                        threeState: true, rowLineHeight: 25, rowHeight: 25,
                                        datatype: "json", headerRowHeight: 25, leftSplit: 3, editable: true,
                                        scheme:
                                        {
                                            $change: function (obj) {
                                                var css = {};
                                                obj.$cellCss = css;
                                            }
                                        },
                                        columns: [
                                            {
                                                id: "icon_edit", header: "&nbsp;", width: 40, template: function (row) {
                                                    return "<span style='cursor:pointer' class='webix_icon fa-pencil'></span>";
                                                }
                                            },
                                            { id: "NO", header: "No.", css: "rank", width: 50, sort: "int" },
                                            { id: "Part_No", header: ["Part Number", { content: "textFilter" }], width: 150 },
                                            { id: "Part_Name", header: ["Part Name", { content: "textFilter" }], width: 240 },
                                            { id: "MMTH_Part_No", header: ["MMTH Part Number", { content: "textFilter" }], width: 180 },
                                            { id: "CBM_Per_Package", header: ["CBM/Package", { content: "textFilter" }], width: 140 },
                                            { id: "Qty_Per_Package", header: ["Qty/Package", { content: "textFilter" }], width: 140 },
                                            { id: "Specification", header: ["Specification", { content: "textFilter" }], width: 200 },
                                            { id: "Weight_Package_Part", header: ["Weight(Package+Part)", { content: "textFilter" }], width: 200 },
                                            { id: "TAST_No", header: ["TAST No.", { content: "textFilter" }], width: 140 },
                                            { id: "Active", header: ["Active", { content: "textFilter" }], width: 100 },
                                            { id: "Creation_Date", header: ["Creation Date", { content: "textFilter" }], width: 150 },

                                        ],
                                        onClick:
                                        {
                                            "fa-pencil": function (e, t) {
                                                console.log(ele('win_edit').show());
                                                var row = this.getItem(t);
                                                console.log(row);
                                                console.log(ele('win_edit_form').setValues(row));
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
                                ],
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