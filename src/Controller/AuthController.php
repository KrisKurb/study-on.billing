<?php


namespace App\Controller;

use App\Entity\User;
use App\Model\UserDto;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Service\RefreshToken;
use JMS\Serializer\SerializerInterface;
use OpenApi\Annotations as OA;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/v1")
 */
class AuthController extends AbstractController
{
    /**
     * @OA\Post (
     *     path="/api/v1/auth",
     *     tags={"User"},
     *     summary="Автроизация пользователя",
     *     description="Автроизация пользователя",
     *     operationId="auth",
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="username",
     *                  type="string",
     *                  example="user@mail.ru"
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  type="string",
     *                  example="123456"
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Успешная авторизация",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="token",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="refresh_token",
     *                  type="string"
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="Неудалось авторизоваться",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *              )
     *          )
     *     )
     *  )
     * @Route("/auth", name="api_login_check", methods={"POST"})
     */
    public function auth(): void
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     tags={"User"},
     *     summary="Регистрация нового пользователя",
     *     description="Регистрация доступна только для новых пользователей",
     *     operationId="register",
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  type="string",
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="201",
     *          description="Регистрация прошла успешно",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="token",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="refresh_token",
     *                  type="string"
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="500",
     *          description="Сервер не отвечает"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Ошибка при валидации данных",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="message",
     *                  type="array",
     *                  @OA\Items(
     *                      type="string"
     *                  )
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="403",
     *          description="Данный пользователь уже существует",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *     )
     * )
     * @Route("/register", name="register", methods={"POST"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param JWTTokenManagerInterface $JWTManager
     * @return Response
     */
    public function register(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserPasswordEncoderInterface $passwordEncoder,
        JWTTokenManagerInterface $JWTManager,
        RefreshTokenManagerInterface $refreshTokenManager
    ): Response {
        // Десериализация
        $userDto = $serializer->deserialize($request->getContent(), UserDto::class, 'json');

        $data = [];
        $response = new Response();
        // Проверяем ошибки при валидации
        $validErrors = $validator->validate($userDto);
        if (count($validErrors)) {
            // Параметры
            $data = [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => $validErrors,
            ];
            // Статус ответа 400
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setContent($serializer->serialize($data, 'json'));
            $response->headers->add(['Content-Type' => 'application/json']);
            return $response;
        }
        // Существует ли данный пользовательн в системе
        $entityManager = $this->getDoctrine()->getManager();
        $userRepository = $entityManager->getRepository(User::class);
        if ($userRepository->findOneBy(['email' => $userDto->getEmail()])) {
            $data = [
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'Пользователь с данным email уже существует',
            ];
            // Статус ответа 403, если пользователь уже существует
            $response->setStatusCode(Response::HTTP_FORBIDDEN);
        } else {
            // Создаем пользователя
            $user = User::fromDto($userDto);
            $user->setPassword($passwordEncoder->encodePassword(
                $user,
                $user->getPassword()
            ));
            $entityManager->persist($user);
            $entityManager->flush();

            // Token обновления
            $refreshToken = $refreshTokenManager->create();
            $refreshToken->setUsername($user->getEmail());
            $refreshToken->setRefreshToken();
            $refreshToken->setValid((new \DateTime())->modify('+1 month'));
            $refreshTokenManager->save($refreshToken);

            $data = [
                // JWT token
                'token' => $JWTManager->create($user),
                'refresh_token' => $refreshToken->getRefreshToken(),
            ];
            // Устанавливаем статус ответа
            $response->setStatusCode(Response::HTTP_CREATED);
        }
        $response->setContent($serializer->serialize($data, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);
        return $response;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/token/refresh",
     *     tags={"User"},
     *     summary="Refresh token",
     *     operationId="token.refresh",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *              @OA\Property(
     *                  property="refresh_token",
     *                  type="string"
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Обновление токена успешно завершено",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="token",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="refresh_token",
     *                  type="string"
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="Не удалось обновить токен",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="integer",
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *              )
     *          )
     *     )
     * )
     *
     * @Route("/token/refresh", name="refresh", methods={"POST"})
     * @param Request $request
     * @param RefreshToken $refreshService
     * @return mixed
     */
    public function refresh(Request $request, RefreshToken $refreshService)
    {
        return $refreshService->refresh($request);
    }
}
