<?php
mysqli_report(MYSQLI_REPORT_STRICT);
$mysqli = mysqli_init();
if (!$mysqli) {echo json_encode(array('ch'=>2,'data'=>'mysqli_init failed'));exit();}
try
{
  $mysqli->real_connect('127.0.0.1', 'root', '1234', 'tachi',3306);
  $mysqli->set_charset("utf8");
}
catch( mysqli_sql_exception $e )
{
    echo json_encode(array('ch'=>2,'data'=>'ไม่สามารถติดต่อฐานข้อมูลได้'));
    exit();
}

function str_format()
{
   $args = func_get_args();
   $str = mb_ereg_replace('{([0-9]+)}', '%\\1$s', array_shift($args));
   return vsprintf($str, array_values($args));
}

function sprintf_assoc( $string = '', $replacement_vars = array(), $prefix_character = '%' ) {
    if ( ! $string ) return '';
    if ( is_array( $replacement_vars ) && count( $replacement_vars ) > 0 ) {
        foreach ( $replacement_vars as $key => $value ) {
            $string = str_replace( $prefix_character . $key, $value, $string );
        }
    }
    return $string;
}
 
function printf_assoc( $string = '', $replacement_vars = array(), $prefix_character = '%' ) {
    echo sprintf_assoc( $string, $replacement_vars, $prefix_character );
}

function toArrayString($result,$ch)
{
  if($ch == 1)echo '[';
    $field = 0;
    $c = 0;
      while($obj = $result->fetch_object())
      { 
        $c++;
          if($field == 0)
          {
            $fieldName = array_keys(get_object_vars($obj));
            $numField = count($fieldName);
            $field = 1;
          }
          echo '[';
          for($i=0;$i<$numField;$i++)
          {
            // if (is_numeric ($obj->{$fieldName[$i]})) echo  $obj->{$fieldName[$i]};
            // else echo '"'.$obj->{$fieldName[$i]}.'"';
            echo '"'.$obj->{$fieldName[$i]}.'"';
            if($i < $numField-1) echo ',';
          }
          echo ']';
          if($c < $result->num_rows) echo ',';
      } 
  if($ch == 1) echo ']';
}

function toArrayStringAddNumberRow($result,$ch)
{
  if($ch == 1)echo '[';
  $len = $result->num_rows;
  if($len>0)
  {
    echo '["'; 
    $row = $result->fetch_array(MYSQLI_NUM);
    array_unshift($row,1);
    echo join('","', $row);
    echo '"]';
    for($i=1;$i<$len;$i++)
    {
      echo ',["'; 
      $row = $result->fetch_array(MYSQLI_NUM);
      array_unshift($row,$i+1);
      echo join('","', $row);
      echo '"]';
    }
  }
  if($ch == 1) echo ']';
}

function toArrayStringAddNumberRowSort($result,$ch,$nSort=0)
{
  $c=$nSort;
  if($ch == 1)echo '[';
  if($result->num_rows>0) 
  {
    echo '["'; 
    $row = $result->fetch_array(MYSQLI_NUM);
    array_unshift($row,++$c);
    echo join('","', $row);
    echo '"]';
  }
  while($row = $result->fetch_array(MYSQLI_NUM))
  {
    echo ',["'; 
    array_unshift($row,++$c);
    echo join('","', $row);
    echo '"]'; 
  }
  if($ch == 1) echo ']';
}


function toArrayStringOne($result,$ch)
{
  echo '[';
  $len = $result->num_rows;
  if($len>0)
  {
    echo '"'; 
    $row = $result->fetch_array(MYSQLI_NUM);
    echo join('","', $row);
    echo '"'; 
    for($i=1;$i<$len;$i++)
    {
      echo ',"'; 
      $row = $result->fetch_array(MYSQLI_NUM);
      echo join('","', $row);
      echo '"'; 
    }
  }
  echo ']';
}

function toJsonString($result,$ch)
{
  if($ch == 1)echo '[';
    $field = 0;
    $c = 0;
      while($obj = $result->fetch_object())
      { 
        $c++;
          if($field == 0)
          {
            $fieldName = array_keys(get_object_vars($obj));
            $numField = count($fieldName);
            $field = 1;
          }
          echo '{';
          for($i=0;$i<$numField;$i++)
          {
            // if (is_numeric ($obj->{$fieldName[$i]})) echo  '"'.$fieldName[$i].'":'.$obj->{$fieldName[$i]};
            // else echo '"'.$fieldName[$i].'":'.'"'.$obj->{$fieldName[$i]}.'"';
            echo '"'.$fieldName[$i].'":'.'"'.$obj->{$fieldName[$i]}.'"';
            if($i < $numField-1) echo ',';
          }
          echo '}';
          if($c < $result->num_rows) echo ',';
      } 
  if($ch == 1) echo ']';
}

function toJsonStringAddNumberRow($result,$ch)
{
  if($ch == 1)echo '[';
    $field = 0;
    $c = 0;
      while($obj = $result->fetch_object())
      { 
        $c++;
          if($field == 0)
          {
            $fieldName = array_keys(get_object_vars($obj));
            $numField = count($fieldName);
            $field = 1;
          }
          echo '{"No":'.$c.',';
          for($i=0;$i<$numField;$i++)
          {
            // if (is_numeric ($obj->{$fieldName[$i]})) echo  '"'.$fieldName[$i].'":'.$obj->{$fieldName[$i]};
            // else echo '"'.$fieldName[$i].'":'.'"'.$obj->{$fieldName[$i]}.'"';
            echo '"'.$fieldName[$i].'":'.'"'.$obj->{$fieldName[$i]}.'"';
            if($i < $numField-1) echo ',';
          }
          echo '}';
          if($c < $result->num_rows) echo ',';
      } 
  if($ch == 1) echo ']';
}

function toJsonStringOne($result,$ch)
{
  echo '{';
    $field = 0;
    $c = 0;
      while($obj = $result->fetch_object())
      { 
        $c++;
          if($field == 0)
          {
            $fieldName = array_keys(get_object_vars($obj));
            $numField = count($fieldName);
            $field = 1;
          }
          // echo '{';
          for($i=0;$i<$numField;$i++)
          {
            // if (is_numeric ($obj->{$fieldName[$i]})) echo  '"'.$fieldName[$i].'":'.$obj->{$fieldName[$i]};
            // else echo '"'.$fieldName[$i].'":'.'"'.$obj->{$fieldName[$i]}.'"';
            echo '"'.$fieldName[$i].'":'.'"'.$obj->{$fieldName[$i]}.'"';
            if($i < $numField-1) echo ',';
          }
          // echo '}';
          if($c < $result->num_rows) echo ',';
      } 
  echo '}';
}

function toTableString($result,$ch)
{
  echo '<table class="table table-bordered">';
  $field = 0;
    $c = 0;

      while($obj = $result->fetch_object())
      { 
        $c++;
          if($field == 0)
          {
            echo '<thead><tr><th>No</th>'; 
            $fieldName = array_keys(get_object_vars($obj));
            $numField = count($fieldName);
            $field = 1;
            for($j=0;$j<$numField;$j++)
            {
              echo '<th>'.$fieldName[$j].'</th>';
            }
            echo '</tr></thead><tbody>';

          }

          echo '<tr><td>'.$c.'</td>';
          for($i=0;$i<$numField;$i++)
          {
            echo '<td>'.$obj->{$fieldName[$i]}.'</td>';
          }
          echo '</tr>';
      }
  echo '</tbody></table>';
}

function toCsvStr($result,$header)
{
  
  echo $header;
  echo "\n";
  $c= 0;
  while($row = $result->fetch_array(MYSQLI_NUM))
  {
    echo ++$c;
    echo ','; 
    echo '"'; 
    echo join('","',$row);
    echo '"'; 
    echo "\n";
  }
}

function jsonRow($result,$row=true,$seq=0,$header='')
{
  $data = array();
  $colData = '';
  $isHeader = 0;
  
  if($header != '')
  {
    $isHeader = 1;
  }

  $i=$seq;
  $countRow = 0;

  if($row)
  {
    while ($row=$result->fetch_array(MYSQLI_ASSOC))
    {
      $row['NO'] = ++$i;
      $countRow++;
      if($isHeader == 1)
      {           
        if($colData != $row[$header])
        {
          $row['isHeader'] = 1;
          $colData = $row[$header];
        }
        else
        {
          $row['isHeader'] = 0;
        }
      }
      $data[] = $row;
    }
  }
  else
  {
    while ($row=$result->fetch_array(MYSQLI_ASSOC))
    {
      $countRow++;
      if($isHeader == 1)
      {           
        if($colData != $row[$header])
        {
          $row['isHeader'] = 1;
          $colData = $row[$header];
        }
        else
        {
          $row['isHeader'] = 0;
        }
      }
      $data[] = $row;
    }
  }
  return $data;
}

function jsonRowNotNull($result,$crow=true,$seq=0)
{
  $data = array();
  if($crow)
  {
    $i=$seq;
    while ( $row=array_map('htmlentities',$result->fetch_array(MYSQLI_ASSOC)) )
    {
      $row['NO'] = ++$i;
      $data[] = $row;
    }
  }
  else
  {
    while ( $row=array_map('htmlentities',$result->fetch_array(MYSQLI_ASSOC)) )
    {
      $data[] = $row;
    }
  }
  return $data;
}

function pageHelper($pq_curPage, $pq_rPP, $total_Records){
    $skip = ($pq_rPP * ($pq_curPage - 1));

    if ($skip >= $total_Records)
    {        
        $pq_curPage = ceil($total_Records / $pq_rPP);
        $skip = ($pq_rPP * ($pq_curPage - 1));
    }    
    return $skip;
}

function closeDB($mysqli)
{
  $mysqli->close();exit();
}

function closeDBT($mysqli,$type,$txt)
{
  echo json_encode(array('ch'=>$type,'data'=>$txt));
  $mysqli->close();exit();
}

function checkINT($mysqli,$data,$strtoupper=1)
{
  if($strtoupper == 1) return !isset($data) ? 0 : intval($mysqli->real_escape_string(trim(mb_strtoupper($data))));
  else return !isset($data) ? 0 : intval($mysqli->real_escape_string(trim($data)));
}

function checkTXT($mysqli,$data,$strtoupper=1)
{
  if($strtoupper == 1) return !isset($data) ? '' : $mysqli->real_escape_string(trim(mb_strtoupper($data)));
  else return !isset($data) ? '' : $mysqli->real_escape_string(trim($data));
}

function checkFLOAT($mysqli,$data,$strtoupper=1)
{
  if($strtoupper == 1) return !isset($data) ? 0 : floatval($mysqli->real_escape_string(trim(mb_strtoupper($data))));
  else return !isset($data) ? 0 : floatval($mysqli->real_escape_string(trim($data)));
}

function checkParams($post,$data)
{

  $result = array();
  for($i=0,$len=count($data);$i<$len;$i++)
  {
    $val = $data[$i];
    if($val != '')
    {
      $val = explode('=>',$val);
      $arrayCount = count($val);
      if($arrayCount == 1)
      {
        if(!isset($post[$val[0]])) $result[] = 'ไม่พบ '.$val[0];
      }
      else if($arrayCount == 2)
      {
        if(!isset($post[$val[0]][$val[1]])) $result[] = 'ไม่พบ '.$val[0].'=>'.$val[1];
      }
    }
  }
  return $result;
}

function sqlError($mysqli,$lineCode,$sql,$rollback=0,$showSql=0)
{
	if(!$re = $mysqli->query($sql))
	{
    $showDetail = $showSql == 0 ? $mysqli->error : $sql;
		if($rollback == 0)
			closeDBT($mysqli,2,'ERROR LINE '.$lineCode.'<br>'.$showDetail);
		else
		throw new Exception('ERROR LINE '.$lineCode.'<br>'. $showDetail);
	} 
	return $re;
}

function checkParamsAndDelare($post,$data,$mysqli)
{
  $result = array();
  for($i=0,$len=count($data);$i<$len;$i++)
  {
    $val = $data[$i];
    if($val != '')
    {
      $val = explode('=>',$val);
      $arrayCount = count($val);
      if($arrayCount == 1)
      {
        if(!isset($post[$val[0]])) $result[] = 'ไม่พบ '.$val[0];
      }
      else if($arrayCount == 2)
      {
        $varAr = explode(':',$val[1]);
        $varCount = count($varAr);        
        if($varCount>0)
        {
          if(!isset($post[$val[0]][$varAr[0]])) $result[] = 'ไม่พบ '.$val[0].'=>'.$varAr[0];
          else
          {
            if($varCount == 1)
            {
              
            }
            else if($varCount == 2)
            {
                if($varAr[1] == 's') $GLOBALS[$varAr[0]] = checkTXT($mysqli,$post[$val[0]][$varAr[0]]);
                else if($varAr[1] == 'i') $GLOBALS[$varAr[0]] = checkINT($mysqli,$post[$val[0]][$varAr[0]]);
                else if($varAr[1] == 'f') $GLOBALS[$varAr[0]] = checkFLOAT($mysqli,$post[$val[0]][$varAr[0]]);
            }
            else if($varCount >= 3)
            {
                if($varAr[1] == 's') $GLOBALS[$varAr[0]] = checkTXT($mysqli,$post[$val[0]][$varAr[0]],$varAr[2]);
                else if($varAr[1] == 'i') $GLOBALS[$varAr[0]] = checkINT($mysqli,$post[$val[0]][$varAr[0]],$varAr[2]);
                else if($varAr[1] == 'f') $GLOBALS[$varAr[0]] = checkFLOAT($mysqli,$post[$val[0]][$varAr[0]],$varAr[2]);
            }

            if($varCount >= 4)
            {
              if($varAr[1] == 's')
              {
                if(strlen($GLOBALS[$varAr[0]]) < intval($varAr[3]))
                {
                  $result[] = 'อักษรต้องยาว'.$varAr[3].'ตัวขึ้นไป '.str_replace('_',' ',$varAr[0]).'=>'.$GLOBALS[$varAr[0]];
                }
              }
              else if($varAr[1] == 'i')
              {
                if($GLOBALS[$varAr[0]] < intval($varAr[3]))
                {
                  $result[] = 'ตัวเลขต้องมากกว่า'.$varAr[3].'ขึ้นไป '.str_replace('_',' ',$varAr[0]).'=>'.$GLOBALS[$varAr[0]];
                }
              }
              else if($varAr[1] == 'f')
              {
                if($GLOBALS[$varAr[0]] < floatval($varAr[3]))
                {
                  $result[] = 'ตัวเลขต้องมากกว่า'.$varAr[3].'ขึ้นไป '.str_replace('_',' ',$varAr[0]).'=>'.$GLOBALS[$varAr[0]];
                }
              }
              
            }
          }
        }
        else
        {
          $result[] = 'ไม่พบ '.$val[0].'=>'.$val[1];
        }
      }
    }
  }
  return $result;
}

function prepareInsert($dataAr)
{
	$txt = '';
	$col = array();	
	$valueAr = array();
	for($i=0,$len=count($dataAr);$i<$len;$i++)
	{
		$data = $dataAr[$i];
		if($i==0)
		{
			$col = array_keys($data);
			$txt = '('.join(',',$col).') values';
		}
		
		$value = array();
		for($j=0,$len2=count($col);$j<$len2;$j++)
		{
			$v = $data[$col[$j]];
			if(is_string($v))
			{
				if(substr($v,0,4)=='sql=')
				{
					$vAr = explode('sql=',$v);
					$v = $vAr[1];
        }        
				else
				{
					$v = "'$v'";
				}				
      }
      else if(is_null($v))
      {
        $v = 'null';
      }
			$value[] = $v;	
		}
		$valueAr[] = '('.join(',',$value).')';
	}
	$txt .= join(',',$valueAr);
	return $txt;
}

function prepareInsertExists($dataAr)
{
	$txt = '';
	$col = array();	
	$valueAr = array();
	for($i=0,$len=count($dataAr);$i<$len;$i++)
	{
		$data = $dataAr[$i];
		if($i==0)
		{
			$col = array_keys($data);
			$txt = '('.join(',',$col).')';
		}
		
		$value = array();
		for($j=0,$len2=count($col);$j<$len2;$j++)
		{
			$v = $data[$col[$j]];
			if(is_string($v))
			{
				if(substr($v,0,4)=='sql=')
				{
					$vAr = explode('sql=',$v);
					$v = $vAr[1];
        }        
				else
				{
					$v = "'$v'";
				}				
      }
      else if(is_null($v))
      {
        $v = 'null';
      }
			$value[] = $v;	
		}
		$valueAr[] = '(select '.join(',',$value).')';
  }
  
	$txt .= "SELECT * FROM ".join(',',$valueAr)." AS tmp";
	return $txt;
}

function prepareUpdate($dataAr)
{
	$txt = '';
	$col = array();	
	$valueAr = array();
	for($i=0,$len=count($dataAr);$i<$len;$i++)
	{
		$data = $dataAr[$i];
		if($i==0)
		{
			$col = array_keys($data);
		}
		
		$value = array();
		for($j=0,$len2=count($col);$j<$len2;$j++)
		{
			$c = $col[$j];
			$v = $data[$c];
			if(is_string($v))
			{
				if(substr($v,0,4)=='sql=')
				{
					$vAr = explode('sql=',$v);
					$v = $vAr[1];
					$v = $c."=$v";
        }
				else
				{
					$v = $c."='$v'";
				}
      }
      else if(is_null($v))
      {
        $v = $c."=null";
      }
			else
			{
				$v = $c."=$v";
			}
			$value[] = $v;	
		}
		$valueAr[] = join(',',$value);
	}
	$txt .= join(',',$valueAr);
	return $txt;
}

function validateDate($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

?>
