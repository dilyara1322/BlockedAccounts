<?
	/**
	* Класс CurlRes
	* Класс для работы с curl
	*/
	class CurlRes
	{
		/**
		* дескриптор curl
		*/
		private $ch = null;
		
		/**
		* логин и пароль в массиве
		*/
		private $user = null;

		
		/**
		* конструктор
		* @param string $url - адрес, куда послать запрос
		*/
		public function __construct($url = "")
		{
			if (!empty($url) && is_string($url)) 
				$this->ch = curl_init($url);
			else 
				$this->ch = curl_init();

			curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		}

		/**
		* метод для установки url, куда послать запрос
		* @param string $url - адрес, куда послать запрос
		*/
		public function SetURL($url)
		{
			if (!empty($url) && is_string($url)) 
				curl_setopt($this->ch, CURLOPT_URL, $url);
		}

		/**
		* метод для установки логина и пароля
		* @param string $login - логин
		* @param string $password - пароль
		*/
		public function SetUser($login, $password)
		{
			$this->user = array('login' => $login, 'password' => md5($password));
		}

		/**
		* метод для выполнения запроса
		* @return string - результат запроса|сообщение об ошибке
		*/
		private function Execute()
		{
			//задержка для соблюдения ограничения на количество запросов
			sleep(1);

			$res = curl_exec($this->ch);
			if (curl_errno($this->ch) > 0) 
				return curl_error($this->ch);
			else 
				return $res;
		}

		/**
		* метод для выполнения post запроса
		* @return string - результат запроса|сообщение об ошибке
		*/
		public function PostQuery($url = "", $post_data = null)
		{
			if (is_null($post_data)) 
				$post_data = $this->user;
			if (!empty($url) && is_string($url)) 
				$this->SetURL($url);
			curl_setopt($this->ch, CURLOPT_POST, 1);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_data);
			
			$res = $this->Execute();
			return $res;
		}

		/**
		* деструктор
		*/
		public function __destruct()
		{
			curl_close($this->ch);
		}
	}