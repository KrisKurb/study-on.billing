<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
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
        $manager->flush();
    }
}
