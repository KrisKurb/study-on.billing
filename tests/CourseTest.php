<?php


namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\DataFixtures\CourseFixtures;
use App\Model\courseDto;
use App\Service\PaymentService;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

class CourseTest extends AbstractTest
{
    /**
     * @var string
     */
    private $startingPath = '/api/v1/courses';

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
            new CourseFixtures(),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$kernel->getContainer()->get('jms_serializer');
    }

    public function auth($user): array
    {
        // Создание запроса
        $client = self::getClient();
        $client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            [ 'CONTENT_TYPE' => 'application/json' ],
            $this->serializer->serialize($user, 'json')
        );


        // Проверим содержимое ответа
        return json_decode($client->getResponse()->getContent(), true);
    }

        // Тест на получение всех курсов
    public function testGetAllCourses(): void
    {
        // Авторизация админом
        $user = [
            'username' => 'admin@mail.ru',
            'password' => '123456',
        ];
        $userData = $this->auth($user);

        $client = self::getClient();
        // Формирование запроса
        $client->request(
            'GET',
            $this->startingPath,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token']
            ]
        );

        // Проверим статус ответа
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверим содержимое ответа
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(9, $response);
    }

        // Тест на получение информации о курсе
    public function testGetCourse(): void
    {
        // Проверка получения курса
        // Авторизация админом
        $user = [
            'username' => 'admin@mail.ru',
            'password' => '123456',
        ];
        $userData = $this->auth($user);

        $client = self::getClient();
        // Формирование запроса
        $codeCourse = 'LFGFDGJFDJJCHJVHJF5';
        $client->request(
            'GET',
            $this->startingPath . '/' . $codeCourse,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ]
        );

        // Проверим статус ответа
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверим содержимое ответа арендуемого курса
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals('rent', $response['type']);

        // Проверка получения курса, которого нет
        //Формирование запроса
        $codeCourse = 'jjhnkjh1';
        $client->request(
            'GET',
            $this->startingPath . '/' . $codeCourse,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ]
        );
        // Проверим статус ответа
        $this->assertResponseCode(Response::HTTP_NOT_FOUND, $client->getResponse());
    }

    // Тест покупки курса
    public function testPayCourse(): void
    {
        // Авторизация админом
        $user = [
            'username' => 'admin@mail.ru',
            'password' => '123456',
        ];
        $userData = $this->auth($user);

        $client = self::getClient();
        // Создание запроса
        $codeCourse = 'LFGFDGJFDJJCHJVHJF5';
        $client->request(
            'POST',
            $this->startingPath . '/' . $codeCourse . '/pay',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ]
        );
        // Проверим статус ответа
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        // Проверим содержимое ответа
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals(true, $response['success']);
        
        // Проверка неуспешной покупки курса c невалидным jwt токеном
        $token = '123';
        $client->request(
            'POST',
            $this->startingPath . '/' . $codeCourse . '/pay',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );
        // Проверим статус ответа
        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());
    }

    // Тест на создание нового курса
    public function testNewCourse(): void
    {
        // Добавление нового курса
        // Авторизация администратором
        $user = [
            'username' => 'admin@mail.ru',
            'password' => '123456',
        ];
        $userData = $this->auth($user);

        $client = self::getClient();
        // Формирование запроса
        $courseDto = new courseDto('LFGFDCHJVHJF7', 'buy', 800, 'Новый курс');
        $dataRequest = $this->serializer->serialize($courseDto, 'json');
        $client->request(
            'POST',
            $this->startingPath . '/new',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ],
            $dataRequest
        );
        // Проверим статус ответа
        $this->assertResponseCode(Response::HTTP_CREATED, $client->getResponse());

        // Проверим содержимое ответа
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals(true, $response['success']);

        // Добавление курса, который уже существует
        $client = self::getClient();
        // Формирование запроса
        $courseDto = new courseDto('LFGFDGJFDJJCHJV4514', 'buy', 1100, 'Другой язык');
        $dataRequest = $this->serializer->serialize($courseDto, 'json');
        $client->request(
            'POST',
            $this->startingPath . '/new',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ],
            $dataRequest
        );
        // Проверим статус ответа
        $this->assertResponseCode(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse());

        // Проверим содержимое ответа (ошибка)
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals($response['message'], 'Курс с данным кодом уже существует');

        // Проверим на добавление курса обычным пользователем без доступа
        // Авторизация обычным пользователем
        $user = [
            'username' => 'user@mail.ru',
            'password' => '123456',
        ];
        $userData = $this->auth($user);

        $client = self::getClient();
        // Формирование запроса
        $courseDto = new courseDto('JFDFJJSDKK444', 'buy', 0, 'Тест');
        $dataRequest = $this->serializer->serialize($courseDto, 'json');
        $client->request(
            'POST',
            $this->startingPath . '/new',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ],
            $dataRequest
        );
        // Проверим статус ответа
        $this->assertResponseCode(Response::HTTP_FORBIDDEN, $client->getResponse());
    }

        // Тест на редактирования курса
    public function testEditCourse(): void
    {
        // Успешное редактирование курса
        // Авторизация админом
        $user = [
            'username' => 'admin@mail.ru',
            'password' => '123456',
        ];
        $userData = $this->auth($user);

        $client = self::getClient();
        $code =  'LFGFDGJFDJJCHJV4511';
        // Формирование запроса
        $courseDto = new courseDto('NEWLFGFDGJFDJJCHJV4511', 'rent', 1000, 'Обновленный');
        $dataRequest = $this->serializer->serialize($courseDto, 'json');
        $client->request(
            'POST',
            $this->startingPath . '/' . $code . '/edit',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ],
            $dataRequest
        );
        // Проверим статус ответа
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        // Проверим содержимое ответа
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals(true, $response['success']);

        // Неуспешное редактирование курса (замена кода курса на тот, что уже существует)
        $client = self::getClient();
        $code =  'JVJNJKBNDNJFDDFF444';
        // Формирование запроса
        $courseDto = new courseDto(
            'JVJNJKBNDNJFDDFF445',
            'free',
            0,
            'Тестовый'
        );
        $dataRequest = $this->serializer->serialize($courseDto, 'json');
        $client->request(
            'POST',
            $this->startingPath . '/' . $code . '/edit',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ],
            $dataRequest
        );
        // Проверим статус ответа
        $this->assertResponseCode(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse());

        // Проверим содержимое ответа
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals($response['message'], 'Курс с данным кодом уже существует в системе');

        // Проверка на редоктирование курса обычным пользователем без доступа
        // Авторизация обычным пользователем
        $user = [
            'username' => 'user@mail.ru',
            'password' => '123456',
        ];
        $userData = $this->auth($user);

        $client = self::getClient();

        $code =  'MDMJMMDNFKK4';
        // Формирование запроса
        $courseDto = new courseDto('EDITCOURSE1', 'free', 0, 'Тестовый');
        $dataRequest = $this->serializer->serialize($courseDto, 'json');
        $client->request(
            'POST',
            $this->startingPath . '/' . $code . '/edit',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ],
            $dataRequest
        );
        // Проверим статус ответа
        $this->assertResponseCode(Response::HTTP_FORBIDDEN, $client->getResponse());
    }
}
