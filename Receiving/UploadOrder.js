var header_UploadOrder = function () {
    var menuName = "UploadOrder_", fd = "Receiving/" + menuName + "data.php";

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

    function exportExcel(btn) {
        var dataT1 = ele("dataT1"), obj = {}, data = [];
        if (dataT1.count() == 0) {
            webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ไม่พบข้อมูลในตาราง', callback: function () { } });
        }

        for (var i = -1, len = dataT1.config.columns.length; ++i < len;) {
            obj[dataT1.config.columns[i].id] = dataT1.config.columns[i].header[0].text;
        }
        delete obj.icon_edit;
        var objKey = Object.keys(obj);
        var f = [];
        for (var i = -1, len = objKey.length; ++i < len;) {
            f.push(objKey[i]);
        }

        var col = [];
        for (var i = -1, len = f.length; ++i < len;) {
            col[col.length] = obj[f[i]];
        }
        data[data.length] = col;
        if (dataT1.count() > 0) {
            btn.disable();
            dataT1.eachRow(function (row) {
                var r = dataT1.getItem(row), rr = [];
                for (var i = -1, len = f.length; ++i < len;) {
                    rr[rr.length] = r[f[i]];
                }
                data[data.length] = rr;
            });

            var worker = new Worker('js/workerToExcel.js?v=1');
            worker.addEventListener('message', function (e) {
                saveAs(e.data, 'ABT' + new Date().getTime() + ".xlsx");
                btn.enable();
                webix.message({ expire: 7000, text: "Export สำเร็จ" });
            }, false);
            worker.postMessage({ 'cmd': 'start', 'msg': data });
        }
        else 
        { webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: "ไม่พบข้อมูลในตาราง", callback: function () { } }); }
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
                                                        vw2("datepicker", 'Header_DateTime_edit', 'Header_DateTime', "Header_DateTime", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, required: false }),
                                                        vw2('text', 'DN_Number_edit', 'DN_Number', 'DN_Number', { labelPosition: "top" }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'DN_Date_Text_edit', 'DN_Date_Text', 'DN_Date_Text', { labelPosition: "top" }),
                                                        vw2('text', 'Package_Number', 'Package_Number', 'Package_Number', { labelPosition: "top", required: false }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'FG_Serial_Number_edit', 'FG_Serial_Number', 'FG_Serial_Number', { labelPosition: "top" }),
                                                        vw2('text', 'FG_Date_Text_edit', 'FG_Date_Text', 'FG_Date_Text', { labelPosition: "top" }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'Part_No_edit', 'Part_No', 'Part_No', { labelPosition: "top", required: false }),
                                                        vw2('richselect', 'Receive_Status_edit', 'Receive_Status', 'Receive_Status', {
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
        id: "header_UploadOrder",
        body:
        {
            id: "UploadOrder_id",
            type: "clean",
            rows:
                [
                    {
                        view: "form", scroll: false, id: $n('form1'),
                        elements: [
                            {
                                cols:
                                    [
                                        vw1("uploader", 'Upload_DN', "Upload DN", {
                                            link: "mytemplate", autosend: false,
                                            width: 150, hidden: false, multiple: false, on:
                                            {
                                                onBeforeFileAdd: function (file) {
                                                    var type = file.type.toLowerCase();
                                                    if (type == "txt") {
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

                                        vw1("button", 'save_file', "Save files", {
                                            hidden: 1,
                                            type: 'form', width: 150,
                                            click: function () {
                                                ele("Upload_DN").files.data.each(function (obj, index) {
                                                    var formData = new FormData();
                                                    formData.append("upload", obj.file);
                                                    if($$("mytemplate") == null){
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
                                                                            ajax(fd, {}, 1, function (json) {
                                                                                setTable('dataT1', json.data);
                                                                            }, null,
                                                                                function (json) {
                                                                                });
                                                                        }
                                                                        var json = JSON.parse(data);
                                                                        ele("Upload_DN").files.data.clearAll();
                                                                        webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.mms, callback: function () { } });
                                                                        ele("Upload_DN").enable();
                                                                        ele("save_file").hide();     
                                                                    }
                                                                });



                                                        }
                                                    });
                                                });
                                            }
                                        }),
                                        vw1('button', 'refresh', 'Refresh', {
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
                                        vw1("button", 'btnExport', "Export (โหลดเป็นไฟล์เอ๊กเซล)", {
                                            width: 200, on:
                                            {
                                                onItemClick: function () {
                                                    exportExcel(this);
                                                }
                                            }
                                        })
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
                                            { id: "Header_DateTime", header: ["Header DateTime", { content: "textFilter" }], width: 200 },
                                            { id: "DN_Number", header: ["DN Number", { content: "textFilter" }], width: 150 },
                                            { id: "DN_Date_Text", header: ["DN Date", { content: "textFilter" }], width: 100 },
                                            { id: "Package_Number", header: ["Package Number", { content: "textFilter" }], width: 150 },
                                            { id: "FG_Serial_Number", header: ["FG Serial Number", { content: "textFilter" }], width: 200 },
                                            { id: "FG_Date_Text", header: ["FG Date", { content: "textFilter" }], width: 200 },
                                            { id: "Part_No", header: ["Part No", { content: "textFilter" }], width: 150 },
                                            { id: "Receive_Status", header: ["Receive Status", { content: "textFilter" }], width: 100 },
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