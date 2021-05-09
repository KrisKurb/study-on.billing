<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use App\Model\UserDto;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

class UserTest extends AbstractTest
{
    /**
     * @var string
     */
    private $startingPath = '/api/v1';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function getFixtures(): array
    {
        return [new AppFixtures(self::$kernel->getContainer()->get('security.password_encoder'))];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$kernel->getContainer()->get('jms_serializer');
    }

    public function testCurrent(): void
    {
        $client = self::getClient();

        // Авторизируемся существующим пользователем
        $user = [
            'username' => 'user@mail.ru',
            'password' => '123456',
        ];

        // Формируем запрос
        $client = self::getClient();
        $client->request(
            'POST',
            $this->startingPath.'/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );
        $json = json_decode($client->getResponse()->getContent(), true);
        // Получаем токен клиента
        $token = $json['token'];

        //Проверка успешого получения данных
        // Формирование праивльного запроса
        $contentHeaders = [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'CONTENT_TYPE' => 'application/json',
        ];

        $client->request(
            'GET',
            $this->startingPath.'/users/current',
            [],
            [],
            $contentHeaders
        );
        // Проверка статуса ответа, 200
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        // Проверяем заголовок ответа, что он в формате json
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        /** @var UserDto $responseUserDto */
        $responseUserDto = $this->serializer->deserialize($client->getResponse()->getContent(), UserDto::class, 'json');

        // Получим данные о пользователе из бд и сравним
        $em = self::getEntityManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => $responseUserDto->getUsername()]);
        // Сравнение данных
        self::assertEquals($responseUserDto->getUsername(), $user->getEmail());
        self::assertEquals($responseUserDto->getRoles()[0], $user->getRoles()[0]);
        self::assertEquals($responseUserDto->getBalance(), $user->getBalance());

        //Проверка неуспешной операции (jwt токен неверный)
        $token = 'какой то токен';
        // Передаем неверный токен
        $contentHeaders = [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'CONTENT_TYPE' => 'application/json',
        ];

        $client->request(
            'GET',
            $this->startingPath.'/users/current',
            [],
            [],
            $contentHeaders
        );
        // Проверка статуса ответа, 401
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());
    }
}