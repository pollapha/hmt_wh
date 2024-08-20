var header_OrderRepack = function () {
    var menuName = "OrderRepack_", fd = "Packing/" + menuName + "data.php";

    function init() {
        loadDataByOrderNo();
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

    webix.ui.datafilter.complexFunction = webix.extend({
        refresh: (master, node, value) => {
            let max = 7000;
            let sum_net = 0;
            master.data.order.forEach(id => {
                const obj = master.getItem(id);
                sum_net += obj.net_per_pcs || 0;
            });
            const result = max - sum_net;
            node.innerHTML =
                new Intl.NumberFormat().format(result);
        }
    }, webix.ui.datafilter.summColumn);

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
                saveAs(e.data, 'sale order_' + dayjs().format('YYYYMMDDHHmmss') + ".xlsx");
                btn.enable();
                webix.message({ expire: 7000, text: "Export สำเร็จ" });
            }, false);
            worker.postMessage({ 'cmd': 'start', 'msg': data });
        }
        else { webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: "ไม่พบข้อมูลในตาราง", callback: function () { } }); }
    };


    function reload_options_part(supplier_code) {
        var partList = ele("part_no").getPopup().getList();
        partList.clearAll();
        partList.load("common/partMaster.php?type=4&supplier_code=" + supplier_code);
    };

    function reload_options_supplier() {
        var supplierList = ele("supplier_code").getPopup().getList();
        supplierList.clearAll();
        supplierList.load("common/supplierMaster.php?type=2");
    };

    function reload_options_order_no() {
        var orderList = ele("order_no").getPopup().getList();
        orderList.clearAll();
        orderList.load("common/orderNo.php?type=1");
    };


    function setDeliveryDate(value) {
        var date = value;
        var day = date.getDay();
        if (day == '1') {
            var delivery_date = new Date(date.getFullYear(), date.getMonth(), date.getDate() + 2);
        } else if (day == '3') {
            var delivery_date = new Date(date.getFullYear(), date.getMonth(), date.getDate() + 2);
        } else if (day == '5') {
            var delivery_date = new Date(date.getFullYear(), date.getMonth(), date.getDate() + 3);
        }
        ele('delivery_date').setValue(delivery_date);
    }


    function loadDataByOrderNo() {
        var obj = ele('form1').getValues();
        ajax(fd, obj, 1, function (json) {
            if (json.data.header.length > 0) {
                reload_options_order_no();
                reload_options_supplier();
                ele('order_no').setValue(json.data.header[0].order_no);
                ele('supplier_code').setValue(json.data.header[0].supplier_code);
                ele('order_date').setValue(json.data.header[0].order_date);
                ele('delivery_date').setValue(json.data.header[0].delivery_date);
                ele('repack').setValue(json.data.header[0].repack);
                setTable('dataT2', json.data.body);
                ele('supplier_code').disable();
                ele('part_no').enable();
                FindItem();
            } else {
                ele('form1').setValues('');
                ele('order_date').setValue(new Date());
                ele('dataT1').clearAll();
                ele('dataT2').clearAll();
                ele('supplier_code').enable();
                ele('part_no').disable();
            }
        });
    };


    function FindItemByCaseTag(row) {
        $.post(fd, { obj: row, type: 4 })
            .done(function (data) {

                var json = JSON.parse(data);
                if (json.ch == 1) {
                    setTable('dataSplit', json.data.body);
                }
                else if (json.ch == 2) {
                    webix.alert({
                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                            ele('dataSplit').clearAll();
                            //ele('form2').setValues('');
                        }
                    });
                }
                // else {
                //     webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                // }
            });

    }

    function FindItem() {
        var obj = ele('form1').getValues();
        $.post(fd, { obj: obj, type: 3 })
            .done(function (data) {

                var json = JSON.parse(data);
                if (json.ch == 1) {
                    setTable('dataT1', json.data.body);
                }
                else if (json.ch == 2) {
                    webix.alert({
                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                            ele('dataT1').clearAll();
                            //ele('form2').setValues('');
                        }
                    });
                }
                // else {
                //     webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                // }
            });

    }

    function AddItem(row, type, t) {
        var obj1 = ele('form1').getValues();
        var obj = { ...obj1, ...row };
        var dataTable = ele('dataT1');
        // dataTable.disable();
        $.post(fd, { obj: obj, type: type })
            .done(function (data) {
                var json = JSON.parse(data);
                if (json.ch == 1) {
                    webix.message({
                        text: "Complete",
                        type: "success",
                        expire: 200,
                    });
                    
                    loadDataByOrderNo();

                    dataTable.enable();

                    if (type == 12) {
                        console.log(row);
                        console.log(t);
                        row.hidden = row.hidden ? false : true;
                        ele('dataSplit').updateItem(t, row);
                        ele('dataSplit').filter(function (obj) {
                            return !obj.hidden;
                        });
                    }

                    // focus('part_no');
                }
                else if (json.ch == 2) {
                    webix.alert({
                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                            dataTable.enable();
                            //ele('form2').setValues('');
                        }
                    });
                }
                /* else {
                    webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                } */
            });
    };

    function loadDataView(btn) {
        var obj = ele("form2").getValues();
        ajax("Packing/OrderRepackView_data.php", obj, 1, function (json) {
            setTable("dataTREE", json.data);
        }, btn);
    };


    webix.ui(
        {
            view: "window", id: $n("win_part_tag"), modal: 1,
            head: "ข้อมูล Part Tag (Split)",
            //top: 50,
            //position: "center",
            position: function (state) {
                state.left = 40; // fixed values
                state.top = 50;
            },
            close: true, move: true,
            body:
            {
                view: "form", scroll: false, id: $n("win_part_tag_form"), width: 630,
                elements:
                    [
                        {
                            view: "datatable", id: $n("dataSplit"), navigation: true, select: true,
                            resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                            threeState: true, rowLineHeight: 25, rowHeight: 25,
                            datatype: "json", headerRowHeight: 25, leftSplit: 2,
                            editable: true,
                            navigation: true,
                            scrollX: true,
                            footer: true,
                            height: 300,
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
                                    id: "icon_add", header: [{ text: "Add", css: { "text-align": "center" } }], width: 35, template: function (row) {
                                        return "<button class='mdi mdi-plus-circle webix_button' title='เพิ่ม' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #0C84C8; '></button>";
                                    }
                                },
                                { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: { "text-align": "center" }, width: 30, sort: "int" },
                                { id: "part_tag_no", header: [{ text: "Part Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 180, css: { "text-align": "center" }, footer: { text: "Total : ", css: { "text-align": "right" } } },
                                { id: "net_per_pcs", header: [{ text: "Net Weight", css: { "text-align": "center" } }, { text: "(Kg.)", css: { "text-align": "center" } }], width: 65, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, format: webix.i18n.numberFormat },
                                { id: "qty", header: [{ text: "Qty", css: { "text-align": "center" } }, { content: "numberFilter" }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 40, css: { "text-align": "center" }, hidden: 1 },
                                //{ id: "package_type", header: [{ text: "Package Type", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                            ],
                            onClick:
                            {
                                "mdi-plus-circle": function (e, t) {
                                    var row = this.getItem(t), dataTable = this;
                                    AddItem(row, 12, t);

                                },

                            },
                        },
                    ],
                rules:
                {
                }
            }
        });




    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_OrderRepack",
        body:
        {
            id: "OrderRepack_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Order", type: "header" },
                    {
                        padding: 8,
                        view: 'tabview',
                        id: $n("MasterTab"),
                        cells: [
                            {
                                header: "Create Order",
                                id: $n('tab1'),
                                view: "form",
                                scroll: false,
                                //hidden: 1,
                                elements: [
                                    {
                                        rows: [
                                            {
                                                view: "form",
                                                id: $n("form1"),
                                                on: {
                                                    "onSubmit": function (view, e) {

                                                        if (view.config.name == 'supplier_code') {
                                                            focus('part_no');
                                                        } else if (view.config.name == 'part_no') {
                                                            focus('btn_save');
                                                        }
                                                    },
                                                },
                                                elements:
                                                    [
                                                        {
                                                            view: "fieldset", label: "Choose Order", body:
                                                            {
                                                                rows: [
                                                                    {
                                                                        cols: [
                                                                            vw1('combo', 'order_no', 'Order No.', {
                                                                                options: [''],
                                                                                disabled: false, hidden: 0, required:false,
                                                                                on: {
                                                                                    onBlur: function () {
                                                                                        this.getList().hide();
                                                                                    },
                                                                                    onItemClick: function () {
                                                                                        reload_options_order_no();
                                                                                    },
                                                                                    onChange: function (value) {
                                                                                        if (value != '') {
                                                                                            loadDataByOrderNo();
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }),
                                                                            vw1('combo', 'supplier_code', 'Destination', {
                                                                                disabled: false, required: true, hidden: 0,
                                                                                suggest: "common/supplierMaster.php?type=1", Count: "5",
                                                                                on: {
                                                                                    onBlur: function () {
                                                                                        this.getList().hide();
                                                                                    },
                                                                                    onItemClick: function () {
                                                                                        reload_options_supplier();
                                                                                    }, onChange: function (value) {
                                                                                        ele('repack').setValue('Yes');
                                                                                        /* if (value == 'HMTH') {
                                                                                            ele('repack').setValue('Yes');
                                                                                        } else if (value == 'NKAPM') {
                                                                                            ele('repack').setValue('No');
                                                                                        } */
                                                                                    }
                                                                                },
                                                                            }),
                                                                            vw1("datepicker", 'order_date', "Order Date", {
                                                                                value: dayjs().format("YYYY-MM-DD"), stringResult: true,
                                                                                ...datatableDateFormatShortNameDay, required: true,
                                                                                on: {
                                                                                    onChange: function (value) {
                                                                                        if (value != '') {
                                                                                            setDeliveryDate(value);
                                                                                        }
                                                                                    }
                                                                                },
                                                                            }),
                                                                            vw1("datepicker", 'delivery_date', "Delivery Date", {
                                                                                value: '', stringResult: true,
                                                                                ...datatableDateFormatShortNameDay, required: true,
                                                                            }),
                                                                            vw1('richselect', 'repack', 'repack', {
                                                                                hidden: 1,
                                                                                value: '', options: [
                                                                                    { id: 'Yes', value: "Yes" },
                                                                                    { id: 'No', value: "No" },
                                                                                ]
                                                                            }),
                                                                            {
                                                                                rows: [
                                                                                    {},
                                                                                    vw1('button', 'btn_find', 'Find', {
                                                                                        width: 120,
                                                                                        css: "webix_primary",
                                                                                        icon: "mdi mdi-magnify", type: "icon",
                                                                                        tooltip: { template: "ค้นหา", dx: 10, dy: 15 },
                                                                                        on: {
                                                                                            onItemClick: function (id, e) {
                                                                                                FindItem();
                                                                                                ele('part_no').enable();
                                                                                            }
                                                                                        }
                                                                                    }),
                                                                                ]
                                                                            },
                                                                            {
                                                                                rows: [
                                                                                    {},
                                                                                    vw1("button", 'btn_clear', "Clear", {
                                                                                        width: 120,
                                                                                        css: "webix_secondary",
                                                                                        icon: "mdi mdi-backspace", type: "icon",
                                                                                        tooltip: { template: "ล้างข้อมูล", dx: 10, dy: 15 },
                                                                                        on:
                                                                                        {
                                                                                            onItemClick: function () {
                                                                                                ele('order_no').setValue('');
                                                                                                ele('supplier_code').setValue('');
                                                                                                ele('repack').setValue('');

                                                                                                ele('order_date').setValue(new Date());
                                                                                                ele('dataT1').clearAll();
                                                                                                ele('dataT2').clearAll();

                                                                                                ele('supplier_code').enable();
                                                                                                ele('part_no').disable();
                                                                                            }
                                                                                        }
                                                                                    }),
                                                                                ]
                                                                            }
                                                                        ]
                                                                    },
                                                                    {
                                                                        rows: [
                                                                            {
                                                                                cols:
                                                                                    [
                                                                                        vw1('combo', 'part_no', 'Part No.', {
                                                                                            disabled: false, required: true, hidden: 1,
                                                                                            suggest: "common/partMaster.php?type=1", Count: "5",
                                                                                            disabled: true,
                                                                                            on: {
                                                                                                onBlur: function () {
                                                                                                    this.getList().hide();
                                                                                                },
                                                                                                onItemClick: function () {
                                                                                                    var supplier_code = ele('supplier_code').getValue();
                                                                                                    reload_options_part(supplier_code);
                                                                                                },
                                                                                                /* onChange: function (value) {
                                                                                                    if (value != '') {
                                                                                                        FindItem();
                                                                                                    }
                                                                                                } */
                                                                                            },
                                                                                        }),
                                                                                        {
                                                                                            rows: [
                                                                                                //{},
                                                                                                vw1("button", 'btn_clear_body', "Clear", {
                                                                                                    width: 120,
                                                                                                    css: "webix_secondary",
                                                                                                    icon: "mdi mdi-backspace", type: "icon",
                                                                                                    tooltip: { template: "ล้างข้อมูล", dx: 10, dy: 15 },
                                                                                                    hidden: 1,
                                                                                                    on:
                                                                                                    {
                                                                                                        onItemClick: function () {
                                                                                                            ele('part_no').setValue('');
                                                                                                            FindItem();
                                                                                                        }
                                                                                                    }
                                                                                                }),
                                                                                            ]
                                                                                        }
                                                                                    ]
                                                                            },
                                                                            {
                                                                                cols: [
                                                                                    {},
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
                                                                                                                ajax(fd, obj, 41, function (json) {
                                                                                                                    loadDataByOrderNo();
                                                                                                                    var dos_no = json.data.dos_no;
                                                                                                                    loadDataView();

                                                                                                                    /* if (dos_no != '') {
                                                                                                                        window.open("print/doc/dos.php?data=" + dos_no, '_blank');
                                                                                                                    } */
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
                                                                                                                ajax('Packing/OrderRepackView_data.php', obj, 32, function (json) {
                                                                                                                    webix.alert({
                                                                                                                        title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ยกเลิกสำเร็จ', callback: function () {
                                                                                                                            loadDataByOrderNo();
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
                                                                        ]
                                                                    }
                                                                ],
                                                            },
                                                        },
                                                        {
                                                            cols: [
                                                                {
                                                                    view: "fieldset", label: "Case Tag", body:
                                                                    {
                                                                        cols: [
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
                                                                                        id: "icon_add", header: [{ text: "Add", css: { "text-align": "center" } }], width: 35, template: function (row) {
                                                                                            return "<button class='mdi mdi-plus-circle webix_button' title='เพิ่ม' style=' height:22px; font-size:12px; color:#ffffff; background-color: #0C84C8; '></button>";
                                                                                        }
                                                                                    },
                                                                                    {
                                                                                        id: "icon_split", header: [{ text: "Split", css: { "text-align": "center" } }], width: 35, template: function (row) {
                                                                                            return "<button class='mdi mdi-set-split webix_button' title='แบ่ง' style=' height:22px; font-size:16px; color:#ffffff; background-color: #fa8b02; '></button>";
                                                                                        }
                                                                                    },
                                                                                    { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: { "text-align": "center" }, width: 30, sort: "int" },
                                                                                    { id: "pallet_no", header: [{ text: "Pallet No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                                    { id: "case_tag_no", header: [{ text: "Case Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                                                    // { id: "part_tag_no", header: [{ text: "Part Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                                    { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" },  },
                                                                                    { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 180, css: { "text-align": "center" }, footer: { text: "Total : ", css: { "text-align": "right" } } },
                                                                                    { id: "net_per_pallet", header: [{ text: "Net/Pallet(Kg.)", css: { "text-align": "center" } }, { content: "numberFilter" }], width: 80, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, format: webix.i18n.numberFormat },
                                                                                    { id: "qty", header: [{ text: "Qty", css: { "text-align": "center" } }, { content: "numberFilter" }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 40, css: { "text-align": "center" }, hidden: 0 },
                                                                                    { id: "package_type", header: [{ text: "Package Type", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                                                    { id: "supplier_code", header: [{ text: "supplier_code", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 1 },
                                                                                ],
                                                                                onClick:
                                                                                {
                                                                                    "mdi-plus-circle": function (e, t) {
                                                                                        var row = this.getItem(t), dataTable = this;
                                                                                        AddItem(row, 11, t);
                                                                                    },
                                                                                    "mdi-set-split": function (e, t) {
                                                                                        var row = this.getItem(t), dataTable = this;
                                                                                        ele('win_part_tag').show();
                                                                                        FindItemByCaseTag(row);
                                                                                    },
                                                                                },
                                                                            },
                                                                        ]
                                                                    }
                                                                },
                                                                {
                                                                    view: "fieldset", label: "Order Data", body:
                                                                    {
                                                                        cols: [
                                                                            {
                                                                                view: "datatable", id: $n("dataT2"), navigation: true, select: true,
                                                                                resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                                                                                threeState: true, rowLineHeight: 25, rowHeight: 25,
                                                                                datatype: "json", headerRowHeight: 25, leftSplit: 2,
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
                                                                                    {
                                                                                        id: "icon_delete", header: { text: "Delete", css: { "text-align": "center" } }, width: 35, template: function (row) {
                                                                                            return "<button class='mdi mdi-delete webix_button' title='ลบ' style='width:25px; height:20px; color:#556892; background-color: #dadee0;'></button>";
                                                                                        }
                                                                                    },
                                                                                    { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: { "text-align": "center" }, width: 30, sort: "int" },
                                                                                    { id: "part_tag_no", header: [{ text: "Part Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                                                                    { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                                                    { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 180, css: { "text-align": "center" }, footer: { text: "Total : ", css: { "text-align": "right" } } },
                                                                                    {
                                                                                        id: "net_per_pcs", header: [{ text: "Net Weight", css: { "text-align": "center" } }, { text: "(Kg.)", css: { "text-align": "center" } }], width: 65, css: { "text-align": "center" },
                                                                                        footer: { content: "summColumn", css: { "text-align": "center" } }, format: webix.i18n.numberFormat
                                                                                    },
                                                                                    { id: "sum_net", header: [{ text: "Total Net", css: { "text-align": "center" } }, { text: "(By Part)", css: { "text-align": "center" } }], width: 60, css: { "text-align": "center" }, format: webix.i18n.numberFormat },
                                                                                    {
                                                                                        id: "left_net",
                                                                                        header: [{ text: "เหลือ", css: { "text-align": "center" } }],
                                                                                        width: 60,
                                                                                        footer: [
                                                                                            { content: "complexFunction", css: { "text-align": "center" } }
                                                                                        ]
                                                                                    },
                                                                                    { id: "work_order_no", header: [{ text: "Work Order No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 1 },
                                                                                    //{ id: "qty", header: [{ text: "Qty", css: { "text-align": "center" } }, { content: "numberFilter" }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 60, css: { "text-align": "center" }, hidden: 0 },
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
                                                                                            loadDataByOrderNo();
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
                                        id: $n("form2"),
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
                                                    pager: $n("Master_pagerA"),
                                                    datafetch: 10, // Number of rows to fetch at a time
                                                    loadahead: 100, // Number of rows to prefetch
                                                    /* scheme:
                                                    {
                                                        $change: function (item) {
                                                            if (item.order_status == 'Packing') {
                                                                item.$css = { "color": "#afeac8", "font-weight": "bold" };
                                                            }
                                                        }
                                                    }, */
                                                    columns: [
                                                        {
                                                            id: "icon_cancel", header: { text: "Cancel", rotate: true, height: 30, css: { "text-align": "center" } }, width: 40, template: function (row) {
                                                                if (row.row_num == 1) {
                                                                    return "<button class='mdi mdi-cancel webix_button' title='ยกเลิกเอกสาร' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #ed3755;'></button>";
                                                                }
                                                                else {
                                                                    return "";
                                                                }
                                                            }
                                                        },
                                                        {
                                                            id: "icon_edit", header: [{ text: "Edit", rotate: true, height: 30, css: { "text-align": "center" } }], width: 40, template: function (row) {
                                                                if (row.row_num == 1) {
                                                                    return "<button class='mdi mdi-pencil webix_button' title='แก้ไขเอกสาร' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #f08502;'></button>";
                                                                }
                                                                else {
                                                                    return "";
                                                                }
                                                            }
                                                        },
                                                        // {
                                                        //     id: "doc", header: { text: "View", rotate: true, height: 30, css: { "text-align": "center" } }, width: 45, template: function (row) {
                                                        //         if (row.row_num == 1) {
                                                        //             return "<button class='mdi mdi-file webix_button' title='ดูเอกสาร' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #556892;'></button>";
                                                        //         }
                                                        //         else {
                                                        //             return "";
                                                        //         }
                                                        //     }
                                                        // },
                                                        { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: { "text-align": "center" }, width: 30, sort: "int" },
                                                        {
                                                            id: "order_status", header: [{ text: "Order Status", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "left" }, hidden: 0, template: function (row) {
                                                                if (row.order_status == 'Packing') {
                                                                    return "<div class='mdi mdi-circle-medium' style='color:#07bcff;'>" + row.order_status + "</div>";
                                                                } else if (row.order_status == 'Packed') {
                                                                    return "<div class='mdi mdi-circle-medium' style='color:#4556ff;'>" + row.order_status + "</div>";
                                                                } else if (row.order_status == 'Picking') {
                                                                    return "<div class='mdi mdi-circle-medium' style='color:#fe51af;'>" + row.order_status + "</div>";
                                                                } else if (row.order_status == 'In-transit') {
                                                                    return "<div class='mdi mdi-circle-medium' style='color:#eb9240;'>" + row.order_status + "</div>";
                                                                } else if (row.order_status == 'Delivered') {
                                                                    return "<div class='mdi mdi-circle-medium' style='color:#00c69d;'>" + row.order_status + "</div>";
                                                                } else {
                                                                    return "<div class='mdi mdi-circle-medium'>" + row.order_status + "</div>";
                                                                }
                                                            }
                                                        },
                                                        {
                                                            id: "delivery_status", header: [{ text: "Delivery Status", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "left" }, hidden: 0, template: function (row) {
                                                                if (row.delivery_status == 'On process') {
                                                                    return "<div class='mdi mdi-circle-medium' style='color:#4556ff;'>" + row.delivery_status + "</div>";
                                                                } else if (row.delivery_status == 'Delay') {
                                                                    return "<div class='mdi mdi-circle-medium' style='color:#e35837;'>" + row.delivery_status + "</div>";
                                                                } else if (row.delivery_status == 'Delivery delay') {
                                                                    return "<div class='mdi mdi-circle-medium' style='color:#fd7a38;'>" + row.delivery_status + "</div>";
                                                                } else if (row.delivery_status == 'Delivery on-time') {
                                                                    return "<div class='mdi mdi-circle-medium' style='color:#2eb88a;'>" + row.delivery_status + "</div>";
                                                                } else if (row.delivery_status == 'Delivery early') {
                                                                    return "<div class='mdi mdi-circle-medium' style='color:#45b3ff;'>" + row.delivery_status + "</div>";
                                                                } else {
                                                                    var date = new Date().toJSON().slice(0, 10);
                                                                    if (row.delivery_status == 'Pending' && date > row.delivery_date) {
                                                                        return "<div class='mdi mdi-circle-medium' style='color:#e35837;'>" + 'Delay' + "</div>";
                                                                    } else {
                                                                        return "<div class='mdi mdi-circle-medium'>" + row.delivery_status + "</div>";
                                                                    }
                                                                }
                                                            }
                                                        },
                                                        { id: "order_no", header: [{ text: "Order No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, hidden: 0 },
                                                        { id: "order_date", header: [{ text: "Order Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        { id: "delivery_date", header: [{ text: "Delivery Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        { id: "supplier_code", header: [{ text: "Destination", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                                        { id: "pallet_no", header: [{ text: "Pallet No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                                        { id: "case_tag_no", header: [{ text: "Case Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                                        { id: "part_tag_no", header: [{ text: "Part Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                                        { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" } },
                                                        { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" } },
                                                        { id: "net_per_pcs", header: [{ text: "Net(Kg.)", css: { "text-align": "center" } }, { content: "textFilter" }], width: 80, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, format: webix.i18n.numberFormat },
                                                        { id: "qty", header: [{ text: "Qty(pcs.)", css: { "text-align": "center" } }, { content: "numberFilter" }], width: 80, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, format: webix.i18n.numberFormat },
                                                        { id: "sum_net", header: [{ text: "Total Net", css: { "text-align": "center" } }, { text: "Net(Kg.)", css: { "text-align": "center" } }], width: 80, css: { "text-align": "center" }, },
                                                        { id: "sum_qty", header: [{ text: "Total Qty", css: { "text-align": "center" } }, { text: "(Pcs.)", css: { "text-align": "center" } }], width: 80, css: { "text-align": "center" } },
                                                        { id: "created_at", header: [{ text: "Created At", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        { id: "created_by", header: [{ text: "Created By", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        { id: "updated_at", header: [{ text: "Updated At", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                        { id: "updated_by", header: [{ text: "Updated By", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },          
                                                        /* {
                                                            id: "sum_pallet", header: [{ text: "Total Pallet", css: { "text-align": "center" } }, { content: "numberFilter" }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 100, css: { "text-align": "center" }, template: function (row) {
                                                                if (row.row_num == 1) {
                                                                    return row.sum_pallet;
                                                                }
                                                                else {
                                                                    return "";
                                                                }
                                                            }
                                                        },
                                                        {
                                                            id: "sum_net", header: [{ text: "Total Net(Kg.)", css: { "text-align": "center" } }, { content: "numberFilter" }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 100, css: { "text-align": "center" }, template: function (row) {
                                                                if (row.row_num == 1) {
                                                                    return row.sum_net;
                                                                }
                                                                else {
                                                                    return "";
                                                                }
                                                            }
                                                        }, */
                                                    ],
                                                    on:
                                                    {

                                                    },
                                                    onClick:
                                                    {
                                                        "mdi-file": function (e, t) {
                                                            var row = this.getItem(t);
                                                            var data = row.order_no;

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
                                                                    ele('order_no').setValue(json.data);
                                                                    loadDataByOrderNo();

                                                                    setTimeout(function () {
                                                                        FindItem();
                                                                    }, 500);
                                                                }, null,
                                                                    function (json) {
                                                                    });
                                                            }, row);


                                                        },
                                                        "mdi-cancel": function (e, t) {
                                                            var row = this.getItem(t), datatable = this;
                                                            //console.log(row);
                                                            msBox('ยกเลิก', function () {
                                                                ajax('Packing/OrderRepackView_data.php', row, 32, function (json) {
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