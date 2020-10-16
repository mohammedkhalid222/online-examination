<?php

//include files

include('master/Examination.php');

$exam = new Examination;
//check if admin varified
if(isset($_GET['type'], $_GET['code']))
{
	if($_GET['type'] == 'master')
	{
		$exam->data = array(
			':email_verified'		=>	'yes'
		);

		$exam->query = "
		UPDATE admin_table
		SET email_verified = :email_verified
		WHERE admin_verfication_code = '".$_GET['code']."'
		";

		$exam->execute_query();
//redirect to admin login
		$exam->redirect('master/login.php?verified=success');
	}
//check if user is varified
	if($_GET['type'] == 'user')
	{
		$exam->data = array(
			':user_email_verified'	=>	'yes'
		);

		$exam->query = "
		UPDATE user_table
		SET user_email_verified = :user_email_verified
		WHERE user_verfication_code = '".$_GET['code']."'
		";

		$exam->execute_query();
//redirect to login
		$exam->redirect('login.php?verified=success');
	}
}


?>
