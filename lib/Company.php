<?
	require_once "Directors.php";
	require_once "BlockedAccs.php";
	require_once "ApiWork.php";

	/**
	* Класс Company
	* Класс для работы с карточкой компании
	*/
	class Company extends ApiWork
	{
		/**
		* ИНН организации
		*/
		private $inn = '';
		
		/**
		* company_id организации
		*/
		private $company_id = '';
		
		/**
		* наименование организации
		*/
		private $name = '';
		
		/**
		* статус организации
		*/
		private $status = '';
		
		/**
		* руководители организации
		*/
		private $directors = null;
		
		/**
		* заблокированные счета организации
		*/
		private $blockedAccs = null;
		
		/**
		* информация об ошибке, если она появилась
		*/
		private $error = false;

		/**
		* был ли запрос к апи
		*/
		//private $isInfoFromAPI = false;

		
		/**
		* конструктор
		* @param int $inn - ИНН компании
		*/
		public function __construct($inn)
		{
			parent::__construct();

			$this->inn = $inn;
			//загрузка основной информации об организации
			$this->LoadCompany();
			if ($this->error === false)
			{
				//загрузка инфорации о руководителях организации
				$this->FindDirectors();
			}
		}

		/**
		* метод для получения основной информации о компании
		*/
		private function LoadCompany()
		{
			if(parent::Connect())
			{
				$company = parent::_select('companies', '*', '`inn` = '.$this->inn);
				parent::Close();

				if ($company && $company->num_rows > 0)
				{
					$company = $company->fetch_assoc();
					$this->company_id = $company['company_id'];
					$this->name = $company['name'];
					$this->status = $company['status'];
					return;
				}
			}
			//загрузка информации из апи, если ее нет в базе
			$this->FindCompanyInAPI();
		}

		/**
		* метод для получения основной информации о компании из апи
		*/
		private function FindCompanyInAPI()
		{
			//задержка для соблюдения ограничения на количество запросов
			// sleep(1);
			
			//получение информации из апи в json
			$company = parent::CompanyMainInfo($this->inn);
			//$this->isInfoFromAPI = true;

			//разбор json
			$company = json_decode($company);
			if (json_last_error() == JSON_ERROR_NONE && is_array($company))
			{
				if (sizeof($company) > 0)
				{
					$company = $company[0];

					if (property_exists($company, 'company_id')) $this->company_id = $company->company_id;
					if (property_exists($company, 'full_name')) $this->name = $company->full_name;
					if (property_exists($company, 'status')) $this->status = $company->status;
					
					//сохранение информации в базе
					$this->SaveMainInfo();
				}
				else $this->error = 'Неверный ИНН';
			}
			else $this->error = $company->error;
		}

		/**
		* метод для сохранения основной информации о компании в базе
		*/
		public function SaveMainInfo()
		{
			if (parent::Connect())
			{
				$companyData = array(array('company_id','name','inn','status'),
									array($this->company_id, $this->name, $this->inn, $this->status));
				parent::_insert("companies", $companyData);
				parent::Close();
			}
		}

		/**
		* метод для получения информации о руководителях компании
		*/
		public function FindDirectors()
		{
			if (!empty($this->company_id)) 
			{
				$this->directors = new Directors($this->company_id);
				if ($this->directors->GetError() !== false)
					$this->error = $this->directors->GetError();
				// else 
				// 	$this->isInfoFromAPI = ($this->isInfoFromAPI || $this->directors->GetIsInfoFromAPI());
			}
		}

		/**
		* метод для получения информации о заблок.счетах компании
		* @param string $save_date - дата и время сохранения информации о счетах
		* @param int $page - номер группы счетов (номер страницы)
		*/
		public function FindBlockedAccs($save_date, $page)
		{
			if (!empty($this->company_id)) {
				$this->blockedAccs = new BlockedAccs($this->company_id);
				$this->blockedAccs->LoadFromDB($save_date, $page);
				if ($this->blockedAccs->GetError() !== false)
					$this->error = $this->blockedAccs->GetError();
				// else 
				// 	$this->isInfoFromAPI = ($this->isInfoFromAPI || $this->blockedAccs->GetIsInfoFromAPI());
			}
		}

		/**
		* метод для обновления информации о заблок.счетах компании в базе
		*/
		public function UpdateBlockedAccs()
		{
			if (!empty($this->company_id)) 
			{
				$this->blockedAccs = new BlockedAccs($this->company_id, $this->isInfoFromAPI);
				$this->blockedAccs->LoadFromApi();
				if ($this->blockedAccs->GetError() !== false)
					$this->error = $this->blockedAccs->GetError();
				else 
					$this->isInfoFromAPI = ($this->isInfoFromAPI || $this->blockedAccs->GetIsInfoFromAPI());
			}
		}

		/**
		* метод для получения информации о компании в формате json
		* @return string - информации о компании в формате json
		*/
		public function GetInfo()
		{
			$data = array('inn' => $this->inn, 'name' => $this->name, 'status' => $this->status, 'directors' => '', 'blockedAccs' => '', 'save_dates' => '', 'blockedAccsCount' => 0);
			if (is_object($this->directors)) 
			{
				$data['directors'] = json_decode($this->directors->GetDirectorsInJSON());
			}
			if (is_object($this->blockedAccs)) 
			{
				$data['blockedAccs'] = json_decode($this->blockedAccs->GetAccsInJSON());
				$data['save_dates'] = json_decode($this->blockedAccs->AllDates());
				$data['blockedAccsCount'] = $this->blockedAccs->SaveDateCount();
			}

			return json_encode($data);
		}

		/**
		* метод для получения сообщения возникшей ошибки
		* @return string|bool - сообщение об ошибке|false
		*/
		public function GetError()
		{
			return $this->error;
		}

		/**
		* метод для получения ИНН организации
		* @return int - ИНН организации
		*/
		public function GetInn()
		{
			return $this->inn;
		}

		/**
		* метод для получения company_id организации
		* @return int - company_id организации
		*/
		public function GetCompanyId()
		{
			return $this->company_id;
		}
		
		/**
		* метод для получения наименования организации
		* @return string - наименование организации
		*/
		public function GetName()
		{
			return $this->name;
		}
		
		/**
		* метод для получения статуса организации
		* @return string - статус организации
		*/
		public function GetStatus()
		{
			return $this->status;
		}
		
		/**
		* метод для получения руководителей организации в формате json
		* @return string - руководители организации в формате json
		*/
		public function GetDirectorsInJSON()
		{
			if (is_object($this->directors)) return $this->directors->GetDirectorsInJSON();
		}
		
		/**
		* метод для получения руководителей организации в виде объекта
		* @return Directors - руководители организации в виде объекта
		*/
		public function GetDirectors()
		{
			return $this->directors;
		}
		
		/**
		* метод для получения заблок.счетов организации в формате json
		* @return string - заблок.счета организации в формате json
		*/
		public function GetBlockedAccsInJSON()
		{
			if (is_object($this->blockedAccs)) return $this->blockedAccs->GetAccsInJSON();
		}
		
		/**
		* метод для получения заблок.счетов организации в виде объекта
		* @return BlockedAccs - заблок.счетов организации в виде объекта
		*/
		public function GetBlockedAccs()
		{
			return $this->blockedAccs;
		}

		/**
		* деструктор
		*/
		public function __destruct()
		{
			parent::__destruct();
		}
	}