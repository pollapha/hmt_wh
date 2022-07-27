var header_ViewGRN = function () {
    var menuName = "ViewGRN_", fd = "Receiving/" + menuName + "data.php";

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

    var status = [
        { id: 'PENDING', value: "PENDING" },
        { id: 'COMPLETE', value: "COMPLETE" },
        { id: 'CANCEL', value: "CANCEL" },
    ];


    //edit
    webix.ui(
        {
            view: "window", id: $n("win_edit"), modal: 1, top: 50, position: "center",
            width: 1000, height: 400,
            head: {
                view: "toolbar", cols: [
                    { width: 4 },
                    { view: "label", label: "Edit (แก้ไขข้อมูล)", align: 'center' },
                    {
                        view: "button", label: 'X', width: 30, align: 'right', type: 'danger',
                        click: function () {
                            ele('win_edit').hide();
                        }
                    }
                ]
            },
            body:
            {
                rows: [
                    {

                        view: "datatable", id: $n('dataT1'), headerRowHeight: 20, rowLineHeight: 25, rowHeight: 25, resizeColumn: true, css: { "font-size": "13px" },
                        columns: [
                            // {
                            //     id: $n("icon_cancel"), header: "&nbsp;", width: 40, template: function (row) {
                            //         if (row.Is_Header != "YES") {
                            //             return "<span style='cursor:pointer' class='webix_icon fa-trash'></span>";
                            //         }
                            //         else {
                            //             return '';
                            //         }
                            //     }
                            // },
                            { id: "NO", header: "No.", css: "rank", width: 50, sort: "int" },
                            { id: "GRN_Number", header: ["GRN Number", { content: "textFilter" }], width: 140 },
                            { id: "Receive_DateTime", header: ["Receive DateTime", { content: "textFilter" }], width: 140 },
                            { id: "DN_Number", header: ["DN Number", { content: "textFilter" }], width: 130 },
                            //{ id: "Confirm_Receive_DateTime", header: ["Confirm Receive DateTime", { content: "textFilter" }], width: 200 },
                            { id: "status", header: ["Status", { content: "textFilter" }], editor: "richselect", collection: status, width: 100 },
                            { id: "Part_No", header: ["Part No.", { content: "textFilter" }], width: 140 },
                            { id: "Package_Number", header: ["Package Number", { content: "textFilter" }], width: 180 },
                            { id: "FG_Serial_Number", header: ["Serial Number", { content: "textFilter" }], width: 200 },
                            { id: "Qty", header: ["Qty", { content: "textFilter" }], width: 100 },
                        ],
                        editable: true,
                        editaction: "custom",
                        select: "cell",
                        on: {
                            onItemClick: function (data, prevent) {
                                this.editCell(data.row, data.column);
                                var row = this.getItem(data), datatable = this;
                                this.attachEvent("onAfterEditStop", function (obj, editor, ignoreUpdate) {
                                    if (obj.value != obj.old) {
                                        var obj = row.GRN_Number.concat("/", row.FG_Serial_Number).concat("/", row.status);
                                        ajax(fd, obj, 32, function (json) {
                                            setTable('dataT1', json.data);
                                            //webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'บันทึกสำเร็จ', callback: function () { } });
                                        }, null,
                                            function (json) {
                                            });
                                    }
                                });
                            },
                        }

                    },
                    // {
                    //     cols: [
                    //         {},
                    //         vw1("button", 'btn_save', "Save (บันทึก)", {
                    //             width: 120, type: 'form', on:
                    //             {
                    //                 onItemClick: function (e, t) {
                    //                     var row = this.getItem(t), datatable = this;
                    //                     var obj = row
                    //                     console.log(obj);
                    //                 }
                    //             }
                    //         }),
                    //         vw1("button", 'btn_cancel', "Cancel (ยกเลิก)", {
                    //             width: 120, type: 'danger', on:
                    //             {
                    //                 onItemClick: function () {
                    //                     ele('win_edit').hide();
                    //                 }
                    //             }
                    //         }),
                    //     ]
                    // },
                ]


            },
        });


    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_ViewGRN",
        body:
        {
            id: "ViewGRN_id",
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
                                            // vw1("text", 'GRN_Number', "PS Number", {
                                            //     //required: true, suggest: fd + "?type=1", 
                                            //     width: 250
                                            // },
                                            // ),
                                            {
                                                rows: [
                                                    //{},
                                                    vw1("button", 'btnFind', "Find (ค้นหา)", {
                                                        width: 170, on:
                                                        {
                                                            onItemClick: function () {
                                                                var btn = this;
                                                                loadData(btn);
                                                            }
                                                        }
                                                    }),
                                                ]
                                            },
                                            {},
                                            {
                                                rows: [
                                                    {},
                                                    vw1("button", 'btnExport', "Export (โหลดเป็นไฟล์เอ๊กเซล)", {
                                                        width: 200, on:
                                                        {
                                                            onItemClick: function () {
                                                                var dataT1 = ele("dataTREE"), obj = {}
                                                                if (dataT1.count() != 0) {
                                                                    var obj = {};
                                                                    obj.filenameprefix = 'GRN_Report';
                                                                    $.fileDownload("Receiving/ViewGRN_data.php",
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
                                            }
                                        ]
                                },
                                {
                                    view: "treetable", id: $n('dataTREE'), headerRowHeight: 20, rowLineHeight: 25, rowHeight: 25, resizeColumn: true, css: { "font-size": "13px" },
                                    editable: true, scheme:
                                    {
                                        $change: function (item) {
                                            if (item.Is_Header == 'YES' && item.Confirm_Receive_DateTime != null && item.Status_Receiving == 'COMPLETE') {
                                                item.$css = { "background": "#afeac8", "font-weight": "bold" };
                                            }
                                            if (item.Is_Header == 'YES' && item.Confirm_Receive_DateTime == null && item.Status_Receiving == 'PENDING') {
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
                                        // {
                                        //     id: $n("icon_edit"), header: "&nbsp;", width: 40, template: function (row) {
                                        //         if (row.Is_Header == "YES" && row.Status_Receiving == 'PENDING') {
                                        //             return "<span style='cursor:pointer' class='webix_icon fa-pencil'></span>";
                                        //         }
                                        //         else {
                                        //             return '';
                                        //         }
                                        //     }

                                        // },
                                        {
                                            id: $n("icon_cancel"), header: "&nbsp;", width: 40, template: function (row) {
                                                if (row.Is_Header == "YES" && row.Status_Receiving == 'COMPLETE' && row.Pick_status != 'Y') {
                                                    return "<span style='cursor:pointer' class='webix_icon fa-ban'></span>";
                                                }
                                                else if (row.Is_Header == "YES" && row.Status_Receiving == 'PENDING'){
                                                    return "<span style='cursor:pointer' class='webix_icon fa-trash'></span>";
                                                }
                                                else{
                                                    return ''; 
                                                }
                                            }
                                        },
                                        { id: "No", header: "", css: { "text-align": "right" }, editor: "", width: 40 },
                                        {
                                            id: "GRN_Number", header: ["GRN Number", { content: "textFilter" }], editor: "", width: 180,
                                            template: "{common.treetable()} #GRN_Number#"
                                        },
                                        { id: "Receive_DateTime", header: ["Receive DateTime", { content: "textFilter" }], width: 140 },
                                        { id: "DN_Number", header: ["DN Number", { content: "textFilter" }], width: 130 },
                                        { id: "Status_Receiving", header: ["Status", { content: "textFilter" }], width: 100 },
                                        { id: "Confirm_Receive_DateTime", header: ["Confirm Receive DateTime", { content: "textFilter" }], width: 200 },
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
                                            var data = row.GRN_Number;
                                            window.open("print/doc/grn.php?data=" + data, '_blank');
                                        },
                                        // "fa-pencil": function (e, t) {
                                        //     ele('win_edit').show();
                                        //     var row = this.getItem(t);
                                        //     var obj = row.GRN_Number;
                                        //     console.log(obj);
                                        //     ajax(fd, obj, 21, function (json) {
                                        //         setTable('dataT1', json.data);
                                        //     }, null,
                                        //         function (json) {
                                        //         });
                                        // },
                                        "fa-ban": function (e, t) {
                                            var row = this.getItem(t), datatable = this;
                                            var obj = row.GRN_Number;
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
                                        "fa-trash": function (e, t) {
                                            var row = this.getItem(t), datatable = this;
                                            var obj = row.GRN_Number;
                                            console.log('obj : ', obj);
                                            msBox('บันทึก', function () {
                                                ajax(fd, obj, 32, function (json) {
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