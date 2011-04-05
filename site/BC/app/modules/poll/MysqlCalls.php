<?php

function get_mysql_connection_string(){

    $arr=parse_ini_file("pws.ini",true);
/*
    echo "get_mysql_conntection_string<br/>";
    echo "ldap host = ".$arr['ldap']['ldap_host']."<br/>";
    echo "mysql_host = ".$arr['mysql']['mysql_host']."<br/>";
    echo "mysql_user = ".$arr['mysql']['mysql_user']."<br/>";
    echo "mysql_password = ".$arr['mysql']['mysql_password']."<br/>";
    echo "mysql_database = ".$arr['mysql']['mysql_database']."<br/>";
*/

    $mysql_connection_string = array(
        'host'=>$arr['mysql']['mysql_host'],
        'user'=>$arr['mysql']['mysql_user'],
        'password'=>$arr['mysql']['mysql_password'],
        'database'=>$arr['mysql']['mysql_database'],
        'table'=>"user_information",
        'table_col_names' => array('uid', 'first_name', 'last_name', 'mail', 'affiliate', 'title', 'dir')
    );

    return $mysql_connection_string;

}

// insert a new user
function insert_user($rowdata, $mysql_connection_string){
    $host=$mysql_connection_string["host"];
    $user=$mysql_connection_string["user"];
    $password=$mysql_connection_string["password"];
    $database=$mysql_connection_string["database"];
    $table=$mysql_connection_string["table"];
    $table_col_names = $mysql_connection_string["table_col_names"];

    if (is_null($rowdata) || is_null($rowdata["uid"])){
        echo "row data is empty <br>";
        return false;
    }


    //echo "connecting...$host, $user, $password <br>";
    $link = mysql_connect($host,$user,$password);
    if (!$link) {
        die('Could not connect: ' . mysql_error());
    }
    //echo "finished connecting...<br>";
    @mysql_select_db($database) or die( "Unable to select database");

    //echo "insertinging...<br>";
    $query =   "insert into $table (";
    $first_time = true;
    foreach ($table_col_names as $item ){
        if ($first_time){
            $first_time=false;
        }
        else{
            $query = $query.",";
        }
        $query = $query.$item;
    }
    $query = $query.") values(";
    $first_time = true;
    foreach ($table_col_names as $item ){
        if ($first_time){
            $first_time=false;
        }
        else{
            $query = $query.",";
        }
	    //echo ('name='.$item.', value='.$rowdata[$item].'<br>');
        $tmp = $rowdata[$item];
        $tmp = str_replace("'", "\'", $tmp);
        $tmp = str_replace('"', '\"', $tmp);

        $query = $query."'".$tmp."'";
        //$query = $query."'".$rowdata[$item]."'";
    }
    $query = $query.")";
    //echo "query=".$query."<br>";

    mysql_query($query);

    //echo "closinging...<br>";
    mysql_close();

    return true;
}

// update a existing user from ldap
function update_user($rowdata, $mysql_connection_string){
    $host=$mysql_connection_string["host"];
    $user=$mysql_connection_string["user"];
    $password=$mysql_connection_string["password"];
    $database=$mysql_connection_string["database"];
    $table=$mysql_connection_string["table"];
    $table_col_names = $mysql_connection_string["table_col_names"];

    //echo "update_user...<br/>";

    if (is_null($rowdata) || is_null($rowdata["uid"])){
        echo "row data is empty <br>";
        return false;
    }

    $link = mysql_connect($host,$user,$password);
    if (!$link) {
        die('Could not connect: ' . mysql_error());
    }
    @mysql_select_db($database) or die( "Unable to select database");

    // update user_information
    $uid = $rowdata['uid'];
    $query =   "update user_information set first_name='".$rowdata['firstName']."', ";
    $query = $query." last_name='".$rowdata['lastName']."', ";
    $query = $query." affiliate='".$rowdata['affiliate']."', ";
    $mail = $rowdata['mail'];
    if (is_null($mail) || strlen($mail) == 0){
        $mail = $uid."@bc.edu";
    }
    $query = $query." mail='".$mail."' ";
    $query = $query." where uid='".$uid."' ";

    //echo $query."<br/>";

    mysql_query($query);

    // delete old user_department
    $query =   "delete from user_department where user_id='".$rowdata['uid']."' ";
    //echo $query."<br/>";
    mysql_query($query);

    // insert new department code
    $query0 =   "insert into  user_department(user_id, department_code) values('";
    $departs = $rowdata['departmentbranch'];
    foreach ($departs as $item ){
        if ($item != $departs['count']){
            $query = $query0.$uid."', '".$item."')";
            //echo $query."<br/>";
            mysql_query($query);
        }
    }

    //echo "closinging...<br>";
    mysql_close();

    return true;
}

// delete existing user
function delete_user($uid, $mysql_connection_string){
    $host=$mysql_connection_string["host"];
    $user=$mysql_connection_string["user"];
    $password=$mysql_connection_string["password"];
    $database=$mysql_connection_string["database"];
    $table=$mysql_connection_string["table"];
    $table_col_names = $mysql_connection_string["table_col_names"];

    if (is_null($uid) ){
        return false;
    }
    mysql_connect($host,$user,$password);
    @mysql_select_db($database) or die( "Unable to select database");

    $query = "delete from $table where uid='$uid' ";
    mysql_query($query);

    mysql_close();

    return true;

}

// get existing user
function get_user($uid, $mysql_connection_string){
    $host=$mysql_connection_string["host"];
    $user=$mysql_connection_string["user"];
    $password=$mysql_connection_string["password"];
    $database=$mysql_connection_string["database"];
    $table=$mysql_connection_string["table"];
    $table_col_names = $mysql_connection_string["table_col_names"];

    if (is_null($uid) ){
        return false;
    }

    $ret = NULL;

    mysql_connect($host,$user,$password);
    @mysql_select_db($database) or die( "Unable to select database");

    $query = "select * from $table where uid='$uid' ";

    $result=mysql_query($query);
    $num=mysql_numrows($result);

    if ($num>0){
        $ret = array();
        foreach ($table_col_names as $item ){
    	    $ret[$item] = mysql_result($result,0,$item);
        }
    }

    mysql_close();

    return $ret;
}

// get existing user
function get_user_by_dir($dir, $mysql_connection_string){
    $host=$mysql_connection_string["host"];
    $user=$mysql_connection_string["user"];
    $password=$mysql_connection_string["password"];
    $database=$mysql_connection_string["database"];
    $table=$mysql_connection_string["table"];
    $table_col_names = $mysql_connection_string["table_col_names"];

    if (is_null($dir) ){
        return false;
    }
    $dir = str_replace("'", "\'", $dir);
    $dir = str_replace('"', '\"', $dir);


    $ret = NULL;

    mysql_connect($host,$user,$password);
    @mysql_select_db($database) or die( "Unable to select database");

    $query = "select * from $table where dir='$dir' ";
    //echo "query=".$query."<br>";

    $result=mysql_query($query);
    $num=mysql_numrows($result);

    if ($num>0){
        $ret = array();
        foreach ($table_col_names as $item ){
            $ret[$item] = mysql_result($result,0,$item);
        }
    }

    mysql_close();

    return $ret;
}

// insert  new user department
function query_user_by_name_affiliate($firstName, $lastName, $department, $mysql_connection_string){
    //echo "query_user_by_name..."."<br/>";
    $host=$mysql_connection_string["host"];
    $user=$mysql_connection_string["user"];
    $password=$mysql_connection_string["password"];
    $database=$mysql_connection_string["database"];
    $table=$mysql_connection_string["table"];
    $table_col_names = $mysql_connection_string["table_col_names"];
    mysql_connect($host,$user,$password);
    //echo "firstname, lastname=".$firstName.",".$lastName."<br/>";
    @mysql_select_db($database) or die( "Unable to select database");
    //echo "connect, select db fine..."."<br/>";


    $query = "select a.* from user_information a ";
    $withWhere = false;
    if (!is_null($department) && $department != '') {
        $query = "select a.* from user_information a, user_department b where a.uid = b.user_id";
        $query = $query." and b.department_code = '".$department."' ";
        $withWhere = true;
    }
    if (!is_null($firstName) && $firstName != ''){
        $firstName =  trim($firstName);
        $tmp = $firstName;
        $tmp = str_replace("'", "\'", $tmp);
        $tmp = str_replace('"', '\"', $tmp);
        $firstName = $tmp;

        if (!$withWhere){
            $query=$query." where UPPER(a.first_name) like UPPER('".$firstName."%') ";
            $withWhere = true;
        }
        else{
            $query=$query." and UPPER(a.first_name) like UPPER('".$firstName."%') ";
        }
    }
    if (!is_null($lastName) && $lastName != ''){
        $lastName =  trim($lastName);
        $tmp = $lastName;
        $tmp = str_replace("'", "\'", $tmp);
        $tmp = str_replace('"', '\"', $tmp);
        $lastName = $tmp;
        if (!$withWhere){
            $query=$query." where UPPER(a.last_name) like UPPER('".$lastName."%') ";
            $withWhere = true;
        }
        else{
            $query=$query." and UPPER(a.last_name) like UPPER('".$lastName."%') ";
        }
    }
    //echo("query=".$query."<br/>");
    $query=$query." order by a.last_name, a.first_name ";

    //echo "query = ".$query."<br/>";

    $result=mysql_query($query);
    $num=mysql_numrows($result);

    //echo "num = ".$num."<br/>";
    if ($num>0){
        $ret = array();

        for ($counter=0; $counter<$num; $counter++ ){
            $item = array();

            $item['uid'] = mysql_result($result,$counter,'uid');
            $item['firstName'] = mysql_result($result,$counter,'first_name');
            $item['lastName'] = mysql_result($result,$counter,'last_name');
            $item['mail'] = mysql_result($result,$counter,'mail');
            $item['affiliate'] = mysql_result($result,$counter,'affiliate');
            $item['title'] = mysql_result($result,$counter,'title');
            $item['dirName'] = mysql_result($result,$counter,'dir');

            // use mail to generate dir name
	    /*
            $mail =  $item['mail'];

            if (!is_null($mail)){
                // get firstname.lastname@bc.edu -> andrew.li
                $userName = substr($mail, 0, strpos($mail, "@"));
                //echo 'username='.$userName;

                if (!is_null($userName)){
                    $dirName = str_replace(".", "-", $userName);
                    $item["dirName"] = $dirName;
                }
            }
	    */

            $ret[$counter] = $item;
        }
        return $ret;
    }

    mysql_close();
}

// get existing user by last name's first char
function query_user_by_letter($first_char, $affiliate, $mysql_connection_string){
    $host=$mysql_connection_string["host"];
    $user=$mysql_connection_string["user"];
    $password=$mysql_connection_string["password"];
    $database=$mysql_connection_string["database"];
    $table=$mysql_connection_string["table"];
    $table_col_names = $mysql_connection_string["table_col_names"];

    if (is_null($first_char) || is_null($affiliate) ){
        return false;
    }

    $ret = NULL;

    mysql_connect($host,$user,$password);
    @mysql_select_db($database) or die( "Unable to select database");

    $query = "select * from $table where UPPER(last_name) like UPPER('$first_char%') ";
    if ( strcmp($affiliate, "student") == 0 || strcmp($affiliate, "faculty") == 0){
        $query = $query."AND affiliate = '".$affiliate."'";
    }
    else{
        $query = $query."AND affiliate <> 'student' AND affiliate <> 'faculty' ";
    }
    $query=$query." order by last_name, first_name ";
    //echo "query = ".$query."<br/>";

    $result=mysql_query($query);
    $num=mysql_numrows($result);

    //echo "num = ".$num."<br/>";
    if ($num>0){
        $ret = array();

        for ($counter=0; $counter<$num; $counter++ ){
            $item = array();

    	    $item['uid'] = mysql_result($result,$counter,'uid');
    	    $item['firstName'] = mysql_result($result,$counter,'first_name');
    	    $item['lastName'] = mysql_result($result,$counter,'last_name');
    	    $item['mail'] = mysql_result($result,$counter,'mail');
    	    $item['affiliate'] = mysql_result($result,$counter,'affiliate');
            $item['title'] = mysql_result($result,$counter,'title');
            $item['dirName'] = mysql_result($result,$counter,'dir');

            // use mail to generate dir name
	    /*
            $mail =  $item['mail'];

            if (!is_null($mail)){
                // get firstname.lastname@bc.edu -> andrew.li
                $userName = substr($mail, 0, strpos($mail, "@"));
                //echo 'username='.$userName;

                if (!is_null($userName)){
                    $dirName = str_replace(".", "-", $userName);
                    $item["dirName"] = $dirName;
                    //echo 'dirname='.$data['dirName']."<br/>";
                }
            }
            */

            $ret[$counter] = $item;
        }
    }

    mysql_close();

    return $ret;
}

// get departments
function get_departments($mysql_connection_string){
    $host=$mysql_connection_string["host"];
    $user=$mysql_connection_string["user"];
    $password=$mysql_connection_string["password"];
    $database=$mysql_connection_string["database"];

    mysql_connect($host,$user,$password);
    @mysql_select_db($database) or die( "Unable to select database");

    $query = "select distinct a.* from department a, user_department b where a.code=b.department_code order by name";
    $result=mysql_query($query);
    $num=mysql_numrows($result);

    //echo "num = ".$num."<br/>";
    $ret = array();
    if ($num>0){
        for ($counter=0; $counter<$num; $counter++ ){
            $item = array();

            $item['name'] = mysql_result($result,$counter,'name');
            $item['code'] = mysql_result($result,$counter,'code');
            $ret[$counter] = $item;
        }

    }
    mysql_close();
    return $ret;
}

// insert  new user department
function insert_user_department($uid, $departments, $mysql_connection_string){
    $host=$mysql_connection_string["host"];
    $user=$mysql_connection_string["user"];
    $password=$mysql_connection_string["password"];
    $database=$mysql_connection_string["database"];
    $table=$mysql_connection_string["table"];
    $table_col_names = $mysql_connection_string["table_col_names"];
    mysql_connect($host,$user,$password);
    @mysql_select_db($database) or die( "Unable to select database");

	$query0 = " insert into user_department(user_id, department_code) values('".$uid."','";

	foreach ($departments as $item ){
        if ($item != $departments['count']){
            $query = $query0.$item."')";
            //echo "insert string for user_deparment:".$query."<br/>";
            mysql_query($query);
        }

	}
    mysql_close();
}

// get user dir
function get_user_dir($uid, $mysql_connection_string){
    $host=$mysql_connection_string["host"];
    $user=$mysql_connection_string["user"];
    $password=$mysql_connection_string["password"];
    $database=$mysql_connection_string["database"];

    if (is_null($uid) || $uid=="" ){
        return "";
    }
    mysql_connect($host,$user,$password);
    @mysql_select_db($database) or die( "Unable to select database");

    $query = "select dir from user_information where uid = '".$uid."'";

    $result=mysql_query($query);
    $num=mysql_numrows($result);

    $ret = NULL;
    if ($num>0){
        $ret = mysql_result($result,0,'dir');
    }
    mysql_close();
    return $ret;
}

// test; functions
function test_user_sql(){
    $mysql_connection_string = get_mysql_connection_string();

    echo "test_user_sql...<br>";
    // test insert
    $rowdata = array();
    $rowdata['uid']="liex";
    $rowdata['first_name']="andrew";
    $rowdata['last_name']="li";
    $rowdata['mail']="andrew.li@bc.edu";
    $rowdata['affiliate']="IT";

    insert_user($rowdata, $mysql_connection_string);

    $rowdata = get_user("liex", $mysql_connection_string);
    print_r($rowdata);
    echo "<br>";

    $rowdata = array();
    $rowdata['uid']="liex1";
    $rowdata['first_name']="andrew1";
    $rowdata['last_name']="li1";
    $rowdata['mail']="andrew.li.1@bc.edu";
    $rowdata['affiliate']="IT1";
    insert_user($rowdata, $mysql_connection_string);


    $data = query_user_by_letter("m", $mysql_connection_string);
    if (is_null($data)){
        echo "no data for m <br>";
    }else{
        echo "there is data for m <br>";
        print_r($data);
    }

    $data = query_user_by_letter("l", $mysql_connection_string);
    if (is_null($data)){
        echo "no data for l <br>";
    }else{
        echo "there is data for l <br>";
        print_r($data);
    }

//    delete_user("liex", $mysql_connection_string);
//    delete_user("liex1", $mysql_connection_string);
}

////////////////////////////////////
// new functions for poll
// get a poll

function get_poll($id, $mysql_connection_string){

//    echo "enter get_poll <br>";

    $host=$mysql_connection_string["host"];
    $user=$mysql_connection_string["user"];
    $password=$mysql_connection_string["password"];
    $database=$mysql_connection_string["database"];

    if (is_null($id) ){
        return false;
    }

    $ret = NULL;

//    echo "connecting to mysql <br>";
    mysql_connect($host,$user,$password);
//    echo "mysql host=".$host." <br>";
    @mysql_select_db($database) or die( "Unable to select database");

    $query = "select distinct a.question, a.total, b.answer, b.question_id, b.id, b.selected from survey_question a, survey_answer b where a.id = b.question_id and a.header_id = '$id' order by a.id, b.id";
//    echo "query=".$query." <br>";
    //$query = "select * from survey_question where id='$id' ";

    $result=mysql_query($query);
    $num=mysql_numrows($result);
//    echo "return row =".$num." <br>";

    //$table_col_names = array('a.id', 'a.question', 'b.answer', 'a.total', 'b.selected');
    $table_col_names = array('question', 'answer', 'total', 'selected', 'question_id', 'id');
    $ret = array();

    if ($num>0)
	for ($counter=0; $counter<$num; $counter++ ){
        $row = array();
        foreach ($table_col_names as $item ){
    	    $row[$item] = mysql_result($result,$counter,$item);
//    		echo "$item =".$row[$item]." <br>";
        }
        $ret[$counter] = $row;
    }

    mysql_close();
    $ret_string = json_encode($ret);
//    echo "json encode = ".$ret_string."<br>";

//    echo "finish get_poll <br>";
    return $ret_string;
}

// update poll count
function update_poll($question_id, $answer_id, $mysql_connection_string){
    $host=$mysql_connection_string["host"];
    $user=$mysql_connection_string["user"];
    $password=$mysql_connection_string["password"];
    $database=$mysql_connection_string["database"];

//    echo "connecting to mysql <br>";
    mysql_connect($host,$user,$password);
//    echo "mysql host=".$host." <br>";
    @mysql_select_db($database) or die( "Unable to select database");

	// update question total
 	$query = "update survey_question set total = total + 1 where  id = ".$question_id;
    $result=mysql_query($query);
 	$query = "update survey_answer set selected = selected + 1 where  id = ".$answer_id;
    $result=mysql_query($query);

    mysql_close();

}

?>


