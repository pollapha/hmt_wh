var header_LocationMaster = function () {
    var menuName = "LocationMaster_", fd = "MasterData/" + menuName + "data.php";

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
            setTable('dataT1', json.data);
        }, null,
            function (json) {
                //ele('find').callEvent("onItemClick", []);
            }, btn);

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
                saveAs(e.data, 'location_master' + dayjs().format('YYYYMMDDHHmmss') + ".xlsx");
                btn.enable();
                webix.message({ expire: 7000, text: "Export สำเร็จ" });
            }, false);
            worker.postMessage({ 'cmd': 'start', 'msg': data });
        }
        else { webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: "ไม่พบข้อมูลในตาราง", callback: function () { } }); }
    };

    //add
    webix.ui(
        {
            view: "window", id: $n("win_add"), modal: 1,
            head: "Add (เพิ่มข้อมูล)", top: 50, position: "center", css: "webix_win_head",
            close: true, move: true,
            body:
            {
                view: "form", scroll: false, id: $n("win_add_form"), width: 570,
                elements:
                    [
                        {
                            cols:
                                [
                                    {
                                        paddingX: 20,
                                        paddingY: 10,
                                        rows:
                                            [
                                                {
                                                    cols: [
                                                        vw1('text', 'location_code', 'Location Code', {}),
                                                        //vw1('text', 'location_area', 'Area', {}),
                                                        vw1('richselect', 'location_area', 'Area', {
                                                            options: ['receiving', 'storage', 'overflow', 'wip', 'scrap', 'picking', 'truck-sim']
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
                                    vw1('button', 'btn_save_add', 'Save', {
                                        width: 120, css: "webix_green",
                                        icon: "mdi mdi-content-save", type: "icon",
                                        tooltip: { template: "บันทึก", dx: 10, dy: 15 },
                                        on: {
                                            onItemClick: function () {
                                                var obj = ele('win_add_form').getValues();
                                                webix.confirm(
                                                    {
                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                        callback: function (res) {
                                                            if (res) {
                                                                ajax(fd, obj, 11, function (json) {
                                                                    loadData();
                                                                    ele('win_add').hide();
                                                                    ele('win_add_form').setValues('');
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
                                    vw1('button', 'btn_cancel_add', 'Cancel', {
                                        width: 120, css: "webix_red",
                                        icon: "mdi mdi-cancel", type: "icon",
                                        tooltip: { template: "ยกเลิก", dx: 10, dy: 15 },
                                        on: {
                                            onItemClick: function () {
                                                ele('win_add').hide();
                                                ele('win_add_form').setValues('');
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
            close: true, move: true,
            body:
            {
                view: "form", scroll: false, id: $n("win_edit_form"), width: 600,
                elements:
                    [
                        {
                            cols:
                                [
                                    {
                                        paddingX: 20,
                                        paddingY: 10,
                                        rows:
                                            [
                                                {
                                                    cols: [
                                                        vw1('text', 'location_id', 'location_id', { labelPosition: "top", hidden: 1 }),
                                                        vw2('text', 'location_code_edit', 'location_code', 'Location Code', {}),
                                                        vw2('richselect', 'location_area_edit', 'location_area', 'Area', {
                                                            options: ['receiving', 'storage', 'overflow', 'wip', 'scrap', 'picking', 'truck-sim']
                                                        }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('richselect', 'status_edit', 'status', 'Status', {
                                                            labelPosition: "top",
                                                            value: 'Active', options: [
                                                                { id: 'Active', value: "Active" },
                                                                { id: 'Inactive', value: "Inactive" },
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
                                    vw1('button', 'btn_save_edit', 'Save', {
                                        width: 120, css: "webix_green",
                                        icon: "mdi mdi-content-save", type: "icon",
                                        tooltip: { template: "บันทึก", dx: 10, dy: 15 },
                                        on: {
                                            onItemClick: function () {
                                                var obj = ele('win_edit_form').getValues();
                                                webix.confirm(
                                                    {
                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                        callback: function (res) {
                                                            if (res) {
                                                                ajax(fd, obj, 21, function (json) {
                                                                    ele('win_edit').hide();
                                                                    loadData();
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

                                    vw1('button', 'btn_cancel_edit', 'Cancel', {
                                        width: 120, css: "webix_red",
                                        icon: "mdi mdi-cancel", type: "icon",
                                        tooltip: { template: "ยกเลิก", dx: 10, dy: 15 },
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
        id: "header_LocationMaster",
        body:
        {
            id: "LocationMaster_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Location Master", type: "header" },
                    {
                        view: "form", scroll: false, id: $n('form1'),
                        elements: [
                            {
                                cols:
                                    [
                                        vw1('button', 'btn_add', 'Add', {
                                            width: 120, css: "webix_blue",
                                            icon: "mdi mdi-plus-circle", type: "icon",
                                            tooltip: { template: "เพิ่มข้อมูล", dx: 10, dy: 15 },
                                            on: {
                                                onItemClick: function () {
                                                    ele('win_add').show();
                                                }
                                            }
                                        }),
                                        {},
                                        vw1('button', 'btn_export_template', 'Template Upload', {
                                            width: 150, css: "webix_secondary",
                                            icon: "mdi mdi-download", type: "icon",
                                            tooltip: { template: "ตัวอย่างเทมเพลตที่ใช้ในการอัพโหลด", dx: 10, dy: 15 },
                                            on: {
                                                onItemClick: function () {
                                                    window.location.href = 'MasterData/template_upload/template_upload_location_master.xlsx';
                                                }
                                            },
                                        }),
                                        vw1("uploader", 'btn_upload_file', "Upload Data", {
                                            width: 120, css: "webix_blue",
                                            icon: "mdi mdi-upload", type: "icon",
                                            tooltip: { template: "ดาวน์โหลดข้อมูล", dx: 10, dy: 15 },
                                            on:
                                            {
                                                onBeforeFileAdd: function (file) {
                                                    var type = file.type.toLowerCase();
                                                    if (type == "csv" || type == "xlsx" || type == "xls") {

                                                    }
                                                    else {
                                                        webix.alert({ title: "<b>ข้อความจากระบบ</b>", text: "รองรับ CSV ,XLS ,XLSX เท่านั้น", type: 'alert-error' });
                                                        return false;
                                                    }
                                                    //ele("btn_upload_file").disable();
                                                },
                                                onAfterFileAdd: function (item) {
                                                    var formData = new FormData();
                                                    this.files.data.each(function (obj, i) {
                                                        formData.append("upload", obj.file);
                                                    });
                                                    $.ajax({
                                                        type: 'POST',
                                                        cache: false,
                                                        contentType: false,
                                                        processData: false,
                                                        url: fd + '?type=41',
                                                        data: formData,
                                                        success: function (data) {
                                                            //ele("btn_upload_file").enable();
                                                            loadData();
                                                            var json = JSON.parse(data);
                                                            webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.mms, callback: function () { } });
                                                        }
                                                    });
                                                },
                                            },
                                        }),
                                        vw1('button', 'btn_export', 'Export Data', {
                                            width: 120, css: "webix_orange",
                                            icon: "mdi mdi-table-arrow-down", type: "icon",
                                            tooltip: { template: "ดาวน์โหลดข้อมูล", dx: 10, dy: 15 },
                                            on:
                                            {
                                                onItemClick: function () {
                                                    exportExcel(this);
                                                    // exportExcelHistory(this);
                                                }
                                            },
                                        }),
                                        {},
                                        vw1('button', 'btn_find', 'Find', {
                                            width: 120, css: "webix_primary",
                                            icon: "mdi mdi-magnify", type: "icon",
                                            tooltip: { template: "ค้นหา", dx: 10, dy: 15 },
                                            on: {
                                                onItemClick: function (id, e) {
                                                    loadData();
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
                                        datatype: "json", headerRowHeight: 25, leftSplit: 5, editable: true,
                                        //pager: $n("Master_pagerA"),
                                        scheme:
                                        {
                                            $change: function (obj) {
                                                var css = {};
                                                obj.$cellCss = css;
                                            }
                                        },
                                        columns: [
                                            {
                                                id: "icon_edit", header: "&nbsp;", width: 50, template: function (row) {
                                                    return "<span style='cursor:pointer; font-size:16px;' class='mdi mdi-pencil'></span>";
                                                    //return "<button class='mdi mdi-pencil webix_button' style='width:25px; height:20px; color:#ffffff; background-color: #68A4C4;'></button>";
                                                }
                                            },
                                            { id: "NO", header: "No.", css: "rank", width: 50, sort: "int" },
                                            { id: "location_code", header: [{ text: "Location Code", css: { "text-align": "center" } }, { content: "textFilter" }], width: 180, css: { "text-align": "center" }, },
                                            { id: "location_area", header: [{ text: "Area", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                            { id: "status", header: [{ text: "Status", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "created_at", header: [{ text: "Created at", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "created_by", header: [{ text: "Created by", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "updated_at", header: [{ text: "Updated at", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "updated_by", header: [{ text: "Updated by", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                        ],
                                        onClick:
                                        {
                                            "mdi-pencil": function (e, t) {
                                                ele('win_edit').show();
                                                var row = this.getItem(t);
                                                ele('win_edit_form').setValues(row);
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
                            // {
                            //     type: "wide",
                            //     cols:
                            //         [
                            //             {},
                            //             {
                            //                 view: "pager", id: $n("Master_pagerA"),
                            //                 template: function (data, common) {
                            //                     var start = data.page * data.size
                            //                         , end = start + data.size;
                            //                     if (data.count == 0) start = 0;
                            //                     else start += 1;
                            //                     if (end >= data.count) end = data.count;
                            //                     var html = "<b>showing " + (start) + " - " + end + " total " + data.count + " </b>";
                            //                     return common.first() + common.prev() + " " + html + " " + common.next() + common.last();
                            //                 },
                            //                 size: 50,
                            //                 group: 5
                            //             },
                            //             {}
                            //         ]
                            // }
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