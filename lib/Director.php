<?
	require_once 'MySQL.php';

	/**
	* Класс Director
	* Класс для работы с руководителями компании по отдельности
	*/
	class Director extends MySQL
	{
		/**
		* id компании, к которой относится руководитель
		*/
		private $company_id;

		/**
		* должность руководителя
		*/
		private $job;
		
		/**
		* ФИО руководителя
		*/
		private $name;

		
		/**
		* конструктор
		* @param int $company_id - id компании
		* @param string $job - должность руководителя
		* @param string $name - ФИО руководителя
		*/
		public function __construct($company_id, $job, $name)
		{
			parent::__construct();

			$this->company_id = $company_id;
			$this->job = $job;
			$this->name = $name;
		}

		/**
		* метод для сохранения информации о руководителе в базе
		*/
		public function Save()
		{
			if (parent::Connect())
			{
				$sel = parent::_select('directors', '*', "`company_id` = '".$this->company_id."' AND `job` = '".$this->job."' AND `name` = '".$this->name."'");
				if ($sel && $sel->num_rows == 0)
				{
					$data = array(array('company_id', 'job', 'name'), 
									array($this->company_id, $this->job, $this->name));
					parent::_insert('directors', $data);
				}
				parent::Close();
			}
		}

		/**
		* метод для получения информации о руководителе в json-формате
		* @return string - информация о руководителе в json-формате
		*/
		public function GetInfo()
		{
			return json_encode(array('company_id' => $this->company_id, 'job' => $this->job, 
				'name' => $this->name));
		}

		/**
		* метод для получения company_id организации
		* @return int - company_id организации
		*/
		public function GetCompanyID()
		{
			return $this->company_id;
		}

		/**
		* метод для получения должности руководителя
		* @return string - должности руководителя
		*/
		public function GetJob()
		{
			return $this->job;
		}

		/**
		* метод для получения ФИО руководителя
		* @return string - ФИО руководителя
		*/
		public function GetName()
		{
			return $this->name;
		}

		/**
		* деструктор
		*/
		public function __destruct()
		{
			parent::__destruct();
		}
	}