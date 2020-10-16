<?php

//include files

include('master/Examination.php');

$exam = new Examination;

$exam->user_session_private();

include('header.php');

$exam_id = '';
$exam_status = '';
$remaining_minutes = '';
//if true get exam
if(isset($_GET['code']))
{
	$exam_id = $exam->Get_exam_id($_GET["code"]);
	$exam->query = "
	SELECT online_exam_status, online_exam_datetime, online_exam_duration FROM online_exam_table
	WHERE online_exam_id = '$exam_id'
	";

	$result = $exam->query_result();

	foreach($result as $row)
	{
		//get exam data
		$exam_status = $row['online_exam_status'];
		$exam_star_time = $row['online_exam_datetime'];
		$duration = $row['online_exam_duration'] . ' minute';
		$exam_end_time = strtotime($exam_star_time . '+' . $duration);

		$exam_end_time = date('Y-m-d H:i:s', $exam_end_time);
		$remaining_minutes = strtotime($exam_end_time) - time();
	}
}
else
{
	//if fails redirect
	header('location:enroll_exam.php');
}


?>

<br />
<?php
//if start update user statues
if($exam_status == 'Started')
{
	$exam->data = array(
		':user_id'		=>	$_SESSION['user_id'],
		':exam_id'		=>	$exam_id,
		':attendance_status'	=>	'Present'
	);
//database functions
	$exam->query = "
	UPDATE user_exam_enroll_table
	SET attendance_status = :attendance_status
	WHERE user_id = :user_id
	AND exam_id = :exam_id
	";

	$exam->execute_query();

?>
<!-- load in html-->
<div class="row">
	<div class="col-md-8">
		<div class="card">
			<div class="card-header">Online Exam</div>
			<div class="card-body">
				<div id="single_question_area"></div>
			</div>
		</div>
		<br>
		<!-- question navigation-->
		<div id="question_navigation_area"></div>
	</div>
	<div class="col-md-4">
		<br />
		<!-- timer-->
		<div align="center">
			<div id="exam_timer" data-timer="<?php echo $remaining_minutes; ?>" style="max-width:400px; width: 100%; height: 200px;"></div>
		</div>
		<br />
		<!-- user details-->
		<div id="user_details_area"></div>
	</div>
</div>

<script>
// load exam
$(document).ready(function(){
	var exam_id = "<?php echo $exam_id; ?>";

	load_question();
	question_navigation();

	function load_question(question_id = '')
	{
		$.ajax({
			//use ajax to send from database
			url:"user_ajax_action.php",
			method:"POST",
			data:{exam_id:exam_id, question_id:question_id, page:'view_exam', action:'load_question'},
			success:function(data)
			{
				$('#single_question_area').html(data);
			}
		})
	}
//next and previous buyyon handling
	$(document).on('click', '.next', function(){
		var question_id = $(this).attr('id');
		load_question(question_id);
	});

	$(document).on('click', '.previous', function(){
		var question_id = $(this).attr('id');
		load_question(question_id);
	});

	function question_navigation()
	{
		$.ajax({
			//use ajax to get data from database
			url:"user_ajax_action.php",
			method:"POST",
			data:{exam_id:exam_id, page:'view_exam', action:'question_navigation'},
			success:function(data)
			{
				$('#question_navigation_area').html(data);
			}
		})
	}
//handling navigation buttons
	$(document).on('click', '.question_navigation', function(){
		var question_id = $(this).data('question_id');
		load_question(question_id);
	});
//get user details
	function load_user_details()
	{
		$.ajax({

			//use ajax to get data from database
			url:"user_ajax_action.php",
			method:"POST",
			data:{page:'view_exam', action:'user_detail'},
			success:function(data)
			{
				$('#user_details_area').html(data);
			}
		})
	}

	load_user_details();
//timer handling
	$("#exam_timer").TimeCircles({
		time:{
			Days:{
				show: false
			},
			Hours:{
				show: false
			}
		}
	});
//get timer reaction
	setInterval(function(){
		var remaining_second = $("#exam_timer").TimeCircles().getTime();
		if(remaining_second < 1)
		{
			//if time over message it
			alert('Exam time over');
			location.reload();
		}
	}, 1000);
//handling option answers
	$(document).on('click', '.answer_option', function(){
		var question_id = $(this).data('question_id');

		var answer_option = $(this).data('id');

		$.ajax({
			//use ajax to get data from database
			url:"user_ajax_action.php",
			method:"POST",
			data:{question_id:question_id, answer_option:answer_option, exam_id:exam_id, page:'view_exam', action:'answer'},
			success:function(data)
			{

			}
		})
	});

});
</script>
<?php
}
//view comleted exam
if($exam_status == 'Completed')
{
	$exam->query = "
	SELECT * FROM question_table
	INNER JOIN user_exam_question_answer
	ON user_exam_question_answer.question_id = question_table.question_id
	WHERE question_table.online_exam_id = '$exam_id'
	AND user_exam_question_answer.user_id = '".$_SESSION["user_id"]."'
	";

	//exam completed details in html

	$result = $exam->query_result();
?>
	<div class="card">
		<div class="card-header">
			<div class="row">
				<!-- get result in pdf-->
				<div class="col-md-8">Online Exam Result</div>
				<div class="col-md-4" align="right">
					<a href="pdf_exam_result.php?code=<?php echo $_GET["code"]; ?>" class="btn btn-danger btn-sm" target="_blank">PDF</a>
				</div>
			</div>
		</div>
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-bordered table-hover">
					<tr>
						<th>Question</th>
						<th>Option 1</th>
						<th>Option 2</th>
						<th>Option 3</th>
						<th>Option 4</th>
						<th>Your Answer</th>
						<th>Answer</th>
						<th>Result</th>
						<th>Marks</th>
					</tr>
					<?php
					$total_mark = 0;
//display result
					foreach($result as $row)
					{
						$exam->query = "
						SELECT *
						FROM option_table
						WHERE question_id = '".$row["question_id"]."'
						";

						$sub_result = $exam->query_result();
						$user_answer = '';
						$orignal_answer = '';
						$question_result = '';

						if($row['marks'] == '0')
						{
							$question_result = '<h4 class="badge badge-dark">Not Attend</h4>';
						}

						if($row['marks'] > '0')
						{
							$question_result = '<h4 class="badge badge-success">Right</h4>';
						}

						if($row['marks'] < '0')
						{
							$question_result = '<h4 class="badge badge-danger">Wrong</h4>';
						}

						echo '
						<tr>
							<td>'.$row['question_title'].'</td>
						';

						foreach($sub_result as $sub_row)
						{
							echo '<td>'.$sub_row["option_title"].'</td>';

							if($sub_row["option_number"] == $row['user_answer_option'])
							{
								$user_answer = $sub_row['option_title'];
							}

							if($sub_row['option_number'] == $row['answer_option'])
							{
								$orignal_answer = $sub_row['option_title'];
							}
						}
						echo '
						<td>'.$user_answer.'</td>
						<td>'.$orignal_answer.'</td>
						<td>'.$question_result.'</td>
						<td>'.$row["marks"].'</td>
					</tr>
						';
					}


//get data from database
					$exam->query = "
					SELECT SUM(marks) as total_mark FROM user_exam_question_answer
					WHERE user_id = '".$_SESSION['user_id']."'
					AND exam_id = '".$exam_id."'
					";

					$marks_result = $exam->query_result();

					foreach($marks_result as $row)
					{
						//display marks
					?>
					<tr>
						<td colspan="8" align="right">Total Marks</td>
						<td align="right"><?php echo $row["total_mark"]; ?></td>
					</tr>
					<?php
					}

					?>
				</table>
			</div>
		</div>
	</div>
<?php
}

?>

</div>
</body>
</html>
