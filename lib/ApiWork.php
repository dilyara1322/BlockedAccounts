<?
	require_once "MySQL.php";
	require_once "CurlRes.php";

	/**
	* Class ApiWork
	* Класс для работы с api
	*/
	class ApiWork extends MySQL
	{
		/**
		* объект для работы с curl
		*/
		protected $curl;
		
		/**
		* конструктор
		*/
		public function __construct()
		{
			parent::__construct();

			$this->curl = new CurlRes();
			$this->curl->SetUser(API_LOGIN, API_PASSWORD);
		}

		/**
		* метод для получения основной информации о ЮЛ из апи
		* @param int $inn - инн компании
		* @return string - информация о компании в json-строке
		*/
		public function CompanyMainInfo($inn)
		{
			return $this->curl->PostQuery("https://service.deltasecurity.ru/api/find/company?inn=$inn");
		}

		/**
		* метод для получения карточки ЮЛ из апи
		* @param int $company_id - company_id компании
		* @return string - информация о компании в json-строке
		*/
		public function CompanyCard($company_id)
		{
			return $this->curl->PostQuery("https://service.deltasecurity.ru/api/company/$company_id");
		}

		/**
		* метод для получения списка доступных внешних источников
		* @param int $company_id - company_id компании
		* @return string - список доступных внешних источников по компании
		*/
		public function ExternalSources($company_id)
		{
			return $this->curl->PostQuery("https://service.deltasecurity.ru/api/company/$company_id/sources");
		}

		/**
		* метод для получения отчета по источнику
		* @param int $company_id - company_id компании
		* @param int $type_id - идентификатор типа источника
		* @return string - отчет по источнику в json-строке
		*/
		public function SourceInfo($company_id, $type_id = 111)
		{
			return $this->curl->PostQuery("https://service.deltasecurity.ru/api/company/$company_id/sources/report/$type_id");
		}

		/**
		* деструктор
		*/
		public function __destruct()
		{
			parent::__destruct();
		}
	}