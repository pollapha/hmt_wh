var header_toolbar = {
    view: "toolbar",
    elements: [{
        view: "label",
        label: "<a href="+_getRootUrl+"><img class=\'photo\' src=\'images/dscm_th.png\' height=\'40\' /></a>",
        width: 200
    }, {
        height: 46,
        id: "person_template",
        css: "header_person",
        borderless: !0,
        width: 280,
        data: {
            id:_imageCurrentUser,
            name:_nameCurrentUser
        },
        template: function(e) {
            var t = "<div style=\'height:100%;width:100%;\' onclick=\'webix.$$(\"profilePopup\").show(this)\'>";
            return t += "<img class=\'photo\' src=\'images/user/" + e.id + "\' /><span class=\'name\'>" + e.name + "</span>", t += "<span class=\'webix_icon fa-angle-down\'></span></div>"
        }
    },
    {
        rows:
        [
            {
                id:"toolbar_title",
                css: "toolbar_title",
                borderless: !0,
                template: "<font size=\'5\'>#text#</font>",
                data: {
                    text: ""
                }
            }
        ],
        align:"center"
        
    },
    {
        cols:
        [
            /* {
                view: "label",
                label: "<a href=\''.$getRootUrl.'\'><img class=\'photo\' src=\'images/mmth.png\' height=\'40\' /></a>",
                align:"right"
            },
            {
                view: "label",
                label: "<a href=\''.$getRootUrl.'\'><img class=\'photo\' src=\'images/jcat-logo.jpg\' height=\'40\' /></a>",
                width: 56,
                align:"right"
            }, */
            {
                view: "label",
                label: "<a href=\''.$getRootUrl.'\'><img class=\'photo\' src=\'images/abt-logo.gif\' height=\'40\' /></a>",
                width: 63,
                align:"right"
            }
        ]
    }

    ]
};

var header_menu = {
    width: 230,
    rows: 
    [
        {
            view: "tree",
            id: "app:menu",
            type: "menuTree",
            css: "menu",
            activeTitle: !0,
            select: !0,
            scroll:false,
            tooltip: 
            {
                template: function(e) 
                {
                    return e.$count ? "" : e.details
                }
            },
            on: 
            {
                onBeforeSelect: function(e) 
                {
                    return this.getItem(e).$count ? !1 : void 0
                },
                onAfterSelect: function(e)
                {
                    var t = this.getItem(e);
                    $$("toolbar_title").parse({text: t.value});
                    document.title = t.value;
                    var view = $$("renderTitle");
                    for(var i=-1,len=view.getChildViews().length;++i<len;)
                    {
                        if(view.getChildViews()[i].isVisible() == true)
                        {
                            view.getChildViews()[i].hide();
                            if(view.getChildViews()[i].getChildViews()[0].callEvent instanceof Function) view.getChildViews()[i].getChildViews()[0].callEvent("onHide", []);
                        }
                    }
                    if(header_menuObj[t.id])
                    {
                        var urlCut = getUrlMenu()+t.id;
                        history.pushState(t.id, null, window.location.href);
                        history.replaceState(t.id, null,urlCut);
                        if($$(header_menuObj[t.id]) )
                        {
                            $$(header_menuObj[t.id]).show();
                            if($$(header_menuObj[t.id]).getChildViews()[0].callEvent instanceof Function) $$(header_menuObj[t.id]).getChildViews()[0].callEvent("onShow", []);
                        }
                        else
                        {
                            view.addView(window[header_menuObj[t.id]]());
                            $$(header_menuObj[t.id]).getChildViews()[0].callEvent("onAddView", []);
                        }
                    }
                },
                onAfterClose: function(e)
                {
                    var p = localStorage.getItem("treeMenu") || "[]";
                    p = eval("("+p+")");
                    if(p.indexOf(e) == -1)
                    {
                        p[p.length] = e;
                        localStorage.setItem("treeMenu",JSON.stringify(p));
                    }
                },
                onAfterOpen: function(e)
                {
                    var p = localStorage.getItem("treeMenu") || "[]";
                    p = eval("("+p+")");
                    if(p.indexOf(e) !== -1)
                    {
                        p.splice(p.indexOf(e),1);
                        localStorage.setItem("treeMenu",JSON.stringify(p));
                    }
                }
            },
            data:_header_menuByRole
        }
    ]
};
var title = 
{
    rows: 
    [
        {
            height: 1,
            id: "title",
            css: "title",
            template: "<div class='header'></div><div class='details'></div>",
            data: {
                text: "",
                title: "Home",
                details:"หน้าแรก"
            }
        }, 
        {
            paddingX: 1,
            paddingY: 1,
            rows:
            [
            ]
        }
    ]
};
var submenu = 
{
    view: "submenu",
    id: "profilePopup",
    width: 200,
    padding: 0,
    data: [{
        id: "profileUser",
        icon: "user",
        value: "My Profile",
        details: "ข้อมูลส่วนตัว"
    }, {
        id: "changepass",
        icon: "cog",
        value: "Change Password",
        details: "เปลียนรหัสผ่าน"
    },{
        id: "logout",
        icon: "sign-out",
        value: "Logout",
        details: "ออกจากระบบ"
    }],
    type: {
        template: function(e) {
            return e.type ? "<div class='separator'></div>" : "<span class='webix_icon alerts fa-" + e.icon + "'></span><span>" + e.value + "</span>"
        }
    },
    click:function(id,e)
    {
        if('logout' == id) window.open("logOut.php","_self");
        this.hide();
        var view = $$("renderTitle"),t=this.getItem(id);
        if(document.title == t.value+" ("+t.details+")")
        {
            return;
        } 
        
        $$("toolbar_title").parse({text: t.value+" ("+t.details+")"});
        document.title = t.value+" ("+t.details+")";
        for(var i=-1,len=view.getChildViews().length;++i<len;)
        {
            if(view.getChildViews()[i].isVisible() == true)
            {
                view.getChildViews()[i].hide();
                if(view.getChildViews()[i].getChildViews()[0].callEvent instanceof Function) view.getChildViews()[i].getChildViews()[0].callEvent("onHide", []);
            }
        }

        if(header_menuObj[id])
        {
            $$("app:menu").unselectAll();
            var urlCut = getUrlMenu()+id;
            history.pushState(id, null, window.location.href);
            history.replaceState(id, null,urlCut);
            if($$(header_menuObj[id]) )
            {
                $$(header_menuObj[t.id]).show();
                if($$(header_menuObj[id]).getChildViews()[0].callEvent instanceof Function) $$(header_menuObj[id]).getChildViews()[0].callEvent("onShow", []);
            }
            else
            {
                view.addView(window[header_menuObj[id]]());
                if($$(header_menuObj[id]))
                $$(header_menuObj[id]).getChildViews()[0].callEvent("onAddView", []);
            }
        }
    }
};
// submenu;
webix.ui(
    submenu
);
var mainWebix = webix.ui(
    {   
        rows:
        [
            header_toolbar,
            {
                cols:
                [
                 
                    header_menu,{ view:"resizer" },
                    {
                        id:"renderTitle",height:window.innerHeight-55,
                        rows:
                        [

                        ]
                    }
                ]
            }
        ]
    }
);
webix.event(window, "resize", function()
{
    $$('renderTitle').define("height", window.innerHeight-55);
    $$('renderTitle').resize();
});
function fDisable(name,type)
{
    if(type) $$(name).disable();
    else $$(name).enable();
}
window.addEventListener("popstate", function(e)
{
    var character = e.state;
    var view = $$("renderTitle");
    for(var i=-1,len=view.getChildViews().length;++i<len;)
    {
        if(view.getChildViews()[i].isVisible() == true)
        {
            view.getChildViews()[i].hide();
            if(view.getChildViews()[i].getChildViews()[0].callEvent instanceof Function) view.getChildViews()[i].getChildViews()[0].callEvent("onHide", []);
        }
    }
    if(header_menuObj[character])
    {   
        $$("app:menu").blockEvent();
        $$("app:menu").select(character);
        $$("app:menu").unblockEvent();
        if($$(header_menuObj[character]))
        {
            $$(header_menuObj[character]).show();
            if($$(header_menuObj[character]).getChildViews()[0].callEvent instanceof Function) $$(header_menuObj[character]).getChildViews()[0].callEvent("onShow", []);
        }
        else
        {
            view.addView(window[header_menuObj[character]]());
            $$(header_menuObj[character]).getChildViews()[0].callEvent("onAddView", []);
        }
        var item = $$("app:menu").getSelectedItem();
        if(item) 
        {
            $$("toolbar_title").parse({text:item.value});
            document.title = item.value;
        }
        else 
        {
            $$("toolbar_title").parse({text:"Home (หน้าหลัก)"});
            document.title = "Home (หน้าหลัก)";
        }
    }
    else 
    {
        $$(header_menuObj["homePage"]) ? $$(header_menuObj["homePage"]).show() : view.addView(window[header_menuObj["homePage"]]());
        var urlCut = getUrlMenu();
        $$("app:menu").unselectAll();
        history.replaceState("homePage", null,urlCut);
        $$("toolbar_title").parse({text:"Home (หน้าหลัก)"});
        document.title = "Home (หน้าหลัก)";
    }
    
}); 
var menuName = window.location.pathname.split( "/" );
menuName = menuName[menuName.length-1].length == 0 ? "homePage" : menuName[menuName.length-1];
if(!header_menuObj[menuName]) menuName = '404';
if(header_menuObj[menuName])
{
    if($$(header_menuObj[menuName]))
    {
        $$(header_menuObj[menuName]).show()
    }
    else
    {
        $$("renderTitle").addView(window[header_menuObj[menuName]]());
        if($$(header_menuObj[menuName]))
        $$(header_menuObj[menuName]).getChildViews()[0].callEvent("onAddView", []);
    }
    $$("app:menu").blockEvent();
    $$("app:menu").select(menuName);
    $$("app:menu").unblockEvent();
    
    var item = $$("app:menu").getSelectedItem();
    if(item)
    {
        $$("toolbar_title").parse({text:item.value});
        document.title = item.value;
    } 
    else if($$("profilePopup").getItem(menuName))
    {
        var menu=$$("profilePopup"),t=menu.getItem(menuName);
        $$("toolbar_title").parse({text: t.value});
    }
    else 
    {
        $$("toolbar_title").parse({text:"Home (หน้าหลัก)"});
        document.title = "Home (หน้าหลัก)";
    }
}
var p = localStorage.getItem("treeMenu") || "[]",treeMenu=$$("app:menu");
p = eval("("+p+")");
for(var i=-1,len=p.length;++i<len;)
{
    if(treeMenu.isBranch(p[i])) treeMenu.close(p[i]);
}
$(".menu").slimScroll({
    height: window.innerHeight-55+"px",
    size: "6px",
    distance: "2px",
});

function TTV_changePageWithParams(page,param)
{
    var view = $$("renderTitle");
    for(var i=-1,len=view.getChildViews().length;++i<len;)
    {
        if(view.getChildViews()[i].isVisible() == true)
        {
            view.getChildViews()[i].hide();
            if(view.getChildViews()[i].getChildViews()[0].callEvent instanceof Function) view.getChildViews()[i].getChildViews()[0].callEvent("onHide", []);
        }
    }
    $$("app:menu").blockEvent();
    $$("app:menu").select(page);
    $$("app:menu").unblockEvent();
    var urlCut = getUrlMenu()+page;
    history.pushState(page, null, window.location.href);
    history.replaceState(page, null,urlCut+param);
    if($$(header_menuObj[page]) )
    {
        $$(header_menuObj[page]).show();
        if($$(header_menuObj[page]).getChildViews()[0].callEvent instanceof Function) $$(header_menuObj[page]).getChildViews()[0].callEvent("onShow", []);
    }
    else
    {
        view.addView(window[header_menuObj[page]]());
        $$(header_menuObj[page]).getChildViews()[0].callEvent("onAddView", []);
    }
}

function BufferLoader(context, urlList, callback) {
  this.context = context;
  this.urlList = urlList;
  this.onload = callback;
  this.bufferList = new Array();
  this.loadCount = 0;
}

BufferLoader.prototype.loadBuffer = function(url, index) {
  var request = new XMLHttpRequest();
  request.open("GET", url, true);
  request.responseType = "arraybuffer";

  var loader = this;

  request.onload = function() {
    loader.context.decodeAudioData(
      request.response,
      function(buffer) {
        if (!buffer) {
          alert('error decoding file data: ' + url);
          return;
        }
        loader.bufferList[index] = buffer;
        if (++loader.loadCount == loader.urlList.length)
          loader.onload(loader.bufferList);
      },
      function(error) {
        console.error('decodeAudioData error', error);
      }
    );
  };

  request.onerror = function() {
    alert('BufferLoader: XHR error');
  };

  request.send();
};

BufferLoader.prototype.load = function() {
  for (var i = 0; i < this.urlList.length; ++i)
  this.loadBuffer(this.urlList[i], i);
};

window.AudioContext = window.AudioContext || window.webkitAudioContext;
window.sound_context = new AudioContext();
window.sound_bufferList;
window.sound_bufferLoader = new BufferLoader(
  window.sound_context,
  [
    'sound/Pass.wav',
    'sound/wrong.wav',
    'sound/serial.mp3',
  ],sound_finishedLoading
);
window.sound_bufferLoader.load();
function sound_finishedLoading(bufferList)
{
  window.sound_bufferList = bufferList;
  /*window.playsound(1);*/
};
window.playsound = function(index)
{
  var source1 = window.sound_context.createBufferSource();
  source1.buffer = window.sound_bufferList[index-1];
  source1.connect(window.sound_context.destination);
  source1.start(0);
};


function _enable_(ar)
{
    for(var i=-1,len=ar.length;++i<len;)$$(ar[i]).enable();
}

function _disable_(ar)
{
    for(var i=-1,len=ar.length;++i<len;)$$(ar[i]).disable();
}

function comboSet(element,txt)
{
    var list = $$($$(element).config.suggest).getBody();
    list.clearAll();list.parse([txt]);list.select(list.getFirstId());list.hide();
    $$(element).setValue(txt);
}

function getUrlMenu()
{
    var urlArray = window.location.href.split("/");
    if(urlArray.length == 4)
        return urlArray[0]+"//"+urlArray[2]+"/";
    else
        return urlArray[0]+"//"+urlArray[2]+"/"+urlArray[3]+"/";
}

var Ap_tooltip = webix.ui({view:"tooltip", template:"#value#" });

webix.event(window, "mousemove", function(e){
  var view = $$(e);
  if (view && $$(view).config.name){    
    if($$(view).config.name == 'tooltip')
        Ap_tooltip.show($$(view).config, webix.html.pos(e));
  }
  else Ap_tooltip.hide();  
});

function msBox(msg,callback)
{
    webix.confirm(
    {
      title: "กรุณายืนยัน",ok:"ใช่", cancel:"ไม่",
      text:"คุณต้องการ <font color='#ca4635'><b>"+msg+"</b></font><br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
      callback:function(res)
      {
        if(res)
        {
            callback();
        }
      }
    });
};

function ajax(url,params,type,callback,btn,callbackType)
{
    if(btn) btn.disable();
    $.post(url, { obj: params, type: type })
    .done(function (data) 
    {
        if(btn) btn.enable();
        var json = JSON.parse(data);
        if (json.ch == 1) 
        {
            callback(json);
        }
        else if(json.ch == 2)
        {
            if(isFunction(callbackType)) callbackType(json);
            webix.alert({title:"<b>ข้อความจากระบบ</b>",ok:'ตกลง',text:json.data,callback:function(){}});
        }
        else if(json.ch == 9)
        {
            if(isFunction(callbackType)) callbackType(json);
            webix.alert({title:"<b>ข้อความจากระบบ</b>",ok:'ตกลง',text:json.data,callback:function(){}});
        }
        else if(json.ch == 10)
        {
            if(isFunction(callbackType)) callbackType(json);
            webix.alert({title:"<b>ข้อความจากระบบ</b>",ok:'ตกลง',text:json.data,callback:function(){window.open("login.php","_self");}});
        }
    });
};

function isFunction(x) {
  return Object.prototype.toString.call(x) == '[object Function]';
}

function inputEnter(input,code,e)
{
    if(code==13)
    {
        var list = $$(input.data.suggest).getBody();
        if(list.getSelectedItem() == undefined)
        {
            if(list.getFirstId())
            {
                list.select(list.getFirstId());
                input.setValue(list.getFirstId());
            }
        }
    }
};

function inputFeed(input,url)
{
    input.clearAll();
    input.load(url);
};

