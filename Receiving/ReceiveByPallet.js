var header_ReceiveByPallet = function () {
    var menuName = "ReceiveByPallet_", fd = "Receiving/" + menuName + "data.php";

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
                saveAs(e.data, 'receive_' + dayjs().format('YYYYMMDDHHmmss') + ".xlsx");
                btn.enable();
                webix.message({ expire: 7000, text: "Export สำเร็จ" });
            }, false);
            worker.postMessage({ 'cmd': 'start', 'msg': data });
        }
        else { webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: "ไม่พบข้อมูลในตาราง", callback: function () { } }); }
    };

    function openNewTab() {
        var temp = window.open(window.location.origin + "/hmt_wh/Receive", "_blank");
    }

    function loadData(btn) {
        ajax(fd, {}, 1, function (json) {
            if (json.data.header.length > 0) {
                reload_options_document_no();
                ele('form1').setValues(json.data.header[0]);
                setTable('dataT1', json.data.body);
            }
            else {
                ele('form1').setValues('');
                ele('document_date').setValue(new Date());
                ele('dataT1').clearAll();
            }

        }, btn);
    };

    function loadDataByDocumentNo() {
        var obj = ele('form1').getValues();
        ajax(fd, obj, 2, function (json) {
            if (json.data.header.length > 0) {
                reload_options_document_no();
                ele('form1').setValues(json.data.header[0]);
                setTable('dataT1', json.data.body);
            }
            else {
                ele('form1').setValues('');
                ele('document_date').setValue(new Date());
                ele('dataT1').clearAll();
            }

        });
    };


    function loadDataView(btn) {
        var obj = ele("form3").getValues();
        ajax("Receiving/ReceiveView_data.php", obj, 1, function (json) {
            setTable("dataTREE", json.data);
        }, btn);
    };

    function reload_options_part() {
        var partList = ele("part_no").getPopup().getList();
        partList.clearAll();
        partList.load("common/partMaster.php?type=2");
    };

    function reload_options_document_no() {
        var documentList = ele("document_no").getPopup().getList();
        documentList.clearAll();
        documentList.load("common/documentNo.php?type=1");
    };




    function AddItem() {
        var obj1 = ele('form1').getValues();
        var obj2 = ele('form2').getValues();
        var obj = { ...obj1, ...obj2 };
        var transaction_line_id = ele('transaction_line_id').getValue();
        if (transaction_line_id == '') {
            $.post(fd, { obj: obj, type: 11 })
                .done(function (data) {
                    var json = JSON.parse(data);
                    if (json.ch == 1) {
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


                        ele('pallet_no').setValue('');
                        ele('gross_kg').setValue('');
                        ele('net_per_pallet').setValue('');
                        ele('measurement_cbm').setValue('');
                        ele('qty').setValue('');
                        focus('pallet_no');
                    }
                    else if (json.ch == 2) {
                        webix.alert({
                            title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                                //ele('form2').setValues('');
                            }
                        });
                    }
                    else {
                        webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                    }
                });
        } else {
            $.post(fd, { obj: obj, type: 12 })
                .done(function (data) {
                    var json = JSON.parse(data);
                    if (json.ch == 1) {
                        webix.message({
                            text: "Complete",
                            type: "success",
                            expire: 2000,
                        });
                        loadDataByDocumentNo();
                        // ele('form2').setValues('');

                        ele('transaction_id').setValue('');
                        ele('transaction_line_id').setValue('');
                        ele('part_no').setValue('');
                        // ele('coil_lot_no').setValue('');
                        ele('pallet_no').setValue('');
                        ele('gross_kg').setValue('');
                        ele('net_per_pallet').setValue('');
                        ele('measurement_cbm').setValue('');
                        ele('qty').setValue('');
                        focus('pallet_no');
                    }
                    else if (json.ch == 2) {
                        webix.alert({
                            title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                                //ele('form2').setValues('');
                            }
                        });
                    }
                    else {
                        webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                    }
                });
        }

    }

    webix.ui(
        {
            view: "window", id: $n("win_upload"), modal: 1,
            head: "ข้อมูล Packing List", top: 50, position: "center",
            close: true, move: true,
            body:
            {
                view: "form", scroll: false, id: $n("win_upload_form"), width: 800,
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
                                                        vw1('textarea', 'plan', '', {
                                                            labelAlign: "top",
                                                            height: 250,
                                                            // placeholder: "[*Part_No]   [*Part_Name]   [*SNP]   [*Qty]"
                                                            //placeholder: "[*Part_No]    [*Qty]"
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
                                    vw1('button', 'save_plan', 'Save', {
                                        width: 120, css: "webix_green",
                                        icon: "mdi mdi-content-save", type: "icon",
                                        tooltip: { template: "บันทึก", dx: 10, dy: 15 },
                                        on: {
                                            onItemClick: function () {
                                                var obj1 = ele('form1').getValues();
                                                var obj2 = ele('win_upload_form').getValues();
                                                var obj = { ...obj1, ...obj2 };
                                                // ele('save_plan').disable();
                                                // ele('btn_upload_plan').disable();
                                                webix.confirm(
                                                    {
                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                        callback: function (res) {
                                                            if (res) {
                                                                ele('win_upload').hide();
                                                                ele('win_upload_form').setValues('');
                                                                $.post(fd, { obj: obj, type: 13 })
                                                                    .done(function (data) {
                                                                        var json = JSON.parse(data);
                                                                        if (json.ch == 1) {

                                                                            var document_no = ele('document_no').getValue();
                                                                            if (document_no == '') {
                                                                                loadData();
                                                                            } else {
                                                                                loadDataByDocumentNo();
                                                                            }

                                                                            ele('pallet_no').setValue('');
                                                                            ele('gross_kg').setValue('');
                                                                            ele('net_per_pallet').setValue('');
                                                                            ele('measurement_cbm').setValue('');
                                                                            ele('qty').setValue('');
                                                                            focus('pallet_no');

                                                                            webix.alert({
                                                                                title: "<b>ข้อความจากระบบ</b>", type: "alert-complete", ok: 'ตกลง', text: json.data, callback: function () {

                                                                                    ele('save_plan').enable();
                                                                                    ele('btn_upload_plan').enable();
                                                                                }
                                                                            });
                                                                        }
                                                                        else {
                                                                            webix.alert({
                                                                                title: "<b>ข้อความจากระบบ</b>", type: "alert-error", ok: 'ตกลง', text: json.data, callback: function () {
                                                                                    ele('save_plan').enable();
                                                                                    ele('btn_upload_plan').enable();
                                                                                }
                                                                            });
                                                                        }
                                                                    })
                                                            } else {
                                                                ele('save_plan').enable();
                                                                ele('btn_upload_plan').enable();
                                                            }
                                                        }
                                                    });
                                            }
                                        }
                                    }),
                                    vw1('button', 'cancel_plan', 'Cancel', {
                                        width: 120, css: "webix_red",
                                        icon: "mdi mdi-cancel", type: "icon",
                                        tooltip: { template: "ยกเลิก", dx: 10, dy: 15 },
                                        on: {
                                            onItemClick: function () {
                                                ele('win_upload').hide();
                                                ele('win_upload_form').setValues('');
                                                ele('save_plan').enable();
                                            }
                                        }
                                    }),
                                    vw1('button', 'btn_clear_plan', 'Clear', {
                                        width: 100, css: "webix_secondary",
                                        icon: "mdi mdi-backspace", type: "icon",
                                        tooltip: { template: "ล้างข้อมูล", dx: 10, dy: 15 },
                                        on: {
                                            onItemClick: function () {
                                                ele('win_upload_form').setValues('');
                                                ele('save_plan').enable();
                                            }
                                        }
                                    }),
                                    {}
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
        id: "header_ReceiveByPallet",
        body:
        {
            id: "ReceiveByPallet_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Receive", type: "header" },
                    {
                        padding: 8,
                        view: 'tabview',
                        id: $n("MasterTab"),
                        cells: [
                            {
                                header: "Receive",
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

                                                        if (view.config.name == 'declaration_no') {
                                                            focus('container_no');
                                                        } else if (view.config.name == 'container_no') {
                                                            focus('bl_no');
                                                        } else if (view.config.name == 'bl_no') {
                                                            focus('invoice_no');
                                                        } else if (view.config.name == 'invoice_no') {
                                                            focus('part_no');
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
                                                                                                    loadData();
                                                                                                    loadDataView();

                                                                                                    ele('transaction_id').setValue('');
                                                                                                    ele('transaction_line_id').setValue('');
                                                                                                    ele('part_no').setValue('');
                                                                                                    // ele('coil_lot_no').setValue('');
                                                                                                    ele('pallet_no').setValue('');
                                                                                                    ele('gross_kg').setValue('');
                                                                                                    ele('net_per_pallet').setValue('');
                                                                                                    ele('measurement_cbm').setValue('');
                                                                                                    ele('qty').setValue('');
                                                                                                    focus('part_no');
                                                                                                    var data = json.data;
                                                                                                    //window.open("print/doc/grn.php?data=" + json.data, '_blank');
                                                                                                    setTimeout(function () {

                                                                                                        webix.ajax("print/doc/case_tag_by_docno.php?data=" + data).then(function () {
                                                                                                            webix.ajax("print/doc/part_tag_by_docno.php?data=" + data).then(function () {
                                                                                                                webix.ajax("print/doc/merge_grn.php?data=" + data).then(function () {
                                                                                                                    window.open("print/doc/files/grn/TAG_" + data + '.pdf', '_blank');
                                                                                                                    window.open("print/doc/grn.php?data=" + data, '_blank')
                                                                                                                });
                                                                                                            });
                                                                                                        });
                                                                                                    }, 0);
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
                                                                                                ajax('Receiving/ReceiveView_data.php', obj, 32, function (json) {
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
                                                                                                disabled: false, hidden: 0,
                                                                                                on: {
                                                                                                    onBlur: function () {
                                                                                                        this.getList().hide();
                                                                                                    },
                                                                                                    onItemClick: function () {
                                                                                                        reload_options_document_no();
                                                                                                    },
                                                                                                    onChange: function () {
                                                                                                        loadDataByDocumentNo();
                                                                                                        //focus('pallet_no');
                                                                                                    }
                                                                                                }
                                                                                            }),
                                                                                            vw1("datepicker", 'document_date', "Document Date", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, required: false, }),
                                                                                            vw1('text', 'invoice_no', 'Invoice No.', { disabled: false, required: false, hidden: 0, }),
                                                                                        ]
                                                                                    },
                                                                                    {
                                                                                        cols: [
                                                                                            vw1('text', 'declaration_no', 'Declaration No.', { disabled: false, required: false, hidden: 0, }),
                                                                                            vw1('text', 'container_no', 'Container No.', { disabled: false, required: false, hidden: 0, }),
                                                                                            vw1('text', 'bl_no', 'BL No.', { disabled: false, required: false, hidden: 0, }),
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
                                                                                                                ele('form2').setValues('');
                                                                                                                ele('document_date').setValue(new Date());
                                                                                                                ele('dataT1').clearAll();
                                                                                                                focus('document_no');
                                                                                                                reload_options_document_no();
                                                                                                            }
                                                                                                        }
                                                                                                    }),
                                                                                                ]
                                                                                            }
                                                                                        ]
                                                                                    },
                                                                                ]
                                                                            },
                                                                        },
                                                                    ]
                                                            }
                                                        ]

                                                    },

                                                ]
                                            },
                                            {
                                                view: "form", scroll: false, id: $n('form2'),
                                                on: {
                                                    "onSubmit": function (view, e) {

                                                        if (view.config.name == 'part_no') {
                                                            focus('pallet_no');
                                                        } else if (view.config.name == 'pallet_no') {
                                                            focus('net_per_pallet');
                                                        } else if (view.config.name == 'net_per_pallet') {
                                                            focus('qty');
                                                        } else if (view.config.name == 'qty') {
                                                            focus('certificate_no');
                                                        } else if (view.config.name == 'certificate_no') {
                                                            focus('gross_kg');
                                                        } else if (view.config.name == 'gross_kg') {
                                                            focus('measurement_cbm');
                                                        } else if (view.config.name == 'measurement_cbm') {
                                                            AddItem();
                                                        }
                                                        // focus('remark');
                                                        // } else if (view.config.name == 'remark') {
                                                        //     AddItem();
                                                        // }

                                                    },
                                                },
                                                elements: [
                                                    {
                                                        rows: [
                                                            {
                                                                rows:
                                                                    [
                                                                        {
                                                                            view: "fieldset", label: "Item Packing list", body:
                                                                            {
                                                                                rows: [
                                                                                    {
                                                                                        cols: [
                                                                                            vw1('text', 'transaction_id', 'transaction_id', { disabled: false, required: false, hidden: 1, }),
                                                                                            vw1('text', 'transaction_line_id', 'transaction_line_id', { disabled: false, required: false, hidden: 1, }),
                                                                                            vw1('text', 'part_no', 'Part No.', {
                                                                                                disabled: false, required: true, hidden: 0,
                                                                                                suggest: "common/partMaster.php?type=1", Count: "2",
                                                                                                on: {
                                                                                                    // onBlur: function () {
                                                                                                    //     this.getList().hide();
                                                                                                    // },
                                                                                                    // onItemClick: function () {
                                                                                                    //     reload_options_part();
                                                                                                    // },
                                                                                                    // onChange: function () {
                                                                                                    //     focus('pallet_no');
                                                                                                    // }
                                                                                                },
                                                                                            }),
                                                                                            vw1('text', 'pallet_no', 'Pallet No.', { disabled: false, required: true, hidden: 0, }),
                                                                                        ]
                                                                                    },
                                                                                    {
                                                                                        cols: [
                                                                                            vw1('text', 'net_per_pallet', 'Net(Kg.)', { disabled: false, required: true, hidden: 0, }),
                                                                                            vw1('text', 'qty', 'Qty(pcs.)', { disabled: false, required: true, hidden: 0, }),
                                                                                        ]
                                                                                    },
                                                                                    {
                                                                                        cols: [
                                                                                            vw1('text', 'certificate_no', 'Certificate No.', { disabled: false, required: true, hidden: 0, }),
                                                                                            vw1('text', 'gross_kg', 'Gross(Kg.)', { disabled: false, required: false, hidden: 0, placeholder: '0' }),
                                                                                        ]
                                                                                    },
                                                                                    {
                                                                                        cols: [
                                                                                            vw1('text', 'measurement_cbm', 'Measurement(CBM)', { disabled: false, required: false, hidden: 0, placeholder: '0.00' }),
                                                                                            {},
                                                                                            vw1('text', 'remark', 'Remark', { disabled: false, required: false, hidden: 1, }),
                                                                                        ]
                                                                                    },
                                                                                    {
                                                                                        cols: [
                                                                                            {
                                                                                                view: "uploader", id: "uploader2", value: "2. PDS (PDF file)",
                                                                                                autosend: false, upload: fd + "?type=55",
                                                                                                on: {
                                                                                                    onBeforeFileAdd: function (file) {
                                                                                                        var type = file.type.toLowerCase();
                                                                                                        if (type === "pdf") {

                                                                                                        } else {
                                                                                                            webix.alert({
                                                                                                                title: "<b>System Message</b>",
                                                                                                                text: "รองรับเฉพาะไฟล์ PDF เท่านั้น",
                                                                                                                type: "alert-error",
                                                                                                            });
                                                                                                            return false;
                                                                                                        }
                                                                                                        $$("uploader2").disable();
                                                                                                    },
                                                                                                    onAfterFileAdd: function (item) {
                                                                                                        var formData = new FormData();
                                                                                                        this.files.data.each(function (obj, i) {
                                                                                                            formData.append("upload", obj.file);
                                                                                                        });
                                                                                                        $.ajax({
                                                                                                            type: "POST",
                                                                                                            cache: false,
                                                                                                            contentType: false,
                                                                                                            processData: false,
                                                                                                            url: fd + "?type=14",
                                                                                                            data: formData,
                                                                                                            success: function (data) {
                                                                                                                $$("uploader2").enable();
                                                                                                                var json = JSON.parse(data);
                                                                                                                if (json.ch === 1) {
                                                                                                                    webix.alert({
                                                                                                                        title: "<b>Completed</b>",
                                                                                                                        ok: "OK",
                                                                                                                        text: json.data,
                                                                                                                        callback: function () { },
                                                                                                                    });
                                                                                                                } else if (json.ch === 2) {
                                                                                                                    webix.alert({
                                                                                                                        title: "<b>Error</b>",
                                                                                                                        type: "alert-error",
                                                                                                                        text: json.data,
                                                                                                                    });
                                                                                                                }
                                                                                                            },
                                                                                                        });
                                                                                                    },
                                                                                                },
                                                                                            },

                                                                                            vw1("uploader", 'btn_upload', "Upload Packing List (Excel File)", {
                                                                                                multiple: false, autosend: false,
                                                                                                css: "webix_blue",
                                                                                                icon: "mdi mdi-upload", type: "icon",
                                                                                                tooltip: { template: "อัพโหลด Packing List (Excel)", dx: 10, dy: 15 },
                                                                                                hidden: 1,
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
                                                                                                        //ele("btn_upload").disable();
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
                                                                                                                //ele("btn_upload").enable();
                                                                                                                loadData();
                                                                                                                var json = JSON.parse(data);
                                                                                                                webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.mms, callback: function () { } });
                                                                                                            }
                                                                                                        });
                                                                                                    },
                                                                                                },
                                                                                            }),
                                                                                            vw1('button', 'btn_upload_plan', 'Upload Packing List', {
                                                                                                css: "webix_blue",
                                                                                                icon: "mdi mdi-upload", type: "icon",
                                                                                                tooltip: { template: "เพิ่มข้อมูล", dx: 10, dy: 15 },
                                                                                                hidden: 0,
                                                                                                on: {
                                                                                                    onItemClick: function () {
                                                                                                        ele('win_upload').show();
                                                                                                    }
                                                                                                }
                                                                                            }),
                                                                                            vw1('button', 'btn_add_item', 'Add', {
                                                                                                css: "webix_blue",
                                                                                                icon: "mdi mdi-plus-circle", type: "icon",
                                                                                                tooltip: { template: "เพิ่ม", dx: 10, dy: 15 },
                                                                                                on: {
                                                                                                    onItemClick: function (id, e) {
                                                                                                        AddItem();
                                                                                                    }
                                                                                                }
                                                                                            }),
                                                                                            vw1("button", 'btn_clear', "Clear", {
                                                                                                css: "webix_secondary",
                                                                                                icon: "mdi mdi-backspace", type: "icon",
                                                                                                tooltip: { template: "ล้างข้อมูล", dx: 10, dy: 15 },
                                                                                                on:
                                                                                                {
                                                                                                    onItemClick: function () {
                                                                                                        ele('form2').setValues('');
                                                                                                    }
                                                                                                }
                                                                                            }),
                                                                                        ]
                                                                                    }
                                                                                ]
                                                                            }
                                                                        },
                                                                        {
                                                                            view: "fieldset", label: "Data (ข้อมูลการรับ)", body:
                                                                            {
                                                                                cols: [
                                                                                    {
                                                                                        view: "datatable", id: $n("dataT1"), navigation: true, select: true,
                                                                                        resizeColumn: true, autoheight: true, multiselect: true, hover: "myhover",
                                                                                        threeState: true, rowLineHeight: 25, rowHeight: 25,
                                                                                        datatype: "json", headerRowHeight: 25, leftSplit: 2,
                                                                                        editable: true,
                                                                                        navigation: true,
                                                                                        scrollX: true,
                                                                                        footer: true,
                                                                                        scheme:
                                                                                        {
                                                                                            $change: function (item) {
                                                                                                if (item.Pick == 'N') {
                                                                                                    item.$css = { "background": "#cfcfcf", "font-weight": "bold", "color": "#9e9e9e" };
                                                                                                }
                                                                                            }
                                                                                        },
                                                                                        columns: [
                                                                                            {
                                                                                                id: "icon_edit", header: [{ text: "Edit", css: { "text-align": "center" } }], width: 40, template: function (row) {
                                                                                                    return "<button class='mdi mdi-pencil webix_button' title='แก้ไขข้อมูล' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #f08502;'></button>";
                                                                                                }
                                                                                            },
                                                                                            {
                                                                                                id: "icon_delete", header: { text: "Delete", css: { "text-align": "center" } }, width: 50, template: function (row) {
                                                                                                    return "<button class='mdi mdi-delete webix_button' title='ลบ' style='width:25px; height:20px; color:#556892; background-color: #dadee0;'></button>";
                                                                                                }
                                                                                            },
                                                                                            { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: { "text-align": "center" }, width: 35, sort: "int" },
                                                                                            { id: "case_tag_no", header: [{ text: "Case Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" } },
                                                                                            { id: "pallet_no", header: [{ text: "Pallet No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" } },
                                                                                            { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" } },
                                                                                            { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, footer: { text: "Total : ", css: { "text-align": "right" } } },
                                                                                            { id: "qty", header: [{ text: "Qty(pcs.)", css: { "text-align": "center" } }, { content: "numberFilter" }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 100, css: { "text-align": "center" } },
                                                                                            { id: "net_per_pallet", header: [{ text: "Net(Kg.)", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, },
                                                                                            { id: "gross_kg", header: [{ text: "Gross(Kg.)", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } } },
                                                                                            { id: "measurement_cbm", header: [{ text: "Measurement(CBM)", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, format: webix.i18n.numberFormat },
                                                                                            // { id: "coil_lot_no", header: [{ text: "Coil Lot No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                                                            { id: "certificate_no", header: [{ text: "Certificate No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, hidden: 0 },
                                                                                            { id: "remark", header: [{ text: "Remark", css: { "text-align": "center" } }, { content: "textFilter" }], width: 250, css: { "text-align": "center" }, hidden: 1 },

                                                                                        ],
                                                                                        on: {
                                                                                            "onItemClick": function (id) {
                                                                                            },
                                                                                        },
                                                                                        onClick:
                                                                                        {
                                                                                            "mdi-pencil": function (e, t) {
                                                                                                var row = this.getItem(t), dataTable = this;
                                                                                                ele('form2').setValues(row);
                                                                                                reload_options_part();

                                                                                            },
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
                                                    view: "datatable", id: $n('dataTREE'), navigation: true, select: "row", editaction: "custom",
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
                                                            id: "doc", header: { text: "View", rotate: true, height: 30, css: { "text-align": "center" } }, width: 45, template: function (row) {
                                                                if (row.row_no == 1) {
                                                                    return "<button class='mdi mdi-file webix_button' title='ดูเอกสาร' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #556892;'></button>";
                                                                }
                                                                else {
                                                                    return "";
                                                                }
                                                            }
                                                        },
                                                        { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: { "text-align": "center" }, width: 30, sort: "int" },
                                                        //{ id: "No", header: "", css: { "text-align": "right" }, editor: "", width: 40 },
                                                        // {
                                                        //     id: "document_no", header: [{ text: "Document No.", css: { "text-align": "center" } }, { content: "textFilter" }], editor: "", width: 180, css: { "text-align": "center" },
                                                        //     template: "{common.treetable()} #document_no#", footer: [{ text: "Total :", css: { "text-align": "left" } }],
                                                        // },
                                                        { id: "document_no", header: [{ text: "Document No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        { id: "document_date", header: [{ text: "Document Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        { id: "transaction_type", header: [{ text: "Transaction Type", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        { id: "pallet_no", header: [{ text: "Pallet No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" } },
                                                        // { id: "coil_lot_no", header: [{ text: "Coil Lot No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                        { id: "invoice_no", header: [{ text: "Invoice No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        { id: "case_tag_no", header: [{ text: "Case Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" } },
                                                        { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" } },
                                                        { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, },
                                                        { id: "supplier_code", header: [{ text: "Destination", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, footer: { text: "Total : ", css: { "text-align": "right" } } },
                                                        { id: "qty", header: [{ text: "Qty(pcs.)", css: { "text-align": "center" } }, { content: "numberFilter" }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 100, css: { "text-align": "center" } },
                                                        { id: "gross_kg", header: [{ text: "Gross(Kg.)", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } } },
                                                        { id: "net_per_pallet", header: [{ text: "Net(Kg.)", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, },
                                                        { id: "measurement_cbm", header: [{ text: "Measurement(CBM)", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, format: webix.i18n.numberFormat },
                                                        { id: "certificate_no", header: [{ text: "Certificate No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                        // { id: "remark", header: [{ text: "Remark", css: { "text-align": "center" } }, { content: "textFilter" }], width: 250, css: { "text-align": "center" } },
                                                        // { id: "declaration_no", header: [{ text: "Declaration No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        // { id: "container_no", header: [{ text: "Container No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        // { id: "bl_no", header: [{ text: "BL No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                    ],
                                                    on:
                                                    {

                                                    },
                                                    onClick:
                                                    {
                                                        "mdi-file": function (e, t) {
                                                            var row = this.getItem(t);
                                                            var data = row.document_no;

                                                            setTimeout(function () {

                                                                webix.ajax("print/doc/case_tag_by_docno.php?data=" + data).then(function () {
                                                                    webix.ajax("print/doc/part_tag_by_docno.php?data=" + data).then(function () {
                                                                        webix.ajax("print/doc/merge_grn.php?data=" + data).then(function () {
                                                                            window.open("print/doc/files/grn/TAG_" + data + '.pdf', '_blank');
                                                                            window.open("print/doc/grn.php?data=" + data, '_blank')
                                                                        });
                                                                    });
                                                                });
                                                            }, 0);
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
                                                                ajax('Receiving/ReceiveView_data.php', row, 32, function (json) {
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