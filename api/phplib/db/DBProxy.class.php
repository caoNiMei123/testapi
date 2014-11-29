<?php
/**
 * @file DBProxy.class.php
 * @brief The proxy to mysql bulid on DB lib
 *
 */

require_once( dirname( __FILE__ ) . '/db/DBMan.class.php' );

class DBProxy
{
    /**
     * @var DB
	 */
	protected $dbHandle = NULL;


    protected $host = NULL;

    protected $uname = NULL;

    protected $password = NULL;

    protected $dbname = NULL;
	
	/**
	 * @var DBProxy
	 */
	private static $instance = NULL;
	
	/**
	 * @return DBProxy
	 */
	public static function getInstance()
	{
		if ( null === self::$instance ) {
			$clz = __CLASS__;
			self::$instance = new $clz;
		}
		return self::$instance;
	}

	/**
	 * prevent external new()
	 */
	private function __construct()
	{
	}

    public function setDB( $dbConf )
    {
        $this->host     = $dbConf['host'];
        $this->uname    = $dbConf['uname'];
        $this->password = $dbConf['password'];
        $this->dbname   = $dbConf['dbname'];
        if ( isset( $dbConf['charset'] ) ) {
            $this->charset = $dbConf['charset'];
        } else {
            $this->charset = 'utf8';
        }

        if ( null === $this->dbHandle ) {
            $rs = new RandSelector();
            $sm = new StatusManFile('tmp/' . $this->dbname );

            $man = new DBMan( 
                $this->host, 
                array( 'uname'=> $this->uname, 'passwd'=> $this->password ),
                $this->dbname, $rs, $sm );
			$newDBHandle = $man->getDB();
			if ( empty( $newDBHandle ) ) {
				return false;
			} else {
                $newDBHandle->charset( $this->charset );
			    $this->dbHandle = $newDBHandle;
			}
        }
        
		return self::$instance;
    }
	
	/**
	 * @return true if success
	 */
	protected function getDB() 
	{
		if ( null === $this->dbHandle ) {
            return false;
        } else {
            return $this->dbHandle;
        }
	}
    
	// return empty array() if nothing found
	public function select(
		$table, 
		$columns = '*', 
		$conditions = NULL,
		$appendConditions = array( 'start' => 0, 'limit' => '1', 'order_by' => null ),
		$selectOptions = NULL 
	)
	{
	    if ( false === $this->getDB() ) {
	        return false;
	    }
	    if ( is_array( $columns ) && empty( $columns ) ) {
	    	$columns = '*';
	    }
	    
	    // parameters
	    if ( !isset( $appendConditions['start'] ) ) {
	        $appendConditions['start'] = 0;
	    }
	    if ( !isset( $appendConditions['limit'] ) ) {
	        $appendConditions['limit'] = 1;
	    }

        if ( is_array( $conditions ) && !empty( $conditions ) ) {
	        $selectConditions = $this->_convertConditions( $conditions );
        } else {
            $selectConditions = NULL; 
        }

	    $appendConditionsSql = '';
	    
	    // order by
		$orderByArr = array();
		if ( isset( $appendConditions['order_by'] ) ) {
			foreach ( $appendConditions['order_by'] as $key => $val ) {
				$orderByArr[] = $key . ' ' . $val;	
			}
		}
		if ( count( $orderByArr ) > 0 ) {
			$appendConditionsSql .= ' ORDER BY ' . implode( ',', $orderByArr );
		}
		
		// limit
		$limitArr = array();
		$start = 0;
		if ( is_numeric( $appendConditions['start'] )
		    && $appendConditions['start'] > 0
		) {
		    $start = $appendConditions['start'];
		}
		$limit = 1;
		if ( is_numeric( $appendConditions['limit'] )
		    && $appendConditions['limit'] > 1
		) {
		    $limit = $appendConditions['limit'];
		}
		$appendConditionsSql .= ' LIMIT ' . $start . ',' . $limit;
		
		// real select
		$result = $this->dbHandle->select( 
					$table, 
					$columns, 
					$selectConditions, 
					$selectOptions,
					$appendConditionsSql 
		);
		
		return $result;
	}

    public function selectPage(
		$table, 
		$columns = '*', 
		$conditions = NULL,
		$appendConditions = array( 'start' => 0, 'limit' => '1', 'order_by' => null ),
		$selectOptions = NULL 
	)
	{
	    if ( false === $this->getDB() ) {
	        return false;
	    }
	    
	    // parameters
	    if ( !isset( $appendConditions['start'] ) ) {
	        $appendConditions['start'] = 0;
	    }
	    if ( !isset( $appendConditions['limit'] ) ) {
	        $appendConditions['limit'] = 1;
	    }

        if ( is_array( $conditions ) && !empty( $conditions ) ) {
	        $selectConditions = $this->_convertConditions( $conditions );
        } else {
            $selectConditions = NULL; 
        }

	    
	    $appendConditionsSql = '';
	    // order by
		$orderByArr = array();
        $orderBySql = NULL;
		if ( isset( $appendConditions['order_by'] ) ) {
			foreach ( $appendConditions['order_by'] as $key => $val ) {
				$orderByArr[] = $key . ' ' . $val;	
			}
		}
		if ( count( $orderByArr ) > 0 ) {
			$appendConditionsSql .= ' ORDER BY ' . implode( ',', $orderByArr );
            // 此处将分页查询的结果排序去掉了，理论上同一张表在子查询里面的orderby与limit已经能保证结果的排序
			//$orderBySql = ' ORDER BY ' . implode( ',', $orderByArr );
		}
		
		// limit
		$limitArr = array();
		$start = 0;
		if ( is_numeric( $appendConditions['start'] )
		    && $appendConditions['start'] > 0
		) {
		    $start = $appendConditions['start'];
		}
		$limit = 1;
		if ( is_numeric( $appendConditions['limit'] )
		    && $appendConditions['limit'] > 1
		) {
		    $limit = $appendConditions['limit'];
		}
		$appendConditionsSql .= ' LIMIT ' . $start . ',' . $limit;
		
		// real select
		$result = $this->dbHandle->selectPage( 
					$table, 
					$columns, 
					$selectConditions, 
					$selectOptions,
					$appendConditionsSql,
                    $orderBySql
		);
		
		return $result;
	}

	// added by zl
	// 行锁
	public function selectForUpdate($table, 
									$columns = '*', 
									$conditions = NULL,
									$appendConditions = array( 'start' => 0, 'limit' => '1', 'order_by' => null ))
	{
	    if ( false === $this->getDB() )
	    {
	        return false;
	    }
	    
	    if (is_array($columns) && empty($columns))
	    {
	    	$columns = '*';
	    }

	 	// parameters
	    if ( !isset( $appendConditions['start'] ) ) {
	        $appendConditions['start'] = 0;
	    }
	    if ( !isset( $appendConditions['limit'] ) ) {
	        $appendConditions['limit'] = 1;
	    }
	    
        if (is_array($conditions) && !empty($conditions))
        {
	        $selectConditions = $this->_convertConditions($conditions);
        }
        else
        {
            $selectConditions = NULL; 
        }
		
        $appendConditionsSql = '';
        
		// order by
		$orderByArr = array();
		if ( isset( $appendConditions['order_by'] ) ) {
			foreach ( $appendConditions['order_by'] as $key => $val ) {
				$orderByArr[] = $key . ' ' . $val;	
			}
		}
		if ( count( $orderByArr ) > 0 ) {
			$appendConditionsSql .= ' ORDER BY ' . implode( ',', $orderByArr );
		}
		
		// limit
		$limitArr = array();
		$start = 0;
		if ( is_numeric( $appendConditions['start'] )
		    && $appendConditions['start'] > 0
		) {
		    $start = $appendConditions['start'];
		}
		$limit = 1;
		if ( is_numeric( $appendConditions['limit'] )
		    && $appendConditions['limit'] > 1
		) {
		    $limit = $appendConditions['limit'];
		}
		$appendConditionsSql .= ' LIMIT ' . $start . ',' . $limit;
		
		// real select
		$result = $this->dbHandle->selectForUpdate($table, 
												   $columns, 
												   $selectConditions,
												   $appendConditionsSql);
		
		return $result;
	}
	// added by zl

	public function count(
		$table, $field = '*', $conditions = NULL, 
		$appendConditions = NULL,
		$selectOptions = NULL 
	)
	{
		if ( false === $this->getDB() ) {
		    return false;
		}
		
		$selectConditions = $this->_convertConditions( $conditions );
		if ( is_array( $selectConditions ) && count( $selectConditions ) == 0 ) {
		    trigger_error( "[warning][FIXME]empty condition. use NULL instead." );
		}
		$result = $this->dbHandle->selectCount( $table, $field, $selectConditions, $selectOptions, $appendConditions );
		return $result;
	}

	// return 1 if new record is inserted. 2 if old record is updated.
	// 0 if old record found but not updated. false if failed
    /*
    example:
    $dbProxy->insert(
        't_test',
        array(
            'field1' => 1,  // unique
            'field2' => 2,
            'field3' => 3,
        ),
        array(
            'filed2' => 2
        )
    );
    sql:  "INSERT t_test SET field1=1, field2=2, field=3 ON DUPLICATE KEY UPDATE field2=2"
    */
    public function insert( 
        $table, $row = array(), 
        $onDuplicateKeyUpdate = NULL, $insertOptions = NULL  
    )
    {
        if ( false === $this->getDB() ) {
            return false;
        }
        
        $result = $this->dbHandle->insert( $table, $row, $insertOptions, $onDuplicateKeyUpdate );
        return $result;
    }

	public function insertValues( 
		$table, 
		$columns = array(), 
		$values = array(),
		$onDuplicateKeyUpdate = NULL, $insertOptions = NULL 
	)
	{
		if ( false === $this->getDB() ) {
		    return false;
		}
		
		$result = $this->dbHandle->insertValues( $table, $columns, $values, $insertOptions, $onDuplicateKeyUpdate );

		return $result;
	}

	// return affected line count if success.
	// return false if failed
	public function update(
		$table, 
		$conditions,
		$row, 
		$appendConditions = NULL,
		$updateOptions = NULL
	)
	{
		if ( false === $this->getDB() ) {
		    return false;
		}
		
		$updateConditions = $this->_convertConditions( $conditions );
		$result = $this->dbHandle->update( $table, $row, $updateConditions, $updateOptions, $appendConditions );

		return $result;
	}

	public function delete( 
		$table, 
		$conditions, 
		$conditionAppends = NULL,
		$deleteOptions = NULL
	)
	{
		if ( false === $this->getDB() ) {
		    return false;
		}
		
		$deleteConditions = $this->_convertConditions( $conditions );
		$result = $this->dbHandle->delete( $table, $deleteConditions, $deleteOptions, $conditionAppends );

		return $result;
	}

	public function startTransaction()
	{
		if ( false === $this->getDB() ) {
		    return false;
		}
		
		$result = $this->dbHandle->startTransaction();

		return $result;
	}

	public function setAutoCommit( $toAuto = true )
	{
		if ( false === $this->getDB() ) {
		    return false;
		}
		
		$result = $this->dbHandle->autoCommit( $toAuto );

		return $result;
	}

	public function commit()
	{
		if ( false === $this->getDB() ) {
		    return false;
		}
		
		$result = $this->dbHandle->commit();

		return $result;
	}

	public function rollback()
	{
		if ( false === $this->getDB() ) {
		    return false;
		}
		
		$result = $this->dbHandle->rollback();

		return $result;
	}
	
	public function getErrorCode()
	{
	    if ( empty( $this->dbHandle ) ) {
	        return -1;
	    } else {
		    return $this->dbHandle->errno();
	    } 
	}

	public function getErrorMsg()
	{
	    if ( empty( $this->dbHandle ) ) {
	        return 'does not connect to mysql';
	    } else {
	       $dbError = $this->dbHandle->error();
           if ( $dbError === '' ) {
               return 'success';
           } else {
               return $dbError;
           }
	    }
	}

	public function getLastSQL()
	{
	    if ( empty( $this->dbHandle ) ) {
	        return 'unkown';
	    } else {
	        return $this->dbHandle->getLastSQL();
	    }
	}
    public function getAffectedRows()
	{
	    if ( empty( $this->dbHandle ) ) {
	        return 'unkown';
	    } else {
	        return $this->dbHandle->getAffectedRows();
	    }
	}

	public function close()
	{
	    if ( empty( $this->dbHandle ) ) {
	        $this->dbHandle->close();
	    }
	}

	private function _convertConditions ( $conditions )
	{
		if ( is_null ( $conditions ) ) {
			return NULL;
		}
		$andConvertConditions = array();
		$andConditions = array();

		$orConditions = array();
		$orConvertConditions = array();

		if ( isset( $conditions['and'] ) ) {
			$andConditions = $conditions['and'];
		}
		if ( isset( $conditions['or'] ) ) {
			$orConditions = $conditions['or'];
		}

		/* and conditions */
		foreach ( $andConditions as $andCondition ) {
		    if ( isset( $andCondition['and'] ) || isset( $andCondition['or'] ) ) {
		        $andConvertConditions[] = '(' . implode( ') AND (', $this->_convertConditions( $andCondition ) ) . ')';
		    } else {
		        foreach ( $andCondition as $conditionKey => $conditionVal ) {
		            foreach ( $conditionVal as $key => $val ) {
		                if ( is_numeric( $val ) ) {
		                    $andConvertConditions[] = $conditionKey . ' ' . $key . ' ' . $val;
		                } elseif ( is_array( $val ) ) {
                          
                            if ( $key === 'against' ) {
                                if ( count($val) === 2 ) {
                                  if ( $val[1] == 'WITH QUERY EXPANSION' || $val[1] === 'IN BOOLEAN MODE' ) {
                                  	$andConvertConditions[] = $conditionKey . ' ' . $key . ' (\'' . $val[0] . '\' ' . $val[1] . ')';       
                                  } else {
                                    $andConvertConditions[] = $conditionKey . ' ' . $key . ' (\'' . $val[0] . '\')';       
                                  }
                                } else {
                                	$andConvertConditions[] = $conditionKey . ' ' . $key . ' (\'' . implode( '\', \'', $val ) . '\')'; 
                                }
                                
                            } else {                            	
		                    	$andConvertConditions[] = $conditionKey . ' ' . $key . ' (\'' . implode( '\', \'', $val ) . '\')';
                            }
		                } else {	                	

		                    $andConvertConditions[] = $conditionKey . ' ' . $key . ' \'' . $val . '\'';
		                }
		            }
		        }
		    }
		}

		/* or conditions */
		foreach( $orConditions as $orCondition ) {
		    if ( isset( $orCondition['and'] ) || isset( $orCondition['or'] ) ) {
		        // yes, this is 'AND' ----------------------+
		        //                                         \|/
		        //                                          '
		        $orConvertConditions[] = '(' . implode( ') AND (', $this->_convertConditions( $orCondition ) ) . ')';
		    } else {
		        foreach ( $orCondition as $conditionKey => $conditionVal ) {
		            foreach ( $conditionVal as $key => $val ) {
		                if ( is_numeric( $val ) ) {
		                    $orConvertConditions[] = $conditionKey . ' ' . $key . ' ' . $val;
		                } elseif ( is_array( $val ) ) {
		                    $orConvertConditions[] = $conditionKey . ' ' . $key . ' (\'' . implode( '\', \'', $val ) . '\')';
		                } else {
		                    $orConvertConditions[] = $conditionKey . ' ' . $key . ' \'' . $val . '\'';
		                }
		            }
		        }
		    }
		}
		if ( count( $orConvertConditions) > 0 ) {
		    $andConvertConditions[] = '(' . implode( ') OR (', $orConvertConditions ) . ')';
		}
		if ( empty($andConvertConditions) ) {
		    return NULL;
		}
		return $andConvertConditions;
	}
		
}
