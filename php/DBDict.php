<?php

/**
 * DBDict: 数据库字典类
 *
 * Author : Lv Xiang
 * Website: http://www.okuer.com
 *
 * Usage:
 *      $config=['host'=>'127.0.0.1','port'=>'3306','user'=>'root','pass'=>'','name'=>''];
 *      $db_manager=DBDict::getInstance($config);
 *      $db_manager->exportDB();
 */
class DBDict{

    protected static $db_manager=null;

    protected $db_handle=null;
    private $db_host=null;
    private $db_port=null;
    private $db_user=null;
    private $db_pass=null;
    private $db_name=null;


    /**
     * Init
     *
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $pass
     * @param string $name
     */
    protected function __construct($host='127.0.0.1',$port=3306,$user='root',$pass='',$name=''){

        //Init DB Settings
        $this->db_host=$host;
        $this->db_port=$port;
        $this->db_user=$user;
        $this->db_pass=$pass;
        $this->db_name=$name;
    }

    /**
     * 获取类的实例
     *
     * @param $options
     * @return DBDict|null
     */
    public static function getInstance($options){

        if(!self::$db_manager){

            self::$db_manager=new DBDict($options['host'],$options['port'],$options['user'],$options['pass'],$options['name']);
        }
        return self::$db_manager;
    }

    /**
     * 获取数据库句柄
     *
     * @return null|PDO
     */
    public function getDBHandle(){

        if(!$this->db_handle){
            try{
                $dsn='mysql:host='.$this->db_host.';dbname='.$this->db_name;
                $this->db_handle=new \PDO($dsn,$this->db_user,$this->db_pass);
                $this->db_handle->query('SET NAMES UTF8');
            }catch (Exception $e){
                die("Error >> ".$e->getMessage().PHP_EOL);
            }
        }
        return $this->db_handle;
    }

    /**
     * 导出数据库表信息
     *
     * @param string $format
     * @return void
     */
    public function exportDB($format='html'){

        if($format=='html') {
            $this->exportDBUseHtml();
        }
    }

    /**
     * 导出html文件
     *
     * @return void
     */
    protected function exportDBUseHtml(){

        $tables=[];
        $database=[];

        $structure=$this->getStructure();
        foreach($structure as $table){
            $tables[]=$this->getTableInfo($table);
        }
        $database['_db_name']=$this->getDbName();
        $database['tables']=$tables;

        $this->render(['database'=>$database]);

        return ;
    }

    /**
     * 获取数据库表结构
     *
     * @return array
     */
    protected function getStructure(){

        $key='Tables_in_virtual-station';
        $sql='show tables;';

        $db_handle=$this->getDBHandle();
        $structure=[];
        $res=$db_handle->query($sql);

        foreach($res as $row){
            $structure[]=$row[$key];
        }

        return $structure;
    }

    /**
     * 获取列信息
     *
     * @param $table_name
     * @return array
     */
    protected function getTableInfo($table_name){

        $column_info=[];
        $table_info=[];

        $db_handle=$this->getDBHandle();
        $sql='SELECT * FROM INFORMATION_SCHEMA.COLUMNS  WHERE table_name= \''.$table_name.'\'';
        $sql.=' AND table_schema = \''.$this->getDbName().'\'';
        $table_sql='SELECT * FROM INFORMATION_SCHEMA.TABLES  WHERE table_name= \''.$table_name.'\'';
        $table_sql.=' AND table_schema = \''.$this->getDbName().'\'';
        $res=$db_handle->query($sql);
        $tab_res=$db_handle->query($table_sql);

        foreach($res as $row){
            $column_info[]=$row;
        }
        foreach($tab_res as $tab_row){
            $table_info[]=$tab_row;
        }
        return array($column_info,$table_info);
    }

    /**
     * 渲染
     *
     * @param $assign_vars
     */
    protected function render($assign_vars){

        // 期望的变量
        header('Content-Type:text/html;charset=utf-8');
        $db_name=$assign_vars['database']['_db_name'];
        $html = '';
        foreach ($assign_vars['database']['tables'] as $k => $v ){

            $col=$v[0];
            $tab=$v[1][0];
            $html .= '<table  border="1" cellspacing="0" cellpadding="0" align="center">';
            $html .= '<caption>' . $tab ['TABLE_NAME'] . ' <span style=\'color:gray\'> ' . $tab ['TABLE_COMMENT'] . ' </span> </caption>';
            $html .= '<tbody><tr><th>字段名</th><th>数据类型</th><th>默认值</th>
            <th>允许非空</th>
            <th>自动递增</th><th>备注</th></tr>';
            $html .= '';
            foreach ( $col as $f ) {
                $html .= '<tr><td class="c1">' . $f ['COLUMN_NAME'] . '</td>';
                $html .= '<td class="c2">' . $f ['COLUMN_TYPE'] . '</td>';
                $html .= '<td class="c3">&nbsp;' . $f ['COLUMN_DEFAULT'] . '</td>';
                $html .= '<td class="c4">&nbsp;' . $f ['IS_NULLABLE'] . '</td>';
                $html .= '<td class="c5">' . ($f ['EXTRA'] == 'auto_increment' ? '是' : '&nbsp;') . '</td>';
                $html .= '<td class="c6">&nbsp;' . $f ['COLUMN_COMMENT'] . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></p>';
        }
        echo '<html>
                <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <title>' . $db_name . '</title>
                <style>
                body,td,th {font-family:"宋体"; font-size:12px;}
                table{border-collapse:collapse;border:1px solid #CCC;background:#6089D4;}
                table caption{text-align:left; background-color:#fff; line-height:2em; font-size:14px; font-weight:bold; }
                table th{text-align:left; font-weight:bold;height:26px; line-height:25px; font-size:16px; border:3px solid #fff; color:#ffffff; padding:5px;}
                table td{height:25px; font-size:12px; border:3px solid #fff; background-color:#f0f0f0; padding:5px;}
                .c1{ width: 150px;}
                .c2{ width: 130px;}
                .c3{ width: 70px;}
                .c4{ width: 80px;}
                .c5{ width: 80px;}
                .c6{ width: 300px;}
                </style>
                </head>
                <body>';
        echo '<h1 style="text-align:center;">' . $db_name . '</h1>';
        echo $html;
        echo '</body></html>';


    }

    /**
     * @return null|string
     */
    public function getDbName()
    {
        return $this->db_name;
    }


    /**
     * 打印demo
     *
     * @return void
     */
    public static function printDemo(){

        $config=['host'=>'127.0.0.1','port'=>'3306','user'=>'root','pass'=>'aa','name'=>'virtual-station'];
        $db_manager=DBDict::getInstance($config);
        $db_manager->exportDB();

    }



}
