<?php
/*
 * DB Class
 * This class is used for database related (connect, insert, update, and delete) operations
 */
class DatabaseClass
{
	public function __construct(){
        if(!isset($this->db)){
            // Connect to the database
            try{
                $connection = new PDO(
					'mysql:host='.DBHOSTNAME.';
					dbname='.DBNAME,
					DBUSERNAME,
					DBPASSWORD
				);
                $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->db = $connection;
            } catch(PDOException $e) {
                die('Failed to connect with MySQL: '.$e->getMessage());
            }
        }
    }
    
    /*
     * Returns rows from the database based on the conditions
     * @param string name of the table
     * @param array select, where, order_by, limit and return_type conditions
     */
    public function getRows($table, $conditions = array())
	{
        $sqlQuery = 'SELECT ';
        $sqlQuery .= array_key_exists('select' ,$conditions) ? $conditions['select']: '*';
        $sqlQuery .= ' FROM '.$table;
        if (array_key_exists('where', $conditions)) {
            $sqlQuery .= ' WHERE ';
            $index = 0;
            foreach($conditions['where'] as $key => $value){
                $pre = ($index > 0) ? ' AND ':'';
                $sqlQuery .= $pre.$key." = '".$value."'";
                $index++;
            }
        }
        
        if (array_key_exists('order_by', $conditions)) {
            $sqlQuery .= ' ORDER BY '.$conditions['order_by']; 
        }
        
        if (array_key_exists('start', $conditions) && array_key_exists('limit', $conditions)) {
            $sqlQuery .= ' LIMIT '.$conditions['start'].','.$conditions['limit']; 
        } elseif (!array_key_exists('start', $conditions) && array_key_exists('limit', $conditions)) {
            $sqlQuery .= ' LIMIT '.$conditions['limit']; 
        }
        
        $query = $this->db->prepare($sqlQuery);
        $query->execute();
        
        if (array_key_exists('return_type', $conditions) && $conditions['return_type'] != 'all') {
            switch ($conditions['return_type']) {
                case 'count':
                    $data = $query->rowCount();
                    break;
                case 'single':
                    $data = $query->fetch(PDO::FETCH_ASSOC);
                    break;
                default:
                    $data = '';
            }
        } else {
            if ($query->rowCount() > 0){
                $data = $query->fetchAll();
            }
        }
        return !empty($data) ? $data:false;
    }
    
    /*
     * Insert data into the database
     * @param string name of the table
     * @param array the data for inserting into the table
     */
    public function insert($table, $data)
	{
        if (!empty($data) && is_array($data)) {
            $columns = '';
            $values  = '';
            $index = 0;
            if (!array_key_exists('created_on', $data)) {
                $data['created_on'] = date('Y-m-d H:i:s');
            }

            $columnString = implode(',', array_keys($data));
            $valueString = ':'.implode(',:', array_keys($data));
            $insertSql = 'INSERT INTO '.$table.' ('.$columnString.') VALUES ('.$valueString.')';
            $query = $this->db->prepare($insertSql);
            foreach ($data as $key=>$val) {
                 $query->bindValue(':'.$key, $val);
            }
            $insert = $query->execute();

            return $insert?$this->db->lastInsertId():false;
        } else {
            return false;
        }
    }
    
    /*
     * Update data into the database
     * @param string name of the table
     * @param array the data for updating into the table
     * @param array where condition on updating data
     */
    public function update($table, $data, $conditions)
	{
        if (!empty($data) && is_array($data)) {
            $colvalSet = '';
            $whereSql = '';
            $index = 0;

            foreach ($data as $key => $val) {
                $pre = ($index > 0) ? ', ':'';
                $colvalSet .= $pre.$key."='".$val."'";
                $index++;
            }
            if (!empty($conditions) && is_array($conditions)) {
                $whereSql .= ' WHERE ';
                $index = 0;
                foreach ($conditions as $key => $value) {
                    $pre = ($index > 0) ? ' AND ':'';
                    $whereSql .= $pre.$key." = '".$value."'";
                    $index++;
                }
            }
            $sql = "UPDATE ".$table." SET ".$colvalSet.$whereSql;
            $query = $this->db->prepare($sql);
            $update = $query->execute();

            return $update?$query->rowCount():false;
        } else {
            return false;
        }
    }   
}