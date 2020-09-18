<?	//получение всей доступной информации о компании любым способом (БД или api)
	require "../lib/Company.php";
	if (isset($_POST['inn']))
	{
		//установка нужных парамертров
		$save_date = "";
		if (!empty($_POST['save_date'])) 
			$save_date = $_POST['save_date'];

		$page = "1";
		if (!empty($_POST['page'])) 
			$page = $_POST['page'];

		$inn = $_POST['inn'];

		//создание объекта компании и его наполнение данными
		$company = new Company($inn);
		if ($company->GetError() === false)
		{
			$company->FindBlockedAccs($save_date, $page);
			if ($company->GetError() === false)
				echo $company->GetInfo();
			else 
				echo $company->GetError();
		}
		else 
			echo $company->GetError();
		
	}