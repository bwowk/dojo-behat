<?php
/**
 * Created by PhpStorm.
 * User: bwowk
 * Date: 17/07/17
 * Time: 09:30
 */

namespace Ciandt;


class Calc
{
    private $result = 0;
    private $history = "";

    public function doOp($op,$n){
        switch ($op) {
            case '+':
                $this->result += $n;
                break;
            case '-':
                $this->result -= $n;
                break;
            case '/':
                $this->result /= $n;
                break;
            case '*':
                $this->result *= $n;
                break;
            default:
                return;
        }
        $this->logOp($op, $n);
    }

    public function equals(){
        $this->logResult();
        return $this->result;
    }

    public function calcFromFile($filepath){
        $file = new \SplFileObject($filepath);
        while (!$file->eof()) {
            $line = $file->fgets();
            $op = substr($line, 1, 1);
            $n = (int) substr($line, 2);
            $this->doOp($op,$n);
        }
        return $this->equals();
    }

    private function logOp($op, $n){
        $this->history .= "$op $n\n";
    }

    private function logResult(){
        $this->history .= "----------\n";
        $this->history .= "{$this->result}\n";
    }

    public function printHistory(){
        return $this->history;
    }

    public function clear(){
        $this->history = "";
        $this->result = 0;
    }

}