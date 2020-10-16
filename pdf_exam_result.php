<?php

//includes files

include('master/Examination.php');

require_once('class/pdf.php');

$exam = new Examination;
//get data from database
if(isset($_GET["code"]))
{
	$exam_id = $exam->Get_exam_id($_GET["code"]);

	$exam->query = "
	SELECT * FROM question_table
	INNER JOIN user_exam_question_answer
	ON user_exam_question_answer.question_id = question_table.question_id
	WHERE question_table.online_exam_id = '$exam_id'
	AND user_exam_question_answer.user_id = '".$_SESSION["user_id"]."'
	";

	$result = $exam->query_result();
//display data in html
	$output = '
	<h3 align="center">Exam Result</h3>
	<table width="100%" border="1" cellpadding="5" cellspacing="0">
		<tr>
			<th>Question</th>
			<th>Your Answer</th>
			<th>Answer</th>
			<th>Result</th>
			<th>Marks</th>
		</tr>
	';

	$total_mark = 0;
//get completed data
	foreach($result as $row)
	{
		$exam->query = "
		SELECT * FROM option_table
		WHERE question_id = '".$row["question_id"]."'
		";

		$sub_result = $exam->query_result();

		$user_answer = '';
		$orignal_answer = '';
		$question_result = '';
//handle maarks
		if($row["marks"] == '0')
		{
			$question_result = 'Not Attend';
		}

		if($row["marks"] > '0')
		{
			$question_result = 'Right';
		}

		if($row['marks'] < '0')
		{
			$question_result = 'Wrong';
		}
//output question and answers
		$output .= '
		<tr>
			<td>'.$row["question_title"].'</td>
		';

		foreach($sub_result as $sub_row)
		{
			if($sub_row["option_number"] == $row["user_answer_option"])
			{
				$user_answer = $sub_row["option_title"];
			}

			if($sub_row["option_number"] == $row["answer_option"])
			{
				$orignal_answer = $sub_row["option_title"];
			}
		}
		//display output in html
		$output .= '
			<td>'.$user_answer.'</td>
			<td>'.$orignal_answer.'</td>
			<td>'.$question_result.'</td>
			<td>'.$row["marks"].'</td>
		</tr>
		';
	}
//get user data from database
	$exam->query = "
	SELECT SUM(marks) as total_mark FROM user_exam_question_answer
	WHERE user_id = '".$_SESSION['user_id']."'
	AND exam_id = '".$exam_id."'
	";

	$marks_result = $exam->query_result();

	foreach($marks_result as $row)
	{
		//display total marks
		$output .= '
		<tr>
			<td colspan="4" align="right">Total Marks</td>
			<td align="right">'.$row["total_mark"].'</td>
		</tr>
		';
	}
	$output .= '</table>';
//make pdf documents
	$pdf = new Pdf();

	$pdf->set_paper('letter','landscape');

	$file_name = 'Exam Result.pdf';

	$pdf->loadHtml($output);

	$pdf->render();

	$pdf->stream($file_name, array("Attachment" => false));
	exit(0);
}

?>
