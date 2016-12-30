<?php
	include 'dbconf.php';
	
	//Cache connect
	$my_conn = new mysqli($cache_addr,$cache_user,$cache_password,$cache_schemas);

	
	$cache_stmt = mysqli_query($my_conn,"SELECT value FROM control WHERE name = 'lastupdate';");
	$lastupdate = 0;
	$updatefreq = 0;
	if(!$cache_stmt)
	{
		while($row = mysqli_fetch_array($cache_stmt))
		{
			switch( $row['name'] )
			{
				case "lastupdate":
					$lastupdate = $row['value'];
					break;
				case "updatefreq":
					$updatefreq = $row['value'];
					break;
				default: 
					break;
			}
		}
	}

	
	
	
	if(time() > $lastupdate + $updatefreq )
	{
//		foreach($db_conn_string as $src => $o_conn_string)
//		{
		$src = 1;
//		$o_conn_string =$db_conn_string[$src];
//		$o_user = $db_user[$src];
//		$o_password = $db_password[$src];
		
		//Update cache start
		$conn = oci_connect($db_user,$db_password,$db_conn_string,"utf8");
		
		
		
		//Cache T_SUBJECT
		mysqli_query($my_conn,"DELETE FROM t_subject ;");
		$sql = "SELECT T_SUBJECT.SUBJECTID,T_SUBJECT.SUBJECTNAME,T_SUBJECT.PARENTSUBJECTID,T_SUBJECT.FULLMARK FROM T_SUBJECT";
		$stmt = oci_parse($conn,$sql);
		oci_execute($stmt);
		
		$subject = array();
		
		while($result = oci_fetch_array($stmt))
		{
			$subjectid = $result['SUBJECTID'];
			$subjectname = $result['SUBJECTNAME'];
			$fullmark = $result['FULLMARK'];
			
			
			//Make table name
			if($result["PARENTSUBJECTID"] == NULL)
			{
				$tablename = "T_" . $result['SUBJECTID'] . "SUBQUESTIONSTAT";
				mysqli_query($my_conn,"INSERT INTO t_subject VALUES ( '$subjectid' , '$subjectname' , '$fullmark' , NULL , '$tablename' ,1)");
			}
			else 
			{
				$parentsubject = $result["PARENTSUBJECTID"];
				mysqli_query($my_conn,"INSERT INTO t_subject VALUES ( '$subjectid' , '$subjectname' , '$fullmark' , '$parentsubject' , NULL ,1 )");
			}
			$subject[0+$subjectid] = $subjectname;
		}
		unset($stmt);
		
		
		//Cache T_SUBQUESTION
		mysqli_query($my_conn,"DELETE FROM t_subquestion ;");
		$sql = "SELECT T_SUBQUESTION.SUBQUESTIONID,T_SUBQUESTION.SUBQUESTIONNAME,T_SUBQUESTION.MAXMARK FROM T_SUBQUESTION";
		$stmt = oci_parse($conn,$sql);
		oci_execute($stmt);
		
		while($result = oci_fetch_array($stmt))
		{
			$subquestionid = $result['SUBQUESTIONID'];
			
			$subjectid = 0+substr($subquestionid,0,2);	//Get subjectid

			$subquestionname = $result['SUBQUESTIONNAME'];	//Question full name
			
			$fullmark = $result['MAXMARK'];
			
			mysqli_query($my_conn,"INSERT INTO t_subquestion VALUES ( '$subquestionid' , '$subquestionname' , '$fullmark' , '$subjectid' ,1);");
		}
		unset($stmt);
		
		
		
		//Cache T_STUDENT
		mysqli_query($my_conn,"DELETE FROM t_student ;");
		$sql = "SELECT T_STUDENT.STUDENTID,T_STUDENT.NAME,T_STUDENT.SCHOOLID,T_STUDENT.CLASSNAME FROM T_STUDENT";
		$stmt = oci_parse($conn,$sql);
		oci_execute($stmt);
		
		while($result = oci_fetch_array($stmt))
		{
			$studentid = $result['STUDENTID'];
			$name = $result['NAME'];
			$schoolid = $result['SCHOOLID'];
			$classname = $result['CLASSNAME'];
			mysqli_query($my_conn,"INSERT INTO t_student VALUES ( '$studentid' , '$name' , '$schoolid' , '$classname' ,1);");
		}
		unset($stmt);
		
		
		
		//Cache T_USER
		mysqli_query($my_conn,"DELETE FROM t_user ;");
		$sql = "SELECT T_USER.USERID, T_USER.USERNAME FROM T_USER";
		$stmt = oci_parse($conn,$sql);
		oci_execute($stmt);
		
		while($result = oci_fetch_array($stmt))
		{
			$userid = $result['USERID'];
			$username = $result['USERNAME'];
			mysqli_query($my_conn,"INSERT INTO t_user VALUES ( '$userid' , '$username' ,1);");
		}
		unset($stmt);
		
		
		
		mysqli_query($my_conn,"UPDATE control SET value = ".time()." WHERE name = 'lastupdate';");
		oci_close($conn);
			
		//}
	}
	
	mysqli_close($my_conn)
?>