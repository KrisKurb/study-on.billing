<?php


namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TransactionFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $courseRepository = $manager->getRepository(Course::class);
        $userRepository = $manager->getRepository(User::class);
        // Пользователь
        $user = $userRepository->findOneBy(['email' => 'user@mail.ru']);
        // Получаем существующие курсы
        $rentCourses = $courseRepository->findBy(['courseType' => 1]);
        $buyCourses = $courseRepository->findBy(['courseType' => 3]);

        $transactions = [
            // Арендованные курс, у которых закончился срок аренды
            [
                'typeOperation' => 1,
                'amount' => $rentCourses[0]->getPrice(),
                'expiresAt' => new \DateTime('2021-04-28 00:00:00'),
                'course' => $rentCourses[0],
                'userBilling' => $user,
                'createdAt' => new \DateTime('2021-04-20 00:00:00'),
            ],
            [
                'typeOperation' => 1,
                'amount' => $rentCourses[0]->getPrice(),
                'expiresAt' => new \DateTime('2021-05-06 00:00:00'),
                'course' => $rentCourses[0],
                'userBilling' => $user,
                'createdAt' => new \DateTime('2021-04-29 00:00:00'),
            ],
            // Арендованные курсы, у которых еще не закончился срок аренды
            [
                'typeOperation' => 1,
                'amount' => $rentCourses[2]->getPrice(),
                'expiresAt' => (new \DateTime())->modify('+1 day'),
                'course' => $rentCourses[2],
                'userBilling' => $user,
                'createdAt' => (new \DateTime())->modify('-6 day'),
            ],
            // Купленые курсы
            [
                'typeOperation' => 1,
                'amount' => $buyCourses[1]->getPrice(),
                'course' => $buyCourses[1],
                'userBilling' => $user,
                'createdAt' => new \DateTime('2021-04-05 00:00:00'),
            ],
            [
                'typeOperation' => 1,
                'amount' => $buyCourses[2]->getPrice(),
                'course' => $buyCourses[2],
                'userBilling' => $user,
                'createdAt' => new \DateTime('2021-05-08 00:00:00'),
            ],
        ];

        foreach ($transactions as $transaction) {
            $newTransaction = new Transaction();
            $newTransaction->setTypeOperation($transaction['typeOperation']);
            $newTransaction->setCourse($transaction['course']);
            $newTransaction->setUserBilling($transaction['userBilling']);
            $newTransaction->setCreatedAt($transaction['createdAt']);
            $newTransaction->setAmount($transaction['amount']);
            if (isset($transaction['expiresAt'])) {
                $newTransaction->setExpiresAt($transaction['expiresAt']);
            }
            $manager->persist($newTransaction);
        }

        $manager->flush();
    }
}