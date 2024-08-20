var header_ConfirmReceive = function () {
    var menuName = "ConfirmReceive_", fd = "Receiving/" + menuName + "data.php";

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


    function FindItem() {
        var obj = ele('form1').getValues();
        $.post(fd, { obj: obj, type: 1 })
            .done(function (data) {
                var json = JSON.parse(data);
                if (json.ch == 1) {
                    focus('scan_check');
                    setTable('dataT1', json.data);
                }
                else if (json.ch == 2) {
                    webix.alert({
                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                            ele('document_no').setValue('');
                            focus('document_no');
                        }
                    });
                }
            });
    };

    function AddItem() {
        var obj = ele('form1').getValues();
        $.post(fd, { obj: obj, type: 11 })
            .done(function (data) {
                var json = JSON.parse(data);
                if (json.ch == 1) {
                    ele('pallet_no').setValue('');
                    ele('case_tag_no').setValue('');
                    ele('scan_check').setValue('');
                    focus('scan_check');
                    FindItem();
                }
                else if (json.ch == 2) {
                    webix.alert({
                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                            ele('pallet_no').setValue('');
                            ele('case_tag_no').setValue('');
                            ele('scan_check').setValue('');
                            focus('scan_check');
                        }
                    });
                }
            });
    };

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_ConfirmReceive",
        body:
        {
            id: "ConfirmReceive_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Confirm Check Tag", type: "header" },
                    {
                        view: "form",
                        id: $n("form1"),

                        on: {
                            "onSubmit": function (view, e) {
                                if (view.config.name == 'document_no') {
                                    FindItem();
                                }
                                else if (view.config.name == 'scan_check') {
                                    var tag_no = ele('scan_check').getValue();

                                    if (tag_no.substring(1, 0) == 'R') {
                                        var obj = ele('form1').getValues();
                                        $.post(fd, { obj: obj, type: 3 })
                                            .done(function (data) {
                                                var json = JSON.parse(data);
                                                if (json.ch == 1) {
                                                    ele('case_tag_no').setValue(tag_no);
                                                    ele('scan_check').setValue('');
                                                    focus('scan_check');

                                                    var case_tag_no = ele('case_tag_no').getValue();
                                                    var pallet_no = ele('pallet_no').getValue();

                                                    if (case_tag_no != '' && pallet_no != '') {
                                                        AddItem();
                                                    }
                                                }
                                                else if (json.ch == 2) {
                                                    webix.alert({
                                                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                                                            ele('case_tag_no').setValue('');
                                                            ele('pallet_no').setValue('');
                                                            ele('scan_check').setValue('');
                                                            focus('scan_check');
                                                        }
                                                    });
                                                }
                                            });
                                    } else {
                                        var obj = ele('form1').getValues();
                                        $.post(fd, { obj: obj, type: 3 })
                                            .done(function (data) {
                                                var json = JSON.parse(data);
                                                if (json.ch == 1) {
                                                    ele('pallet_no').setValue(tag_no);
                                                    ele('scan_check').setValue('');
                                                    focus('scan_check');

                                                    var case_tag_no = ele('case_tag_no').getValue();
                                                    var pallet_no = ele('pallet_no').getValue();

                                                    if (case_tag_no != '' && pallet_no != '') {
                                                        AddItem();
                                                    }
                                                }
                                                else if (json.ch == 2) {
                                                    webix.alert({
                                                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                                                            ele('pallet_no').setValue('');
                                                            ele('case_tag_no').setValue('');
                                                            ele('scan_check').setValue('');
                                                            focus('scan_check');
                                                        }
                                                    });
                                                }
                                            });
                                    }
                                }
                            },
                        },
                        elements:
                            [
                                {
                                    cols:
                                        [
                                            vw1('text', 'document_no', 'Document No. (GRN)', { disabled: false, hidden: 0, }),
                                        ]
                                },
                                {
                                    cols:
                                        [
                                            vw1('text', 'scan_check', 'Pallet No./Case Tag No. (Scan)', { disabled: false, required: true, hidden: 0 }),
                                            vw1('text', 'pallet_no', 'Pallet No.', { disabled: true, required: true, hidden: 0 }),
                                            vw1('text', 'case_tag_no', 'Case Tag No.', { disabled: true, required: true, hidden: 0 }),
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
                                                                ele('form1').setValues('');
                                                                focus('document_no');
                                                                ele('dataT1').clearAll();
                                                            }
                                                        }
                                                    }),
                                                ]
                                            }
                                        ]
                                },
                                {
                                    cols: [
                                        vw1('button', 'btn_save', 'Save', {
                                            css: "webix_green",
                                            icon: "mdi mdi-content-save", type: "icon",
                                            tooltip: { template: "บันทึก", dx: 10, dy: 15 },
                                            on: {
                                                onItemClick: function (id, e) {
                                                    ele('form1').setValues('');
                                                    focus('document_no');
                                                    ele('dataT1').clearAll();
                                                }
                                            }
                                        }),
                                    ]
                                },
                                {
                                    cols: [
                                        {
                                            view: "datatable", id: $n("dataT1"), navigation: true, select: false,
                                            resizeColumn: true, autoheight: true, hover: "myhover",
                                            threeState: true, rowLineHeight: 25, rowHeight: 25,
                                            datatype: "json", headerRowHeight: 25, leftSplit: 2,
                                            editable: true,
                                            editaction: "dblclick",
                                            navigation: true,
                                            scrollX: true,
                                            footer: true,
                                            css: "webix_selectable",
                                            // drag: "order",
                                            scheme:
                                            {
                                                $change: function (item) {
                                                    if (item.tag_check == 'Yes') {
                                                        item.$css = { "background": "#afeac8", "font-weight": "bold" };
                                                    }
                                                }
                                            },
                                            columns: [
                                                { id: "pallet_no", header: [{ text: "Pallet No.", css: { "text-align": "center" } },], width: 100, css: { "text-align": "center" } },
                                                { id: "case_tag_no", header: [{ text: "Case Tag No.", css: { "text-align": "center" } },], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } },], width: 150, css: { "text-align": "center" } },
                                                { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } },], width: 180, css: { "text-align": "center" }, },
                                            ],
                                            onClick:
                                            {

                                            },
                                            on:
                                            {
                                                "onEditorChange": function (id, value) {
                                                    // console.log(id, value);
                                                    var row = this.getItem(id), dataTable = this;
                                                    row.change = 1;
                                                    dataTable.updateItem(id.row, row);
                                                }
                                            }
                                        },
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