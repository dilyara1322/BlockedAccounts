<?
	require '../lib/Company.php';
	if (isset($_POST['inn']))
	{
		$inn = $_POST['inn'];
		$company = new Company($inn);
		echo $company->GetBlockedAccs()->AllDates();
	}