<?php
require_once 'reader.php';

class ExcelDumper
{
    public $excel = null;

    public $outName = '';
    public $startRow = 0;
    public $startCol = 0;
    public $endRow = 0;
    public $endCol = 0;
    public $deepRow = 1;
    public $deepCol = 1;

    public $rowTitle = array();
    public $colTitle = array();

    public $pCells = array();  /*con trỏ đến data của từng cell*/
    public $pCellsInfo = array();  /*con trỏ đên thông tin của từng cell*/
    public $data = array();  /*data lấy được từ excel*/

    public function init($excelFileName)
    {
        if (empty($excelFileName)) return false;
        $this->excel = new Spreadsheet_Excel_Reader();
        $this->excel->setOutputEncoding('UTF8');
        $this->excel->read($excelFileName);

        return !empty($this->excel);
    }

    public function getAllSheetInfo()
    {
        $allSheets = array();
        foreach ($this->excel->boundsheets as $index => $sheetInfo) {
            $allSheets[$index] = $sheetInfo['name'];
        }
        return $allSheets;
    }

    public function buildConfigOnSheet($sheetId)
    {
        if (!$this->excel->sheets[$sheetId]) return false;
        $this->pCells = &$this->excel->sheets[$sheetId]['cells'];
        $this->pCellsInfo = &$this->excel->sheets[$sheetId]['cellsInfo'];

        if (false === $this->_bulidConfigInfo()) return false;

    }

    public function dump()
    {
        echo '<pre>';
        print_r($this->pCells);
        print_r($this->pCellsInfo);
        echo '<pre>';
    }

    private function _flushDataOnOneSheet()
    {
        $this->excel = null;
        $this->pCells = null;
        $this->pCellsInfo = null;
        $this->data = null;
    }

    private function _bulidConfigInfo()
    {
        $this->outName = $this->pCells[1][2];
        $this->startRow = $this->pCells[2][2];
        $this->startCol = $this->pCells[3][2];
        $this->endRow = $this->pCells[4][2];
        $this->endCol = $this->pCells[5][2];
        $this->deepRow = $this->pCells[6][2];
        $this->deepCol = $this->pCells[7][2];

        return
            !empty($this->outName) &&
            $this->startRow > 0 && $this->startCol > 0 &&
            $this->endRow > 0 && $this->endCol > 0 &&
            $this->deepRow > 0 && $this->deepCol > 0;
    }

    /*data cho tang cuoi cung*/
    public function buildLastDeepData()
    {
        $colTitle = array();
        $rowTitle = array();

        $rowTitleIndex = $this->startRow + $this->deepRow - 1;
        $colTitleIndex = $this->startCol + $this->deepCol - 1;

        for ($row = $rowTitleIndex + 1; $row <= $this->endRow; ++$row) {
            if (isset($this->pCells[$row][$colTitleIndex]))
                $colTitle[$row] = $this->pCells[$row][$colTitleIndex];
        }

        for ($col = $colTitleIndex + 1; $col <= $this->endCol; ++$col) {
            if (isset($this->pCells[$rowTitleIndex][$col]))
                $rowTitle[$col] = $this->pCells[$rowTitleIndex][$col];
        }

        //return array($rowTitle, $colTitle);
        return $this->pCells;
    }

    public function build()
    {
        $listSheet = $this->getAllSheetInfo();
        $data = &$this->excel;
        // foreach sheets
        $object = array();
        foreach ($listSheet as $si => $sn) {
            $posFirst = array(
                $data->sheets[$si]['cells'][3][1],
                $data->sheets[$si]['cells'][3][2],
            );
//	        if (isset($oFile['configSheet'][$si]['beginPos'][0]))
//	            $posFirst = $oFile['configSheet'][$si]['beginPos'];

            $maxdepthRow = 1;
            $maxdepthCol = 1;
//	        if (isset($oFile['configSheet'][$si]['numDepth'][0]))
//	        {
//	            $maxdepthRow = $oFile['configSheet'][$si]['numDepth'][0];
//	            $maxdepthCol = $oFile['configSheet'][$si]['numDepth'][1];
            $maxdepthRow = $data->sheets[$si]['cells'][3][6];
            $maxdepthCol = $data->sheets[$si]['cells'][3][5];

//	        }

            $numRows = $data->sheets[$si]['cells'][3][3];
            $numCols = $data->sheets[$si]['cells'][3][4];

//	        $numRows = $data->sheets[$si]['numRows'];
//	        $numCols = $data->sheets[$si]['numCols'];
//	        if (isset($oFile['configSheet'][$si]['area'][0]))
//	        {
//	            $numRows = $oFile['configSheet'][$si]['area'][0];
//	            $numCols = $oFile['configSheet'][$si]['area'][1];
//	        }


            //$posStart = array($posFirst[1]+$maxdepthRow,$posFirst[0]+$maxdepthCol);
            $posStart = array();
            $posStart[0] = $posFirst[0] + $maxdepthCol;
            $posStart[1] = $posFirst[1] + $maxdepthRow;
            $result = array();

            // standardize sheets
            //standardize rows
            $rS = $posStart[0] + 1;
            $cS = $posStart[1] - $maxdepthRow;

            //echo $rS . "_" . ($numRows). "_" . $cS ."_" . ($cS+$maxdepthRow-2)."<br/>";

            for ($i = $rS; $i <= $numRows; $i++) {
                for ($j = $cS; $j <= $cS + $maxdepthRow - 2; $j++) {
                    if ($data->sheets[$si]['cells'][$i][$j] == null) //if (empty($data->sheets[$si]['cells'][$i][$j]))
                    {
                        $check = true;
                        if ($j > $cS) {
                            if ($data->sheets[$si]['cells'][$i][$j - 1] != $data->sheets[$si]['cells'][$i - 1][$j - 1])
                                $check = false;
                        }

                        if ($check)
                            $data->sheets[$si]['cells'][$i][$j] = $data->sheets[$si]['cells'][$i - 1][$j];
                    }
                }
            }

            //standardize cols
            $rS = $posStart[0] - $maxdepthCol;
            $cS = $posStart[1] + 1;

            //echo $rS . "_ " . ($rS+$maxdepthCol-2). "_" . $cS ."_" . $numCols."<br/>";

            for ($i = $rS; $i <= $rS + $maxdepthCol - 1; $i++) {
                for ($j = $cS; $j <= $numCols; $j++) {
                    if ($data->sheets[$si]['cells'][$i][$j] == null) //if (empty($data->sheets[$si]['cells'][$i][$j]))
                    {
                        $check = true;
                        if ($i > $rS) {
                            if ($data->sheets[$si]['cells'][$i - 1][$j - 1] != $data->sheets[$si]['cells'][$i - 1][$j])
                                $check = false;
                        }

                        if ($check)
                            $data->sheets[$si]['cells'][$i][$j] = $data->sheets[$si]['cells'][$i][$j - 1];
                    }
                }
            }

            // gen from sheet to php array
            for ($i = $posStart[0]; $i <= $numRows; $i++) {

                $listRows = array();
                for ($ii = $posStart[1] - $maxdepthRow; $ii < $posStart[1] - $maxdepthRow + $maxdepthRow; $ii++)
                    if ($data->sheets[$si]['cells'][$i][$ii] != null)
                        //if(!empty($data->sheets[$si]['cells'][$i][$ii]))
                        $listRows[] = trim($data->sheets[$si]['cells'][$i][$ii]);


//                echo "list rows : ". ($posStart[1]-$maxdepthRow). " ". ($posStart[1]-$maxdepthRow+$maxdepthRow). "---";
//                var_dump($listRows);
//                echo "<br/>";
                for ($j = $posStart[1]; $j <= $numCols; $j++)
                    if ($data->sheets[$si]['cells'][$i][$j] != null) //if(!empty($data->sheets[$si]['cells'][$i][$j]))
                    {

                        if (is_numeric($data->sheets[$si]['cells'][$i][$j]))
                            $data->sheets[$si]['cells'][$i][$j] = floatval($data->sheets[$si]['cells'][$i][$j]);

                        $listCols = array();
                        for ($jj = $posStart[0] - $maxdepthCol; $jj < $posStart[0] - $maxdepthCol + $maxdepthCol; $jj++)
                            if ($data->sheets[$si]['cells'][$jj][$j] != null)
                                //if(!empty($data->sheets[$si]['cells'][$jj][$j]))
                                $listCols[] = trim($data->sheets[$si]['cells'][$jj][$j]);


                        //echo "          list cols : ".($posStart[0]-$maxdepthCol)." ".($posStart[0]-$maxdepthCol+$maxdepthCol)."---";
                        //echo "    ";
                        //var_dump($listCols);
                        //echo "    <br/>";

                        $kk = &$result;
                        foreach ($listRows as $in => $val) {
                            $kk = &$kk[$val];
                        }
                        foreach ($listCols as $in => $val) {
                            $kk = &$kk[$val];
                        }


                        // check autoIncrement or list string or value
                        $bvalue = $data->sheets[$si]['cells'][$i][$j];
                        $arrValue = array();
                        if (false !== strpos($bvalue, ",")) {
                            $tempArrValue = explode(",", $bvalue);
                            foreach ($tempArrValue as $idxx => $ttb) {
                                if (null == $ttb || "" == $ttb) continue;
                                if (is_numeric($ttb)) {
                                    $arrValue[$idxx] = floatval($ttb);
                                } else {
                                    $arrValue[$idxx] = utf8_encode($ttb);
                                }
                            }
                        }

                        if (count($arrValue) > 0 && (strpos($bvalue, ",") !== false)) {
//						if ("inc" === $arrValue[0])
//						{
//							if (count($arrValue) < 4)
//							{
//								echo "<br/> Inc prefix need more than 4 params in it!  at Col = ". $i . " Row = " .$j;
//								break;
//							}

//							$step = intval($arrValue[1]);
//							if (($arrValue[3]-$arrValue[2])/$step <0)
//							{
//								echo "<br/> Inc infinitive loop!  at Col = ".$i . " Row = " .$j;
//								break;
//							}

//							for ($jj = intval($arrValue[2]); $jj<= intval($arrValue[3]); $jj += $step)
//								$kk[] = $jj;
//						}
//						else
                            {
                                $kk = $arrValue;
                                $kk = array_map(utf8_encode, $kk);
                            }
                        } else {
                            $kk = $data->sheets[$si]['cells'][$i][$j];
                        }

                    }


            }
            // write to file
            $object[$sn] = $result;
        }
        return $object;
    }
}
