<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="input_words")
 */
class inputWords
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string" , nullable=true)
     */
    protected $hashWords;
    /**
     * @ORM\Column(type="string" , nullable=true)
     */
    protected $inWords;
    /**
     * @ORM\Column(type="string" , nullable=true)
     */
    protected $outWords;
    /**
     * @ORM\Column(type="string" , nullable=true)
     */
    protected $inFWords;
    /**
     * @ORM\Column(type="string" , nullable=true)
     */
    protected $outFWords;

    /**
     * @ORM\Column(type="string" , nullable=true)
     */
    protected $permiso;
    /**
     * @ORM\Column(type="datetime" , nullable=true)
     */
    protected $blockdate;

    public function __construct()
    {
        $this->user = new ArrayCollection();
        // your own logic
    }

    /**
     * @return mixed
     */
    public function getBlockdate()
    {
        return $this->blockdate;
    }

    /**
     * @param mixed $blockdate
     */
    public function setBlockdate($blockdate)
    {
        $this->blockdate = $blockdate;
    }

    /**
     * @return mixed
     */
    public function getHashWords()
    {
        return $this->hashWords;
    }

    /**
     * @param mixed $hashWords
     */
    public function setHashWords($hashWords)
    {
        $this->hashWords = $hashWords;
    }

    /**
     * @return mixed
     */
    public function getInWords()
    {
        return $this->inWords;
    }

    /**
     * @param mixed $inWords
     */
    public function setInWords($inWords)
    {
        $this->inWords = $inWords;
    }

    /**
     * @return mixed
     */
    public function getOutWords()
    {
        return $this->outWords;
    }

    /**
     * @param mixed $outWords
     */
    public function setOutWords($outWords)
    {
        $this->outWords = $outWords;
    }

    /**
     * @return mixed
     */
    public function getInFWords()
    {
        return $this->inFWords;
    }

    /**
     * @param mixed $inFWords
     */
    public function setInFWords($inFWords)
    {
        $this->inFWords = $inFWords;
    }

    /**
     * @return mixed
     */
    public function getOutFWords()
    {
        return $this->outFWords;
    }

    /**
     * @param mixed $outFWords
     */
    public function setOutFWords($outFWords)
    {
        $this->outFWords = $outFWords;
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
    public function getPermiso()
    {
        return $this->permiso;
    }

    /**
     * @param mixed $permiso
     */
    public function setPermiso($permiso)
    {
        $this->permiso = $permiso;
    }


}
