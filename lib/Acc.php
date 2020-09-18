<?
	require_once 'MySQL.php';

	/**
	* Класс Acc
	* Класс для работы с заблокированными счетами по отдельности
	*/
	class Acc extends MySQL
	{
		/**
		* id компании, к которой относится заблок.счет
		*/
		private $company_id;

		/**
		* дата и время размещения информации о заблок.счете
		*/
		private $date;

		/**
		* код налогового органа
		*/
		private $code;

		/**
		* БИК / к.сч банка
		*/
		private $bic;

		/**
		* информация о банке
		*/
		private $bank_info;

		/**
		* дата решения о приостановлении
		*/
		private $updated;

		/**
		* номер решения о приостановлении
		*/
		private $number;

		/**
		* дата и время сохранения информации в базе
		*/
		private $save_date;


		/**
		* конструктор
		* @param int $company_id - id компании
		* @param string $date - дата и время размещения информации о заблок.счете
		* @param int $code - код налогового органа
		* @param string $bic - БИК / к.сч банка
		* @param string $bank_info - информация о банке
		* @param string $updated - дата решения о приостановлении
		* @param int $number - номер решения о приостановлении
		* @param string $save_date - дата и время сохранения информации в базе
		*/
		public function __construct($company_id, $date, $code, $bic, $bank_info, $updated, $number, $save_date = "")
		{
			parent::__construct();

			if (empty($save_date)) $save_date = new DateTime();

			$this->company_id = $company_id;
			$this->date = $date;
			$this->code = $code;
			$this->bic = $bic;
			$this->bank_info = $bank_info;
			$this->updated = $updated;
			$this->number = $number;
			$this->save_date = $save_date;
		}

		/**
		* метод для сохранения счета в базе
		*/
		public function Save()
		{
			if (parent::Connect())
			{
				$data = array(
					array('company_id', 'number', 'date', 'code', 'bic', 'bank_info', 'updated', 'save_date'), 
					array($this->company_id, $this->number, $this->date, $this->code, $this->bic, $this->bank_info, $this->updated, $this->save_date));
				parent::_insert('blocked_accounts', $data);
				parent::Close();
			}
		}

		/**
		* метод для получения информации о счете в формате json
		* @return string - массив с данными о счете в формате json
		*/
		public function GetInfo()
		{
			return json_encode(array('company_id' => $this->company_id, 'date' => $this->date, 
				'code' => $this->code, 'bic' => $this->bic, 'bank_info' => $this->bank_info, 
				'updated' => $this->updated, 'number' => $this->number, 'save_date' => $this->save_date));
		}

		/**
		* метод для получения id компании
		* @return int - id компании
		*/
		public function GetCompanyID()
		{
			return $this->company_id;
		}

		/**
		* метод для получения даты и времени размещения информации
		* @return string - дата и время размещения информации
		*/
		public function GetDate()
		{
			return $this->date;
		}

		/**
		* метод для получения кода налогового органа
		* @return int - код налогового органа
		*/
		public function GetCode()
		{
			return $this->code;
		}

		/**
		* метод для получения БИК/к.сч. банка
		* @return string - БИК/к.сч. банка
		*/
		public function GetBic()
		{
			return $this->bic;
		}

		/**
		* метод для получения информации о банке
		* @return string - информация о банке
		*/
		public function GetBankInfo()
		{
			return $this->bank_info;
		}

		/**
		* метод для получения даты решения
		* @return string - дата решения о приостановлении
		*/
		public function GetUpdated()
		{
			return $this->updated;
		}

		/**
		* метод для получения номера решения
		* @return int - номер решения
		*/
		public function GetNumber()
		{
			return $this->number;
		}

		/**
		* метод для получения даты и времени сохранения информации
		* @return string - дата и ремя сохранения информации
		*/
		public function GetSaveDate()
		{
			return $this->save_date;
		}

		/**
		* деструктор
		*/
		public function __destruct()
		{
			parent::__destruct();
		}
	}			