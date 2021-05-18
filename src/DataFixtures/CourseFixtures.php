<?php

namespace App\DataFixtures;

use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $courses = [
            // Для аренды
            [
                'code' => 'LFGFDGJFDJJCHJVHJF5',
                'title' => 'Курс испанского языка (1 уровень)',
                'courseType' => 1,
                'price' => 999,
            ],
            [
                'code' => 'LFGFDGJFDJJCHJV4514',
                'title' => 'Курс испанского языка (2 уровень)',
                'courseType' => 1,
                'price' => 1100,
            ],
            [
                'code' => 'LFGFDGJFDJJCHJV4511',
                'title' => 'Курс испанского языка (3 уровень)',
                'courseType' => 1,
                'price' => 2000,
            ],
            // Бесплатные курсы
            [
                'code' => 'JVJNJKBNDNJFDDFF444',
                'title' => 'Курс корейского языка (1 уровень)',
                'courseType' => 2,
                'price' => 0,
            ],
            [
                'code' => 'JVJNJKBNDNJFDDFF445',
                'title' => 'Курс корейского языка (2 уровень)',
                'courseType' => 2,
                'price' => 0
            ],
            [
                'code' => 'JVJNJKBNDNJFDDFF447',
                'title' => 'Курс корейского языка (3 уровень)',
                'courseType' => 2,
                'price' => 0
            ],
            // Разовая покупка
            [
                'code' => 'DSFDFSDFDSLFLHGLHLG',
                'title' => 'Курс английского языка (1 уровень)',
                'courseType' => 3,
                'price' => 1500,
            ],
            [
                'code' => 'DSFDJGMFKGJMDLKLDDD',
                'title' => 'Курс английского языка (2 уровень)',
                'courseType' => 3,
                'price' => 2000,
            ],
            [
                'code' => 'DSFDFSDFDFDFFSDFSDG',
                'title' => 'Курс английского языка (3 уровень)',
                'courseType' => 3,
                'price' => 2400,
            ],
        ];

        foreach ($courses as $course) {
            $newCourse = new Course();
            $newCourse->setCode($course['code']);
            $newCourse->setTitle($course['title']);
            $newCourse->setCourseType($course['courseType']);
            if (isset($course['price'])) {
                $newCourse->setPrice($course['price']);
            }
            $manager->persist($newCourse);
        }
        $manager->flush();
    }
}