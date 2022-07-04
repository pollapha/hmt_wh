var header_ConfirmGRN = function () {
    var menuName = "ConfirmGRN_", fd = "Receiving/" + menuName + "data.php";

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
        ajax(fd, {}, 2, function (json) {
            setTable('dataT1', json.data);
        }, btn);
    };


    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_ConfirmGRN",
        body:
        {
            id: "ConfirmGRN_id",
            type: "clean",
            rows:
                [
                    {
                        view: "form", scroll: false, id: $n('form1'),
                        elements: [
                            {
                                rows: [
                                    {
                                        cols: [
                                            vw1("text", 'GRN_Number', "GRN Number", {
                                                required: true, suggest: fd + "?type=1", width: 250
                                            }
                                            ),
                                            {
                                                rows: [
                                                    {},
                                                    vw1('button', 'find', 'Find (ค้นหา)', {
                                                        width: 100,
                                                        on: {
                                                            onItemClick: function () {
                                                                var obj = ele('form1').getValues();
                                                                console.log(obj);
                                                                ajax(fd, obj, 2, function (json) {
                                                                    ele('confirm').show();
                                                                    setTable('dataT1', json.data);
                                                                }, null,
                                                                    function (json) {
                                                                        //ele('find').callEvent("onItemClick", []);
                                                                    });
                                                            }
                                                        }
                                                    }),
                                                ]
                                            },
                                            {
                                                rows: [
                                                    {},
                                                    vw1('button', 'confirm', 'Confirm GRN', {
                                                        type: 'form',
                                                        width: 120,
                                                        hidden: 1,
                                                        on: {
                                                            onItemClick: function () {
                                                                var obj = ele('form1').getValues();
                                                                webix.confirm(
                                                                    {
                                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                        callback: function (res) {
                                                                            if (res) {
                                                                                ajax(fd, obj, 41, function (json) {
                                                                                    ele('GRN_Number').setValue('');
                                                                                    ele('confirm').hide();
                                                                                    ele('dataT1').clearAll();
                                                                                    
                                                                                }, null,
                                                                                    function (json) {
                                                                                        //ele('find').callEvent("onItemClick", []);
                                                                                    });
                                                                            }
                                                                            ele('confirm').show();
                                                                        }
                                                                    });
                                                            }
                                                        }
                                                    }),
                                                ]
                                            },
                                            {}
                                        ],
                                    },

                                ]
                            },
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
                                    { id: "Receive_DateTime", header: ["Receive DateTime", { content: "textFilter" }], width: 200 },
                                    { id: "DN_Number", header: ["DN Number", { content: "textFilter" }], width: 150 },
                                    { id: "Package_Number", header: ["Package Number", { content: "textFilter" }], width: 150 },
                                    { id: "FG_Serial_Number", header: ["FG Serial Number", { content: "textFilter" }], width: 200 },
                                    { id: "Qty", header: ["Qty", { content: "textFilter" }], width: 100 },
                                    { id: "Confirm_Receive_DateTime", header: ["Confirm Receive DateTime", { content: "textFilter" }], width: 200 },
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
                ], on:
            {
                onHide: function () {

                },
                onShow: function () {

                },
                onAddView: function () {
                    init();
                    //ele('form1').bind('dataT1');
                }
            }
        }
    };
};