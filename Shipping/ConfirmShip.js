var header_ConfirmShip = function () {
    var menuName = "ConfirmShip_", fd = "Shipping/" + menuName + "data.php";

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
        var obj = ele("form1").getValues();
        $.post(fd, { obj: obj, type: 2 })
            .done(function (data) {
                var json = JSON.parse(data);
                if (json.ch == 1) {
                    focus('document_no');
                    setTable('dataT1', json.data);
                }
                else if (json.ch == 2) {
                    webix.alert({
                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                            ele('form1').setValues('');
                            ele('dataT1').clearAll();

                            focus('document_no');
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
        $.post(fd, { obj: obj, type: 3 })
            .done(function (data) {
                var json = JSON.parse(data);
                if (json.ch == 1) {
                    focus('document_no');
                    setTable('dataT1', json.data);
                }
                else if (json.ch == 2) {
                    webix.alert({
                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                            ele('form1').setValues('');
                            ele('dataT1').clearAll();

                            focus('document_no');
                        }
                    });
                }
                /* else {
                    webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                } */
            });
    };

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_ConfirmShip",
        body:
        {
            id: "ConfirmShip_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Confirm Ship", type: "header" },
                    {
                        view: "form", scroll: false, id: $n('form1'),
                        on: {
                            "onSubmit": function (view, e) {

                                if (view.config.name == 'document_no') {
                                    FindItem();
                                    focus('document_no');
                                }

                            },
                        },
                        elements: [
                            {
                                rows: [
                                    {
                                        rows:
                                            [
                                                {
                                                    view: "fieldset", label: "INFO (ข้อมูล)", body:
                                                    {
                                                        rows: [
                                                            {
                                                                cols: [
                                                                    vw1('text', 'document_no', 'Document No. (GTN)', {}),
                                                                    {
                                                                        rows: [
                                                                            {},
                                                                            vw1('button', 'btn_save', 'Confirm', {
                                                                                css: "webix_green",
                                                                                icon: "mdi mdi-check", type: "icon",
                                                                                tooltip: { template: "บันทึก", dx: 10, dy: 15 },
                                                                                width: 120,
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
                                                                                                            loadData();
                                                                                                            //window.open("print/doc/gtn.php?data=" + data, '_blank');

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

                                                                                        focus('document_no');
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
                                                                                if (item.transaction_type == 'Out') {
                                                                                    item.$css = { "background": "#afeac8", "font-weight": "bold" };
                                                                                }
                                                                            }
                                                                        },
                                                                        columns: [
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
                                                                            "mdi-plus-circle": function (e, t) {
                                                                                var row = this.getItem(t), dataTable = this;
                                                                                AddItem(row);
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