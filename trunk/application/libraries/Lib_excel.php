<?php
/**
 * 数据导出xls格式下载
 *
 * @version: $Id: JExcel.php 617 2011-06-12 15:19:59Z peilong $
 *
 * demo
 * $excel = new JExcel();
 * $excel->createRow(array('姓名', '年龄', '职业'));
 * $excel->createRow(array('李四', '26', '程序员'));
 * $excel->createRow(array('张三', '24', '销售'));
 * $excel->download();
 */
class Lib_excel
{
	private $_data = '';
	private $_char;

	function __construct()
	{
		$this->_char = 'UTF-8';
	}

	/**
	 * 写一行
	 */
	public function createRow($row)
	{
		if(is_array($row)) {
			foreach ($row as $k => $v) {
				//$row[$k] = str_replace(array("\t", "\n", '\t'), '', $v);
				$row[$k] = "\"$v\"";
			}
			$data = implode(",", $row);
		} else {
			$data = $row;
		}

		if ($this->_char != 'GBK') {
                $data = @iconv($this->_char, 'GBK//IGNORE', $data);
		}
		$this->_data .= "{$data}\r\n";
	}

	/**
	 * 设置字符编码
	 */
	public function setChar($char)
	{
		$this->_char = strtoupper($char);
	}

	/**
	 * 创建一个空行
	 */
	public function createBlank()
	{
		$this->_data .= "\r\n";
	}

	/**
	 * 下载
	 */
	public function download($fileName = '')
	{
		$fileName = $fileName ? $fileName : date('ymd-Hi');
		header("Content-type:application/vnd.ms-excel; charset=utf-8");
		header("Content-Disposition:attachment;filename={$fileName}.csv");
		echo $this->_data;
		exit;
	}
    
    /* 导出excel函数*/
    public function download_excel($data,$name='')
    {
        error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING); 
        $ci = & get_instance();
        
        $ci->load->library('PHPExcel');
        $ci->load->library('PHPExcel/IOFactory');
    
        $objPHPExcel = new PHPExcel();
        //
        /*以下是一些设置 ，什么作者  标题啊之类的*/
        $objPHPExcel->getProperties()
            ->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
         /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/
        foreach($data as $k => $v)
        {
             $num=$k+1;
             $sheet = $objPHPExcel->setActiveSheetIndex(0);
             foreach($v as $kk=>$vv)
             {
                 $pos = chr(65+$kk);
                 $sheet->setCellValueExplicit($pos.$num, $vv);
             }
        }
        //$objPHPExcel->getActiveSheet()->setTitle('User');
        $objPHPExcel->setActiveSheetIndex(0);
        ob_end_clean();
        header('Content-Type: applicationnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$name.'.xls"');
        header('Cache-Control: max-age=0');
        
        $objWriter = IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
      }
    
    /**
     * 保存excel文件
     */ 
    public function save_excel($data,$name='')
    {
        error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING); 
        $objWriter = $this->_get_writer_obj($data);
        if(stripos($name,'xls') === FALSE)
        {
            $name.='.xls';
        }
        $objWriter->save($name);
        return true;
    }
    
    /**
     * 处理excel数据
     */ 
     private function _get_writer_obj($data)
     {
        $ci = & get_instance();
        
        $ci->load->library('PHPExcel');
        $ci->load->library('PHPExcel/IOFactory');
    
        $objPHPExcel = new PHPExcel();
        //
        /*以下是一些设置 ，什么作者  标题啊之类的*/
        $objPHPExcel->getProperties()
            ->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
         /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/
        foreach($data as $k => $v)
        {
             $num=$k+1;
             $sheet = $objPHPExcel->setActiveSheetIndex(0);
             foreach($v as $kk=>$vv)
             {
                 $pos = chr(65+$kk);
                 $sheet->setCellValue($pos.$num, $vv);
             }
        }
        //$objPHPExcel->getActiveSheet()->setTitle('User');
        $objPHPExcel->setActiveSheetIndex(0);
        
        $objWriter = IOFactory::createWriter($objPHPExcel, 'Excel5');
        
        return $objWriter;
     }
    
    /**
	 * 返回数据
	 */
    public function getData ()
    {
        return $this->_data;
    }
}