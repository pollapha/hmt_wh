var header_CheckPickup = function () {
    var menuName = "CheckPickup_", fd = "Packing/" + menuName + "data.php";

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
                    focus('case_tag_no');
                    setTable('dataT1', json.data);
                }
                else if (json.ch == 2) {
                    webix.alert({
                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                            ele('work_order_no').setValue('');
                            focus('work_order_no');
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
                    ele('case_tag_no').setValue('');
                    FindItem();
                }
                else if (json.ch == 2) {
                    webix.alert({
                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                            ele('case_tag_no').setValue('');
                            focus('case_tag_no');
                        }
                    });
                }
            });
    };

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_CheckPickup",
        body:
        {
            id: "CheckPickup_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Check Pick-up", type: "header" },
                    {
                        view: "form",
                        id: $n("form1"),

                        on: {
                            "onSubmit": function (view, e) {
                                if (view.config.name == 'work_order_no') {
                                    FindItem();
                                }
                                else if (view.config.name == 'case_tag_no') {

                                    var obj = ele('form1').getValues();
                                    $.post(fd, { obj: obj, type: 2 })
                                        .done(function (data) {
                                            var json = JSON.parse(data);
                                            if (json.ch == 1) {
                                                focus('case_tag_no');
                                                AddItem();
                                            }
                                            else if (json.ch == 2) {
                                                webix.alert({
                                                    title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                                                        ele('case_tag_no').setValue('');
                                                        focus('case_tag_no');
                                                    }
                                                });
                                            }
                                        });
                                }
                            },
                        },
                        elements:
                            [
                                {
                                    cols:
                                        [
                                            vw1('text', 'work_order_no', 'Work Order No.', { disabled: false, hidden: 0, }), vw1('text', 'case_tag_no', 'Case Tag No.', { disabled: false, required: true, hidden: 0 }),
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
                                                                focus('work_order_no');
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
                                                    focus('work_order_no');
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
                                            datatype: "json", headerRowHeight: 25,
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
                                                    if (item.pick_check == 'Yes') {
                                                        item.$css = { "background": "#afeac8", "font-weight": "bold" };
                                                    }
                                                }
                                            },
                                            columns: [
                                                // { id: "pallet_no", header: [{ text: "Pallet No.", css: { "text-align": "center" } },], width: 100, css: { "text-align": "center" } },
                                                { id: "case_tag_no", header: [{ text: "Case Tag No.", css: { "text-align": "center" } },], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                { id: "part_tag_no", header: [{ text: "Part Tag No.", css: { "text-align": "center" } },], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                { id: "part_no", header: [{ text: "Part No.", css: { "text-align": "center" } },], width: 150, css: { "text-align": "center" } },
                                                { id: "part_name", header: [{ text: "Part Description", css: { "text-align": "center" } },], width: 180, css: { "text-align": "center" }, },
                                                { id: "pick_check", header: [{ text: "pick_check", css: { "text-align": "center" } },], width: 180, css: { "text-align": "center" }, hidden:1 },
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