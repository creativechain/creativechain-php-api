<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="weight_words")
 */
class weightWords
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $word;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $weight;

    public function __construct()
    {
        $this->word = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getWord()
    {
        return $this->word;
    }

    /**
     * @param mixed $word
     */
    public function setWord($word)
    {
        $this->word = $word;
    }

    /**
     * @return mixed
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param mixed $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

}
