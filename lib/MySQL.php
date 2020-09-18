<?
	require_once "../config.php";

	/**
	* Класс MySQL
	* Класс для работы с базой данных MySQL
	*/
	class MySQL
	{
		/**
		* имя БД
		*/
		protected $db;
		

		/**
		* конструктор
		*/
		public function __construct()
		{
			$this->db = null;
		}

		/**
		* метод для открытия соединения с базой
		* @param string $database - название базы
		* @return bool - есть ли соединение
		*/
		public function Connect($database = 'companies_db')
		{
			$this->db = new mysqli(HOST, USER, PASSWORD, $database);
			if ($error = $this->db->connect_error) 
			{
				$this->db = null;
				return false;
			}
			else
			{
				$this->db->set_charset("utf8"); 
				return true;
			}
		}

		/**
		* метод для осуществления сложных запросов (небезопасный)
		* @param string $query - запрос
		* @return ответ на sql-запрос
		*/
		public function _query($query)
		{
			return $this->db->query($query);
		}

		/**
		* метод для осуществления выборки из таблицы
		* @param string $table - имя таблицы
		* @param string $fields - поля таблицы
		* @param string $where - условие where
		* @param string $orderby - поле, по которому идет сортировка
		* @param string $limit - необходимое количество записей
		* @return sql-table|null
		*/
		public function _select($table, $fields = " * ", $where = "", $orderby = "", $limit = "")
		{
			if (is_null($this->db)) return null;

			$table = $this->db->real_escape_string($table);
			$fields = $this->db->real_escape_string($fields);
			//$where = $this->db->real_escape_string($where);
			$orderby = $this->db->real_escape_string($orderby);
			$limit = $this->db->real_escape_string($limit);

			$query = "SELECT $fields FROM $table";
			if (!empty($where)) $query .= " WHERE $where";
			if (!empty($orderby)) $query .= " ORDER BY $orderby";
			if (!empty($limit)) $query .= " LIMIT $limit";
			return $this->db->query($query);
		}

		/**
		* метод для добавления строк в базу
		* @param string                               $table - название таблицы
		* @param array{array(string), array(string)}  $fvalues - массив с именами полей и значениями
		* @return null|bool
		*/
		public function _insert($table, $fvalues)
		{
			if (is_null($this->db)) return null;

			if (is_array($fvalues) && array_key_exists(0, $fvalues) && array_key_exists(1, $fvalues) && is_array($fvalues[0]) && is_array($fvalues[1]))
			{
				$table = $this->db->real_escape_string($table);
				foreach ($fvalues[0] as $key => $value) 
				{
					$fvalues[0][$key] = $this->db->real_escape_string($value);
				}
				foreach ($fvalues[1] as $key => $value) 
				{
					$fvalues[1][$key] = $this->db->real_escape_string($value);
				}

				$fields = "`" . implode("`, `", $fvalues[0]) . "`";
				$values = "'" . implode("', '", $fvalues[1]) . "'"; 

				return $this->db->query("INSERT INTO $table ($fields) VALUES ($values)");
			}
		}

		/**
		* метод для обновления таблиц
		* @param string                               $table - название таблицы
		* @param array{array(string), array(string)}  $fvalues - массив с именами полей и значениями
		* @return null|bool
		*/
		public function _update($table, $fvalues, $where = "")
		{
			if (is_null($this->db)) return null;

			if (is_array($fvalues) && array_key_exists(0, $fvalues) && array_key_exists(1, $fvalues) && is_array($fvalues[0]) && is_array($fvalues[1]) && 
				sizeof($fvalues[0]) == sizeof($fvalues[1]))
			{
				$table = $this->db->real_escape_string($table);
				foreach ($fvalues[0] as $key => $value) 
				{
					$fvalues[0][$key] = $this->db->real_escape_string($value);
				}
				foreach ($fvalues[1] as $key => $value) 
				{
					$fvalues[1][$key] = $this->db->real_escape_string($value);
				}

				$set = '';
				for ($i = 0; $i < sizeof($fvalues[0]); $i++) { 
					$set .= $fvalues[0][$i] . " = " . $fvalues[1][$i];
					if ($i < sizeof($fvalues[0]) - 1) $set .= ", ";
				}

				if ($where != '') $where = " WHERE ".$where;

				return $this->db->query("UPDATE $table SET $set $where");
			}
			else return false;
		}

		/**
		* метод для закрытия соединения с базой
		*/
		public function Close()
		{
			if (!is_null($this->db)) 
				$this->db->close();
			$this->db = null;
		}

		/**
		* деструктор
		*/
		public function __destruct()
		{
			$this->Close();
		}			
	}