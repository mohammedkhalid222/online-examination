<?php

//include files

include('master/Examination.php');

require_once('class/class.phpmailer.php');

$exam = new Examination;
//time and date
$current_datetime = date("Y-m-d") . ' ' . date("H:i:s", STRTOTIME(date('h:i:sa')));
//register handling
if(isset($_POST['page']))
{
	if($_POST['page'] == 'register')
	{
		if($_POST['action'] == 'check_email')
		{
			//database functions
			$exam->query = "
			SELECT * FROM user_table
			WHERE user_email_address = '".trim($_POST["email"])."'
			";

			$total_row = $exam->total_row();

			if($total_row == 0)
			{
				$output = array(
					'success'		=>	true
				);
				//send in json
				echo json_encode($output);
			}
		}
//handle register form
		if($_POST['action'] == 'register')
		{
			//database variables
			$user_verfication_code = md5(rand());

			$receiver_email = $_POST['user_email_address'];

			$exam->filedata = $_FILES['user_image'];

			$user_image = $exam->Upload_file();

			$exam->data = array(
				':user_email_address'	=>	$receiver_email,
				':user_password'		=>	password_hash($_POST['user_password'], PASSWORD_DEFAULT),
				':user_verfication_code'=>	$user_verfication_code,
				':user_name'			=>	$_POST['user_name'],
				':user_gender'			=>	$_POST['user_gender'],
				':user_address'			=>	$_POST['user_address'],
				':user_mobile_no'		=>	$_POST['user_mobile_no'],
				':user_image'			=>	$user_image,
				':user_created_on'		=>	$current_datetime
			);
//datatbase function
			$exam->query = "
			INSERT INTO user_table
			(user_email_address, user_password, user_verfication_code, user_name, user_gender, user_address, user_mobile_no, user_image, user_created_on)
			VALUES
			(:user_email_address, :user_password, :user_verfication_code, :user_name, :user_gender, :user_address, :user_mobile_no, :user_image, :user_created_on)
			";

			$exam->execute_query();

			$subject= 'varify email';
//varification message
			$body = '

			<p> click the link to verify your eMail address  <a href="'.$exam->home_page.'verify_email.php?type=user&code='.$user_verfication_code.'" target="_blank"><b>here</b></a>.</p>
			<p>In case if you have any difficulty please eMail us.</p>

			<p>Mohammed Khaled</p>
			';

			$exam->send_email($receiver_email, $subject, $body);

			$output = array(
				'success'		=>	true
			);
      //send in json
			echo json_encode($output);
		}
	}
//handling login
	if($_POST['page'] == 'login')
	{
		if($_POST['action'] == 'login')
		{
			//get email
			$exam->data = array(
				':user_email_address'	=>	$_POST['user_email_address']
			);
//database functions
			$exam->query = "
			SELECT * FROM user_table
			WHERE user_email_address = :user_email_address
			";

			$total_row = $exam->total_row();
//handling wrong email and/or password
			if($total_row > 0)
			{
				$result = $exam->query_result();

				foreach($result as $row)
				{
					if($row['user_email_verified'] == 'yes')
					{
						if(password_verify($_POST['user_password'], $row['user_password']))
						{
							$_SESSION['user_id'] = $row['user_id'];

							$output = array(
								'success'	=>	true
							);
						}
						else
						{
							$output = array(
								'error'		=>	'Wrong Password'
							);
						}
					}
					else
					{
						$output = array(
							'error'		=>	'Your Email is not verify'
						);
					}
				}
			}
			else
			{
				$output = array(
					'error'		=>	'Wrong Email Address'
				);
			}
//send i json
			echo json_encode($output);
		}
	}
//profile handle
	if($_POST['page'] == "profile")
	{
		if($_POST['action'] == "profile")
		{
			//image
			$user_image = $_POST['hidden_user_image'];

			if($_FILES['user_image']['name'] != '')
			{
				$exam->filedata = $_FILES['user_image'];

				$user_image = $exam->Upload_file();
			}
    //add data to databse
			$exam->data = array(
				':user_name'				=>	$exam->clean_data($_POST['user_name']),
				':user_gender'				=>	$_POST['user_gender'],
				':user_address'				=>	$exam->clean_data($_POST['user_address']),
				':user_mobile_no'			=>	$_POST['user_mobile_no'],
				':user_image'				=>	$user_image,
				':user_id'					=>	$_SESSION['user_id']
			);
//database functions
			$exam->query = "
			UPDATE user_table
			SET user_name = :user_name, user_gender = :user_gender, user_address = :user_address, user_mobile_no = :user_mobile_no, user_image = :user_image
			WHERE user_id = :user_id
			";
			$exam->execute_query();

			$output = array(
				'success'		=>	true
			);
//send using json
			echo json_encode($output);

		}
	}
//handling vhange password
	if($_POST['page'] == 'change_password')
	{
		if($_POST['action'] == 'change_password')
		{
			//databse functions
			$exam->data = array(
				':user_password'	=>	password_hash($_POST['user_password'], PASSWORD_DEFAULT),
				':user_id'			=>	$_SESSION['user_id']
			);

			$exam->query = "
			UPDATE user_table
			SET user_password = :user_password
			WHERE user_id = :user_id
			";

			$exam->execute_query();

			session_destroy();
//success message
			$output = array(
				'success'		=>	'Password has been change'
			);
//send in json
			echo json_encode($output);
		}
	}
//exam handling
	if($_POST['page'] == 'index')
	{
		//get exam data
		if($_POST['action'] == "fetch_exam")
		{
			$exam->query = "
			SELECT * FROM online_exam_table
			WHERE online_exam_id = '".$_POST['exam_id']."'
			";

			$result = $exam->query_result();

//out put exam in html
			$output = '
			<div class="card">
				<div class="card-header">Exam Details</div>
				<div class="card-body">
					<table class="table table-striped table-hover table-bordered">
			';
			foreach($result as $row)
			{
				$output .= '
				<tr>
					<td><b>Exam Title</b></td>
					<td>'.$row["online_exam_title"].'</td>
				</tr>
				<tr>
					<td><b>Exam Date & Time</b></td>
					<td>'.$row["online_exam_datetime"].'</td>
				</tr>
				<tr>
					<td><b>Exam Duration</b></td>
					<td>'.$row["online_exam_duration"].' Minute</td>
				</tr>
				<tr>
					<td><b>Exam Total Question</b></td>
					<td>'.$row["total_question"].' </td>
				</tr>
				<tr>
					<td><b>Marks Per Right Answer</b></td>
					<td>'.$row["marks_per_right_answer"].' Mark</td>
				</tr>
				<tr>
					<td><b>Marks Per Wrong Answer</b></td>
					<td>-'.$row["marks_per_wrong_answer"].' Mark</td>
				</tr>
				';
				//exam details
				if($exam->If_user_already_enroll_exam($_POST['exam_id'], $_SESSION['user_id']))
				{
					$enroll_button = '
					<tr>
						<td colspan="2" align="center">
							<button type="button" name="enroll_button" class="btn btn-info">You Already Enroll it</button>
						</td>
					</tr>
					';
				}
				else
				{
					$enroll_button = '
					<tr>
						<td colspan="2" align="center">
							<button type="button" name="enroll_button" id="enroll_button" class="btn btn-warning" data-exam_id="'.$row['online_exam_id'].'">Enroll it</button>
						</td>
					</tr>
					';
				}
				$output .= $enroll_button;
			}
			$output .= '</table>';
			echo $output;
		}
//if enrolled exam
		if($_POST['action'] == 'enroll_exam')
		{
			//user table databse functions
			$exam->data = array(
				':user_id'		=>	$_SESSION['user_id'],
				':exam_id'		=>	$_POST['exam_id']
			);

			$exam->query = "
			INSERT INTO user_exam_enroll_table
			(user_id, exam_id)
			VALUES (:user_id, :exam_id)
			";

			$exam->execute_query();
//exam table dataabase functions
			$exam->query = "
			SELECT question_id FROM question_table
			WHERE online_exam_id = '".$_POST['exam_id']."'
			";
			$result = $exam->query_result();
			foreach($result as $row)
			{
				$exam->data = array(
					':user_id'				=>	$_SESSION['user_id'],
					':exam_id'				=>	$_POST['exam_id'],
					':question_id'			=>	$row['question_id'],
					':user_answer_option'	=>	'0',
					':marks'				=>	'0'
				);

				$exam->query = "
				INSERT INTO user_exam_question_answer
				(user_id, exam_id, question_id, user_answer_option, marks)
				VALUES (:user_id, :exam_id, :question_id, :user_answer_option, :marks)
				";
				$exam->execute_query();
			}
		}

	}
//send enroll exam data
	if($_POST["page"] == 'enroll_exam')
	{
		if($_POST['action'] == 'fetch')
		{
			$output = array();
//get data from database
			$exam->query = "
			SELECT * FROM user_exam_enroll_table
			INNER JOIN online_exam_table
			ON online_exam_table.online_exam_id = user_exam_enroll_table.exam_id
			WHERE user_exam_enroll_table.user_id = '".$_SESSION['user_id']."'
			AND (";
//search data
			if(isset($_POST["search"]["value"]))
			{
			 	$exam->query .= 'online_exam_table.online_exam_title LIKE "%'.$_POST["search"]["value"].'%" ';
			 	$exam->query .= 'OR online_exam_table.online_exam_datetime LIKE "%'.$_POST["search"]["value"].'%" ';
			 	$exam->query .= 'OR online_exam_table.online_exam_duration LIKE "%'.$_POST["search"]["value"].'%" ';
			 	$exam->query .= 'OR online_exam_table.total_question LIKE "%'.$_POST["search"]["value"].'%" ';
			 	$exam->query .= 'OR online_exam_table.marks_per_right_answer LIKE "%'.$_POST["search"]["value"].'%" ';
			 	$exam->query .= 'OR online_exam_table.marks_per_wrong_answer LIKE "%'.$_POST["search"]["value"].'%" ';
			 	$exam->query .= 'OR online_exam_table.online_exam_status LIKE "%'.$_POST["search"]["value"].'%" ';
			}

			$exam->query .= ')';
//sort data
			if(isset($_POST["order"]))
			{
				$exam->query .= 'ORDER BY '.$_POST['order']['0']['column'].' '.$_POST['order']['0']['dir'].' ';
			}
			else
			{
				$exam->query .= 'ORDER BY online_exam_table.online_exam_id DESC ';
			}

			$extra_query = '';
//get results from database
			if($_POST["length"] != -1)
			{
			 	$extra_query .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
			}

			$filterd_rows = $exam->total_row();

			$exam->query .= $extra_query;

			$result = $exam->query_result();

			$exam->query = "
			SELECT * FROM user_exam_enroll_table
			INNER JOIN online_exam_table
			ON online_exam_table.online_exam_id = user_exam_enroll_table.exam_id
			WHERE user_exam_enroll_table.user_id = '".$_SESSION['user_id']."'";

			$total_rows = $exam->total_row();

			$data = array();
//put results in arrays
			foreach($result as $row)
			{
				$sub_array = array();
				$sub_array[] = html_entity_decode($row["online_exam_title"]);
				$sub_array[] = $row["online_exam_datetime"];
				$sub_array[] = $row["online_exam_duration"] . ' Minute';
				$sub_array[] = $row["total_question"] . ' Question';
				$sub_array[] = $row["marks_per_right_answer"] . ' Mark';
				$sub_array[] = '-' . $row["marks_per_wrong_answer"] . ' Mark';
				$status = '';
//send message if created
				if($row['online_exam_status'] == 'Created')
				{
					$status = '<span class="badge badge-success">Created</span>';
				}
//send message if stated
				if($row['online_exam_status'] == 'Started')
				{
					$status = '<span class="badge badge-primary">Started</span>';
				}
//send message if completed
				if($row['online_exam_status'] == 'Completed')
				{
					$status = '<span class="badge badge-dark">Completed</span>';
				}
//status
				$sub_array[] = $status;

				if($row["online_exam_status"] == 'Started')
				{
					$view_exam = '<a href="view_exam.php?code='.$row["online_exam_code"].'" class="btn btn-info btn-sm">View Exam</a>';
				}
				if($row["online_exam_status"] == 'Completed')
				{
					$view_exam = '<a href="view_exam.php?code='.$row["online_exam_code"].'" class="btn btn-info btn-sm">View Exam</a>';
				}
//view status

				$sub_array[] = $view_exam;

				$data[] = $sub_array;
			}
//set output
			$output = array(
			 	"draw"    			=> 	intval($_POST["draw"]),
			 	"recordsTotal"  	=>  $total_rows,
			 	"recordsFiltered" 	=> 	$filterd_rows,
			 	"data"    			=> 	$data
			);
			//send it in json
			echo json_encode($output);
		}
	}
//view exam handling
	if($_POST['page'] == 'view_exam')
	{
		if($_POST['action'] == 'load_question')
		{
			if($_POST['question_id'] == '')
			{
				//database dunctions
				$exam->query = "
				SELECT * FROM question_table
				WHERE online_exam_id = '".$_POST["exam_id"]."'
				ORDER BY question_id ASC
				LIMIT 1
				";
			}
			else
			{
				//if exam exist get questions
				$exam->query = "
				SELECT * FROM question_table
				WHERE question_id = '".$_POST["question_id"]."'
				";
			}

			$result = $exam->query_result();

			$output = '';
//display questions in html
			foreach($result as $row)
			{
				//title
				$output .= '
				<h1>'.$row["question_title"].'</h1>
				<hr />
				<br />
				<div class="row">
				';

				$exam->query = "
				SELECT * FROM option_table
				WHERE question_id = '".$row['question_id']."'
				";
				$sub_result = $exam->query_result();

				$count = 1;

				foreach($sub_result as $sub_row)
				{
					//options
					$output .= '
					<div class="col-md-6" style="margin-bottom:32px;">
						<div class="radio">
							<label><h4><input type="radio" name="option_1" class="answer_option" data-question_id="'.$row["question_id"].'" id-data="'.$count.'"/>&nbsp;'.$sub_row["option_title"].'</h4></label>
						</div>
					</div>
					';

					$count = $count + 1;
				}
				$output .= '
				</div>
				';
				//get previous and next quesion
				$exam->query = "
				SELECT question_id FROM question_table
				WHERE question_id < '".$row['question_id']."'
				AND online_exam_id = '".$_POST["exam_id"]."'
				ORDER BY question_id DESC
				LIMIT 1";

				$previous_result = $exam->query_result();

				$previous_id = '';
				$next_id = '';
//change question result and get data from database
				foreach($previous_result as $previous_row)
				{
					$previous_id = $previous_row['question_id'];
				}

				$exam->query = "
				SELECT question_id FROM question_table
				WHERE question_id > '".$row['question_id']."'
				AND online_exam_id = '".$_POST["exam_id"]."'
				ORDER BY question_id ASC
				LIMIT 1";

  				$next_result = $exam->query_result();
//change question in html
  				foreach($next_result as $next_row)
				{
					$next_id = $next_row['question_id'];
				}

				$if_previous_disable = '';
				$if_next_disable = '';

				if($previous_id == "")
				{
					$if_previous_disable = 'disabled';
				}

				if($next_id == "")
				{
					$if_next_disable = 'disabled';
				}


//display it
				$output .= '
					<br /><br />
				  	<div align="center">
				   		<button type="button" name="previous" class="btn btn-info btn-lg previous" id="'.$previous_id.'" '.$if_previous_disable.'>Previous</button>
				   		<button type="button" name="next" class="btn btn-warning btn-lg next" id="'.$next_id.'" '.$if_next_disable.'>Next</button>
				  	</div>
				  	<br /><br />';
			}
//get output
			echo $output;
		}
		//question navigations
		if($_POST['action'] == 'question_navigation')
		{
			//database functions
			$exam->query = "
				SELECT question_id FROM question_table
				WHERE online_exam_id = '".$_POST["exam_id"]."'
				ORDER BY question_id ASC
				";
				//display results
			$result = $exam->query_result();
			$output = '
			<div class="card">
				<div class="card-header">Question Navigation</div>
				<div class="card-body">
					<div class="row">
			';
			$count = 1;
			foreach($result as $row)
			{
				$output .= '
				<div class="col-md-2" style="margin-bottom:24px;">
					<button type="button" class="btn btn-primary btn-lg question_navigation" data-question_id="'.$row["question_id"].'">'.$count.'</button>
				</div>
				';
				$count++;
			}
			$output .= '
				</div>
			</div></div>
			';
			//put the output
			echo $output;
		}
//get use details
		if($_POST['action'] == 'user_detail')
		{
			//database functions
			$exam->query = "
			SELECT * FROM user_table
			WHERE user_id = '".$_SESSION["user_id"]."'
			";

			$result = $exam->query_result();
//display it in html
			$output = '
			<div class="card">
				<div class="card-header">User Details</div>
				<div class="card-body">
					<div class="row">
			';

			foreach($result as $row)
			{
				//display data in html
				$output .= '
				<div class="col-md-3">
					<img src="upload/'.$row["user_image"].'" class="img-fluid" />
				</div>
				<div class="col-md-9">
					<table class="table table-bordered">
						<tr>
							<th>Name</th>
							<td>'.$row["user_name"].'</td>
						</tr>
						<tr>
							<th>Email ID</th>
							<td>'.$row["user_email_address"].'</td>
						</tr>
						<tr>
							<th>Gendar</th>
							<td>'.$row["user_gender"].'</td>
						</tr>
					</table>
				</div>
				';
			}
			$output .= '</div></div></div>';
			echo $output;
		}
		//handling answers
		if($_POST['action'] == 'answer')
		{
			//get answer data
			$exam_right_answer_mark = $exam->Get_question_right_answer_mark($_POST['exam_id']);

			$exam_wrong_answer_mark = $exam->Get_question_wrong_answer_mark($_POST['exam_id']);
			$orignal_answer = $exam->Get_question_answer_option($_POST['question_id']);

			$marks = 0;
//get marks of right and wrong
			if($orignal_answer == $_POST['answer_option'])
			{
				$marks = '+' . $exam_right_answer_mark;
			}
			else
			{
				$marks = '-' . $exam_wrong_answer_mark;
			}
//display data
			$exam->data = array(
				':user_answer_option'	=>	$_POST['answer_option'],
				':marks'				=>	$marks
			);
//update databse depend on answer
			$exam->query = "
			UPDATE user_exam_question_answer
			SET user_answer_option = :user_answer_option, marks = :marks
			WHERE user_id = '".$_SESSION["user_id"]."'
			AND exam_id = '".$_POST['exam_id']."'
			AND question_id = '".$_POST["question_id"]."'
			";
			$exam->execute_query();
		}
	}

}

?>
