<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Service\PaymentService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $passwordEncoder;
    private $paymentService;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, PaymentService $paymentService)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->paymentService = $paymentService;
    }

    public function load(ObjectManager $manager)
    {
        //Создание обычного пользователя
        $user = new User();
        $user->setEmail('user@mail.ru');
        $user->setPassword($this->passwordEncoder->encodePassword(
            $user,
            '123456'
        ));
        $user->setRoles(["ROLE_USER"]);
        $user->setBalance(0);
        $manager->persist($user);
        $this->paymentService->deposit($user, $_ENV['MONEY']);

        // Создание супер пользователя
        $user = new User();
        $user->setEmail('admin@mail.ru');
        $user->setPassword($this->passwordEncoder->encodePassword(
            $user,
            '123456'
        ));
        $user->setRoles(["ROLE_SUPER_ADMIN"]);
        $user->setBalance(0);
        $manager->persist($user);
        $this->paymentService->deposit($user, $_ENV['MONEY']);
        $manager->flush();
    }
}
