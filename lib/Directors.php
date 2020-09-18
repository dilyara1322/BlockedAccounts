<?
	require_once "ApiWork.php";
	require_once "Director.php";

	/**
	* Класс Directors
	* Класс для работы с совокупностью руководителей компании
	*/
	class Directors extends ApiWork
	{
		/**
		* id компании, к которой относятся руководители
		*/
		private $company_id;
		
		/**
		* массив с информацией о руководителях
		*/
		private $directors = array();
		
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
		* @param int $company_id - id компании
		*/
		public function __construct($company_id)
		{
			parent::__construct();

			$this->company_id = $company_id;
			//$this->isInfoFromAPI = $isInfoFromAPI;

			$this->Load();
			
		}

		/**
		* метод для получения информации о руководителях из базы
		*/
		public function Load()
		{
			if (parent::Connect())
			{
				$directors = parent::_select('directors', '*', '`company_id` = '.$this->company_id);
				parent::Close();

				if ($directors && $directors->num_rows > 0)
				{
					while ($row = $directors->fetch_assoc()) {
						$this->directors[] = new Director($this->company_id, $row['job'], $row['name']);
					}
					return;
				}
			}
			//поиск в апи, если в базе нет информации
			$this->FindInAPI();
		}

		/**
		* метод для получения информации о руководителях из апи
		*/
		public function FindInAPI()
		{
			//задержка для соблюдения ограничения на количество запросов
			// if ($this->isInfoFromAPI) sleep(0.01);
			// else sleep(1);

			//получение информации о руководителях из апи в json
			$data = parent::CompanyCard($this->company_id);
			//$this->isInfoFromAPI = true;

			//разбор json
			$card = json_decode($data);
			if (json_last_error() == JSON_ERROR_NONE)
			{
				try{
					$directors = $card->directors;
					//получение и сохранение в базе информации о руководителях
					foreach ($directors as $dir) {
						$job = ''; 
						if (property_exists($dir, 'job')) $job = $dir->job;
						$last_name = '';
						if (property_exists($dir, 'last_name')) $last_name = $dir->last_name;
						$first_name = '';
						if (property_exists($dir, 'first_name')) $first_name = $dir->first_name;
						$middle_name = '';
						if (property_exists($dir, 'middle_name')) $middle_name = $dir->middle_name;

						$director = new Director($this->company_id, $job, 
									$last_name." ".$first_name." ".$middle_name);
						$director->Save();	
						$this->directors[] = $director;	
					}
				}
				catch(Exception $e) {
					$this->error = $e->getMessage();
				}
			}
			else $this->error = $data;
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
		* метод для получения информации о том, был ли запрос к апи
		* @return bool - был ли запрос к апи
		*/
		// public function GetIsInfoFromAPI()
		// {
		// 	return $this->isInfoFromAPI;
		// }

		/**
		* метод для получения информации о руководителях в json-формате
		* @return string - информация о руководителях в json-формате
		*/
		public function GetDirectorsInJSON()
		{
			$arr = array();
			foreach ($this->directors as $dir) {
				$arr[] = json_decode($dir->GetInfo());
			}
			return json_encode($arr);
		}

		/**
		* метод для получения информации о руководителях в виде массива объектов
		* @return array - информация о руководителях в виде массива объектов
		*/
		public function GetDirectors()
		{
			return $this->directors;
		}

		/**
		* метод для получения company_id организации
		* @return inn - company_id организации
		*/
		public function GetCompanyId()
		{
			return $this->company_id;
		}

		/**
		* деструктор
		*/
		public function __destruct()
		{
			parent::__destruct();
		}
	}