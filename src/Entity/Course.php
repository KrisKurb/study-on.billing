<?php

namespace App\Entity;

use App\Model\CourseDto;
use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CourseRepository::class)
 */
class Course
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $code;

    private const TYPES_COURSE = [
        1 => 'rent',
        2 => 'free',
        3 => 'buy',
    ];

    /**
     * @ORM\Column(type="smallint")
     */
    private $courseType;

    /**
     * @ORM\Column(type="float")
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\OneToMany(targetEntity=Transaction::class, mappedBy="course")
     */
    private $transactions;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getStringType(): string
    {
        return self::TYPES_COURSE[$this->courseType];
    }

    public function getNumberType(): ?int
    {
        return $this->courseType;
    }

    public function setCourseType(int $courseType): self
    {
        $this->courseType = $courseType;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection|Transaction[]
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setCourse($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getCourse() === $this) {
                $transaction->setCourse(null);
            }
        }

        return $this;
    }

    public static function fromDtoNew(CourseDto $courseDto): self
    {
        $course = new self();
        $course->setCode($courseDto->getCode());
        $course->setCourseType(array_search($courseDto->getType(), self::TYPES_COURSE));
        $course->setPrice($courseDto->getPrice());
        $course->setTitle($courseDto->getTitle());

        return $course;
    }

    public function fromDtoEdit(CourseDto $courseDto): self
    {
        $this->price = $courseDto->getPrice();
        $this->title = $courseDto->getTitle();
        $this->code = $courseDto->getCode();
        $this->courseType = array_search($courseDto->getType(), self::TYPES_COURSE);

        return $this;
    }
}
