<?php

namespace Custom\Core;

require_once __DIR__ . '/excel/PHPExcel.php';
require_once __DIR__ . '/excel/PHPExcel/Writer/Excel2007.php';
require_once __DIR__ . '/excel/PHPExcel/Writer/Excel5.php';
require_once __DIR__ . '/excel/PHPExcel/IOFactory.php';

class ExportExcel {
    private $fileName;
    private $filePath;
    private $borderStyle =  array(
        'borders'=>array(
            'allborders' => array(
                'style' => \PHPExcel_Style_Border::BORDER_THIN,
                'color' => array('rgb' => '000000')
            )
        )
    );

    private $statusStyleName = [
        "Возврат согласован" => 'blue',
        "Принят" => 'yellow',
        "В работе" => 'blue',
        "Запрос информации" => 'yellow',
        "Работы приостановлены" => 'yellow',
        "Выполнен" => 'green',
        "Оплачен" => 'green',
        "Отменён" => 'red',
        "Черновик" => 'gray'
    ];
	
	private $boolStyleName = [
		"Да" => 'green',
		"Нет" => 'gray'
	];
	
    private $statusStyle = [
        'red' => ['color' => 'b80022', 'background' => 'ffaab9'],
        'yellow' => ['color' => 'c97200', 'background' =>'fee697'],
        'green' => ['color' => '0a9100', 'background' => 'b0e998'],
        'gray' => ['color' => '888888', 'background' => 'dddddd'],
        'blue' => ['color' => '0086c2', 'background' => 'b8e9ff']
    ];
    public function setFileName($name){
        $this->fileName = $name;
        return $this;
    }

    public function setFilePath($path){
        $this->filePath = $path;
        return $this;
    }

    public function getFileName(){
        return $this->fileName;
    }

    public function getFilePath(){
        return $this->filePath;
    }

    public function openFile(){
        $path = $this->filePath;
        $fileName = $this->fileName;
        if($path && $fileName)
            return \PHPExcel_IOFactory::load($path . '/' . $fileName);
        else
            return null;

    }

    public function saveFile($file){

        $path = $this->filePath;
        $fileName = $this->fileName;

        if($path && $fileName){
            $objWriter = new \PHPExcel_Writer_Excel2007($file);
            $objWriter->save($path . '/' . $fileName);
            return true;
        }

        return false;
    }

    public function createFile(){
        return new \PHPExcel();
    }

    public function getColumnLetter($num){
        $num -= 1;
        for ($r = ""; $num >= 0; $num = intval($num / 26) - 1)
            $r = chr($num % 26 + 0x41) . $r;
        return $r;
    }

    public function getBorderStyle(){
        return $this->borderStyle;
    }

    public function getStatusStyle($statusName = false){
        if(!$statusName) return $this->statusStyle['blue'];
        if(!key_exists($statusName,$this->statusStyleName))
            return $this->statusStyle['blue'];
        return $this->statusStyle[$this->statusStyleName[$statusName]];
    }
	
	public function getBoolStyle($yes = false){
		if(!$yes) return $this->statusStyle['gray'];
		if(!key_exists($yes, $this->boolStyleName))
			return $this->statusStyle['gray'];
		return $this->statusStyle[$this->boolStyleName[$yes]];
	}
	
    public function downloadFile($xls){
        global $APPLICATION;
        $APPLICATION->RestartBuffer();

        header("Expires: Mon, 1 Apr 1974 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF-8');
        header("Content-Disposition: attachment; filename=" . $this->fileName);
        $objWriter = new \PHPExcel_Writer_Excel2007($xls);
        $objWriter->save('php://output');
        die;
    }
}