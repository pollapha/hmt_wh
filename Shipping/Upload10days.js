var header_Upload10days = function () {
    var menuName = "Upload10days_", fd = "Shipping/" + menuName + "data.php";

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
                                                        vw1('text', 'DN_ID', 'DN ID', { labelPosition: "top", hidden: 1 }),
                                                        vw2("datepicker", 'Header_DateTime_edit', 'Header_DateTime', "Header DateTime", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, required: false }),
                                                        vw2('text', 'DN_Number_edit', 'DN_Number', 'DN Number', { labelPosition: "top" }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'DN_Date_Text_edit', 'DN_Date_Text', 'DN Date Text', { labelPosition: "top" }),
                                                        vw2('text', 'Package_Number', 'Package_Number', 'Package Number', { labelPosition: "top", required: false }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'FG_Serial_Number_edit', 'FG_Serial_Number', 'Serial Number', { labelPosition: "top" }),
                                                        vw2('text', 'FG_Date_Text_edit', 'FG_Date_Text', 'FG Date Text', { labelPosition: "top" }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'Part_No_edit', 'Part_No', 'Part_No', { labelPosition: "top", required: false }),
                                                        vw2('richselect', 'Receive_Status_edit', 'Receive_Status', 'Receive Status', {
                                                            labelPosition: "top",
                                                            value: 'Y', options: [
                                                                { id: 'Y', value: "Yes" },
                                                                { id: 'N', value: "No" },
                                                            ]
                                                        }),
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
                                    vw1('button', 'edit', 'Save', {
                                        type: 'form', width: 100,
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

                                    vw1('button', 'cancel_edit', 'Cancel', {
                                        type: 'danger', width: 100,
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
        id: "header_Upload10days",
        body:
        {
            id: "Upload10days_id",
            type: "clean",
            rows:
                [
                    {
                        view: "form", paddingY: 0, scroll: false, id: $n('form1'),
                        elements: [
                            {
                                cols:
                                    [
                                        vw1('button', 'find', 'Find (ค้นหา)', {
                                            width: 150,
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
                                        vw1("uploader", 'Upload', "Upload 10 Days", {
                                            link: "mytemplate", autosend: false,
                                            width: 150, hidden: false, multiple: false, on:
                                            {
                                                onBeforeFileAdd: function (file) {
                                                    var type = file.type.toLowerCase();
                                                    if (type == "xlsx") {
                                                        //ele("Upload_DN").disable();
                                                        ele("save_file").show();
                                                    }
                                                    else {
                                                        webix.alert({ title: "<b>ข้อความจากระบบ</b>", text: "รองรับ TXT เท่านั้น", type: 'alert-error' });
                                                        return false;
                                                    }

                                                },
                                            },
                                        }),
                                        {
                                            view: "list",
                                            id: "mytemplate",
                                            type: "uploader",
                                            autoheight: true,
                                            borderless: true,
                                        },

                                        vw1("button", 'save_file', "Save files (บันทึกไฟล์)", {
                                            hidden: 1,
                                            type: 'form', width: 200,
                                            click: function () {
                                                ele("Upload").files.data.each(function (obj, index) {
                                                    var formData = new FormData();
                                                    formData.append("upload", obj.file);
                                                    if ($$("mytemplate") == null) {
                                                        ele("save_file").hide();
                                                    }
                                                    $.ajax({
                                                        type: 'POST',
                                                        cache: false,
                                                        contentType: false,
                                                        processData: false,
                                                        url: fd + '?type=41',
                                                        data: formData,
                                                        success: function (data) {
                                                            webix.confirm(
                                                                {
                                                                    title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                    callback: function (res) {
                                                                        if (res) {
                                                                            // ajax(fd, {}, 1, function (json) {
                                                                            //     //setTable('dataT1', json.data);
                                                                            // }, null,
                                                                            //     function (json) {
                                                                            //     });
                                                                        }
                                                                        var json = JSON.parse(data);
                                                                        ele("Upload").files.data.clearAll();
                                                                        webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.mms, callback: function () { } });
                                                                        ele("Upload").enable();
                                                                        ele("save_file").hide();
                                                                    }
                                                                });



                                                        }
                                                    });
                                                });
                                            }
                                        }),
                                        // vw1("button", 'btnExport', "Export (โหลดเป็นไฟล์เอ๊กเซล)", {
                                        //     width: 200, on:
                                        //     {
                                        //         onItemClick: function () {
                                        //             exportExcel(this);
                                        //         }
                                        //     }
                                        // })
                                    ]
                            },
                            {
                                cols: [
                                    {
                                        view: "datatable", id: $n("dataT1"), navigation: true, select: "row", editaction: "custom",
                                        resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                                        threeState: true, rowLineHeight: 25, rowHeight: 25,
                                        datatype: "json", headerRowHeight: 25, leftSplit: 4, editable: true,
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
                                            { id: "Customer", header: ["Customer", { content: "textFilter" }], width: 200 },
                                            { id: "Dock", header: ["Dock", { content: "textFilter" }], width: 150 },
                                            { id: "Sale_Part", header: ["Sale_Part", { content: "textFilter" }], width: 200 },
                                            { id: "Delivery_Date", header: ["Delivery_Date", { content: "textFilter" }], width: 200 },
                                            { id: "Bin_No", header: ["Bin_No", { content: "textFilter" }], width: 200 },
                                            { id: "Qty", header: ["Qty", { content: "textFilter" }], width: 200 },
                                            { id: "Order_No", header: ["Order_No", { content: "textFilter" }], width: 200 },
                                            { id: "Attri_Info", header: ["Attri_Info", { content: "textFilter" }], width: 200 },
                                            { id: "Plan_Delivery_Date", header: ["Plan_Delivery_Date", { content: "textFilter" }], width: 200 },
                                            { id: "Plan_Bin_No", header: ["Plan_Bin_No", { content: "textFilter" }], width: 200 },
                                            { id: "Part_No", header: ["Part_No", { content: "textFilter" }], width: 200 },
                                            { id: "KanbanID", header: ["KanbanID", { content: "textFilter" }], width: 200 },
                                            { id: "SNP", header: ["SNP", { content: "textFilter" }], width: 200 },
                                            { id: "Box_Type", header: ["Box_Type", { content: "textFilter" }], width: 200 },
                                            { id: "Part_Name", header: ["Part_Name", { content: "textFilter" }], width: 200 },
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