var header_Onhand = function () {
    var menuName = "Onhand_", fd = "Inventory/" + menuName + "data.php";

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
        var obj = ele("form1").getValues();
        ajax(fd, obj, 1, function (json) {
            setTable("dataT1", json.data);

            var group_by = ele('group_by').getValue();
            if (group_by == 'By part') {
                ele('dataT1').hideColumn('receive_date');
                ele('dataT1').hideColumn('invoice_no');
                ele('dataT1').hideColumn('pallet_no');
                ele('dataT1').hideColumn('certificate_no');
                ele('dataT1').hideColumn('case_tag_no');
                ele('dataT1').hideColumn('fg_tag_no');
                ele('dataT1').hideColumn('package_no');
                ele('dataT1').hideColumn('location_code');
                ele('dataT1').hideColumn('location_area');
                ele('dataT1').hideColumn('qty');
                ele('dataT1').hideColumn('net_per_pallet');
                ele('dataT1').hideColumn('repack_process');
            } else {
                ele('dataT1').showColumn('receive_date');
                ele('dataT1').showColumn('invoice_no');
                ele('dataT1').showColumn('pallet_no');
                ele('dataT1').showColumn('certificate_no');
                ele('dataT1').showColumn('case_tag_no');
                ele('dataT1').showColumn('fg_tag_no');
                ele('dataT1').showColumn('package_no');
                ele('dataT1').showColumn('location_code');
                ele('dataT1').showColumn('location_area');
                ele('dataT1').showColumn('qty');
                ele('dataT1').showColumn('net_per_pallet');
                ele('dataT1').showColumn('repack_process');
            }
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
                saveAs(e.data, 'onhand_' + dayjs().format('YYYYMMDDHHmmss') + ".xlsx");
                btn.enable();
                webix.message({ expire: 7000, text: "Export สำเร็จ" });
            }, false);
            worker.postMessage({ 'cmd': 'start', 'msg': data });
        }
        else { webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: "ไม่พบข้อมูลในตาราง", callback: function () { } }); }
    };

    function reload_options_customer() {
        var customerList = ele("Customer_Code").getPopup().getList();
        customerList.clearAll();
        customerList.load("common/customerMaster.php?type=2");
    };

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_Onhand",
        body:
        {
            id: "Onhand_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Onhand", type: "header" },
                    {
                        view: "form",
                        id: $n("form1"),
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
                                                                // exportExcel(this);

                                                                var obj = ele('form1').getValues();
                                                                var dataT1 = ele("dataT1");
                                                                if (dataT1.count() != 0) {

                                                                    setTimeout(function () {
                                                                        $.post(fd, { obj: obj, type: 2 })
                                                                            .done(function (data) {
                                                                                var json = JSON.parse(data);
                                                                                data = eval('(' + data + ')');
                                                                                if (json.ch == 1) {
                                                                                    webix.alert({
                                                                                        title: "<b>ข้อความจากระบบ</b>", type: "alert-complete", ok: 'ตกลง', text: 'Export Complete', callback: function () {
                                                                                            ele("btnExport").enable();
                                                                                            window.location.href = 'Inventory/' + json.data;
                                                                                        }
                                                                                    });
                                                                                }
                                                                                else {
                                                                                    webix.alert({
                                                                                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: 'ผิดพลาดโปรดลองอีกครั้ง', callback: function () {
                                                                                            ele("btnExport").enable();
                                                                                            window.playsound(2);
                                                                                        }
                                                                                    });
                                                                                }
                                                                            })
                                                                    }, 0);

                                                                }
                                                                else {
                                                                    webix.alert({ title: "<b>ข้อความจากระบบ</b>", type: 'alert-error', ok: 'ตกลง', text: 'ไม่พบข้อมูลในตาราง', callback: function () { } });
                                                                }

                                                            }
                                                        }
                                                    }),
                                                ]
                                            },
                                            {},
                                            vw1('richselect', 'group_by', 'Group By', {
                                                required: false,
                                                value: 'By item',
                                                options: ['By part', 'By item'],
                                                on: {
                                                    onChange: function (value) {
                                                        loadData();
                                                    }
                                                }
                                            }),
                                            vw1('richselect', 'supplier_code', 'Destination', {
                                                required: false,
                                                value: '',
                                                options: ['HMTH', 'NKAPM'],
                                                on: {
                                                    onChange: function (value) {
                                                        loadData();
                                                    }
                                                }
                                            }),
                                            {
                                                rows: [
                                                    {},
                                                    vw1("button", 'btn_find', "Find", {
                                                        width: 120, css: "webix_primary",
                                                        icon: "mdi mdi-magnify", type: "icon",
                                                        tooltip: { template: "ค้นหา", dx: 10, dy: 15 },
                                                        on: {
                                                            onItemClick: function () {
                                                                loadData();
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
                                                                ele('supplier_code').setValue('');
                                                                ele('dataT1').eachColumn(function (id, col) {
                                                                    var filter = this.getFilter(id);
                                                                    if (filter) {
                                                                        if (filter.setValue) filter.setValue("")
                                                                        else filter.value = "";
                                                                    }
                                                                });
                                                            }
                                                        }
                                                    }),
                                                ]
                                            }
                                        ]
                                },
                                {
                                    view: "datatable", id: $n("dataT1"), navigation: true, select: true,
                                    resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                                    threeState: true, rowLineHeight: 25, rowHeight: 25,
                                    datatype: "json", headerRowHeight: 25, leftSplit: 4,
                                    editable: true,
                                    navigation: true,
                                    scrollX: true,
                                    footer: true,
                                    height: 450,
                                    pager: $n("Master_pagerA"),
                                    datafetch: 50, // Number of rows to fetch at a time
                                    loadahead: 100, // Number of rows to prefetch
                                    scheme:
                                    {
                                        $change: function (item) {
                                            // if (item.Pick == 'N') {
                                            //     item.$css = { "background": "#cfcfcf", "font-weight": "bold", "color": "#9e9e9e" };
                                            // }
                                        }
                                    },
                                    columns: [
                                        { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: { "text-align": "center" }, width: 35, sort: "int" },
                                        { id: "supplier_code", header: [{ text: "Destination", css: { "text-align": "center" } }, { content: "textFilter" }], width: 80, css: { "text-align": "center" } },
                                        { id: "receive_date", header: [{ text: "Receive Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                        { id: "invoice_no", header: [{ text: "Invoice No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                        { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 160, css: { "text-align": "center" } },
                                        { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, },
                                        { id: "pallet_no", header: [{ text: "Pallet No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                        { id: "certificate_no", header: [{ text: "Certificate No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                        { id: "case_tag_no", header: [{ text: "Case Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, footer: { text: "Total : ", css: { "text-align": "right" } } },
                                        { id: "total_net_per_pallet", header: [{ text: "Net Weight", css: { "text-align": "center" } }, { text: "(Kg.)", css: { "text-align": "center" } }], width: 80, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, format: webix.i18n.numberFormat },
                                        { id: "total_qty", header: [{ text: "Qty", css: { "text-align": "center" } }, { text: " (Pcs.)", css: { "text-align": "center" } }], width: 80, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, },
                                        { id: "fg_tag_no", header: [{ text: "FG Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                        // { id: "package_no", header: [{ text: "Package No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                        // { id: "qty", header: [{ text: "Qty(pcs.)", css: { "text-align": "center" } },], width: 80, css: { "text-align": "center" } },
                                        // { id: "net_per_pallet", header: [{ text: "Net(Kg.)", css: { "text-align": "center" } },], width: 80, css: { "text-align": "center" }, },
                                        { id: "repack_process", header: [{ text: "Coil Direction Process", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, hidden: 0 },
                                        { id: "location_code", header: [{ text: "Location", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },
                                        { id: "location_area", header: [{ text: "Area", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" } },

                                    ],
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