var header_PutAway = function()
{
	var menuName="PutAway_",fd = "Receiving/"+menuName+"data.php";

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

	return {
        view: "scrollview",
        scroll: "native-y",
        id:"header_PutAway",
        body: 
        {
        	id:"PutAway_id",
        	type:"clean",
    		rows:
    		[
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