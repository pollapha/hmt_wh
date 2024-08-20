var header_PrintTag = function () {
    var menuName = "PrintTag_", fd = "Inventory/" + menuName + "data.php";

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
        //var obj = ele("form1").getValues();
        ajax(fd, {}, 1, function (json) {
            setTable("dataT1", json.data);
        }, btn);
    };

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_PrintTag",
        body:
        {
            id: "PrintTag_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Reprint Tag", type: "header" },
                    {
                        view: "form", scroll: false, id: $n('form1'),
                        elements: [
                            {
                                rows: [
                                    {
                                        rows:
                                            [
                                                {
                                                    view: "fieldset", label: "Input Tag No.", body:
                                                    {
                                                        rows: [
                                                            vw1('text', 'tag_no', 'Case Tag/ FG Tag/ Part Tag', { disabled: false, required: false, hidden: 0, }),
                                                            vw1('text', 'case_tag_no', 'Part Tag (ทั้งหมดใน Case Tag)', {
                                                                disabled: false, required: false, hidden: 0, placeholder: 'Case Tag'
                                                            }),
                                                            {
                                                                cols: [
                                                                    {},
                                                                    vw1("button", 'btn_reprint', "Reprint", {
                                                                        css: "webix_blue",
                                                                        icon: "mdi mdi-printer", type: "icon",
                                                                        tooltip: { template: "ปริ้นใหม่", dx: 10, dy: 15 },
                                                                        width: 120,
                                                                        on:
                                                                        {
                                                                            onItemClick: function () {
                                                                                var data = ele('tag_no').getValue();
                                                                                var case_tag_no = ele('case_tag_no').getValue();
                                                                                var length = data.length;
                                                                                var str = data.substring(0, 1);

                                                                                if (case_tag_no == '') {
                                                                                    if (str == 'R') {
                                                                                        if (length == 12) {
                                                                                            setTimeout(function () {
                                                                                                window.open("print/doc/case_tag.php?data=" + data, '_blank');
                                                                                            }, 0);
                                                                                        } else {
                                                                                            window.open("print/doc/part_tag.php?data=" + data, '_blank');
                                                                                        }
                                                                                    } else if (str == 'F') {
                                                                                        window.open("print/doc/fg_tag.php?data=" + data, '_blank');
                                                                                    }
                                                                                } else {
                                                                                    window.open("print/doc/part_tag_by_case.php?data=" + case_tag_no, '_blank');
                                                                                }


                                                                                ele('form1').setValues('');
                                                                            }
                                                                        }
                                                                    }),
                                                                    vw1("button", 'btn_clear_all', "Clear", {
                                                                        css: "webix_secondary",
                                                                        icon: "mdi mdi-backspace", type: "icon",
                                                                        tooltip: { template: "ล้างข้อมูล", dx: 10, dy: 15 },
                                                                        width: 120,
                                                                        on:
                                                                        {
                                                                            onItemClick: function () {
                                                                                ele('form1').setValues('');
                                                                            }
                                                                        }
                                                                    }),
                                                                    {}
                                                                ]
                                                            },
                                                        ]
                                                    },
                                                },
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