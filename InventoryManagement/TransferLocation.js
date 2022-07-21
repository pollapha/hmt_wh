var header_TransferLocation = function () {
    var menuName = "TransferLocation_", fd = "InventoryManagement/" + menuName + "data.php";

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

    function loadData2(btn) {
        var obj = ele('form2').getValues();
        ajax(fd, obj, 3, function (json) {
            setTable('dataT2', json.data);
        }, btn);
    };

    var cells =
        [
            {
                header: "Storage",
                body:
                {
                    view: "form",
                    id: $n("form1"),
                    on:
                    {

                        "onSubmit": function (view, e) {

                            if (view.config.name == 'Package_Number') {

                                view.blur();
                                var obj = ele('form1').getValues();
                                ajax(fd, obj, 2, function (json) {
                                    loadData();
                                    ele('save').show();
                                    ele('Location_Code').show();
                                    webix.UIManager.setFocus(ele('Location_Code'));
                                    console.log('1');
                                }
                                    , null,

                                    function (json) {
                                        ele('GRN_Number').setValue('');
                                        ele('Package_Number').setValue('');
                                        ele('Location_Code').setValue('');
                                        ele('dataT1').clearAll();
                                        ele('save').hide();
                                        ele('Location_Code').hide();
                                        webix.UIManager.setFocus(ele('GRN_Number'));
                                        console.log('2');
                                    });

                            }


                            else if (webix.UIManager.getNext(view).config.type == 'line') {

                                webix.UIManager.setFocus(webix.UIManager.getNext(webix.UIManager.getNext(view)));
                                console.log('3');
                                //view.disable();

                            }

                            else {

                                webix.UIManager.setFocus(webix.UIManager.getNext(view));
                                console.log('4');

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
                                                vw1("text", 'GRN_Number', "GRN Number", { width: 200, }),
                                                vw1("text", 'Package_Number', "Package Number", { width: 200, }),
                                                vw1("text", 'Location_Code', "Location Code", {
                                                    width: 200,
                                                    hidden: 1
                                                }),
                                            ]

                                        },

                                        {
                                            rows: [
                                                {},
                                                vw1('button', 'save', 'Save (บันทึก)', {
                                                    type: 'form',
                                                    width: 100,
                                                    hidden: 1,
                                                    on: {
                                                        onItemClick: function () {
                                                            var obj = ele('form1').getValues();
                                                            console.log(obj);
                                                            ajax(fd, obj, 21, function (json) {
                                                                loadData();
                                                                ele('GRN_Number').setValue('');
                                                                ele('Package_Number').setValue('');
                                                                ele('Location_Code').setValue('');
                                                                ele('save').hide();
                                                                ele('Location_Code').hide();
                                                                webix.UIManager.setFocus(ele('GRN_Number'));
                                                            }, null,
                                                                function (json) {
                                                                    ele('Location_Code').setValue('');
                                                                    webix.UIManager.setFocus(ele('Location_Code'));
                                                                });
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
            },
            {
                header: "Pick",
                body: {
                    view: "form",
                    id: $n("form2"),
                    on:
                    {

                        "onSubmit": function (view, e) {

                            if (view.config.name == 'Package_Number') {

                                view.blur();
                                var obj = ele('form2').getValues();
                                ajax(fd, obj, 4, function (json) {
                                    loadData2();
                                    ele('save_pick').show();
                                    ele('Location_Code_Pick').show();
                                    webix.UIManager.setFocus(ele('Location_Code_Pick'));
                                }
                                    , null,

                                    function (json) {
                                        ele('PS_Number').setValue('');
                                        ele('Package_Number_Pick').setValue('');
                                        ele('Location_Code_Pick').setValue('');
                                        ele('dataT2').clearAll();
                                        ele('save_pick').hide();
                                        ele('Location_Code_Pick').hide();
                                        webix.UIManager.setFocus(ele('PS_Number'));
                                    });

                            }


                            else if (webix.UIManager.getNext(view).config.type == 'line') {

                                webix.UIManager.setFocus(webix.UIManager.getNext(webix.UIManager.getNext(view)));

                                //view.disable();

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
                                                vw1("text", 'PS_Number', "PS Number", { width: 200, }),
                                                vw2("text", 'Package_Number_Pick', 'Package_Number', "Package Number", { width: 200, }),
                                                vw2("text", 'Location_Code_Pick', 'Location_Code', "Location Code", { width: 200, hidden: 1 }),
                                            ]

                                        },

                                        {
                                            rows: [
                                                {},
                                                vw1('button', 'save_pick', 'Save (บันทึก)', {
                                                    type: 'form',
                                                    width: 100,
                                                    hidden: 1,
                                                    on: {
                                                        onItemClick: function () {
                                                            var obj = ele('form2').getValues();
                                                            console.log(obj);
                                                            ajax(fd, obj, 22, function (json) {
                                                                loadData2();
                                                                ele('PS_Number').setValue('');
                                                                ele('Package_Number_Pick').setValue('');
                                                                ele('Location_Code_Pick').setValue('');
                                                                ele('save_pick').hide();
                                                                ele('Location_Code_Pick').hide();
                                                                webix.UIManager.setFocus(ele('PS_Number'));
                                                            }, null,
                                                                function (json) {
                                                                    ele('Location_Code_Pick').setValue('');
                                                                    webix.UIManager.setFocus(ele('Location_Code_Pick'));
                                                                });
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
                                        view: "datatable", id: $n("dataT2"), navigation: true, select: true, editaction: "custom",
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
                                            { id: "PS_Number", header: ["PS Number", { content: "textFilter" }], width: 200 },
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
            },
        ]

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_TransferLocation",
        body:
        {
            id: "TransferLocation_id",
            type: "clean",
            rows:
                [
                    { view: "tabview", cells: cells, multiview: { fitBiggest: true } },
                    // {
                    //     view: "form",
                    //     id: $n("form1"),
                    //     on:
                    //     {

                    //         "onSubmit": function (view, e) {

                    //             if (view.config.name == 'Package_Number') {

                    //                 view.blur();
                    //                 var obj = ele('form1').getValues();
                    //                 ajax(fd, obj, 2, function (json) {
                    //                     loadData();
                    //                     ele('save').show();
                    //                     ele('Location_Code').show();
                    //                     webix.UIManager.setFocus(ele('Location_Code'));
                    //                 }
                    //                     , null,

                    //                     function (json) {
                    //                         ele('GRN_Number').setValue('');
                    //                         ele('Package_Number').setValue('');
                    //                         ele('Location_Code').setValue('');
                    //                         ele('dataT1').clearAll();
                    //                         ele('save').hide();
                    //                         ele('Location_Code').hide();
                    //                         webix.UIManager.setFocus(ele('GRN_Number'));
                    //                     });

                    //             }


                    //             else if (webix.UIManager.getNext(view).config.type == 'line') {

                    //                 webix.UIManager.setFocus(webix.UIManager.getNext(webix.UIManager.getNext(view)));

                    //                 //view.disable();

                    //             }

                    //             else {

                    //                 webix.UIManager.setFocus(webix.UIManager.getNext(view));

                    //             }

                    //         }

                    //     },
                    //     elements:
                    //         [
                    //             {
                    //                 cols:
                    //                     [
                    //                         {
                    //                             cols: [
                    //                                 vw1("text", 'GRN_Number', "GRN Number", {width: 200,}),
                    //                                 vw1("text", 'Package_Number', "Package Number", {width: 200,}),
                    //                                 vw1("text", 'Location_Code', "Location Code", { width: 200, hidden: 1 }),
                    //                             ]

                    //                         },

                    //                         {
                    //                             rows: [
                    //                                 {},
                    //                                 vw1('button', 'save', 'Save (บันทึก)', {
                    //                                     type: 'form',
                    //                                     width: 100,
                    //                                     hidden: 1,
                    //                                     on: {
                    //                                         onItemClick: function () {
                    //                                             var obj = ele('form1').getValues();
                    //                                             console.log(obj);
                    //                                             ajax(fd, obj, 21, function (json) {
                    //                                                 loadData();
                    //                                                  ele('GRN_Number').setValue('');
                    //                                                  ele('Package_Number').setValue('');
                    //                                                  ele('Location_Code').setValue('');
                    //                                                  ele('save').hide();
                    //                                                  ele('Location_Code').hide();
                    //                                                 webix.UIManager.setFocus(ele('GRN_Number'));
                    //                                             }, null,
                    //                                                 function (json) {
                    //                                                     ele('Location_Code').setValue('');
                    //                                                     webix.UIManager.setFocus(ele('Location_Code'));
                    //                                                 });
                    //                                         }
                    //                                     }
                    //                                 }),
                    //                             ]
                    //                         },
                    //                         {}
                    //                     ]
                    //             },

                    //             {
                    //                 padding: 3,
                    //                 cols: [
                    //                     {
                    //                         view: "datatable", id: $n("dataT1"), navigation: true, select: true, editaction: "custom",
                    //                         resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                    //                         threeState: true, rowLineHeight: 25, rowHeight: 25,
                    //                         datatype: "json", headerRowHeight: 25, leftSplit: 2, editable: true,
                    //                         scheme:
                    //                         {
                    //                             $change: function (obj) {
                    //                                 var css = {};
                    //                                 obj.$cellCss = css;
                    //                             }
                    //                         },
                    //                         columns: [
                    //                             { id: "NO", header: "No.", css: "rank", width: 50, sort: "int" },
                    //                             { id: "GRN_Number", header: ["GRN Number", { content: "textFilter" }], width: 200 },
                    //                             { id: "Package_Number", header: ["Package Number", { content: "textFilter" }], width: 150 },
                    //                             { id: "FG_Serial_Number", header: ["Serial Number", { content: "textFilter" }], width: 200 },
                    //                             { id: "Qty", header: ["Qty", { content: "textFilter" }], width: 100 },
                    //                             { id: "Area", header: ["Area", { content: "textFilter" }], width: 150 },
                    //                             { id: "Location_Code", header: ["Location Code", { content: "textFilter" }], width: 150 },
                    //                         ],
                    //                         onClick:
                    //                         {
                    //                         },
                    //                         on: {
                    //                             // "onEditorChange": function (id, value) {
                    //                             // }
                    //                             "onItemClick": function (id) {
                    //                                 this.editRow(id);
                    //                             }
                    //                         }
                    //                     },
                    //                 ]
                    //             },

                    //         ]
                    // }
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