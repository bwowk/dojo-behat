<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ciandt\Behat\VisualRegressionExtension\Definitions;

/**
 * Description of VisualCheckpoint
 *
 * @author bwowk
 */
class VisualCheckpoint
{
    const SKIPPED = "skipped";
    const APPROVED = "approved";
    const BUG = "bug";
    const FALSE_POSITIVE = "false-positive";
    const PENDING = "pending";
    
    
    private $name;
    private $id;
    private $baseline = false;
    private $current;
    private $diff = false;
    private $status;
    private $diffPercent = false;
    private $tags;

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

        
    public function getName()
    {
        return $this->name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getBaseline()
    {
        return $this->baseline;
    }

    public function getCurrent()
    {
        return $this->current;
    }

    public function getDiff()
    {
        return $this->diff;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setBaseline($baseline)
    {
        $this->baseline = $baseline;
    }

    public function setCurrent($current)
    {
        $this->current = $current;
    }

    public function setDiff($diff)
    {
        $this->diff = $diff;
    }

    public function getDiffPercent()
    {
        return $this->diffPercent;
    }

    public function setDiffPercent($diffPercent)
    {
        $this->diffPercent = $diffPercent;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function setTags($tags)
    {
        $this->tags = $tags;
    }
}
