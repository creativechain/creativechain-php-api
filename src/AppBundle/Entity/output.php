<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="output")
 */
class output
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $hash;
    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $in;
    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $out;
    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $inF;
    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $outF;

    /**
     * weight constructor.
     * @param $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param mixed $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return mixed
     */
    public function getIn()
    {
        return $this->in;
    }

    /**
     * @param mixed $in
     */
    public function setIn($in)
    {
        $this->in = $in;
    }

    /**
     * @return mixed
     */
    public function getOut()
    {
        return $this->out;
    }

    /**
     * @param mixed $out
     */
    public function setOut($out)
    {
        $this->out = $out;
    }

    /**
     * @return mixed
     */
    public function getInF()
    {
        return $this->inF;
    }

    /**
     * @param mixed $inF
     */
    public function setInF($inF)
    {
        $this->inF = $inF;
    }

    /**
     * @return mixed
     */
    public function getOutF()
    {
        return $this->outF;
    }

    /**
     * @param mixed $outF
     */
    public function setOutF($outF)
    {
        $this->outF = $outF;
    }


}
