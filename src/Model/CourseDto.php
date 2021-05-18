<?php


namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     title="CourseDto",
 *     description="CourseDto"
 * )
 * Class CourseDto
 * @package App\Model
 */
class CourseDto
{
    /**
     * @OA\Property(
     *     format="string",
     *     title="title",
     *     description="Название курса"
     * )
     * @Serializer\Type("string")
     */
    private $title;

    /**
     * @OA\Property(
     *     format="string",
     *     title="code",
     *     description="Код курса"
     * )
     * @Serializer\Type("string")
     */
    private $code;

    /**
     * @OA\Property(
     *     format="string",
     *     title="type",
     *     description="Тип курса"
     * )
     * @Serializer\Type("string")
     */
    private $type;

    /**
     * @OA\Property(
     *     format="float",
     *     title="price",
     *     description="Стоимость курса"
     * )
     * @Serializer\Type("float")
     */
    private $price;

    public function __construct(string $code, string $type, float $price, string $title)
    {
        $this->code = $code;
        $this->type = $type;
        $this->price = $price;
        $this->title = $title;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): void
    {
        $this->price = $price;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}