var header_CreatePicking = function () {
    var menuName = "CreatePicking_", fd = "Shipping/" + menuName + "data.php";

    function init() {
        webix.UIManager.setFocus(ele('scan'));
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

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_CreatePicking",
        body:
        {
            id: "CreatePicking_id",
            type: "clean",
            rows:
                [

                    {
                        view: "form", scroll: false, id: $n('form1'), on:

                        {

                            "onSubmit": function (view, e) {

                                var obj = ele('form1').getValues();
                                console.log(obj);
                                const propertyValues = Object.values(obj);

                                if (view.config.name == 'scan') {

                                    view.blur();

                                    if (propertyValues[0].length == 18) {

                                        ele('scan').setValue('');
                                        ele('PDS_No').setValue(propertyValues[0]);
                                        webix.UIManager.setFocus(ele('PDS_No'));

                                    }
                                    else if (propertyValues[0].length == 12) {

                                        ele('scan').setValue('');
                                        ele('Order_No').setValue(propertyValues[0]);
                                        webix.UIManager.setFocus(ele('Order_No'));

                                    }

                                    ajax(fd, obj, 11, function (json) {

                                        if (propertyValues[1] != '' || propertyValues[2] != '' && propertyValues[0] != '') {
                                            //console.log('obj : ', propertyValues[0].length);
                                            //ele('PDS_No').setValue('');
                                            ele('Order_No').setValue('');
                                            setTable('dataT1', json.data);

                                        }
                                        //console.log('obj 0 : ', propertyValues[0], ' , obj 1 : ', propertyValues[1]);
                                        if (propertyValues[0].length == 18 && propertyValues[0] != propertyValues[1] && propertyValues[1] != '') {

                                            setTable('dataT1', null);

                                        }

                                    }, null, function (json) {

                                        ele('scan').setValue('');
                                        ele('PDS_No').setValue('');
                                        ele('Order_No').setValue('');
                                        setTable('dataT1', null);

                                    });

                                    webix.UIManager.setFocus(ele('scan'));
                                }
                            },
                        },
                        elements: [
                            {
                                rows: [
                                    {
                                        cols: [
                                            vw1("text", 'scan', "Scan", { width: 250 }),
                                            vw1("text", 'PDS_No', "PDS Number", { width: 250 }),
                                            vw1("text", 'Order_No', "Order No.", { width: 250 }),
                                            {},
                                            {}
                                        ]
                                    },
                                ]
                            },
                        ]
                    },
                    {
                        padding: 3,
                        cols: [
                            {
                                view: "datatable", id: $n("dataT1"), navigation: true, select: "row", editaction: "custom",
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
                                    { id: "Customer", header: ["Customer", { content: "textFilter" }], width: 100 },
                                    { id: "Dock", header: ["Dock", { content: "textFilter" }], width: 100 },
                                    { id: "Sale_Part", header: ["Sale Part", { content: "textFilter" }], width: 120 },
                                    { id: "Delivery_Date", header: ["Delivery Date", { content: "textFilter" }], width: 150 },
                                    { id: "Bin_No", header: ["Bin No.", { content: "textFilter" }], width: 100 },
                                    { id: "Qty", header: ["Qty", { content: "textFilter" }], width: 70 },
                                    { id: "Order_No", header: ["Order No.", { content: "textFilter" }], width: 150 },
                                    { id: "Attri_Info", header: ["Attri Info", { content: "textFilter" }], width: 250 },
                                    { id: "Plan_Delivery_Date", header: ["Plan Delivery Date", { content: "textFilter" }], width: 150 },
                                    { id: "Plan_Bin_No", header: ["Plan Bin No.", { content: "textFilter" }], width: 120 },
                                    { id: "Part_No", header: ["Part No.", { content: "textFilter" }], width: 200 },
                                    { id: "Part_Name", header: ["Part Name", { content: "textFilter" }], width: 250 },
                                    { id: "PDS_No", header: ["PDS No.", { content: "textFilter" }], width: 150 },
                                    { id: "Pick_Qty", header: ["Pick Qty", { content: "textFilter" }], width: 100 },
                                    { id: "Pick_Status", header: ["Pick Status", { content: "textFilter" }], width: 150 },
                                    { id: "Ship_Qty", header: ["Ship Qty", { content: "textFilter" }], width: 100 },
                                    { id: "Ship_Status", header: ["Ship Status", { content: "textFilter" }], width: 120 },
                                    { id: "Slide_Status", header: ["Slide Status", { content: "textFilter" }], width: 120 },
                                    { id: "KanbanID", header: ["Kanban ID", { content: "textFilter" }], width: 120 },
                                    { id: "SNP", header: ["SNP", { content: "textFilter" }], width: 100 },
                                    { id: "Box_Type", header: ["Box Type", { content: "textFilter" }], width: 120 },
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