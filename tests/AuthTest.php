<?php


namespace App\Tests;

use App\DataFixtures\AppFixtures;
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
        return [new AppFixtures(self::$kernel->getContainer()->get('security.password_encoder'))];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$kernel->getContainer()->get('jms_serializer');
    }

    public function testAuth(): void
    {
        //Проверка авторизации
        // Авторизируемся пользователем, который существует
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

        // Проверяем статус ответа
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        // Проверяем заголовок ответа, что он в формате json
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверяем содержимого ответа
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['token']);

        //Проверка неуспешной авторизации
        // Авторизируемся неправильно
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

        // Проверка статуса ответа, 401
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());

        // Проверяем заголовок ответа, что он в формате json
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверка содержимого ответа (оишбка)
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['message']);
    }

    // Тест успешной регистрации нового пользователя
    public function testRegisterSuccessful(): void
    {
        // Введем данные нового пользователя
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

        // Проверка статуса ответа, 201
        $this->assertResponseCode(Response::HTTP_CREATED, $client->getResponse());

        // Проверяем заголовок ответа, что он в формате json
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверка содержимого ответа
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['token']);
    }

    // Тест для неуспешной регистрации
    public function testExistUserRegister(): void
    {
        //Проверка на уже существующего пользователя
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

        // Проверка статуса ответа, 403
        $this->assertResponseCode(Response::HTTP_FORBIDDEN, $client->getResponse());

        // Проверяем заголовок ответа, что он в формате json
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверка содержимого ответа (ошибка)
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals('Пользователь с данным email уже существует', $json['message']);

        //Проверка валидации полей
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

        // Проверка статуса ответа, 400
        $this->assertResponseCode(Response::HTTP_BAD_REQUEST, $client->getResponse());

        // Проверяем заголовок ответа, что он в формате json
        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверка содержимого ответа (ошибка)
        $json = json_decode($client->getResponse()->getContent(), true);
        self::assertNotEmpty($json['message']);
    }
}
