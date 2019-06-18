<?php

class DBdriver{

    private static $_instance = null;

    private $_host               = "localhost",
            $_username           = "username",
            $_password           = "password",
            $_databaseName       = "databaseName",
            $_results 	         = null,
            $_count              = null,
            $_query              = null,
            $_databaseConnection = false,
            $_errorMessage       = "The data base is not available"; 

    public  $_lastQuery      = null,
            $_affectedRows   = 0,
            $_insertKeys     = array(),
            $_insertValues   = array(),
            $_updateSets     = array(),
            $_lastInsertedId = null,
            $_flagError      = false; 

    private function __construct(){
        try{
            $this->_databaseConnection = new PDO('mysql:host='.$this->_host.';dbname='.$this->_databaseName,
												  $this->_username,$this->_password);
        }catch(PDOException $e){
            log_exception($e->getMessage());
            die($this->_errorMessage);
        }
    }

    public static function GetInstance(){
        if(!isset(self::$_instance)){
            self::$_instance = new DBdriver();
        }
        return self::$_instance;
    } 

    public function Close(){
        $this->_query->CloseCursor();
    } 

    public function Escape($value){
        return htmlentities($value,ENT_QUOTES,"UTF-8");
    } 

    public function GeneralQuery($sql,$params = array()){
        $this->_flagError = false;
        if($this->_query = $this->_databaseConnection->prepare($sql)){
            $x = 1;
            if(count($params)){
                foreach($params as $param){
                    $this->_query->bindValue($x,$param);
                    $x++;
                }
            }
            if($this->_query->execute()){
                $this->_results = $this->_query->fetchAll(PDO::FETCH_OBJ);
                $this->_count   = $this->_query->rowCount();
                $this->Close();
            }else{
				$this->_flagError = true;
                log_exception($this->_query->errorInfo());
                die($this->_errorMessage);
            }
        }
        return $this;
    } 

    public function Insert($table){
        if(!empty($table) && !empty($this->_insertKeys) && !empty($this->_insertValues)){
            $values = "";
            $i = 1;
            foreach ($this->_insertValues as $field){
                $values .= "?";
                if($i < count( $this->_insertValues)){
                    $values .= ",";
                }
                $i++;
            }
            $sql = "INSERT INTO {$table}(`".implode('`, `', $this->_insertKeys)."`) VALUES({$values})";
            if(!$this->GeneralQuery($sql, $this->_insertValues)->_flagError){
                //last inser id
                $this->_lastInsertedId = $this->_databaseConnection->lastInsertId();
                $this->_insertKeys   = array();
                $this->_insertValues = array();
                return true;
            }
            return false;
        }
        return false;
    }

    public function Update($table = null, $id = null){
        if(!empty($table) && !empty($id) && !empty($this->_insertKeys) && !empty($this->_insertValues)){
            $values = "";
            $i = 1;
            foreach ($this->_insertKeys as $field){
                $values .= "{$field} = ?";
                if($i < count($this->_insertKeys)){
                    $values .=", ";
                }
                $i++;
            }
            $sql = "UPDATE `{$table}` SET ";
            $sql .= $values;
            $sql .= "WHERE id = ".$this->IntegerFilter($id)."";
            return !$this->GeneralQuery($sql, $this->_InsertValues)->_flagError ? true: false;
        }
    }

	public function Delete_($table, $where){
        return $this->Action("DELETE", $table, $where);
    }
	
    // allows to perform specific operation using query method
    public function Action($action, $table, $where = array()){
        if(count($where) === 3){
            $operators = array('=','>','<','>=','<=');
            $field     = $where[0];
            $operator  = $where[1];
            $value     = $where[2];
            if(in_array($operator, $operators)){
                $sql = "{$action} FROM {$table} WHERE {$field} {$operator} ?";
                if(!$this->GeneralQuery($sql, array($value))->_flagError){
                    return $this;
                }
            }
        }
        return false;
    }
	
	public function PrepareInsert($array = null){
        if(!empty($array)){
            foreach($array as $key => $value){
                $this->_insertKeys[] = $key;
                $this->_insertValues[] = $this->Escape($value);
            }
        }
    } 

	private function IntegerFilter($var){
		$var = preg_replace('/[^0-9]/','',$var);
			$var = $var != ''?$var:null;
		return $var;
	}
	
    public function GetResults(){
        if($this->count_() != 0)
            return $this->_results;
        else
            return null;
    }

    public function GetFirst(){
        if($this->count_() != 0){
            return $this->GetResults()[0];
        }else{
            return null;
        }
    }

    public function GetCount_(){
        return $this->_count;
    }

    public function GetLastId(){
        return $this->_lastInsertedId;
    }

}
?>
