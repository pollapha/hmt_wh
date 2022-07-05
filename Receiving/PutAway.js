var header_PutAway = function () {
    var menuName = "PutAway_", fd = "Receiving/" + menuName + "data.php";

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
        var obj = ele('form1').getValues();
        ajax(fd, obj, 1, function (json) {
            setTable('dataT1', json.data);
        }, btn);
    };

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_PutAway",
        body:
        {
            id: "PutAway_id",
            type: "clean",
            rows:
                [
                    {
                        view: "form",
                        id: $n("form1"),
                        on:
                        {

                            "onSubmit": function (view, e) {

                                if (view.config.name == 'Package_Number') {

                                    view.blur();

                                }
                                if (view.config.name == 'Location_Code') {
                                    view.blur();
                                    var obj = ele('form1').getValues();
                                    ajax(fd, obj, 2, function (json) {
                                        loadData();
                                        webix.UIManager.setFocus(ele('GRN_Number'));
                                    }
                                        , null,

                                        function (json) {
                                            loadData();
                                            ele('GRN_Number').setValue('');
                                            ele('Package_Number').setValue('');
                                            ele('Location_Code').setValue('');
                                            webix.UIManager.setFocus(ele('GRN_Number'));
                                        });
                                }

                                else if (webix.UIManager.getNext(view).config.type == 'line') {

                                    webix.UIManager.setFocus(webix.UIManager.getNext(webix.UIManager.getNext(view)));

                                    view.disable();

                                }

                                else {

                                    webix.UIManager.setFocus(webix.UIManager.getNext(view));

                                }

                            }

                        },
                        elements:
                            [
                                {
                                    cols:
                                        [
                                            {
                                                cols: [
                                                    vw1("text", 'GRN_Number', "GRN Number", {}),
                                                    vw1("text", 'Package_Number', "Package Number", {}),
                                                    vw1("text", 'Location_Code', "Location Code", {}),
                                                ]

                                            },

                                            {
                                                rows: [
                                                    {},
                                                    vw1('button', 'save', 'Save (บันทึก)', {
                                                        type: 'form',
                                                        width: 100,
                                                        on: {
                                                            onItemClick: function () {
                                                                var obj = ele('form1').getValues();
                                                                console.log(obj);
                                                                ajax(fd, obj, 21, function (json) {
                                                                    loadData();
                                                                    ele('GRN_Number').setValue('');
                                                                    ele('Package_Number').setValue('');
                                                                    ele('Location_Code').setValue('');
                                                                    webix.UIManager.setFocus(ele('GRN_Number'));
                                                                }, null,
                                                                    function (json) {
                                                                        //ele('find').callEvent("onItemClick", []);
                                                                    });
                                                                webix.UIManager.setFocus(ele('GRN_Number'));
                                                            }
                                                        }
                                                    }),
                                                ]
                                            },
                                            {}
                                        ]
                                },

                                {
                                    padding: 3,
                                    cols: [
                                        {
                                            view: "datatable", id: $n("dataT1"), navigation: true, select: true, editaction: "custom",
                                            resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                                            threeState: true, rowLineHeight: 25, rowHeight: 25,
                                            datatype: "json", headerRowHeight: 25, leftSplit: 2, editable: true,
                                            scheme:
                                            {
                                                $change: function (obj) {
                                                    var css = {};
                                                    obj.$cellCss = css;
                                                }
                                            },
                                            columns: [
                                                { id: "NO", header: "No.", css: "rank", width: 50, sort: "int" },
                                                { id: "GRN_Number", header: ["GRN Number", { content: "textFilter" }], width: 200 },
                                                { id: "Package_Number", header: ["Package Number", { content: "textFilter" }], width: 150 },
                                                { id: "FG_Serial_Number", header: ["Serial Number", { content: "textFilter" }], width: 200 },
                                                { id: "Qty", header: ["Qty", { content: "textFilter" }], width: 100 },
                                                { id: "Area", header: ["Area", { content: "textFilter" }], width: 150 },
                                                { id: "Location_Code", header: ["Location Code", { content: "textFilter" }], width: 150 },
                                            ],
                                            onClick:
                                            {
                                            },
                                            on: {
                                                // "onEditorChange": function (id, value) {
                                                // }
                                                "onItemClick": function (id) {
                                                    this.editRow(id);
                                                }
                                            }
                                        },
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