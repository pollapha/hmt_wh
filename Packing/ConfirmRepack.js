var header_ConfirmRepack = function () {
    var menuName = "ConfirmRepack_", fd = "Packing/" + menuName + "data.php";

    function init() {
        //loadData();
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

    function reload_options_workorder_no() {
        var documentList = ele("work_order_no").getPopup().getList();
        documentList.clearAll();
        documentList.load("common/workorderNo.php?type=1");
    };



    function loadDataByDocumentNo() {
        var obj = ele('form1').getValues();
        ajax(fd, obj, 1, function (json) {
            setTable('dataT1', json.data.body);
        });
    };

    function AddItem(row) {
        var obj = ele('form1').getValues();
        $.post(fd, { obj: obj, type: 11 })
            .done(function (data) {
                var json = JSON.parse(data);
                if (json.ch == 1) {
                    webix.message({
                        text: "Complete",
                        type: "success",
                        expire: 2000,
                    });
                    //loadData();
                    loadDataByDocumentNo();
                    var fg_tag_no = json.data.fg_tag_no;
                    if (fg_tag_no != '') {
                        //window.open("print/doc/fg_tag.php?data=" + fg_tag_no, '_blank');
                        ele('package_no').setValue('');
                        ele('part_tag_no').setValue('');
                        ele('steel_qty').setValue('');
                        focus('package_no');
                    } else {
                        // ele('package_no').setValue('');
                        // ele('steel_qty').setValue('');
                        ele('part_tag_no').setValue('');
                        focus('part_tag_no');
                    }
                    window.playsound(1);
                }
                else if (json.ch == 2) {
                    window.playsound(2);
                    webix.alert({
                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                            ele('part_tag_no').setValue('');
                            focus('part_tag_no');
                        }
                    });
                }
                /* else {
                    webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                } */
            });
    };

    webix.ui(
        {
            view: "window", id: $n("win_move_tag"), modal: 1,
            head: "Move Part Tag",
            //top: 50,
            //position: "center",
            position: function (state) {
                state.left = 40; // fixed values
                state.top = 50;
            },
            close: true, move: true,
            body:
            {
                view: "form", scroll: false, id: $n("win_move_tag_form"), width: 630,
                on: {
                    "onSubmit": function (view, e) {
                        if (view.config.name == 'fg_tag_no_edit') {
                            focus('fg_tag_no_edit');
                        }
                    },
                },
                elements:
                    [
                        {
                            cols: [
                                vw2('text', 'fg_tag_no_edit', 'fg_tag_no', 'FG Tag No.', { disabled: false, required: true, hidden: 0 }),
                                vw2('text', 'part_tag_no_edit', 'part_tag_no', 'Part Tag No.', { disabled: true, required: true, hidden: 0 }),
                                {
                                    rows: [
                                        {},
                                        vw1('button', 'btn_move', 'Save', {
                                            css: "webix_green",
                                            icon: "mdi mdi-content-save", type: "icon",
                                            tooltip: { template: "บันทึก", dx: 10, dy: 15 },
                                            width: 120,
                                            on: {
                                                onItemClick: function (id, e) {
                                                    var obj1 = ele('form1').getValues();
                                                    var obj2 = ele('win_move_tag_form').getValues();
                                                    var obj = { ...obj1, ...obj2 };
                                                    $.post(fd, { obj: obj, type: 22 })
                                                        .done(function (data) {
                                                            var json = JSON.parse(data);
                                                            if (json.ch == 1) {
                                                                webix.message({
                                                                    text: "Complete",
                                                                    type: "success",
                                                                    expire: 2000,
                                                                });
                                                                //loadData();
                                                                loadDataByDocumentNo();
                                                                window.playsound(1);
                                                                ele('win_move_tag').hide();
                                                                ele('fg_tag_no_edit').setValue('');

                                                            }
                                                            else if (json.ch == 2) {
                                                                window.playsound(2);
                                                                webix.alert({
                                                                    title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                                                                        ele('fg_tag_no_edit').setValue('');
                                                                        focus('fg_tag_no_edit');
                                                                    }
                                                                });
                                                            }
                                                            /* else {
                                                                webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                                                            } */
                                                        });
                                                }
                                            }
                                        }),
                                    ]
                                },
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
        id: "header_ConfirmRepack",
        body:
        {
            id: "ConfirmRepack_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Confirm Repack", type: "header" },
                    {
                        view: "form",
                        id: $n("form1"),

                        on: {
                            "onSubmit": function (view, e) {
                                if (view.config.name == 'work_order_no') {
                                    var obj = ele('form1').getValues();
                                    $.post(fd, { obj: obj, type: 3 })
                                        .done(function (data) {
                                            var json = JSON.parse(data);
                                            if (json.ch == 1) {
                                                if(json.data == 'HMTH'){
                                                    focus('package_no');
                                                }else{
                                                    focus('part_tag_no');
                                                }
                                                loadDataByDocumentNo();
                                            }
                                            else if (json.ch == 2) {
                                                webix.alert({
                                                    title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                                                        ele('work_order_no').setValue('');
                                                        focus('work_order_no');
                                                        ele('dataT1').clearAll();
                                                    }
                                                });
                                            }
                                        });
                                }
                                else if (view.config.name == 'package_no') {
                                    var obj = ele('form1').getValues();
                                    $.post(fd, { obj: obj, type: 2 })
                                        .done(function (data) {
                                            var json = JSON.parse(data);
                                            if (json.ch == 1) {
                                                focus('steel_qty');
                                                // console.log(json.data);
                                                if (json.data == 'Wooden') {
                                                    ele('steel_qty').setValue(0);
                                                } else {
                                                    ele('steel_qty').setValue('');
                                                }
                                            }
                                            else if (json.ch == 2) {
                                                webix.alert({
                                                    title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                                                        ele('package_no').setValue('');
                                                        focus('package_no');
                                                    }
                                                });
                                            }
                                        });
                                } else if (view.config.name == 'steel_qty') {
                                    focus('part_tag_no');
                                } else if (view.config.name == 'part_tag_no') {
                                    AddItem();
                                    //ele('part_tag_no').setValue();
                                    focus('part_tag_no');
                                }

                            },
                        },
                        elements:
                            [
                                {
                                    cols:
                                        [
                                            vw1('text', 'work_order_no', 'Work Order No.', {
                                                //options: [''],
                                                disabled: false, hidden: 0,
                                                /* on: {
                                                    onBlur: function () {
                                                        this.getList().hide();
                                                    },
                                                    onItemClick: function () {
                                                        reload_options_workorder_no();
                                                    },
                                                    onChange: function (value) {
                                                        if (value != '') {
                                                            focus('package_no');
                                                            loadDataByDocumentNo();
                                                        }
                                                    }
                                                } */
                                            }),
                                            vw1('text', 'package_no', 'Package No.', { disabled: false, required: false, hidden: 0 }),
                                            vw1('text', 'steel_qty', 'Steel Pipe (Qty)', { disabled: false, required: false, hidden: 0 }),
                                        ]
                                },
                                {
                                    cols:
                                        [
                                            vw1('text', 'part_tag_no', 'Part Tag No.', { disabled: false, required: true, hidden: 0 }),
                                            {
                                                rows: [
                                                    {},
                                                    vw1("button", 'btn_clear', "Clear", {
                                                        css: "webix_secondary",
                                                        icon: "mdi mdi-backspace", type: "icon",
                                                        tooltip: { template: "ล้างข้อมูล", dx: 10, dy: 15 },
                                                        width: 120,
                                                        on:
                                                        {
                                                            onItemClick: function () {
                                                                ele('work_order_no').setValue('');
                                                                ele('steel_qty').setValue('');
                                                                ele('part_tag_no').setValue('');
                                                                ele('package_no').setValue('');

                                                                focus('work_order_no');

                                                                ele('dataT1').clearAll();
                                                            }
                                                        }
                                                    }),
                                                ]
                                            }
                                        ]
                                },
                                {
                                    cols: [
                                        vw1('button', 'btn_save', 'Save', {
                                            css: "webix_green",
                                            icon: "mdi mdi-content-save", type: "icon",
                                            tooltip: { template: "บันทึก", dx: 10, dy: 15 },
                                            on: {
                                                onItemClick: function (id, e) {
                                                    var obj = ele('form1').getValues();
                                                    webix.confirm(
                                                        {
                                                            title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                            callback: function (res) {
                                                                if (res) {
                                                                    $.post(fd, { obj: obj, type: 41 })
                                                                        .done(function (data) {
                                                                            var json = JSON.parse(data);
                                                                            if (json.ch == 1) {
                                                                                // loadDataByDocumentNo();
                                                                                var work_order_no = json.data.work_order_no;
                                                                                var dos_no = json.data.dos_no;
                                                                                if (dos_no != '') {
                                                                                    // ele('form1').setValues('');
                                                                                    // window.open("print/doc/dos.php?data=" + dos_no, '_blank');
                                                                                    // ele('dataT1').clearAll();
                                                                                }
                                                                                
                                                                                if (work_order_no != '') {
                                                                                    ele('form1').setValues('');
                                                                                    focus('work_order_no');
                                                                                    webix.ajax("print/doc/merge_fg_tag.php?data=" + work_order_no).then(function () {
                                                                                        window.open("print/doc/files/Fg_tag/" + work_order_no + '.pdf', '_blank');
                                                                                    });
                                                                                    ele('dataT1').clearAll();
                                                                                }
                                                                                else {
                                                                                    focus('work_order_no');
                                                                                    ele('dataT1').clearAll();
                                                                                    ele('form1').setValues('');
                                                                                }
                                                                            }
                                                                            else if (json.ch == 2) {
                                                                                webix.alert({
                                                                                    title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                                                                                        focus('work_order_no');
                                                                                    }
                                                                                });
                                                                            }
                                                                            /* else {
                                                                                webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                                                                            } */
                                                                        });
                                                                }
                                                            }
                                                        });
                                                }
                                            }
                                        }),
                                        vw1('button', 'btn_cancel', 'Cancel', {
                                            css: "webix_red",
                                            icon: "mdi mdi-cancel", type: "icon",
                                            tooltip: { template: "ยกเลิก", dx: 10, dy: 15 },
                                            hidden: 0,
                                            on: {
                                                onItemClick: function (id, e) {
                                                    var obj = ele('form1').getValues();
                                                    //console.log(obj4);
                                                    webix.confirm(
                                                        {
                                                            title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการยกเลิก<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                            callback: function (res) {
                                                                if (res) {
                                                                    ajax(fd, obj, 31, function (json) {
                                                                        webix.alert({
                                                                            title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ยกเลิกสำเร็จ', callback: function () {
                                                                                focus('work_order_no');
                                                                                ele('dataT1').clearAll();
                                                                                ele('form1').setValues('');
                                                                            }
                                                                        });
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
                                    ]
                                },
                                {
                                    cols: [
                                        {
                                            view: "datatable", id: $n("dataT1"), navigation: true, select: false,
                                            resizeColumn: true, autoheight: true, hover: "myhover",
                                            threeState: true, rowLineHeight: 25, rowHeight: 25,
                                            datatype: "json", headerRowHeight: 25, leftSplit: 2,
                                            editable: true,
                                            editaction: "dblclick",
                                            navigation: true,
                                            scrollX: true,
                                            footer: true,
                                            css: "webix_selectable",
                                            // drag: "order",
                                            scheme:
                                            {
                                                $change: function (item) {
                                                    if (item.part_tag_repack == 'Yes') {
                                                        item.$css = { "background": "#afeac8", "font-weight": "bold" };
                                                    }
                                                }
                                            },
                                            columns: [
                                                /* {
                                                    id: "icon_delete", header: { text: "Delete", css: { "text-align": "center" } }, width: 50, template: function (row) {
                                                        return "<button class='mdi mdi-delete webix_button' title='ลบ' style='width:25px; height:20px; color:#556892; background-color: #dadee0;'></button>";
                                                    }
                                                }, */
                                                {
                                                    id: "icon_check", header: [{ text: "Edit", css: { "text-align": "center" } }], adjust: true,
                                                    template: function (row) {
                                                        if (row.change == 1) {
                                                            return "<span style='cursor:pointer' class='webix_icon mdi mdi-check'></span>";
                                                        }
                                                        else {
                                                            return "";
                                                        }

                                                    }
                                                },
                                                { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: { "text-align": "center" }, width: 35, sort: "int" },
                                                {
                                                    id: "package_no", header: [{ text: "Packge No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, footer: { text: "Total : ", css: { "text-align": "right" } },
                                                    editor: "text",
                                                    template: function (row) {
                                                        if (row.row_num == 1) {
                                                            return row.package_no;
                                                        }
                                                        else {
                                                            return "";
                                                        }
                                                    }
                                                },
                                                {
                                                    id: "package_type", header: [{ text: "Packge Type", css: { "text-align": "center" } }, { content: "textFilter" }], width: 80, css: { "text-align": "center" },
                                                    template: function (row) {
                                                        if (row.row_num == 1) {
                                                            return row.package_type;
                                                        }
                                                        else {
                                                            return "";
                                                        }
                                                    }
                                                },
                                                {
                                                    id: "steel_qty", header: [{ text: "Steel Pipe", css: { "text-align": "center" } }, { content: "numberFilter" }], width: 60, css: { "text-align": "center" },
                                                    footer: { content: "summColumn", css: { "text-align": "center" } }, editor: "text",
                                                    template: function (row) {
                                                        if (row.row_num == 1) {
                                                            return row.steel_qty;
                                                        }
                                                        else {
                                                            return "";
                                                        }
                                                    }
                                                },
                                                //{ id: "work_order_no", header: [{ text: "Work Order No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                                // { id: "package_no", header: [{ text: "Packge No.", css: { "text-align": "center" } },], width: 120, css: { "text-align": "center" } },
                                                // { id: "package_type", header: [{ text: "Packge Type", css: { "text-align": "center" } },], width: 100, css: { "text-align": "center" }, },
                                                // { id: "steel_qty", header: [{ text: "Steel Pipe", css: { "text-align": "center" } }, { text: "(Qty)", css: { "text-align": "center" } }], width: 60, css: { "text-align": "center" }, },
                                                { id: "fg_tag_no", header: [{ text: "FG Tag No.", css: { "text-align": "center" } },], width: 100, css: { "text-align": "center" } },
                                                //{ id: "part_tag_no_num", header: [{ text: "Part Tag No.", css: { "text-align": "center" } },], width: 100, css: { "text-align": "center" } },
                                                { id: "part_tag_no", header: [{ text: "Part Tag No.", css: { "text-align": "center" } },], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } },], width: 150, css: { "text-align": "center" } },
                                                { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } },], width: 180, css: { "text-align": "center" }, },
                                                { id: "part_tag_repack", header: [{ text: "Repack", css: { "text-align": "center" } },], width: 50, css: { "text-align": "center" }, hidden: 1 },
                                                { id: "total_qty", header: [{ text: "Total Qty", css: { "text-align": "center" } }, { text: "(Pcs.)", css: { "text-align": "center" } }], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, },
                                                { id: "total_net", header: [{ text: "Net Weight", css: { "text-align": "center" } }, { text: "(Kg.)", css: { "text-align": "center" } },], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, format: webix.i18n.numberFormat },
                                                {
                                                    id: "icon_move", header: [{ text: "Move", css: { "text-align": "center" } }, { text: "Tag", css: { "text-align": "center" } }], width: 40, template: function (row) {

                                                        return "<button class='mdi mdi-cursor-move webix_button' title='ย้ายไป FG Tag อื่น' style='width:25px; height:20px; color:#ffffff; background-color: #f08502;'></button>";
                                                    }
                                                },

                                            ],
                                            onClick:
                                            {
                                                "mdi-cursor-move": function (e, t) {
                                                    var row = this.getItem(t), dataTable = this;
                                                    ele('win_move_tag').show();
                                                    console.log(row.part_tag_no);
                                                    ele('part_tag_no_edit').setValue(row.part_tag_no);
                                                    focus('fg_tag_no_edit');
                                                },
                                                "mdi-check": function (e, t) {
                                                    var row = this.getItem(t), dataTable = this;
                                                    $.post(fd, { obj: row, type: 21 })
                                                        .done(function (data) {
                                                            var json = JSON.parse(data);
                                                            if (json.ch == 1) {
                                                                row.change = 0;
                                                                dataTable.updateItem(t.row, row);
                                                                webix.message({ expire: 7000, text: "บันทึกสำเร็จ" });

                                                                var fg_tag_no = json.data.fg_tag_no;
                                                                if (fg_tag_no != '') {
                                                                    //window.open("print/doc/fg_tag.php?data=" + fg_tag_no, '_blank');
                                                                    // ele('package_no').setValue('');
                                                                    // ele('steel_qty').setValue('');
                                                                    ele('part_tag_no').setValue('');
                                                                    focus('part_tag_no');
                                                                } else {
                                                                    ele('part_tag_no').setValue('');
                                                                    focus('part_tag_no');
                                                                }
                                                                window.playsound(1);
                                                            }
                                                            else if (json.ch == 2) {
                                                                webix.alert({
                                                                    title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                                                                        row.change = 0;
                                                                        loadDataByDocumentNo();
                                                                    }
                                                                });
                                                            }
                                                            /* else {
                                                                webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                                                            } */
                                                        });
                                                },
                                            },
                                            on:
                                            {
                                                "onEditorChange": function (id, value) {
                                                    // console.log(id, value);
                                                    var row = this.getItem(id), dataTable = this;
                                                    row.change = 1;
                                                    dataTable.updateItem(id.row, row);
                                                }
                                            }
                                        },
                                    ]
                                }
                            ]
                    }
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