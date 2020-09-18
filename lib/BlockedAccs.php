<?
	require_once 'ApiWork.php';
	require_once 'Acc.php';

	/**
	* Класс BlockedAccs
	* Класс для работы с совокупностью заблокированных счетов
	*/
	class BlockedAccs extends ApiWork
	{
		/**
		* id компании, к которой относятся заблок.счета
		*/
		private $company_id;
		
		/**
		* дата и время сохранения информации о счетах
		*/
		private $save_date;
		
		/**
		* массив с заблок.счетами
		*/
		private $accounts = array();
		
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
		}

		/**
		* метод для получения некоторого количества счетов из базы
		* @param string $save_date - дата и время сохранения счетов
		* @param int $page - номер группы счетов (номер страницы)
		* @param int $limit - количество счетов
		*/
		public function LoadFromDB($save_date = '', $page = 1, $limit = 20)
		{
			if (!empty($save_date)) $this->save_date = $save_date;
			else $this->save_date = $this->LastDate();

			//поиск в базе
			$where = "`company_id` = '".$this->company_id."' AND `save_date` = '".$this->FormatDateToISO($this->save_date)."'";
			if (!empty($page) && !empty($limit)) $limit = ''. (($page - 1) * $limit) . ', ' . $limit;
			else $limit = "";
			if (parent::Connect()) 
			{
				$accs = parent::_select('blocked_accounts', '*', $where, 'id', $limit);
				parent::Close();
				if ($accs && $accs->num_rows > 0)
				{
					$this->accounts = array();
					while($row = $accs->fetch_assoc())
					{
						$updated = new DateTime($row['updated']);
						$this->accounts[] = new Acc($this->company_id, $this->FormatDateToGOST($row['date']), $row['code'], $row['bic'], $row['bank_info'], $updated->format('d.m.Y'), $row['number'], $this->FormatDateToGOST($row['save_date']));
					}
					return;
				}
			}

			//загрузка счетов из апи, если их нет в базе
			$this->LoadFromApi();		
		}

		/**
		* метод для получения счетов из апи
		*/
		public function LoadFromApi()
		{
			//задержка для соблюдения ограничения на количество запросов
			// if ($this->isInfoFromAPI) sleep(0.01);
			// else sleep(1);

			//получение счетов из апи в json
			$accs = parent::SourceInfo($this->company_id);
			//$this->isInfoFromAPI = true;
			
			//разбор json
			$accs = json_decode($accs);
			if (json_last_error() == JSON_ERROR_NONE)
			{
				if (!empty($accs))
				{
					$accs = json_decode($accs->data);

					if (json_last_error() == JSON_ERROR_NONE)
					{
						//сохранение всех счетов в базе
						$date = new DateTime();
						$save_date = $date->format('Y-m-d H:i:s');
						foreach ($accs as $account) {

							$date = '0000-00-00 00:00:00';
							if (property_exists($account, 'date')) $date = $this->FormatDateToISO($account->date);
							$code = 0;
							if (property_exists($account, 'code')) $code = $account->code;
							$bic = '';
							if (property_exists($account, 'bic')) $bic = $account->bic;
							$bank_info = '';
							if (property_exists($account, 'bank_info')) $bank_info = $account->bank_info;
							$updated = '0000-00-00';
							if (property_exists($account, 'updated')) $updated = $this->FormatDateToISO($account->updated);
							$number = 0;
							if (property_exists($account, 'number')) $number = $account->number;

							$acc = new Acc($this->company_id, $date, $code, $bic, $bank_info, $updated, $number, $save_date);
							$acc->Save();
						}
						//получение из базы нужного количества счетов
						$this->LoadFromDB();
					}
					else $this->error = $accs;
				}
			}
			else $this->error = $accs;
		}

		/**
		* метод для получения количества счетов с заданной датой и временем сохранения
		* @return int|null - количество счетов|null
		*/
		public function SaveDateCount()
		{
			if (parent::Connect())
			{
				$count = parent::_select('blocked_accounts', 'COUNT(*) AS `count`', "`company_id` = '".$this->company_id."' AND `save_date` = '".$this->FormatDateToISO($this->save_date)."'");
				parent::Close();
				if ($count && $row = $count->fetch_assoc()) {
					return $row['count'];
				}
			}
			return null;
		}

		/**
		* метод для получения последних даты и времени сохранения счетов
		* @return string|null - последние дата и время|null
		*/
		public function LastDate()
		{
			if (parent::Connect())
			{
				$d = parent::_query("SELECT MAX(`save_date`) AS `date` FROM `blocked_accounts` WHERE `company_id` = '".$this->company_id."'");
				parent::Close();
				if ($d && $d->num_rows > 0 && $row = $d->fetch_assoc()) {
					return $this->FormatDateToGOST($row['date']);
				}
			}
			return null;
		}

		/**
		* метод для получения массива со всеми датами (и временем) сохранения счетов
		* @return string|null - массива со всеми датами (и временем) в json|null
		*/
		public function AllDates()
		{
			$dates = array();
			if (parent::Connect())
			{
				$d = parent::_query("SELECT `save_date` as `date` FROM `blocked_accounts` WHERE `company_id` = '".$this->company_id."' GROUP BY `save_date` DESC");
				parent::Close();
				while ($d && $row = $d->fetch_assoc()) {
					$dates[] = $this->FormatDateToGOST($row['date']);
				}
				return json_encode($dates);
			}
			else return null;
		}

		/**
		* метод для преобразования даты [и времени] из формата ГОСТ в формат ISO 
		* @param string $dateInGOST - дата [и время] в формате ГОСТ
		* @return string|null - дата [и время] в формате ISO|null
		*/
		private function FormatDateToISO($dateInGOST)
		{
			if (preg_match("/^(\\d){1,2}\\.(\\d){1,2}\\.(\\d){4} (\\d){1,2}:(\\d){1,2}:(\\d){1,2}$/", $dateInGOST, $match))
			{
				$date = DateTime::createFromFormat('d.m.Y H:i:s', $dateInGOST);
				return $date->format('Y-m-d H:i:s');
			}
			else if (preg_match("/^(\\d){1,2}\\.(\\d){1,2}\\.(\\d){4}$/", $dateInGOST, $match))
			{
				$date = DateTime::createFromFormat('d.m.Y', $dateInGOST);		
				return $date->format('Y-m-d');
			}
			else return null;
		}

		/**
		* метод для преобразования даты и времени из формата ISO в формат ГОСТ 
		* @param string $dateInISO - дата и время в формате ISO
		* @return string|null - дата и время в формате ГОСТ|null
		*/
		private function FormatDateToGOST($dateInISO)
		{
			$date = new DateTime($dateInISO);		
			return $date->format('d.m.Y H:i:s');
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
		* метод для получения счетов в виде массива объектов
		* @return array - массив объектов заблок.счетов
		*/
		public function GetAccs()
		{
			return $this->accounts;
		}

		/**
		* метод для получения счетов в формате json
		* @return string - заблок.счета в формате json
		*/
		public function GetAccsInJSON()
		{
			if (sizeof($this->accounts) == 0) return "[]";
			else
			{
				$arr = array();
				foreach ($this->accounts as $account) {
					$arr[] = json_decode($account->GetInfo());
				}
				return json_encode($arr);
			}
		}

		/**
		* деструктор
		*/
		public function __destruct()
		{
			parent::__destruct();
		}
	}