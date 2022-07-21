var header_ViewPS = function () {
    var menuName = "ViewPS_", fd = "Shipping/" + menuName + "data.php";

    function init() {

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
            setTable('dataTREE', json.data);
        }, btn);
    };

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_ViewPS",
        body:
        {
            id: "ViewPS_id",
            type: "clean",
            rows:
                [
                    {
                        view: "form",
                        paddingY: 0,
                        id: $n("form1"),
                        elements:
                            [
                                {
                                    cols:
                                        [
                                            vw1("button", 'btnFind', "Find (ค้นหา)", {
                                                width: 170, on:
                                                {
                                                    onItemClick: function () {
                                                        var btn = this;
                                                        loadData(btn);
                                                    }
                                                }
                                            }),
                                            {},
                                            vw1("button", 'btnExport', "Export (โหลดเป็นไฟล์เอ๊กเซล)", {
                                                width: 200, on:
                                                {
                                                    onItemClick: function () {
                                                        var dataT1 = ele("dataTREE"), obj = {}
                                                        if (dataT1.count() != 0) {
                                                            var obj = {};
                                                            obj.filenameprefix = 'PS_Report';
                                                            $.fileDownload("Shipping/ViewPS_data.php",
                                                                {
                                                                    httpMethod: "POST",
                                                                    data: { obj: obj, type: 5 },
                                                                    successCallback: function (url) {
                                                                    },
                                                                    prepareCallback: function (url) {
                                                                    },
                                                                    failCallback: function (responseHtml, url) {

                                                                    }
                                                                });
                                                        }
                                                        else {
                                                            webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ไม่พบข้อมูลในตาราง', callback: function () { } });
                                                        }
                                                    }
                                                }
                                            }),
                                        ]
                                },
                                {
                                    view: "treetable", id: $n('dataTREE'), headerRowHeight: 20, rowLineHeight: 25, rowHeight: 25, resizeColumn: true, css: { "font-size": "13px" },
                                    editable: true, scheme:
                                    {
                                        $change: function (item) {
                                            if (item.Is_Header == 'YES' && item.Confirm_Picking_DateTime != null && item.Status_Picking == 'COMPLETE') {
                                                item.$css = { "background": "#afeac8", "font-weight": "bold" };
                                            }
                                            if (item.Is_Header == 'YES' && item.Confirm_Picking_DateTime == null && item.Status_Picking == 'PENDING') {
                                                item.$css = { "background": "#ffffb2", "font-weight": "bold" };
                                            }
                                        }
                                    },
                                    columns: [
                                        {
                                            id: "data22", header: "&nbsp;", width: 40,
                                            template: function (row) {
                                                if (row.Is_Header == "YES") {
                                                    return "<span style='cursor:pointer' class='webix_icon fa-file-pdf-o'></span>";
                                                }
                                                else {
                                                    return '';
                                                }
                                            }
                                        },
                                        {
                                            id: $n("icon_cancel"), header: "&nbsp;", width: 40, template: function (row) {
                                                if (row.Is_Header == "YES") {
                                                    return "<span style='cursor:pointer' class='webix_icon fa-ban'></span>";
                                                }
                                                else {
                                                    return '';
                                                }
                                            }
                                        },
                                        { id: "No", header: "", css: { "text-align": "right" }, editor: "", width: 40 },
                                        {
                                            id: "PS_Number", header: ["PS Number", { content: "textFilter" }], editor: "", width: 180,
                                            template: "{common.treetable()} #PS_Number#"
                                        },
                                        { id: "Pick_Date", header: ["Pick Date", { content: "textFilter" }], width: 140 },
                                        //{ id: "DN_Number", header: ["DN Number", { content: "textFilter" }], width: 130 },
                                        { id: "Status_Picking", header: ["Status", { content: "textFilter" }], width: 100 },
                                        { id: "Confirm_Picking_DateTime", header: ["Confirm Picking DateTime", { content: "textFilter" }], width: 200 },
                                        { id: "Part_No", header: ["Part No.", { content: "textFilter" }], width: 140 },
                                        { id: "Package_Number", header: ["Package Number", { content: "textFilter" }], width: 180 },
                                        { id: "FG_Serial_Number", header: ["Serial Number", { content: "textFilter" }], width: 200 },
                                        { id: "Qty", header: ["Qty", { content: "textFilter" }], width: 100 },
                                    ],
                                    on:
                                    {

                                    },
                                    onClick:
                                    {
                                        "fa-file-pdf-o": function (e, t) {
                                            var row = this.getItem(t);
                                            var data = row.PS_Number;
                                            window.open("print/doc/grn.php?data=" + data, '_blank');
                                        },
                                        "fa-ban": function (e, t) {
                                            var row = this.getItem(t), datatable = this;
                                            var obj = row.PS_Number;
                                            console.log('obj : ', obj);
                                            msBox('บันทึก', function () {
                                                ajax(fd, obj, 31, function (json) {
                                                    loadData();
                                                    webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ยกเลิกสำเร็จ', callback: function () { } });

                                                }, null,
                                                    function (json) {
                                                    });
                                            }, row);
                                        },
                                    },
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