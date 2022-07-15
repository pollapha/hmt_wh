var header_UploadWeldOn = function () {
    var menuName = "UploadWeldOn_", fd = "Shipping/" + menuName + "data.php";

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

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_UploadWeldOn",
        body:
        {
            id: "UploadWeldOn_id",
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
                                        vw1("uploader", 'Upload', "Upload Weld on", {
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
                                                                            ajax(fd, {}, 1, function (json) {
                                                                                setTable('dataT1', json.data);
                                                                            }, null,
                                                                                function (json) {
                                                                                });
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
                                        // vw1("button", 'delete', "Delete (ลบ)", {
                                        //     width: 150, type: 'danger', on:
                                        //     {
                                        //         onItemClick: function () {

                                        //             webix.confirm(
                                        //                 {
                                        //                     title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                        //                     callback: function (res) {
                                        //                         if (res) {
                                        //                             ajax(fd, {}, 31, function (json) {
                                        //                                 setTable('dataT1', json.data);
                                        //                             }, null,
                                        //                                 function (json) {
                                        //                                 });
                                        //                         }
                                        //                     }
                                        //                 });
                                        //         }
                                        //     }
                                        // })

                                    ]
                            },
                            {
                                cols: [
                                    {
                                        view: "treetable", id: $n('dataT1'), headerRowHeight: 20, rowLineHeight: 25, rowHeight: 25, resizeColumn: true, css: { "font-size": "13px" },
                                        editable: true,
                                        scheme:
                                        {
                                            $change: function (obj) {
                                                var css = {};
                                                obj.$cellCss = css;
                                            }
                                        },
                                        columns: [
                                            {
                                                id: "data22", header: "&nbsp;", width: 40,
                                                template: function (row) {
                                                    if (row.Is_Header == "YES") {
                                                        return "<span style='cursor:pointer' class='webix_icon fa-file-pdf-o'></span>";
                                                    }
                                                    else {
                                                        return '';
                                                    }
                                                }
                                            },
                                            {
                                                id: $n("icon_cancel"), header: "&nbsp;", width: 40, template: function (row) {
                                                    if (row.Is_Header == "YES") {
                                                        return "<span style='cursor:pointer' class='webix_icon fa-ban'></span>";
                                                    }
                                                    else {
                                                        return '';
                                                    }
                                                }
                                            },
                                            { id: "No", header: "", css: { "text-align": "right" }, editor: "", width: 40 },
                                            {
                                                id: "Weld_On_No", header: ["Weld on No.", { content: "textFilter" }], editor: "", width: 180,
                                                template: "{common.treetable()} #Weld_On_No#"
                                            },
                                            //{ id: "Weld_On_No", header: ["D-Note Number", { content: "textFilter" }], width: 200 },
                                            { id: "Delivery_DateTime", header: ["Delivery DateTime", { content: "textFilter" }], width: 150 },
                                            { id: "MMTH_Part_No", header: ["Part No.", { content: "textFilter" }], width: 150 },
                                            { id: "Part_Descri", header: ["Part Description", { content: "textFilter" }], width: 400 },
                                            { id: "Qty", header: ["Qty", { content: "textFilter" }], width: 70 },
                                            { id: "SNP", header: ["SNP", { content: "textFilter" }], width: 70 },
                                            // { id: "Pick_Qty", header: ["Pick Qty", { content: "textFilter" }], width: 100 },
                                            // { id: "Pick_Status", header: ["Pick Status", { content: "textFilter" }], width: 150 },
                                            // { id: "Ship_Qty", header: ["Ship Qty", { content: "textFilter" }], width: 100 },
                                            // { id: "Ship_Status", header: ["Ship Status", { content: "textFilter" }], width: 120 },
                                            // { id: "Slide_Status", header: ["Slide Status", { content: "textFilter" }], width: 120 },
                                            // { id: "KanbanID", header: ["Kanban ID", { content: "textFilter" }], width: 120 },
                                            // { id: "SNP", header: ["SNP", { content: "textFilter" }], width: 100 },
                                            // { id: "Box_Type", header: ["Box Type", { content: "textFilter" }], width: 120 },
                                        ],
                                        on: {
                                            // "onEditorChange": function (id, value) {
                                            // }
                                            "onItemClick": function (id) {
                                                this.editRow(id);
                                            }
                                        },
                                        onClick:
                                        {
                                            "fa-file-pdf-o": function (e, t) {
                                                var row = this.getItem(t);
                                                var data = row.Weld_On_No;
                                                window.open("print/doc/d-note.php?data=" + data, '_blank');
                                            },
                                            "fa-ban": function (e, t) {
                                                var row = this.getItem(t), datatable = this;
                                                var obj = row.Weld_On_No;
                                                console.log('obj : ', obj);
                                                msBox('ลบ', function () {
                                                    ajax(fd, obj, 31, function (json) {
                                                        setTable('dataT1', json.data);
                                                        webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ลบสำเร็จ', callback: function () { } });

                                                    }, null,
                                                        function (json) {
                                                        });
                                                }, row);
                                            },
                                        },
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