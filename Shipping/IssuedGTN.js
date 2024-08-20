var header_IssuedGTN = function () {
    var menuName = "IssuedGTN_", fd = "Shipping/" + menuName + "data.php";

    function init() {
        loadData();
        // loadDataView();
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
        var dataT1 = ele("dataTREE"), obj = {}, data = [];
        if (dataT1.count() == 0) {
            webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ไม่พบข้อมูลในตาราง', callback: function () { } });
        }

        for (var i = -1, len = dataT1.config.columns.length; ++i < len;) {
            obj[dataT1.config.columns[i].id] = dataT1.config.columns[i].header[0].text;
        }
        delete obj.icon_cancel;
        delete obj.icon_edit;
        delete obj.icon_doc;
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
                saveAs(e.data, 'gtn_' + dayjs().format('YYYYMMDDHHmmss') + ".xlsx");
                btn.enable();
                webix.message({ expire: 7000, text: "Export สำเร็จ" });
            }, false);
            worker.postMessage({ 'cmd': 'start', 'msg': data });
        }
        else { webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: "ไม่พบข้อมูลในตาราง", callback: function () { } }); }
    };


    function loadData(btn) {
        ajax(fd, {}, 1, function (json) {
            if (json.data.header.length > 0) {
                reload_options_document_no();
                reload_options_truck();
                reload_options_driver();
                ele('form1').setValues(json.data.header[0]);
                setTable('dataT2', json.data.body);
                FindItem();

                ele('dos_no').disable();
            }
            else {
                ele('form1').setValues('');
                var truckListCombo = { id: 'N/A', value: 'N/A' };
                ele("truck_number").getPopup().getBody().parse(truckListCombo);
                ele('truck_number').setValue('N/A');

                var driverListCombo = { id: 'N/A', value: 'N/A' };
                ele("driver_name").getPopup().getBody().parse(driverListCombo);
                ele('driver_name').setValue('N/A');
                ele('document_date').setValue(new Date());
                ele('dataT1').clearAll();
                ele('dataT2').clearAll();

                ele('dos_no').enable();
            }

        }, btn);
    };

    function loadDataByDocumentNo() {
        var obj = ele('form1').getValues();
        ajax(fd, obj, 2, function (json) {
            if (json.data.header.length > 0) {
                reload_options_document_no();
                reload_options_truck();
                reload_options_driver();
                ele('form1').setValues(json.data.header[0]);
                setTable('dataT2', json.data.body);
                FindItem();

                ele('dos_no').disable();
            }
            else {
                ele('form1').setValues('');
                var truckListCombo = { id: 'N/A', value: 'N/A' };
                ele("truck_number").getPopup().getBody().parse(truckListCombo);
                ele('truck_number').setValue('N/A');

                var driverListCombo = { id: 'N/A', value: 'N/A' };
                ele("driver_name").getPopup().getBody().parse(driverListCombo);
                ele('driver_name').setValue('N/A');
                ele('document_date').setValue(new Date());
                ele('dataT1').clearAll();
                ele('dataT2').clearAll();

                ele('dos_no').enable();
            }

        });
    };


    function loadDataView(btn) {
        var obj = ele("form3").getValues();
        ajax("Shipping/IssuedGTNView_data.php", obj, 1, function (json) {
            setTable("dataTREE", json.data);
        }, btn);
    };

    function reload_options_document_no() {
        var documentList = ele("document_no").getPopup().getList();
        documentList.clearAll();
        documentList.load("common/documentNo.php?type=3");
    };

    function reload_options_truck() {
        var truckList = ele("truck_number").getPopup().getList();
        truckList.clearAll();
        truckList.load("common/truckMaster.php?type=2");
    };

    function reload_options_driver() {
        var driverList = ele("driver_name").getPopup().getList();
        driverList.clearAll();
        driverList.load("common/DriverMaster.php?type=2");
    };


    function AddItem(row) {
        var obj1 = ele('form1').getValues();
        var obj = { ...obj1, ...row };
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
                    var document_no = ele('document_no').getValue();
                    if (document_no == '') {
                        loadData();
                    } else {
                        loadDataByDocumentNo();
                    }
                    //ele('form1').setValues('');
                }
                else if (json.ch == 2) {
                    window.playsound(2);
                    webix.alert({
                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                            ele('tag_no').setValue('');
                            focus('tag_no');
                        }
                    });
                }
                /* else {
                    webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                } */
            });
    };


    function FindItem() {
        var obj = ele('form1').getValues();
        ajax(fd, obj, 3, function (json) {
            if (json.data.header.length > 0) {
                // reload_options_document_no();
                var document_no = ele('document_no').getValue();
                if (document_no == '') {
                    ele('delivery_date').setValue(json.data.header[0].delivery_date);
                    ele('invoice_no').setValue(json.data.header[0].invoice_no);
                }
                setTable('dataT1', json.data.body);

                // ele('dos_no').disable();
            }
        });
    };

    function FindItem2() {
        var obj = ele('form1').getValues();
        ajax(fd, obj, 4, function (json) {
            if (json.data.header.length > 0) {
                // reload_options_document_no();
                var document_no = ele('document_no').getValue();
                if (document_no == '') {
                    ele('delivery_date').setValue(json.data.header[0].delivery_date);
                    ele('invoice_no').setValue(json.data.header[0].invoice_no);
                }
                setTable('dataT1', json.data.body);

                // ele('dos_no').disable();
            }
        });
    }

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_IssuedGTN",
        body:
        {
            id: "IssuedGTN_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Issued GTN", type: "header" },
                    {
                        padding: 8,
                        view: 'tabview',
                        id: $n("MasterTab"),
                        cells: [
                            {
                                header: "Generate GTN",
                                id: $n('tab1'),
                                view: "form",
                                scroll: false,
                                elements: [
                                    {
                                        rows: [
                                            {
                                                view: "form", scroll: false, id: $n('form1'),
                                                on: {
                                                    "onSubmit": function (view, e) {

                                                        if (view.config.name == 'dos_no') {
                                                            FindItem2();
                                                            focus('truck_number');
                                                        } else if (view.config.name == 'invoice_no') {
                                                            focus('delivery_date');
                                                        } else if (view.config.name == 'delivery_date') {
                                                            focus('truck_number');
                                                        } else if (view.config.name == 'truck_number') {
                                                            focus('driver_name');
                                                        } else if (view.config.name == 'driver_name') {
                                                            focus('tag_no');
                                                        } else if (view.config.name == 'tag_no') {
                                                            focus('tag_no');
                                                            AddItem();
                                                        }

                                                    },
                                                },
                                                elements: [
                                                    {
                                                        rows: [
                                                            {
                                                                cols: [
                                                                    vw1('button', 'btn_save', 'Save', {
                                                                        css: "webix_green",
                                                                        icon: "mdi mdi-content-save", type: "icon",
                                                                        tooltip: { template: "บันทึก", dx: 10, dy: 15 },
                                                                        hidden: 0,
                                                                        on: {
                                                                            onItemClick: function (id, e) {
                                                                                var obj = ele('form1').getValues();
                                                                                webix.confirm(
                                                                                    {
                                                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                                        callback: function (res) {
                                                                                            if (res) {
                                                                                                ajax(fd, obj, 41, function (json) {
                                                                                                    var data = json.data;
                                                                                                    window.open("print/doc/gtn.php?data=" + data, '_blank');

                                                                                                    loadDataByDocumentNo();
                                                                                                    loadDataView();

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
                                                                                webix.confirm(
                                                                                    {
                                                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการยกเลิก<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                                        callback: function (res) {
                                                                                            if (res) {
                                                                                                ajax('Shipping/IssuedGTNView_data.php', obj, 32, function (json) {
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
                                                                rows:
                                                                    [
                                                                        {
                                                                            view: "fieldset", label: "INFO (ข้อมูล)", body:
                                                                            {
                                                                                rows: [
                                                                                    {
                                                                                        cols: [
                                                                                            vw1('combo', 'document_no', 'Document No.', {
                                                                                                options: [''],
                                                                                                disabled: false, hidden: 0, required: false,
                                                                                                on: {
                                                                                                    onBlur: function () {
                                                                                                        this.getList().hide();
                                                                                                    },
                                                                                                    onItemClick: function () {
                                                                                                        reload_options_document_no();
                                                                                                    },
                                                                                                    onChange: function () {
                                                                                                        loadDataByDocumentNo();
                                                                                                        focus('dos_no');
                                                                                                    }
                                                                                                }
                                                                                            }),
                                                                                            vw1("datepicker", 'document_date', "Document Date", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, required: false, }),
                                                                                        ]
                                                                                    },
                                                                                    {
                                                                                        cols: [
                                                                                            vw1('text', 'dos_no', 'DOS No.', {
                                                                                                disabled: false, required: true, hidden: 0,
                                                                                                //suggest: "common/dosNo.php?type=2", Count: "5",
                                                                                            }),
                                                                                            {
                                                                                                rows: [
                                                                                                    {},
                                                                                                    vw1('button', 'btn_find_order', 'Find', {
                                                                                                        width: 120,
                                                                                                        css: "webix_primary",
                                                                                                        icon: "mdi mdi-magnify", type: "icon",
                                                                                                        tooltip: { template: "ค้นหา", dx: 10, dy: 15 },
                                                                                                        on: {
                                                                                                            onItemClick: function (id, e) {
                                                                                                                FindItem2();
                                                                                                            }
                                                                                                        }
                                                                                                    }),
                                                                                                ]
                                                                                            },
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
                                                                                                                ele('document_date').setValue(new Date());
                                                                                                                reload_options_document_no();

                                                                                                                focus('dos_no');
                                                                                                                // ele('dataT2').clearAll();
                                                                                                                ele('dataT1').clearAll();

                                                                                                                ele('dos_no').enable();

                                                                                                                var truckListCombo = { id: 'N/A', value: 'N/A' };
                                                                                                                ele("truck_number").getPopup().getBody().parse(truckListCombo);
                                                                                                                ele('truck_number').setValue('N/A');

                                                                                                                var driverListCombo = { id: 'N/A', value: 'N/A' };
                                                                                                                ele("driver_name").getPopup().getBody().parse(driverListCombo);
                                                                                                                ele('driver_name').setValue('N/A');

                                                                                                            }
                                                                                                        }
                                                                                                    }),
                                                                                                ]
                                                                                            }
                                                                                        ]
                                                                                    },
                                                                                    {
                                                                                        cols: [
                                                                                            vw1('text', 'invoice_no', 'Invoice No.', { disabled: false, required: false, hidden: 1, }),
                                                                                            vw1("datepicker", 'delivery_date', "Delivery Date", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, required: false, }),
                                                                                        ]
                                                                                    },
                                                                                    {
                                                                                        cols: [
                                                                                            vw1('combo', 'truck_number', 'Truck (ทะเบียนรถ)', {
                                                                                                disabled: false, required: false, hidden: 0,
                                                                                                suggest: "common/truckMaster.php?type=1", Count: "5",
                                                                                                on: {
                                                                                                    onBlur: function () {
                                                                                                        this.getList().hide();
                                                                                                    },
                                                                                                    onItemClick: function () {
                                                                                                        reload_options_truck();
                                                                                                    },
                                                                                                },
                                                                                            }),
                                                                                            vw1('combo', 'driver_name', 'Driver (พขร.)', {
                                                                                                disabled: false, required: false, hidden: 0,
                                                                                                suggest: "common/driverMaster.php?type=1", Count: "5",
                                                                                                on: {
                                                                                                    onBlur: function () {
                                                                                                        this.getList().hide();
                                                                                                    },
                                                                                                    onItemClick: function () {
                                                                                                        reload_options_driver();
                                                                                                    },
                                                                                                },
                                                                                            }),
                                                                                        ]
                                                                                    },
                                                                                    {
                                                                                        cols: [
                                                                                            vw1('text', 'tag_no', 'Fg Tag No.', { disabled: false, required: false, hidden: 0, }),
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
                                                                                                                focus('tag_no');
                                                                                                            }
                                                                                                        }
                                                                                                    }),
                                                                                                ]
                                                                                            },
                                                                                        ]
                                                                                    },
                                                                                ]
                                                                            },
                                                                        },
                                                                        {
                                                                            cols: [
                                                                                {
                                                                                    view: "fieldset", label: "Delivery Order", body:
                                                                                    {
                                                                                        rows: [
                                                                                            {
                                                                                                view: "datatable", id: $n("dataT1"), navigation: true, select: true,
                                                                                                resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                                                                                                threeState: true, rowLineHeight: 25, rowHeight: 25,
                                                                                                datatype: "json", headerRowHeight: 25, leftSplit: 2,
                                                                                                editable: true,
                                                                                                navigation: true,
                                                                                                scrollX: true,
                                                                                                footer: true,
                                                                                                height: 300,
                                                                                                css: "webix_font_size",
                                                                                                scheme:
                                                                                                {
                                                                                                    $change: function (item) {
                                                                                                        if (item.check_status == 'Yes') {
                                                                                                            item.$css = { "background": "#afeac8", "font-weight": "bold" };
                                                                                                        }
                                                                                                    }
                                                                                                },
                                                                                                columns: [
                                                                                                    { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: { "text-align": "center" }, width: 30, sort: "int" },
                                                                                                    // { id: "check_status", header: [{ text: "check_status", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                                                    { id: "fg_tag_no", header: [{ text: "FG Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                                                    { id: "package_no", header: [{ text: "Package No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                                                    { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                                                                    { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, footer: { text: "Total : ", css: { "text-align": "right" } } },
                                                                                                    { id: "qty", header: [{ text: "Qty", css: { "text-align": "center" } }, { text: "(Pcs.)", css: { "text-align": "center" } }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 60, css: { "text-align": "center" }, hidden: 0 },
                                                                                                    { id: "net_per_pallet", header: [{ text: "Net", css: { "text-align": "center" } }, { text: "Weight(Kg.)", css: { "text-align": "center" } }], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, format: webix.i18n.numberFormat },

                                                                                                ],
                                                                                                on: {
                                                                                                    "onItemClick": function (id) {
                                                                                                    },
                                                                                                },
                                                                                                onClick:
                                                                                                {
                                                                                                    "mdi-plus-circle": function (e, t) {
                                                                                                        var row = this.getItem(t), dataTable = this;
                                                                                                        AddItem(row);
                                                                                                    },

                                                                                                },
                                                                                            },
                                                                                        ]
                                                                                    }
                                                                                },
                                                                                {
                                                                                    view: "fieldset", label: "Issued Data", body:
                                                                                    {
                                                                                        rows: [
                                                                                            {
                                                                                                view: "datatable", id: $n("dataT2"), navigation: true, select: true,
                                                                                                resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                                                                                                threeState: true, rowLineHeight: 25, rowHeight: 25,
                                                                                                datatype: "json", headerRowHeight: 25, leftSplit: 2,
                                                                                                editable: true,
                                                                                                navigation: true,
                                                                                                scrollX: true,
                                                                                                footer: true,
                                                                                                height: 300,
                                                                                                css: "webix_font_size",
                                                                                                columns: [
                                                                                                    {
                                                                                                        id: "icon_delete", header: { text: "Delete", css: { "text-align": "center" } }, width: 50, template: function (row) {
                                                                                                            return "<button class='mdi mdi-delete webix_button' title='ลบ' style='width:25px; height:20px; color:#556892; background-color: #dadee0;'></button>";
                                                                                                        }
                                                                                                    },
                                                                                                    { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: { "text-align": "center" }, width: 30, sort: "int" },
                                                                                                    // { id: "case_tag_no", header: [{ text: "Case Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                                                    { id: "fg_tag_no", header: [{ text: "FG Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                                                    { id: "package_no", header: [{ text: "Package No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                                                    { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                                                                    { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, footer: { text: "Total : ", css: { "text-align": "right" } } },
                                                                                                    { id: "qty_per_pallet", header: [{ text: "Qty", css: { "text-align": "center" } }, { text: "(Pcs.)", css: { "text-align": "center" } }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 60, css: { "text-align": "center" }, hidden: 0 },
                                                                                                    { id: "net_per_pallet", header: [{ text: "Net", css: { "text-align": "center" } }, { text: "Weight(Kg.)", css: { "text-align": "center" } }], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, format: webix.i18n.numberFormat },

                                                                                                ],
                                                                                                on: {
                                                                                                    "onItemClick": function (id) {
                                                                                                    },
                                                                                                },
                                                                                                onClick:
                                                                                                {
                                                                                                    "mdi-delete": function (e, t) {
                                                                                                        var row = this.getItem(t), dataTable = this;
                                                                                                        ajax(fd, row, 31, function (json) {
                                                                                                            loadDataByDocumentNo();
                                                                                                        }, null,
                                                                                                            function (json) {
                                                                                                            });
                                                                                                    },

                                                                                                },
                                                                                            },
                                                                                        ]
                                                                                    }
                                                                                },
                                                                            ]
                                                                        }
                                                                    ]
                                                            }
                                                        ]

                                                    },

                                                ]
                                            },
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
                                                    datatype: "json", headerRowHeight: 40, leftSplit: 3, editable: true, css: { "font-size": "13px" },
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
                                                        {
                                                            id: "icon_doc", header: { text: "View", rotate: true, height: 30, css: { "text-align": "center" } }, width: 45, template: function (row) {
                                                                if (row.row_no == 1) {
                                                                    return "<button class='mdi mdi-file webix_button' title='ดูเอกสาร' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #556892;'></button>";
                                                                }
                                                                else {
                                                                    return "";
                                                                }
                                                            }
                                                        },
                                                        /* { id: "No", header: "", css: { "text-align": "right" }, editor: "", width: 40 },
                                                        {
                                                            id: "document_no", header: [{ text: "Document No.", css: { "text-align": "center" } }, { content: "textFilter" }], editor: "", width: 180, css: { "text-align": "center" },
                                                            template: "{common.treetable()} #document_no#",
                                                        }, */
                                                        { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: { "text-align": "center" }, width: 30, sort: "int" },
                                                        { id: "document_no", header: [{ text: "Document No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        { id: "document_date", header: [{ text: "Document Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        { id: "transaction_type", header: [{ text: "Transaction Type", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        { id: "dos_no", header: [{ text: "DOS No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" } },
                                                        { id: "invoice_no", header: [{ text: "Invoice No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" } },
                                                        { id: "delivery_date", header: [{ text: "Delivery Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                        { id: "supplier_code", header: [{ text: "Destination", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                        { id: "certificate_no", header: [{ text: "Certificate No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" } },
                                                        // { id: "case_tag_no", header: [{ text: "Case Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                                        { id: "fg_tag_no", header: [{ text: "FG Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                                        // { id: "work_order_no", header: [{ text: "Work Order No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                                        { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 180, css: { "text-align": "center" } },
                                                        { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" } },
                                                        { id: "package_no", header: [{ text: "pckage No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                                        /* {
                                                            id: "total_net_weight", header: [{ text: "Total Net", css: { "text-align": "center" } }, { text: " Weight(Kg.)", css: { "text-align": "center" } }], width: 100, css: { "text-align": "center" },
                                                            template: function (row) {
                                                                if (row.row_num == 1) {
                                                                    return row.total_net_weight;
                                                                }
                                                                else {
                                                                    return "";
                                                                }
                                                            }
                                                        },
                                                        {
                                                            id: "total_qty", header: [{ text: "Total Qty", css: { "text-align": "center" } }, { text: " (Pcs.)", css: { "text-align": "center" } }], width: 100, css: { "text-align": "center" },
                                                            template: function (row) {
                                                                if (row.row_num == 1) {
                                                                    return row.total_qty;
                                                                }
                                                                else {
                                                                    return "";
                                                                }
                                                            }
                                                        }, */
                                                        // { id: "net_weight_pcs", header: [{ text: "Weight/Pcs.", css: { "text-align": "center" } }, { text: " (Kg.)", css: { "text-align": "center" } }], width: 100, css: { "text-align": "center" }, footer: { text: "Total : ", css: { "text-align": "right" } } },
                                                        { id: "qty", header: [{ text: "Qty", css: { "text-align": "center" } }, { text: " (Pcs.)", css: { "text-align": "center" } }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 100, css: { "text-align": "center" } },
                                                        { id: "net_per_pallet", header: [{ text: "Net Weight", css: { "text-align": "center" } }, { text: " (Kg.)", css: { "text-align": "center" } }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 100, css: { "text-align": "center" }, format: webix.i18n.numberFormat },
                                                        { id: "steel_qty", header: [{ text: "Steel Pipe Qty", css: { "text-align": "center" } }, { text: " (Pcs.)", css: { "text-align": "center" } }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 100, css: { "text-align": "center" } },
                                                        // { id: "location_code", header: [{ text: "Location", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                        //footer: { content: "summColumn", css: { "text-align": "center" } }, format: webix.i18n.numberFormat }
                                                    ],
                                                    on:
                                                    {

                                                    },
                                                    onClick:
                                                    {
                                                        "mdi-file": function (e, t) {
                                                            var row = this.getItem(t);
                                                            var data = row.document_no;
                                                            window.open("print/doc/gtn.php?data=" + data, '_blank');
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
                                                                    loadDataByDocumentNo();

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
                                                                ajax('Shipping/IssuedGTNView_data.php', row, 32, function (json) {
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