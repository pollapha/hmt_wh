var header_Picking = function()
{
	var menuName="Picking_",fd = "Shipping/"+menuName+"data.php";

    function init()
    {
    };

    function ele(name)
    {
        return $$($n(name));
    };

    function $n(name)
    {
        return menuName+name;
    };
    
    function focus(name)
    {
        setTimeout(function(){ele(name).focus();},100);
    };
    
    function setView(target,obj)
    {
        var key = Object.keys(obj);
        for(var i=0,len=key.length;i<len;i++)
        {
            target[key[i]] = obj[key[i]];
        }
        return target;
    };

    function vw1(view,name,label,obj)
    {
        var v = {view:view,required:true,label:label,id:$n(name),name:name,labelPosition:"top"};
        return setView(v,obj);
    };

    function vw2(view,id,name,label,obj)
    {
        var v = {view:view,required:true,label:label,id:$n(id),name:name,labelPosition:"top"};
        return setView(v,obj);
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
        id:"header_Picking",
        body: 
        {
        	id:"Picking_id",
        	type:"clean",
    		rows:
    		[
				{ view: "template", template: "", type: "header" },
    		    {

                }
            ],on:
            {
                onHide:function()
                {
                    
                },
                onShow:function()
                {

                },
                onAddView:function()
                {
                	init();
                }
            }
        }
    };
};