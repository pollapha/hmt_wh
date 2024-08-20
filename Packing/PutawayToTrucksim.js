var header_PutawayToTrucksim = function () {
    var menuName = "PutawayToTrucksim_", fd = "Packing/" + menuName + "data.php";

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
                // reload_options_document_no();
                // reload_options_tolocation();
                ele('document_no').setValue(json.data.header[0].document_no);
                ele('work_order_no').setValue(json.data.header[0].work_order_no);
                ele('to_location').setValue(json.data.header[0].to_location);
                setTable('dataT1', json.data.body);
                // FindItem();

                ele('document_no').disable();
                ele('work_order_no').disable();
                ele('to_location').disable();
            }
            else {
                ele('form1').setValues('');
                ele('dataT1').clearAll();

                // ele('document_no').enable();
                ele('work_order_no').enable();
                ele('to_location').enable();

                focus('work_order_no');
            }

        }, btn);
    };


    function loadDataView(btn) {
        var obj = ele("form3").getValues();
        ajax("Packing/PutawayToTrucksimView_data.php", obj, 1, function (json) {
            setTable("dataTREE", json.data);
        }, btn);
    };

    function AddItem() {

        var obj = ele('form1').getValues();
        $.post(fd, { obj: obj, type: 11 })
            .done(function (data) {
                var json = JSON.parse(data);
                if (json.ch == 1) {
                    window.playsound(1);
                    webix.message({
                        text: "Complete",
                        type: "success",
                        expire: 2000,
                    });
                    loadData();
                    focus('tag_no');
                    ele('tag_no').setValue('');
                }
                else if (json.ch == 2) {
                    window.playsound(2);
                    webix.alert({
                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                            focus('tag_no');
                            ele('tag_no').setValue('');
                            //ele('form2').setValues('');
                        }
                    });
                }
                else {
                    webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                }
            });

    };

    function FindItem() {
        var obj = ele('form1').getValues();
        $.post(fd, { obj: obj, type: 2 })
            .done(function (data) {
                var json = JSON.parse(data);
                if (json.ch == 1) {
                    setTable('dataT1', json.data.body);
                    focus('to_location');
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

    };

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_PutawayToTrucksim",
        body:
        {
            id: "PutawayToTrucksim_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Putaway (truck-sim)", type: "header" },
                    {
                        padding: 8,
                        view: 'tabview',
                        id: $n("MasterTab"),
                        cells: [
                            {
                                header: "Putaway",
                                id: $n('tab1'),
                                view: "form",
                                scroll: false,
                                elements: [
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
                                                                FindItem();
                                                                focus('to_location');
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
                                                } else if (view.config.name == 'to_location') {
                                                    focus('tag_no');
                                                } else if (view.config.name == 'tag_no') {
                                                    AddItem();
                                                }

                                            },
                                        },
                                        elements:
                                            [
                                                {
                                                    cols:
                                                        [
                                                            vw1('text', 'document_no', 'Document No.', {
                                                                disabled: true,
                                                                //options: [''],
                                                                // on: {
                                                                //     onBlur: function () {
                                                                //         this.getList().hide();
                                                                //     },
                                                                //     onItemClick: function () {
                                                                //         reload_options_document_no();
                                                                //     },
                                                                //     onChange: function () {
                                                                //         loadDataByDocumentNo();
                                                                //         focus('work_order_no');
                                                                //     }
                                                                // }
                                                            }),
                                                            vw1('text', 'work_order_no', 'Work Order No.', {
                                                                disabled: false, required: true, hidden: 0,
                                                                //suggest: "common/dosNo.php?type=2", Count: "5",
                                                            }),
                                                            vw1('text', 'to_location', 'To Location', {
                                                                disabled: false, required: true, hidden: 0,
                                                                suggest: "common/locationMaster.php?type=5", Count: "5",
                                                            }),
                                                            {
                                                                rows: [
                                                                    {},
                                                                    vw1("button", 'btn_clear_all', "Clear", {
                                                                        css: "webix_secondary",
                                                                        icon: "mdi mdi-backspace", type: "icon",
                                                                        tooltip: { template: "ล้างข้อมูล", dx: 10, dy: 15 },
                                                                        width: 120,
                                                                        on:
                                                                        {
                                                                            onItemClick: function () {
                                                                                ele('form1').setValues('');
                                                                                ele('dataT1').clearAll();
                                                                                focus('work_order_no');
                                                                            }
                                                                        }
                                                                    }),
                                                                ]
                                                            }
                                                        ]
                                                },
                                                {
                                                    cols:
                                                        [
                                                            vw1('text', 'tag_no', 'FG Tag No.', { disabled: false, required: true, hidden: 0 }),
                                                            {
                                                                rows: [
                                                                    {},
                                                                    vw1('button', 'btn_add_item', 'Add', {
                                                                        css: "webix_blue",
                                                                        icon: "mdi mdi-plus-circle", type: "icon",
                                                                        tooltip: { template: "เพิ่ม", dx: 10, dy: 15 },
                                                                        width: 120,
                                                                        on: {
                                                                            onItemClick: function (id, e) {
                                                                                AddItem();
                                                                            }
                                                                        }
                                                                    }),
                                                                ]
                                                            },
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
                                                                                ele('tag_no').setValue('');
                                                                            }
                                                                        }
                                                                    }),
                                                                ]
                                                            }
                                                        ]
                                                },
                                                {
                                                    cols: [
                                                        vw1('button', 'btn_move', 'Putaway', {
                                                            css: "webix_green",
                                                            icon: "mdi mdi-file-move", type: "icon",
                                                            tooltip: { template: "บันทึก", dx: 10, dy: 15 },
                                                            on: {
                                                                onItemClick: function (id, e) {
                                                                    var obj = ele('form1').getValues();
                                                                    webix.confirm(
                                                                        {
                                                                            title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                            callback: function (res) {
                                                                                if (res) {
                                                                                    ajax(fd, obj, 41, function (json) {
                                                                                        loadData();
                                                                                        var data = json.data;
                                                                                        var dos_no = data.dos_no;
                                                                                        if (dos_no != '') {
                                                                                            window.open("print/doc/dos.php?data=" + dos_no, '_blank');
                                                                                        }
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
                                                                                    ajax('Packing/PutawayToTrucksimView_data.php', obj, 32, function (json) {
                                                                                        webix.alert({
                                                                                            title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ยกเลิกสำเร็จ', callback: function () {
                                                                                                loadData();
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
                                                            resizeColumn: true, autoheight: true, multiselect: false, hover: "myhover",
                                                            threeState: true, rowLineHeight: 25, rowHeight: 25,
                                                            datatype: "json", headerRowHeight: 25, leftSplit: 2,
                                                            editable: true,
                                                            editaction: "dblclick",
                                                            navigation: true,
                                                            scrollX: true,
                                                            footer: false,
                                                            scheme:
                                                            {
                                                                $change: function (item) {
                                                                    if (item.location_code != '') {
                                                                        item.$css = { "background": "#afeac8", "font-weight": "bold" };
                                                                    }
                                                                }
                                                            },
                                                            columns: [
                                                                {
                                                                    id: "icon_delete", header: { text: "Delete", css: { "text-align": "center" } }, width: 50,
                                                                    template: function (row) {
                                                                        if (row.location_code != '') {
                                                                            return "<button class='mdi mdi-delete webix_button' title='ลบ' style='width:25px; height:20px; color:#556892; background-color: #dadee0;'></button>";
                                                                        }
                                                                        else {
                                                                            return "";
                                                                        }
                                                                    }

                                                                },
                                                                { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: { "text-align": "center" }, width: 35, sort: "int" },
                                                                { id: "location_code", header: [{ text: "Location", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                //{ id: "location_area", header: [{ text: "Area", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                //{ id: "case_tag_no", header: [{ text: "Case Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                { id: "fg_tag_no", header: [{ text: "FG Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" } },
                                                                { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, footer: { text: "Total : ", css: { "text-align": "right" } } },
                                                                // { id: "qty", header: [{ text: "Qty(pcs.)", css: { "text-align": "center" } }, { content: "numberFilter" }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 80, css: { "text-align": "center" } },

                                                            ],
                                                            onClick:
                                                            {
                                                                "mdi-delete": function (e, t) {
                                                                    var row = this.getItem(t), dataTable = this;
                                                                    var obj1 = ele('form1').getValues();
                                                                    var obj = { ...obj1, ...row };
                                                                    ajax(fd, obj, 31, function (json) {
                                                                        loadData();
                                                                    }, null,
                                                                        function (json) {
                                                                        });
                                                                },

                                                            },
                                                        },

                                                    ]
                                                }
                                            ]
                                    }
                                ]
                            },
                            {
                                header: "View",
                                id: $n('tab2'),
                                view: "form",
                                scroll: false,
                                elements: [
                                    {
                                        view: "form",
                                        id: $n("form3"),
                                        elements:
                                            [
                                                {
                                                    cols:
                                                        [
                                                            {
                                                                rows: [
                                                                    {},
                                                                    vw1("button", 'btnExport', "Export Report", {
                                                                        width: 120, css: "webix_orange",
                                                                        icon: "mdi mdi-table-arrow-down", type: "icon",
                                                                        tooltip: { template: "ดาวน์โหลดข้อมูล", dx: 10, dy: 15 },
                                                                        on: {
                                                                            onItemClick: function () {
                                                                                exportExcel(this);
                                                                                /* var dataTREE = ele("dataTREE");
                                                                                if (dataTREE.count() != 0) {
                                                                                    var start_date = ele('start_date').getValue();
                                                                                    var stop_date = ele('stop_date').getValue();
    
                                                                                    if (start_date == '' || stop_date == '') {
                                                                                        webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'กรุณาป้อนวันที่', callback: function () { } });
                                                                                    } else {
    
                                                                                        var temp = window.open(fd + "?type=51" + "&start_date=" + start_date + "&stop_date=" + stop_date);
                                                                                    }
                                                                                    //temp.addEventListener('load', function () { temp.close(); }, false);
                                                                                }
                                                                                else {
                                                                                    webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ไม่พบข้อมูลในตาราง', callback: function () { } });
                                                                                } */
                                                                            }
                                                                        }
                                                                    }),
                                                                ]
                                                            },
                                                            {},
                                                            //dayjs().format("YYYY-MM-DD")
                                                            vw1("datepicker", 'start_date', "Start Date (วันที่เริ่ม)", { value: '', required: false, stringResult: true, ...datatableDateFormat, width: 150, hidden: 0 }),
                                                            vw1("datepicker", 'stop_date', "End Date (วันที่สิ้นสุด)", { value: '', required: false, stringResult: true, ...datatableDateFormat, width: 150, hidden: 0 }),
                                                            {
                                                                rows: [
                                                                    {},
                                                                    vw1("button", 'btn_find_view', "Find", {
                                                                        width: 100, css: "webix_primary",
                                                                        icon: "mdi mdi-magnify", type: "icon",
                                                                        tooltip: { template: "ค้นหา", dx: 10, dy: 15 },
                                                                        on: {
                                                                            onItemClick: function () {
                                                                                loadDataView();
                                                                            }
                                                                        }
                                                                    }),
                                                                ]
                                                            },
                                                            {
                                                                rows: [
                                                                    {},
                                                                    vw1("button", 'btn_clear_view_form', "Clear", {
                                                                        width: 100, css: "webix_secondary",
                                                                        icon: "mdi mdi-backspace", type: "icon",
                                                                        tooltip: { template: "ล้างข้อมูล", dx: 10, dy: 15 },
                                                                        on:
                                                                        {
                                                                            onItemClick: function () {
                                                                                ele('start_date').setValue('');
                                                                                ele('stop_date').setValue('');
                                                                                //setStartDate();
                                                                                ele('dataTREE').eachColumn(function (id, col) {
                                                                                    var filter = this.getFilter(id);
                                                                                    if (filter) {
                                                                                        if (filter.setValue) filter.setValue("")
                                                                                        else filter.value = "";
                                                                                    }
                                                                                });
                                                                                loadDataView();
                                                                            }
                                                                        }
                                                                    }),
                                                                ]
                                                            },
                                                        ]
                                                },
                                                {
                                                    view: "treetable", id: $n('dataTREE'), navigation: true, select: "row", editaction: "custom",
                                                    resizeColumn: true, autoheight: true, multiselect: true, hover: "myhover",
                                                    threeState: false, rowLineHeight: 25, rowHeight: 25,
                                                    datatype: "json", headerRowHeight: 40, leftSplit: 2, editable: true, css: { "font-size": "13px" },
                                                    editable: true, footer: true,
                                                    footer: true,
                                                    pager: $n("Master_pagerA"),
                                                    datafetch: 50, // Number of rows to fetch at a time
                                                    loadahead: 100, // Number of rows to prefetch
                                                    scheme:
                                                    {
                                                        $change: function (item) {
                                                            // if (item.Is_Header == 'YES' && item.Putaway_All == 'Y') {
                                                            //     item.$css = { "background": "#afeac8", "font-weight": "bold" };
                                                            // }
                                                        }
                                                    },
                                                    columns: [
                                                        {
                                                            id: "icon_cancel", header: { text: "Cancel", rotate: true, height: 30, css: { "text-align": "center" } }, width: 40, template: function (row) {
                                                                if (row.row_no == 1) {
                                                                    return "<button class='mdi mdi-cancel webix_button' title='ยกเลิกเอกสาร' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #ed3755;'></button>";
                                                                }
                                                                else {
                                                                    return "";
                                                                }
                                                            }
                                                        },
                                                        {
                                                            id: "icon_edit", header: [{ text: "Edit", rotate: true, height: 30, css: { "text-align": "center" } }], width: 40, template: function (row) {
                                                                if (row.row_no == 1) {
                                                                    return "<button class='mdi mdi-pencil webix_button' title='แก้ไขเอกสาร' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #f08502;'></button>";
                                                                }
                                                                else {
                                                                    return "";
                                                                }
                                                            }
                                                        },
                                                        /* {
                                                            id: "doc", header: { text: "View", rotate: true, height: 30, css: { "text-align": "center" } }, width: 45, template: function (row) {
                                                                if (row.row_no == 1) {
                                                                    return "<button class='mdi mdi-file webix_button' title='ดูเอกสาร' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #556892;'></button>";
                                                                }
                                                                else {
                                                                    return "";
                                                                }
                                                            }
                                                        }, */
                                                        // { id: "No", header: "", css: { "text-align": "right" }, editor: "", width: 40 },
                                                        // {
                                                        //     id: "document_no", header: [{ text: "Document No.", css: { "text-align": "center" } }, { content: "textFilter" }], editor: "", width: 180, css: { "text-align": "center" },
                                                        //     template: "{common.treetable()} #document_no#", footer: [{ text: "Total :", css: { "text-align": "left" } }],
                                                        // },
                                                        { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: { "text-align": "center" }, width: 30, sort: "int" },
                                                        { id: "document_no", header: [{ text: "Document No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        { id: "document_date", header: [{ text: "Document Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        { id: "from_location", header: [{ text: "From Location", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" } },
                                                        { id: "to_location", header: [{ text: "To Location", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" } },
                                                        { id: "case_tag_no", header: [{ text: "Case Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" } },
                                                        { id: "fg_tag_no", header: [{ text: "FG Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" } },
                                                        { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" } },
                                                        { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, footer: { text: "Total : ", css: { "text-align": "right" } } },
                                                        { id: "qty", header: [{ text: "Qty(pcs.)", css: { "text-align": "center" } }, { content: "numberFilter" }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 100, css: { "text-align": "center" } },
                                                        // { id: "gross_kg", header: [{ text: "Gross(Kg.)", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } } },
                                                        // { id: "net_per_pallet", header: [{ text: "Net(Kg.)", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, },
                                                        // { id: "measurement_cbm", header: [{ text: "Measurement(CBM)", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, format: webix.i18n.numberFormat },
                                                        // { id: "certificate_no", header: [{ text: "Certificate No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                        // { id: "remark", header: [{ text: "Remark", css: { "text-align": "center" } }, { content: "textFilter" }], width: 250, css: { "text-align": "center" } },
                                                    ],
                                                    on:
                                                    {

                                                    },
                                                    onClick:
                                                    {
                                                        "mdi-file": function (e, t) {
                                                            var row = this.getItem(t);
                                                            var data = row.document_no;

                                                            window.open("print/doc/grn.php?data=" + data, '_blank');
                                                        },
                                                        "mdi-pencil": function (e, t) {
                                                            // ele('win_edit').show();
                                                            var row = this.getItem(t);
                                                            // console.log(obj);
                                                            msBox('แก้ไข', function () {
                                                                ajax(fd, row, 21, function (json) {
                                                                    loadDataView();
                                                                    ele('tab1').show();
                                                                    ele('document_no').setValue(json.data);
                                                                    loadData();
                                                                    //loadDataByDocumentNo();

                                                                    //openNewTab();

                                                                }, null,
                                                                    function (json) {
                                                                    });
                                                            }, row);

                                                        },
                                                        "mdi-cancel": function (e, t) {
                                                            var row = this.getItem(t), datatable = this;
                                                            //console.log(row);
                                                            msBox('ยกเลิก', function () {
                                                                ajax('Packing/PutawayToTrucksimView_data.php', row, 32, function (json) {
                                                                    webix.alert({
                                                                        title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ยกเลิกสำเร็จ', callback: function () {
                                                                            loadDataView();
                                                                        }
                                                                    });

                                                                }, null,
                                                                    function (json) {
                                                                    });
                                                            }, row);
                                                        },
                                                    },
                                                },
                                                {
                                                    cols: [
                                                        {},
                                                        {
                                                            view: "pager", id: $n("Master_pagerA"),
                                                            template: function (data, common) {
                                                                var start = data.page * data.size
                                                                    , end = start + data.size;
                                                                if (data.count == 0) start = 0;
                                                                else start += 1;
                                                                if (end >= data.count) end = data.count;
                                                                var html = "<b>showing " + (start) + " - " + end + " total " + data.count + " </b>";
                                                                return common.first() + common.prev() + " " + html + " " + common.next() + common.last();
                                                            },
                                                            size: 100,
                                                            group: 5
                                                        },
                                                        {}
                                                    ]
                                                }

                                            ]
                                    }
                                ]
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