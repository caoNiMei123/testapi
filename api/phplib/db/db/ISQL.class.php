<?php
/**
* brief of ISQL.class.php:
*
* interface of SQL generator
*
*/


interface ISQL
{
    // return SQL text or false on error
    public function getSQL();
}
?>
