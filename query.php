<?php
	
	include 'dbconf.php';
	include 'cache.php';
	
	$my_conn = new mysqli($cache_addr,$cache_user,$cache_password,$cache_schemas);
	
	$examno = $_GET['examno'];
	//$stuname = $_GET['name'];
	
	//Validate
	$sql = "SELECT name,src FROM t_student WHERE ( studentid = ? )";
	$my_stmt = $my_conn -> prepare($sql);
	$my_stmt -> bind_param("s",$examno);
	$my_stmt -> execute();
	$my_stmt -> bind_result($expect_name,$src);
	$my_stmt -> fetch();
	$my_stmt -> close();
	
	if(true)
	{
//		$o_user = $db_user[$src];
//		$o_password = $db_password[$src];
//		$o_conn_string = $db_conn_string[$src];
		
		$conn = oci_connect($db_user,$db_password,$db_conn_string,"utf8");
		
		
		//MySQL
		//Get question full name
		$sql = "SELECT * FROM t_subquestion";
		$question_name = array();
		
		$my_stmt = $my_conn -> prepare($sql);
		$my_stmt -> execute();
		$my_stmt -> bind_result($subquestionid,$subquestionname,$fullmark,$subjectid,$src);
		
		while($my_stmt -> fetch())
		{
			$question_subject[0+$subquestionid] = $subjectid;
			$question_name[0+$subquestionid] = $subquestionname;	//Question full name
		}
		$my_stmt -> close();
		
		
		//MySQL
		//subjectid->subject,child subject?(Has table T_XXSUBQUESTIONSTAT)
		$sql = "SELECT * FROM t_subject";
		$subject = array();
		$question_score = array();
		$q_temp = array();
		
		$my_stmt = $my_conn -> prepare($sql);
		$my_stmt -> execute();
		$my_stmt -> bind_result($subjectid,$subjectname,$fullmark,$parentsubject,$tablename,$src);
		
		while($my_stmt -> fetch())
		{
			$subject[$subjectid] = $subjectname;
			if($tablename != NULL)
			{
				
				//Get question score from Oracle 
				$sql = "SELECT * FROM ".$tablename." WHERE STUDENTID = :STUDENTID ORDER BY SUBQUESTIONID";
				$stmt = oci_parse($conn,$sql);
				oci_bind_by_name($stmt,":STUDENTID",$examno);
				oci_execute($stmt);
				while($result = oci_fetch_array($stmt))
				{
					$q_subject = $question_subject[0+$result['SUBQUESTIONID']];
					$question_score[$q_subject][$result['SUBQUESTIONID']]['name'] = $question_name[0+$result['SUBQUESTIONID']];
					$question_score[$q_subject][$result['SUBQUESTIONID']]['score'] = $result['MARK'];
					//$q_temp[$q_subject][$result['SUBQUESTIONID']]['by'] = $user_name[$result['USERID']];
				}
				//$question_score = array_merge_recursive($question_score,$q_temp);
				unset($stmt);
			}
			if($parentsubject != NULL)
			{
				$question_score[$parentsubject]=array();
			}
		}
		
		
		//Get total score from Oracle
		$sql = "SELECT T_STUDENTMARK.MARK FROM T_STUDENTMARK WHERE T_STUDENTMARK.STUDENTID = :STUDENTID";
		$stmt = oci_parse($conn,$sql);
		oci_bind_by_name($stmt,":STUDENTID",$examno);
		oci_execute($stmt);
		$result = oci_fetch_array($stmt);
		
		$total = $result['MARK'];
		unset($stmt);
		
		
		//get subject score from Oracle
		$subject_score = array();
		$score_temp = array();
		$sql = "SELECT T_SUBJECTMARK.SUBJECTID,T_SUBJECTMARK.OBJECTIVEMARK,T_SUBJECTMARK.SUBJECTIVEMARK FROM T_SUBJECTMARK WHERE T_SUBJECTMARK.STUDENTID = :STUDENTID";
		$stmt = oci_parse($conn,$sql);
		oci_bind_by_name($stmt,":STUDENTID",$examno);
		oci_execute($stmt);
		
		$id = 0;	//Use a number (not 'name') as index,so we can use foreach() easier
		while($result = oci_fetch_array($stmt))
		{
			$id++;
			$score_temp['id'] = 0+$result['SUBJECTID'];
			$score_temp['name'] = $subject[0+$result['SUBJECTID']];
			$score_temp['obj'] = $result['OBJECTIVEMARK'];
			$score_temp['subj'] = $result['SUBJECTIVEMARK'];
			$score_temp['total'] = $score_temp['obj'] + $score_temp['subj'];	//Calculate score with PHP
			$subject_score[$id] = $score_temp;
		}
		unset($stmt);
				
		oci_close($conn);
		
		
		//Start output
		echo "<p>".$expect_name."（".$examno."）的总分：".$total."</p>";
		
		echo "<table class=\"table table-hover\"><tbody><tr><th>科目</th><th>得分</th><th>客观题</th><th>主观题</th></tr>";
		foreach($subject_score as $ss)
		{
			echo "<tr><td>".$ss['name']."</td><td><code>".$ss['total']."</code></td><td><code>".$ss['obj']."</code></td><td><code>".$ss['subj']."</code></td></tr>";						
		}
		echo "</tody></table>";
		
		
		//Quetion score
		echo "<ul class =\"nav nav-tabs\">";
		foreach($subject_score as $ss)
		{
			if(count($question_score[$ss['id']]) == 0) continue;
			echo "<li role=\"presentation\"><a href=\"#_subject_" . $ss['id'] . "\" role=\"tab\" data-toggle=\"tab\">" . $ss['name'] . "</a></li>";
		}
		echo "</ul><div class=\"tab-content\">";
		
		foreach($subject_score as $ss)
		{
			if(count($question_score[$ss['id']]) == 0) continue;
			echo "<div role=\"tabpanel\" class=\"tab-pane\" id=\"_subject_" . $ss['id'] . "\"><table class=\"table table-hover\"><tbody><tr><th>题目</th><th>得分</th></tr>";
			foreach($question_score[$ss['id']] as $qs)
			{
				echo "<tr><td>".$qs['name']."</td><td><code>".$qs['score']."</code></td></tr>";
			}
			echo "</tody></table></div>";
		}
		
		echo "</div>";
	}
	
	else
	{
		echo "身份验证错误，请确认你的考号与姓名无误。";
	}
?>