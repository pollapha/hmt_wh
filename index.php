<?php
if(!ob_start("ob_gzhandler")) ob_start();
// header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
// header('Cache-Control: no-store, no-cache, must-revalidate');
// header('Cache-Control: post-check=0, pre-check=0', FALSE);
// header('Pragma: no-cache');
include('start.php');
session_start();
if(empty($_SESSION['xxxID']))
{
	header("Location:login.php");
}else
{
    include('php/connection.php');
    $cBy = $_SESSION['xxxID'];
    if ($result = $mysqli->query("SELECT t1.user_id,concat(t1.user_fName,' ',t1.user_lname)user_fName,t1.user_image,t1.user_permission,concat('{',group_concat(concat('\"',t3.menu_menuUse,'\"',':[',t2.role_viwe,',',t2.role_insert,',',t2.role_update,',',t2.role_del,']') separator ','),'}')role
    from tbl_user t1 left join tbl_rolemaster t2 on t1.user_permission=t2.role_name 
    left join tbl_menu t3 on t2.menu_id = t3.menu_id
    where t1.user_id=$cBy and t1.user_status = 1 group by t1.user_id;")) 
    { 
        if($result->num_rows > 0)
        {
            $data = $result->fetch_object();
            $_SESSION['xxxRole'] = json_decode($data->role);
        }
    }

    /*if(!apcu_exists($TTV_CACHE_OJBJECT_DATA_PAGE))
    {
        $result = $mysqli->query("SELECT concat('{',group_concat(concat('\"',t2.menu_menuUse,'\":[',t1.data,']',',\"',t2.menu_menuUse,'_1_\":\"',t2.main,'\"')
         order by t1.menu_group separator ','),'}') data from
        (select group_concat(concat('{\"value\":\"',menu_menuId,' ',menu_menuName,'\",\"id\":\"',menu_menuUse,'\",\"icon\":\"',menu_icon,'\",\"css\":\"',menu_css,'\"
          ,\"details\":\"',menu_details,'\"}')
        order by substring_index(menu_menuId,'.',-1)*1 separator ',')data,menu_group 
        from tbl_menu where menu_header=0 group by menu_group order by menu_group,substring_index(menu_menuId,'.',-1)*1) t1
        inner join
        (select menu_group,menu_menuUse,concat(menu_menuId,'. ',menu_menuName) main
        from tbl_menu where menu_header=1 order by substring_index(menu_menuId,'.',-1)*1) t2 on t1.menu_group=t2.menu_group");
        $checkPageDataObject = json_decode($result->fetch_object()->data);
         apcu_add($TTV_CACHE_OJBJECT_DATA_PAGE, $checkPageDataObject);
    }
    else $checkPageDataObject = apcu_fetch($TTV_CACHE_OJBJECT_DATA_PAGE);*/
    $result = $mysqli->query("SELECT concat('{',group_concat(concat('\"',t2.menu_menuUse,'\":[',t1.data,']',',\"',t2.menu_menuUse,'_1_\":\"',t2.main,'\"')
         order by t1.menu_group separator ','),'}') data from
        (select group_concat(concat('{\"value\":\"',menu_menuId,' ',menu_menuName,'\",\"id\":\"',menu_menuUse,'\",\"icon\":\"',menu_icon,'\",\"css\":\"',menu_css,'\"
          ,\"details\":\"',menu_details,'\"}')
        order by substring_index(menu_menuId,'.',-1)*1 separator ',')data,menu_group 
        from tbl_menu where menu_header=0 group by menu_group order by menu_group,substring_index(menu_menuId,'.',-1)*1) t1
        inner join
        (select menu_group,menu_menuUse,concat(menu_menuId,'. ',menu_menuName) main
        from tbl_menu where menu_header=1 order by substring_index(menu_menuId,'.',-1)*1) t2 on t1.menu_group=t2.menu_group");
        $checkPageDataObject = json_decode($result->fetch_object()->data);


}
$pageID = '1';
$role;
/*if(isset($_GET['url']))
{
    $url = explode('/',filter_var(rtrim($_GET['url'],'/'),FILTER_SANITIZE_URL));
    $menuName = ($url[0] == '') ? 'homePage' : $url[0];
}
else $menuName='homePage';*/

/*echo $menuName .' '.$_GET['url'];
$mysqli->close();
exit();
*/
echo preg_replace('/\s{2,}/', '','
<!DOCTYPE html>
<html>
    <head>
        <title></title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link rel="shortcut icon" href="images/dscm_logo1.ico"> 
        <link rel="stylesheet" href="codebase/all.min.css" type="text/css" media="screen" charset="utf-8">
        <script src="codebase/all.min.js"></script>
        <script src="js/dayjs.min.js"></script>
        <script src="js/jquery.fileDownload.js"></script>
        
        <script>
        
            var systemDateFormat = (t) => {
                return dayjs(t).format("YYYY-MM-DD");
            };

            var thDateFormat = (t) => {
                return dayjs(t).format("DD-MM-YYYY");
            };

            var systemDateTimeFormat = (t) => {
                return dayjs(t).format("YYYY-MM-DD HH:mm:ss");
            };

            var thDateTimeFormat = (t) => {
                return dayjs(t).format("DD-MM-YYYY HH:mm:ss");
            };

            const datatableDateFormat = { format: thDateFormat, editFormat: systemDateFormat, editParse: systemDateFormat };

            const datatableDateTimeFormat = { format: thDateTimeFormat, editFormat: systemDateTimeFormat, editParse: systemDateTimeFormat };
        </script>

            
        <style>
        .toolbar_title{
            padding:10px 10px;line-height:10px;
            background:#3498db;color:#fff;
             text-align: center;
        }
        .webix_table_checkbox{
          width:14px;
          height:14px;
          margin-top:3px;
        }

        .rotation
        {
            transform: rotate(270deg);
            text-align: left;
        }
        
        .highlight-yellow
        {
            background-color:#F39C12;
            color:white;
        }

        .highlight-yellow span:only-child
        {
            color:white;
        }

        .highlight-blue
        {
            background-color:#3498db;
            color:white;
        }

        .highlight-blue span:only-child
        {
            color:white;
        }

        .highlight-red
        {
            background-color:#F64747;
            color:white;
        }

        .highlight-red span:only-child
        {
            color:white;
        }

        .highlight-gray
        {
            background-color:#6C7A89;
            color:white;
        }

        .highlight-gray span:only-child
        {
            color: white;
        }

        .highlight-bluelight
        {
            background-color:#D2E3EF;
            color:black;
        }

        .highlight-bluelight span:only-child
        {
            color:black;
        }

        .highlight-green
        {
            background-color:#27ae60;
            color:white;
        }

        .highlight-green span:only-child
        {
            color:white;
        }

        .webix_cal_body .webix_cal_row 
        {
            clear:none;
        }

        
        .webix_drag_handle{
            background-image:url(images/handle.png);
            background-repeat: no-repeat;
            background-position: -2px 3px;
            width:20px;
            height:20px;
            cursor:n-resize;
        }

        .webix_drag_over{
			border-top:1px dashed orange;
			position:relative;
			top:-1px; height:19px;
        }
        
        .map {
            height: 100%;
          }


        .webix_view
        {
            font-family:"Bai Jamjuree",Tahoma;font-size:12px;cursor:default;overflow:hidden;border:0 solid #ddd;white-space:normal;-webkit-appearance:none
        } 


        </style>
    </head>
    <body>
    <script>
');
include('header.php');
echo  '</script><script src="main.js?project='.$TTV_PROJECT_NAME.'&v=2" charset="utf-8"></script></body></html>';
?>

         