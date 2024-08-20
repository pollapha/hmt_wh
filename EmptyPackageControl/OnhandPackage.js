var header_OnhandPackage = function () {
    var menuName = "OnhandPackage_", fd = "EmptyPackageControl/" + menuName + "data.php";

    function init() {
        loadData();
        loadData2();
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
        //var obj = ele("form1").getValues();
        ajax(fd, {}, 1, function (json) {
            setTable("dataT1", json.data);
        }, btn);
    };

    function loadData2(btn) {
        //var obj = ele("form1").getValues();
        ajax(fd, {}, 2, function (json) {
            setTable("dataT2", json.data.steel_pipe);
            setTable("dataT3", json.data.package_steel);
            setTable("dataT4", json.data.package_wooden);
            setTable("dataT5", json.data.package_status);

        }, btn);
    };

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_OnhandPackage",
        body:
        {
            id: "OnhandPackage_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "On-hand (Package)", type: "header" },
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
                                                                        $.post(fd, { obj: obj, type: 3 })
                                                                            .done(function (data) {
                                                                                var json = JSON.parse(data);
                                                                                data = eval('(' + data + ')');
                                                                                if (json.ch == 1) {
                                                                                    webix.alert({
                                                                                        title: "<b>ข้อความจากระบบ</b>", type: "alert-complete", ok: 'ตกลง', text: 'Export Complete', callback: function () {
                                                                                            ele("btnExport").enable();
                                                                                            window.location.href = 'EmptyPackageControl/' + json.data;
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
                                            /* vw1('richselect', 'package_type', 'Package Type', {
                                                required: false,
                                                value: '',
                                                options: ['Steel', 'Wooden'],
                                                on: {
                                                    onChange: function (value) {
                                                        loadData();
                                                    }
                                                }
                                            }), */
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
                                                                loadData2();
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
                                    cols: [
                                        {
                                            view: "fieldset", label: "On-hand (Package)", body:
                                            {
                                                rows: [
                                                    {
                                                        view: "datatable", id: $n("dataT1"), navigation: true, select: false,
                                                        resizeColumn: true, autoheight: false, multiselect: false, hover: "myhover",
                                                        threeState: true, rowLineHeight: 25, rowHeight: 25,
                                                        datatype: "json", headerRowHeight: 25, leftSplit: 2,
                                                        editable: true,
                                                        navigation: true,
                                                        scrollX: true,
                                                        footer: true,
                                                        datafetch: 50, // Number of rows to fetch at a time
                                                        loadahead: 100, // Number of rows to prefetch,
                                                        height: 350,
                                                        width: 620, 
                                                        columns: [
                                                            { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: { "text-align": "center" }, width: 35, sort: "int" },
                                                            { id: "package_no", header: [{ text: "Package No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 75, css: { "text-align": "center" }, },
                                                            { id: "package_type", header: [{ text: "Package Type", css: { "text-align": "center" } }, { content: "selectFilter" }], width: 80, css: { "text-align": "center" }, },
                                                            { id: "delivery_status", header: [{ text: "Status", css: { "text-align": "center" } }, { content: "selectFilter" }], width: 70, css: { "text-align": "center" }, },
                                                            { id: "package_status", header: [{ text: "Empty/FG", css: { "text-align": "center" } }, { content: "selectFilter" }], width: 80, css: { "text-align": "center" }, },
                                                            { id: "ttv", header: [{ text: "TTV", css: { "text-align": "center" } }, { content: "numberFilter" }], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } } },
                                                            { id: "hmth", header: [{ text: "HMTH", css: { "text-align": "center" } }, { content: "numberFilter" }], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } } },
                                                            { id: "nkapm", header: [{ text: "NKAPM", css: { "text-align": "center" } }, { content: "numberFilter" }], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } } },
                                                            { id: "updated_at", header: [{ text: "Updated At", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                                            { id: "updated_by", header: [{ text: "Updated By", css: { "text-align": "center" } }, { content: "textFilter" }], width: 80, css: { "text-align": "center" }, },

                                                        ],
                                                    },
                                                ]
                                            },
                                        },
                                        {
                                            rows:[
                                                {
                                                    view: "fieldset", label: "Total",
                                                    body:
                                                    {
                                                        rows: [
        
                                                            {
                                                                view: "datatable", id: $n("dataT2"), navigation: true, select: false,
                                                                resizeColumn: true, autoheight: true, multiselect: false, hover: "myhover",
                                                                threeState: true, rowLineHeight: 25, rowHeight: 25,
                                                                datatype: "json", headerRowHeight: 25, leftSplit: 0,
                                                                editable: true,
                                                                navigation: true,
                                                                scrollX: false,
                                                                footer: false,
                                                                columns: [
                                                                    { id: "steel_pipe", header: [{ text: "Steel Pipe", css: { "text-align": "center" } },], width: 100, css: { "text-align": "center" }, },
                                                                    { id: "ttv", header: [{ text: "TTV", css: { "text-align": "center" }, }, ], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } } },
                                                                    { id: "hmth", header: [{ text: "HMTH", css: { "text-align": "center" } },], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } } },
                                                                    { id: "nkapm", header: [{ text: "NKAPM", css: { "text-align": "center" } },], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } } },
                                                                    { id: "total", header: [{ text: "Total", css: { "text-align": "center" } },], width: 80, css: { "text-align": "center" } },
        
                                                                ],
                                                            },
                                                            {height:20},
                                                            {
                                                                view: "datatable", id: $n("dataT3"), navigation: true, select: false,
                                                                resizeColumn: true, autoheight: true, multiselect: false, hover: "myhover",
                                                                threeState: true, rowLineHeight: 25, rowHeight: 25,
                                                                datatype: "json", headerRowHeight: 25, leftSplit: 0,
                                                                editable: true,
                                                                navigation: true,
                                                                scrollX: false,
                                                                footer: false,
                                                                columns: [
                                                                    { id: "steel_pipe", header: [{ text: "Package (Steel)", css: { "text-align": "center" } }, ], width:100, css: { "text-align": "center" }, },
                                                                    { id: "ttv", header: [{ text: "TTV", css: { "text-align": "center" } }, ], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } } },
                                                                    { id: "hmth", header: [{ text: "HMTH", css: { "text-align": "center" } },], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } } },
                                                                    { id: "nkapm", header: [{ text: "NKAPM", css: { "text-align": "center" } },], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } } },
                                                                    { id: "total", header: [{ text: "Total", css: { "text-align": "center" } },], width: 80, css: { "text-align": "center" } },
        
                                                                ],
                                                            },
                                                            {height:20},
                                                            {
                                                                view: "datatable", id: $n("dataT4"), navigation: true, select: false,
                                                                resizeColumn: true, autoheight: true, multiselect: false, hover: "myhover",
                                                                threeState: true, rowLineHeight: 25, rowHeight: 25,
                                                                datatype: "json", headerRowHeight: 25, leftSplit: 0,
                                                                editable: true,
                                                                navigation: true,
                                                                scrollX: false,
                                                                footer: false,
                                                                columns: [
                                                                    { id: "steel_pipe", header: [{ text: "Package (Wooden)", css: { "text-align": "center" } },], width: 100, css: { "text-align": "center" }, },
                                                                    { id: "ttv", header: [{ text: "TTV", css: { "text-align": "center" } }, ], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } } },
                                                                    { id: "hmth", header: [{ text: "HMTH", css: { "text-align": "center" } },], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } } },
                                                                    { id: "nkapm", header: [{ text: "NKAPM", css: { "text-align": "center" } },], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } } },
                                                                    { id: "total", header: [{ text: "Total", css: { "text-align": "center" } },], width: 80, css: { "text-align": "center" } },
        
                                                                ],
                                                            },
                                                        ]
                                                    },
                                                },
                                                {
                                                    view: "fieldset", label: "In TTV",
                                                    body:
                                                    {
                                                        rows: [
                                                            {
                                                                view: "datatable", id: $n("dataT5"), navigation: true, select: false,
                                                                resizeColumn: true, autoheight: true, multiselect: false, hover: "myhover",
                                                                threeState: true, rowLineHeight: 25, rowHeight: 25,
                                                                datatype: "json", headerRowHeight: 25, leftSplit: 0,
                                                                editable: true,
                                                                navigation: true,
                                                                scrollX: false,
                                                                footer: true,
                                                                columns: [
                                                                    { id: "package_type", header: [{ text: "Status Package", css: { "text-align": "center" } },], width: 100, css: { "text-align": "center" }, footer: { text: "Total : ", css: { "text-align": "center" } } },
                                                                    { id: "FG", header: [{ text: "FG", css: { "text-align": "center" } }, ], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } } },
                                                                    { id: "Empty", header: [{ text: "Empty", css: { "text-align": "center" } },], width: 60, css: { "text-align": "center" }, footer: { content: "summColumn", css: { "text-align": "center" } } },
                                                                    // { id: "total", header: [{ text: "Total", css: { "text-align": "center" } },], width: 80, css: { "text-align": "center" } },
        
                                                                ],
                                                            },
                                                        ]
                                                    },
                                                },
                                            ]
                                        }
                                        
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