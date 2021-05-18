<?php


namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Service\PaymentService;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

class AuthTest extends AbstractTest
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
        return [
            new AppFixtures(
                self::$kernel->getContainer()->get('security.password_encoder'),
                self::$kernel->getContainer()->get(PaymentService::class)
            ),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$kernel->getContainer()->get('jms_serializer');
    }

    public function testAuth(): void
    {
        // Проверка авторизации
        // Авторизируемся существующим пользователем
        $user = [
            'username' => 'user@mail.ru',
            'password' => '123456',
        ];
        // Формируем запрос
        $client = self::getClient();
        $client->request(
            'POST',
            $this->startingPath . '/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        // Проверим статус ответа
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверим содержимое ответа
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['token']);

        // Проверка неуспешной авторизации
        $user = [
            'username' => 'user@mail.ru',
            'password' => '142536',
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

        // Проверим статус ответа, 401
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверим содержимое ответа (оишбка)
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['message']);
    }

    // Регистрация нового пользователя
    public function testRegisterSuccessful(): void
    {
        $user = [
            'email' => 'newUser@yandex.ru',
            'password' => 'new123456',
        ];

        // Формируем запрос
        $client = self::getClient();
        $client->request(
            'POST',
            $this->startingPath.'/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        // Проверим статус ответа, 201
        $this->assertResponseCode(Response::HTTP_CREATED, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверим содержимое ответа
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['token']);
    }

    // Неудачаня регистрация
    public function testExistUserRegister(): void
    {
        //Проверим на существующего пользователя
        // Данные пользователя
        $user = [
            'email' => 'user@mail.ru',
            'password' => '123456',
        ];

        // Формируем запрос
        $client = self::getClient();
        $client->request(
            'POST',
            $this->startingPath.'/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        // Проверим статус ответа, 403
        $this->assertResponseCode(Response::HTTP_FORBIDDEN, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверим содержимое ответа (ошибка)
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals('Пользователь с данным email уже существует', $json['message']);

        // Проверим валидацию
        // Данные пользователя, где пароль состоит менее чем из 6-и символов
        $user = [
            'email' => 'newtest@mail.ru',
            'password' => '123',
        ];

        // Формируем запрос
        $client = self::getClient();
        $client->request(
            'POST',
            $this->startingPath.'/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        // Проверим статус ответа, 400
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверим содержимое ответа (ошибка)
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['message']);
    }
}
