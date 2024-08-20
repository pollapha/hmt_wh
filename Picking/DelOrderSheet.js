var header_DelOrderSheet = function () {
    var menuName = "DelOrderSheet_", fd = "Picking/" + menuName + "data.php";

    function init() {
        loadDataView();
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
                saveAs(e.data, 'delivery order sheet_' + dayjs().format('YYYYMMDDHHmmss') + ".xlsx");
                btn.enable();
                webix.message({ expire: 7000, text: "Export สำเร็จ" });
            }, false);
            worker.postMessage({ 'cmd': 'start', 'msg': data });
        }
        else { webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: "ไม่พบข้อมูลในตาราง", callback: function () { } }); }
    };

    function loadDataView(btn) {
        var obj = ele("form2").getValues();
        ajax(fd, obj, 1, function (json) {
            setTable("dataTREE", json.data);
        }, btn);
    };

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_DelOrderSheet",
        body:
        {
            id: "DelOrderSheet_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Delivery Order Sheet (DOS)", type: "header" },
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
                                                                /*  var dataTREE = ele("dataTREE");
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
                                            if (item.order_status == 'Picking') {
                                                item.$css = { "color": "#afeac8", "font-weight": "bold" };
                                            }
                                        }
                                    }, */
                                    columns: [
                                        {
                                            id: "icon_cancel", header: { text: "Cancel", rotate: true, height: 30, css: { "text-align": "center" } }, width: 40, template: function (row) {
                                                if (row.row_num == 1) {
                                                    return "<button class='mdi mdi-cancel webix_button' title='ยกเลิกเอกสาร' style='width:23px; height:22px; font-size:12px; color:#ffffff; background-color: #ed3755;'></button>";
                                                }
                                                else {
                                                    return "";
                                                }
                                            }
                                        },
                                        {
                                            id: "icon_doc", header: { text: "View", rotate: true, height: 30, css: { "text-align": "center" } }, width: 45, template: function (row) {
                                                if (row.row_num == 1) {
                                                    return "<button class='mdi mdi-file webix_button' title='ดูเอกสาร' style='width:23px; height:22px; font-size:12px; color:#ffffff; background-color: #556892;'></button>";
                                                }
                                                else {
                                                    return "";
                                                }
                                            }
                                        },
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
                                        { id: "dos_no", header: [{ text: "Document No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, hidden: 0 },
                                        { id: "order_no", header: [{ text: "Order No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, hidden: 0 },
                                        { id: "order_date", header: [{ text: "Order Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                        { id: "delivery_date", header: [{ text: "Delivery Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                        { id: "supplier_code", header: [{ text: "Destination", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                        { id: "case_tag_no", header: [{ text: "Case Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                        { id: "work_order_no", header: [{ text: "Work Order No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                        { id: "fg_tag_no", header: [{ text: "FG Tag No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                        { id: "package_no", header: [{ text: "Package No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" } },
                                        { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" } },
                                        { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, footer: { text: "Total : ", css: { "text-align": "right" } } },
                                        { id: "qty", header: [{ text: "Qty(pcs.)", css: { "text-align": "center" } }, { content: "numberFilter" }], footer: { content: "summColumn", css: { "text-align": "center" } }, width: 80, css: { "text-align": "center" } },
                                        { id: "net_per_pallet", header: [{ text: "Net(Kg.)", css: { "text-align": "center" } }, { content: "textFilter" }], width: 80, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } }, format: webix.i18n.numberFormat  },
                                    ],
                                    on:
                                    {

                                    },
                                    onClick:
                                    {
                                        "mdi-file": function (e, t) {
                                            var row = this.getItem(t);
                                            var data = row.dos_no;
                                            
                                            window.open("print/doc/dos.php?data=" + data, '_blank');

                                        },
                                        
                                        "mdi-cancel": function (e, t) {
                                            var row = this.getItem(t), datatable = this;
                                            //console.log(row);
                                            msBox('ยกเลิก', function () {
                                                ajax(fd, row, 31, function (json) {
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