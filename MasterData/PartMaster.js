var header_PartMaster = function () {
    var menuName = "PartMaster_", fd = "MasterData/" + menuName + "data.php";

    function init() {
        loadData();
        reload_options_supplier();
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
            // var dataT1 = ele('dataT1');
            // console.log(dataT1.getItem());
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
                saveAs(e.data, 'part_master' + dayjs().format('YYYYMMDDHHmmss') + ".xlsx");
                btn.enable();
                webix.message({ expire: 7000, text: "Export สำเร็จ" });
            }, false);
            worker.postMessage({ 'cmd': 'start', 'msg': data });
        }
        else { webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: "ไม่พบข้อมูลในตาราง", callback: function () { } }); }
    };

    function reload_options_supplier() {
        var supplierList = ele("supplier_code").getPopup().getList();
        supplierList.clearAll();
        supplierList.load("common/supplierMaster.php?type=2");
    };

    function reload_options_supplier_edit() {
        var supplierList = ele("supplier_code_edit").getPopup().getList();
        supplierList.clearAll();
        supplierList.load("common/supplierMaster.php?type=2");
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
                                                        vw1('text', 'alloy', 'Alloy', { required: true, }),
                                                        vw1('text', 'gauge', 'Gauge', { required: true, placeholder: '0.00' }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw1('text', 'part_no', 'Part No.', {}),
                                                        vw1('text', 'part_name', 'Part Description', {}),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw1('combo', 'supplier_code', 'Supplier', {
                                                            suggest: "common/supplierMaster.php?type=1", required: true,
                                                            on: {
                                                                onBlur: function () {
                                                                    this.getList().hide();
                                                                },
                                                                onItemClick: function () {
                                                                    reload_options_supplier();
                                                                }
                                                            },
                                                        }),
                                                        vw1('text', 'part_width', 'Part Width', { required: true, placeholder: '0.00' }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw1('richselect', 'package_type', 'Package Type', {
                                                            required: true,
                                                            value: '',
                                                            options: ['Steel', 'Wooden', '']
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
                                                        vw1('text', 'part_id', 'part_id', { hidden: 1 }),
                                                        vw2('text', 'alloy_edit', 'alloy', 'Alloy', { required: true, }),
                                                        vw2('text', 'gauge_edit', 'gauge', 'Gauge', { required: true, placeholder: '0.00' }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'part_no_edit', 'part_no', 'Part No.', {}),
                                                        vw2('text', 'part_name_edit', 'part_name', 'Part Description', {}),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'part_width_edit', 'part_width', 'Part Width', { required: false, placeholder: '0.00' }),
                                                        vw2('combo', 'supplier_code_edit', 'supplier_code', 'Supplier', {
                                                            suggest: "common/supplierMaster.php?type=1", required: false,
                                                            on: {
                                                                onItemClick: function () {
                                                                    reload_options_supplier_edit();
                                                                }
                                                            },
                                                        }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('richselect', 'package_type_edit', 'package_type', 'Package Type', {
                                                            required: false,
                                                            options: ['Steel', 'Wooden', '']
                                                        }),
                                                        vw2('richselect', 'status_edit', 'status', 'Status', {
                                                            value: 'Active',
                                                            options: ['Active', 'Inactive']
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
        id: "header_PartMaster",
        body:
        {
            id: "PartMaster_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Part Master", type: "header" },
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
                                                    window.location.href = 'MasterData/template_upload/template_upload_part_master.xlsx';
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
                                        datatype: "json", headerRowHeight: 25, leftSplit: 4, editable: true,
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
                                            { id: "alloy", header: [{ text: "Alloy", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "gauge", header: [{ text: "Gauge", css: { "text-align": "center" } }, { content: "numberFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "part_width", header: [{ text: "Part Width", css: { "text-align": "center" } }, { content: "numberFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, },
                                            { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 250, css: { "text-align": "center" }, },
                                            { id: "package_type", header: [{ text: "Package type", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "supplier_code", header: [{ text: "Supplier", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
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
                                                reload_options_supplier_edit();
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