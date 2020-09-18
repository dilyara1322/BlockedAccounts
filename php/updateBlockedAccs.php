<?	//получение информации о заблок счетах компании через api
	require '../lib/Company.php';
	if (isset($_POST['inn']))
	{
		$inn = $_POST['inn'];
		$company = new Company($inn);
		if ($company->GetError() === false)
		{
			$company->UpdateBlockedAccs();
			if ($company->GetError() === false)
				echo $company->GetInfo();
			else 
				echo $company->GetError();
		}
		else 
			echo $company->GetError();
	}