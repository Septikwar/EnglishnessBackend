<?php

namespace App\Entity;

use App\Entity\WordGroup;
use App\Repository\WordRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=WordRepository::class)
 */
class Word
{
    /**
     * @Groups({"Word"})
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Groups({"Word"})
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255)
     */
    private $en;

    /**
     * @Groups({"Word"})
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ru;

    /**
     * @Groups({"Word"})
     * @ORM\ManyToMany(targetEntity="App\Entity\WordGroup", inversedBy="words")
     * @ORM\JoinTable(name="wordgroup_word",
     *     joinColumns={@ORM\JoinColumn(name="id_word", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="id_group", referencedColumnName="id")}
     * )
     */
    private $groups;

    /**
     * @ORM\Column(type="datetime")
     */
    private $addDate;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->addDate = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEn(): ?string
    {
        return $this->en;
    }

    public function setEn(string $en): self
    {
        $this->en = $en;

        return $this;
    }

    public function getAddDate(): ?\DateTimeInterface
    {
        return $this->addDate;
    }

    public function setAddDate(\DateTimeInterface $addDate): self
    {
        $this->addDate = $addDate;

        return $this;
    }

    public function addGroup($group)
    {
        $this->groups->add($group);

        return $this;
    }

    public function removeGroup(WordGroup $group)
    {
        $this->groups->removeElement($group);

        return $this;
    }

    public function getGroups(): ?array
    {
        return $this->groups->toArray();
    }

    /**
     * @return mixed
     */
    public function getRu()
    {
        return $this->ru;
    }

    /**
     * @param mixed $ru
     */
    public function setRu($ru): void
    {
        $this->ru = $ru;
    }
}
