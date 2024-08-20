var header_Repack = function () {
    var menuName = "Repack_", fd = "Packing/" + menuName + "data.php";

    function init() {
        loadDataByDocumentNo();
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
                saveAs(e.data, 'repack_' + dayjs().format('YYYYMMDDHHmmss') + ".xlsx");
                btn.enable();
                webix.message({ expire: 7000, text: "Export สำเร็จ" });
            }, false);
            worker.postMessage({ 'cmd': 'start', 'msg': data });
        }
        else { webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: "ไม่พบข้อมูลในตาราง", callback: function () { } }); }
    };


    function loadDataByDocumentNo() {
        var obj = ele('form1').getValues();
        ajax(fd, obj, 1, function (json) {
            if (json.data.header.length > 0) {
                reload_options_document_no();
                reload_options_delivery_date();
                reload_options_order_no_bydate(json.data.header[0].delivery_date);
                ele('form1').setValues(json.data.header[0]);
                setTable('dataT2', json.data.body);
                FindItem();
                ele('delivery_date').disable();
                ele('order_no').disable();
            }
            else {
                ele('form1').setValues('');
                ele('document_date').setValue(new Date());
                ele('dataT1').clearAll();
                ele('dataT2').clearAll();

                ele('delivery_date').enable();
                ele('order_no').enable();
            }

        });
    };


    function loadDataView(btn) {
        var obj = ele("form3").getValues();
        ajax("Packing/RepackView_data.php", obj, 1, function (json) {
            setTable("dataTREE", json.data);
        }, btn);
    };

    function reload_options_document_no() {
        var documentList = ele("document_no").getPopup().getList();
        documentList.clearAll();
        documentList.load("common/documentNo.php?type=2");
    };

    function reload_options_part(order_no) {
        var partList = ele("part_no").getPopup().getList();
        partList.clearAll();
        partList.load("common/orderNo.php?type=3&order_no=" + order_no);
    };

    function reload_options_delivery_date() {
        var deliveryDatetList = ele("delivery_date").getPopup().getList();
        deliveryDatetList.clearAll();
        deliveryDatetList.load("common/orderNo.php?type=4");
    };

    function reload_options_order_no_bydate(delivery_date) {
        var orderList = ele("order_no").getPopup().getList();
        orderList.clearAll();
        orderList.load("common/orderNo.php?type=5&delivery_date=" + delivery_date);
    };


    function AddItemAll(row) {
        var dataT1 = ele("dataT1");
        if (dataT1.count() == 0) {
            webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ไม่พบข้อมูลออเดอร์', callback: function () { } });
        } else {
            var obj1 = ele('form1').getValues();
            var obj = { ...obj1, ...row };
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
                        //ele('form1').setValues('');
                    }
                    else if (json.ch == 2) {
                        webix.alert({
                            title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                            }
                        });
                    }
                    /* else {
                        webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                    } */
                });
        }
    };

    function AddItem(row) {
        var obj1 = ele('form1').getValues();
        var obj = { ...obj1, ...row };

        $.post(fd, { obj: obj, type: 11 })
            .done(function (data) {
                var json = JSON.parse(data);
                if (json.ch == 1) {
                    webix.message({
                        text: "Complete",
                        type: "success",
                        expire: 2000,
                    });
                    loadDataByDocumentNo();
                    //ele('form1').setValues('');
                }
                else if (json.ch == 2) {
                    webix.alert({
                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                        }
                    });
                }
                /* else {
                    webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                } */
            });
        // }
    };


    function FindItem() {
        var obj = ele('form1').getValues();
        $.post(fd, { obj: obj, type: 3 })
            .done(function (data) {
                var json = JSON.parse(data);
                if (json.ch == 1) {
                    setTable('dataT1', json.data.body);

                    var document_no = ele('document_no').getValue();
                    if (document_no == '') {
                        ele('invoice_no').setValue(json.data.invoice_no);
                    }
                }
                else if (json.ch == 2) {
                    webix.alert({
                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                        }
                    });
                }
                // else {
                //     webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                // }
            });

    }


    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_Repack",
        body:
        {
            id: "Repack_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Repack", type: "header" },
                    {
                        padding: 8,
                        view: 'tabview',
                        id: $n("MasterTab"),
                        cells: [
                            {
                                header: "Repack",
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

                                                        if (view.config.name == 'order_no') {
                                                            FindItem();
                                                            focus('part_no');
                                                        } else if (view.config.name == 'part_no') {
                                                            FindItem();
                                                            focus('btn_find_order');
                                                        }

                                                    },
                                                },
                                                elements: [
                                                    {
                                                        rows: [
                                                            {
                                                                cols: [
                                                                    {},
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
                                                                                                    webix.ajax("print/doc/merge_workorder.php?data=" + data).then(function () {
                                                                                                        window.open("print/doc/files/WorkOrder/" + data + '.pdf', '_blank');
                                                                                                    });

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
                                                                                                ajax('Packing/RepackView_data.php', obj, 32, function (json) {
                                                                                                    webix.alert({
                                                                                                        title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ยกเลิกสำเร็จ', callback: function () {
                                                                                                            loadDataByDocumentNo();
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
                                                                    {}
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
                                                                                                    onChange: function (value) {
                                                                                                        if (value != '') {
                                                                                                            loadDataByDocumentNo();
                                                                                                            focus('order_no');
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            }),
                                                                                            vw1("datepicker", 'document_date', "Document Date", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, required: false, }),
                                                                                            vw1('text', 'invoice_no', 'Invoice No.', { disabled: false, required: false, hidden: 1, }),
                                                                                        ]
                                                                                    },
                                                                                    {
                                                                                        cols: [
                                                                                            // vw1("datepicker", 'delivery_date', "Delivery Date", {
                                                                                            //     value: '', stringResult: true,
                                                                                            //     ...datatableDateFormatShortNameDay, required: true,
                                                                                            // }),
                                                                                            vw1('richselect', 'delivery_date', 'Delivery Date', {
                                                                                                disabled: false, required: true, hidden: 0,
                                                                                                options: [''],
                                                                                                on: {
                                                                                                    onBlur: function () {
                                                                                                        this.getList().hide();
                                                                                                    },
                                                                                                    onItemClick: function () {
                                                                                                        reload_options_delivery_date();
                                                                                                    },
                                                                                                    onChange: function (value) {
                                                                                                        //var delivery_date = ele('delivery_date').getValue();
                                                                                                        if (value != '') {
                                                                                                            focus('order_no');
                                                                                                            ele('order_no').setValue('');
                                                                                                            //reload_options_order_no_bydate(delivery_date);
                                                                                                        }
                                                                                                    }
                                                                                                },
                                                                                            }),
                                                                                            vw1('combo', 'order_no', 'Order No.', {
                                                                                                disabled: false, required: true, hidden: 0,
                                                                                                options: [''],
                                                                                                on: {
                                                                                                    onBlur: function () {
                                                                                                        this.getList().hide();
                                                                                                    },
                                                                                                    onItemClick: function () {
                                                                                                        var delivery_date = ele('delivery_date').getValue();
                                                                                                        reload_options_order_no_bydate(delivery_date);
                                                                                                    },
                                                                                                    onChange: function (value) {
                                                                                                        if (value != '') {
                                                                                                            // focus('delivery_date');
                                                                                                            FindItem();
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            }),
                                                                                            vw1('combo', 'part_no', 'Part No.', {
                                                                                                disabled: false, required: true, hidden: 0,
                                                                                                suggest: "common/partMaster.php?type=1", Count: "5",
                                                                                                hidden: 1,
                                                                                                on: {
                                                                                                    onBlur: function () {
                                                                                                        this.getList().hide();
                                                                                                    },
                                                                                                    onItemClick: function () {
                                                                                                        var order_no = ele('order_no').getValue();
                                                                                                        reload_options_part(order_no);
                                                                                                    },
                                                                                                    onChange: function (value) {
                                                                                                        if (value != '') {
                                                                                                            FindItem();
                                                                                                        }
                                                                                                    }
                                                                                                },
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
                                                                                                                FindItem();
                                                                                                            }
                                                                                                        }
                                                                                                    }),
                                                                                                ]
                                                                                            },
                                                                                            {
                                                                                                rows: [
                                                                                                    {},
                                                                                                    vw1('button', 'btn_add_item', 'Repack (Auto)', {
                                                                                                        css: "webix_blue",
                                                                                                        icon: "mdi mdi-set-merge", type: "icon",
                                                                                                        tooltip: { template: "เพิ่ม", dx: 10, dy: 15 },
                                                                                                        width: 120,
                                                                                                        on: {
                                                                                                            onItemClick: function (id, e) {
                                                                                                                AddItemAll();
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

                                                                                                                focus('document_no');
                                                                                                                ele('dataT2').clearAll();
                                                                                                                ele('dataT1').clearAll();

                                                                                                                ele('order_no').enable();
                                                                                                                ele('delivery_date').enable();
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
                                                                                    view: "fieldset", label: "Case Tag", body:
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
                                                                                                height: 450,
                                                                                                css: "webix_font_size",
                                                                                                scheme:
                                                                                                {
                                                                                                    $change: function (item) {
                                                                                                        if (item.Pick == 'N') {
                                                                                                            item.$css = { "background": "#cfcfcf", "font-weight": "bold", "color": "#9e9e9e" };
                                                                                                        }
                                                                                                    }
                                                                                                },
                                                                                                columns: [
                                                                                                    /* {
                                                                                                        id: "icon_add", header: [{ text: "Add", css: { "text-align": "center" } }], width: 35, template: function (row) {
                                                                                                            return "<button class='mdi mdi-plus-circle webix_button' title='เพิ่ม' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #0C84C8; '></button>";
                                                                                                        }
                                                                                                    }, */
                                                                                                    { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: { "text-align": "center" }, width: 30, sort: "int" },
                                                                                                    { id: "part_tag_no", header: [{ text: "Part Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                                                    { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, footer: { text: "Total : ", css: { "text-align": "right" } } },
                                                                                                    { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, footer: { text: "Total : ", css: { "text-align": "right" } } },
                                                                                                    { id: "net_per_pcs", header: [{ text: "Net Weight", css: { "text-align": "center" } }, { text: "(Kg.)", css: { "text-align": "center" } }], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, format: webix.i18n.numberFormat },
                                                                                                    { id: "qty", header: [{ text: "Qty", css: { "text-align": "center" } }, { text: "(Pcs.)", css: { "text-align": "center" } }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 60, css: { "text-align": "center" }, hidden: 0 },
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
                                                                                    view: "fieldset", label: "FG Tag", body:
                                                                                    {
                                                                                        cols: [
                                                                                            {
                                                                                                view: "datatable", id: $n("dataT2"), navigation: true, select: true,
                                                                                                resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                                                                                                threeState: true, rowLineHeight: 25, rowHeight: 25,
                                                                                                datatype: "json", headerRowHeight: 25, leftSplit: 3,
                                                                                                editable: true,
                                                                                                navigation: true,
                                                                                                scrollX: true,
                                                                                                footer: true,
                                                                                                height: 450,
                                                                                                scheme:
                                                                                                {
                                                                                                    $change: function (item) {
                                                                                                        if (item.Pick == 'N') {
                                                                                                            item.$css = { "background": "#cfcfcf", "font-weight": "bold", "color": "#9e9e9e" };
                                                                                                        }
                                                                                                    }
                                                                                                },
                                                                                                columns: [
                                                                                                    /* {
                                                                                                        id: "icon_move", header: [{ text: "Move", css: { "text-align": "center" } },{ text: "FG", css: { "text-align": "center" } }], width: 40, template: function (row) {

                                                                                                            return "<button class='mdi mdi-archive-refresh-outline webix_button' title='ย้ายไป FG Tag อื่น' style='width:25px; height:20px; color:#ffffff; background-color: #f08502;'></button>";
                                                                                                        }
                                                                                                    }, */
                                                                                                    { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: { "text-align": "center" }, width: 30, sort: "int" },
                                                                                                    //{ id: "row_num", header: [{ text: "row", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                                                    { id: "work_order_no", header: [{ text: "Work Order No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                                                    { id: "fg_tag_no", header: [{ text: "FG Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                                                    { id: "part_tag_no", header: [{ text: "Part Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                                                    { id: "qty", header: [{ text: "Qty", css: { "text-align": "center" } }, { text: "(Pcs.)", css: { "text-align": "center" } }], width: 60, css: { "text-align": "center" }, hidden: 0 },
                                                                                                    { id: "net_per_pcs", header: [{ text: "Net", css: { "text-align": "center" } }, { text: "Weight(Kg.)", css: { "text-align": "center" } }], width: 60, css: { "text-align": "center" }, },
                                                                                                    { id: "qty_per_pallet", header: [{ text: "Qty/Pallet", css: { "text-align": "center" } }, { text: "(Pcs.)", css: { "text-align": "center" } }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 60, css: { "text-align": "center" }, hidden: 0 },
                                                                                                    { id: "net_per_pallet", header: [{ text: "Net/Pallet", css: { "text-align": "center" } }, { text: "(Kg.)", css: { "text-align": "center" } }], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, format: webix.i18n.numberFormat },
                                                                                                    { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                                                                    { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 180, css: { "text-align": "center" } },
                                                                                                ],
                                                                                                on: {
                                                                                                    "onItemClick": function (id) {
                                                                                                    },
                                                                                                },
                                                                                                onClick:
                                                                                                {
                                                                                                    "mdi-delete": function (e, t) {
                                                                                                        var obj1 = ele('form1').getValues();
                                                                                                        var row = this.getItem(t), dataTable = this;
                                                                                                        var obj = { ...obj1, ...row };
                                                                                                        console.log(obj);
                                                                                                        ajax(fd, obj, 31, function (json) {
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
                                                            id: "icon_doc", header: { text: "View", rotate: true, height: 30, css: { "text-align": "center" } }, width: 45, template: function (row) {
                                                                if (row.row_no == 1) {
                                                                    return "<button class='mdi mdi-file webix_button' title='ดูเอกสาร' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #556892;'></button>";
                                                                }
                                                                else {
                                                                    return "";
                                                                }
                                                            }
                                                        },
                                                        // { id: "No", header: "", css: { "text-align": "right" }, editor: "", width: 40 },
                                                        { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: { "text-align": "center" }, width: 30, sort: "int" },
                                                        { id: "document_no", header: [{ text: "Document No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        /* {
                                                            id: "document_no", header: [{ text: "Document No.", css: { "text-align": "center" } }, { content: "textFilter" }], editor: "", width: 180, css: { "text-align": "center" },
                                                            template: "{common.treetable()} #document_no#",
                                                        }, */
                                                        { id: "document_date", header: [{ text: "Document Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        { id: "order_no", header: [{ text: "Order No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" } },
                                                        // { id: "order_date", header: [{ text: "Order Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                        { id: "delivery_date", header: [{ text: "Delivery Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                        { id: "invoice_no", header: [{ text: "Invoice No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" } },
                                                        { id: "work_order_no", header: [{ text: "Work Order No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                                        { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 180, css: { "text-align": "center" } },
                                                        { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" } },
                                                        //{ id: "case_tag_no", header: [{ text: "Case Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                                        { id: "fg_tag_no", header: [{ text: "FG Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                                        { id: "total_net", header: [{ text: "Total Net", css: { "text-align": "center" } }, { text: " Weight(Kg.)", css: { "text-align": "center" } }], width: 100, css: { "text-align": "center" }, },
                                                        { id: "total_qty", header: [{ text: "Total Qty", css: { "text-align": "center" } }, { text: " (Pcs.)", css: { "text-align": "center" } }], width: 100, css: { "text-align": "center" }, },
                                                        { id: "case_tag_no", header: [{ text: "Case Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },

                                                        //{ id: "certificate_no", header: [{ text: "Certificate No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" } },
                                                        { id: "location_code", header: [{ text: "Location", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                        { id: "net_weight_pcs", header: [{ text: "Weight/Pcs.", css: { "text-align": "center" } }, { text: " (Kg.)", css: { "text-align": "center" } }], width: 100, css: { "text-align": "center" }, footer: { text: "Total : ", css: { "text-align": "right" } } },
                                                        { id: "qty_per_pallet", header: [{ text: "Qty", css: { "text-align": "center" } }, { text: " (Pcs.)", css: { "text-align": "center" } }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 100, css: { "text-align": "center" } },
                                                        { id: "net_per_pallet", header: [{ text: "Net Weight", css: { "text-align": "center" } }, { text: " (Kg.)", css: { "text-align": "center" } }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 100, css: { "text-align": "center" }, format: webix.i18n.numberFormat },
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
                                                            webix.ajax("print/doc/merge_workorder.php?data=" + data).then(function () {
                                                                window.open("print/doc/files/WorkOrder/" + data + '.pdf', '_blank');
                                                            });
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
                                                                ajax('Packing/RepackView_data.php', row, 32, function (json) {
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