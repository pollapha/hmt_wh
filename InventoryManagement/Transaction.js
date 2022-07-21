var header_Transaction = function () {
    var menuName = "Transaction_", fd = "InventoryManagement/" + menuName + "data.php";

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
            setTable('dataT1', json.data);
        }, btn);
    };

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_Transaction",
        body:
        {
            id: "Transaction_id",
            type: "clean",
            rows:
                [
                    {
                        view: "form", paddingY: 0, scroll: false, id: $n('form1'),
                        elements: [
                            {
                                rows: [
                                    {
                                        cols: [
                                            vw1('button', 'find', 'Find (ค้นหา)', {
                                                width: 100,
                                                on: {
                                                    onItemClick: function () {
                                                        loadData();
                                                    }
                                                }
                                            }),
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
                                datatype: "json", headerRowHeight: 25, leftSplit: 3, editable: true,
                                scheme:
                                {
                                    $change: function (obj) {
                                        var css = {};
                                        obj.$cellCss = css;
                                    }
                                },
                                columns: [
                                    { id: "NO", header: "No.", css: "rank", width: 50, sort: "int" },
                                    { id: "GRN_Number", header: ["GRN Number", { content: "textFilter" }], width: 150 },
                                    { id: "PS_Number", header: ["PS Number", { content: "textFilter" }], width: 150 },
                                    { id: "Package_Number", header: ["Package Number", { content: "textFilter" }], width: 140 },
                                    { id: "Serial_Number", header: ["Serial Number", { content: "textFilter" }], width: 200 },
                                    { id: "Part_No", header: ["Part No.", { content: "textFilter" }], width: 120 },
                                    { id: "Qty", header: ["Qty", { content: "textFilter" }], width: 50 },
                                    { id: "Trans_Type", header: ["Transaction Type", { content: "textFilter" }], width: 150 },
                                    { id: "From_Area", header: ["From Area", { content: "textFilter" }], width: 100 },
                                    { id: "To_Area", header: ["To Area", { content: "textFilter" }], width: 100 },
                                    { id: "From_Location_Code", header: ["From Location", { content: "textFilter" }], width: 120 },
                                    { id: "To_Location_Code", header: ["To Location", { content: "textFilter" }], width: 120 },
                                    { id: "Pick_Number", header: ["Pick Number", { content: "textFilter" }], width: 120 },
                                    { id: "FIFO_No", header: ["FIFO No.", { content: "textFilter" }], width: 120 },
                                    { id: "Creation_DateTime", header: ["Creation Date", { content: "textFilter" }], width: 120 },
                                    { id: "Created_By", header: ["Created By", { content: "textFilter" }], width: 120 },
                                    { id: "Last_Updated_DateTime", header: ["Last Updated Date", { content: "textFilter" }], width: 120 },
                                    { id: "Updated_By", header: ["Updated By", { content: "textFilter" }], width: 120 },
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
                }
            }
        }
    };
};