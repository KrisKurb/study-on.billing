<?php


namespace App\Controller;


use App\Entity\Course;
use App\Entity\User;
use App\Model\CourseDto;
use App\Model\PayDto;
use App\Repository\CourseRepository;
use App\Service\PaymentService;
use JMS\Serializer\SerializerBuilder;
use OpenApi\Annotations as OA;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/courses")
 */
class CourseController extends AbstractController
{
    /**
     * @OA\Get(
     *     path="/api/v1/courses",
     *     tags={"Courses"},
     *     summary="Получение всех курсов",
     *     description="Получение всех курсов",
     *     operationId="courses.index",
     *     @OA\Response(
     *          response="200",
     *          description="Успешное получение курсов",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(
     *                      property="code",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="type",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="price",
     *                      type="number",
     *                      format="float",
     *                  ),
     *              )
     *          )
     *     )
     * )
     *
     * @Route("", name="courses_index", methods={"GET"})
     */
    public function index(SerializerInterface $serializer): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $courseRepository = $entityManager->getRepository(Course::class);

        $courses = $courseRepository->findAll();

        $coursesDto = [];
        foreach ($courses as $course) {
            $coursesDto[] = new CourseDto(
                $course->getCode(),
                $course->getStringType(),
                $course->getPrice(),
                $course->getTitle()
            );
        }

        $response = new Response();
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($serializer->serialize($coursesDto, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);
        return $response;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/courses/{code}",
     *     tags={"Courses"},
     *     summary="Получение курса",
     *     description="Получение курса",
     *     operationId="courses.show",
     *     @OA\Response(
     *         response=200,
     *         description="Курс успешно получен",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="type",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="price",
     *                     type="number",
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Курс не найден",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                 ),
     *             ),
     *        )
     *     ),
     * )
     * @Route("/{code}", name="course_show", methods={"GET"})
     */
    public function show(string $code, SerializerInterface $serializer): Response
    {
        $em = $this->getDoctrine()->getManager();
        $courseRepository = $em->getRepository(Course::class);
        $data=[];
        $status='';
        $course = $courseRepository->findOneBy(['code' => $code]);
        if (!isset($course)) {
            $data = [
                'code' => Response::HTTP_NOT_FOUND,
                'message' => 'Курс не найден',
            ];
            $status = Response::HTTP_NOT_FOUND;
        } else {
            $data = new CourseDto(
                $course->getCode(),
                $course->getStringType(),
                $course->getPrice(),
                $course->getTitle()
            );
            $status = Response::HTTP_OK;
        }

        $response = new Response();
        $response->setStatusCode($status);
        $response->setContent($serializer->serialize($data, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);
        return $response;
    }

    /**
     * @OA\Post(
     *     tags={"Courses"},
     *     path="/api/v1/courses/{code}/pay",
     *     summary="Оплата курса",
     *     description="Оплата курса",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="Курс куплен",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="success",
     *                     type="boolean",
     *                 ),
     *                 @OA\Property(
     *                     property="course_type",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="expires_at",
     *                     type="string",
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Курс не найден",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=406,
     *         description="У вас недостаточно средств",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid JWT Token",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                 ),
     *             ),
     *        )
     *     )
     * )
     * @Route("/{code}/pay", name="course_pay", methods={"POST"})
     */
    public function pay(string $code, PaymentService $paymentService, SerializerInterface $serializer): Response
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $courseRepository = $em->getRepository(Course::class);

            $course = $courseRepository->findOneBy(['code' => $code]);

            if (!$course) {
                $dataResponse = [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Курс не найден',
                ];
                throw new \Exception($dataResponse['message'], $dataResponse['code']);
            }

            /* @var User $user */
            $user = $this->getUser();
            $transaction = $paymentService->paymentCourses($user, $course);
            $expiresAt = $transaction->getExpiresAt();
            $payDto = new PayDto(
                true,
                $course->getStringType(),
                $expiresAt ? $expiresAt->format('Y-m-d H:i:s') : null
            );

            $response = new Response();
            $response->setStatusCode(Response::HTTP_OK);
            $response->setContent($serializer->serialize($payDto, 'json'));
            $response->headers->add(['Content-Type' => 'application/json']);
            return $response;
        } catch (\Exception $e) {
            $dataResponse = [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        $response = new Response();
        $response->setStatusCode($dataResponse['code']);
        $response->setContent($serializer->serialize($dataResponse, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);
        return $response;
    }

    /**
     * @OA\Post(
     *     tags={"Courses"},
     *     path="/api/v1/courses/new",
     *     summary="Создание нового курса",
     *     description="Создание нового курса",
     *     operationId="courses.new",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CourseDto")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Курс успешно создан",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="success",
     *                     type="bool",
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=405,
     *         description="Курс с данным кодом уже существует",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                 ),
     *             ),
     *        )
     *     ),
     * )
     * @Route("/new", name="course_new", methods={"POST"})
     */
    public function new(Request $request, CourseRepository $courseRepository): Response
    {
        try {
            $serializer = SerializerBuilder::create()->build();
            $courseDto = $serializer->deserialize($request->getContent(), CourseDto::class, 'json');

            $course = $courseRepository->findOneBy(['code' => $courseDto->getCode()]);
            if ($course) {
                $dataResponse = [
                    'code' => Response::HTTP_METHOD_NOT_ALLOWED,
                    'message' => 'Курс с данным кодом уже существует',
                ];
                throw new \Exception($dataResponse['message'], $dataResponse['code']);
            }

            $course = Course::fromDtoNew($courseDto);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($course);
            $entityManager->flush();

            $dataResponse = [
                'code' => Response::HTTP_CREATED,
                'success' => true,
            ];
        } catch (\Exception $e) {
            $dataResponse = [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
        $response = new Response();
        $response->setStatusCode($dataResponse['code']);
        $response->setContent($serializer->serialize($dataResponse, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);
        return $response;
    }
    /**
     * @OA\Post(
     *     tags={"Courses"},
     *     path="/api/v1/courses/{code}/edit",
     *     summary="Редактирование курса",
     *     description="Редактирование курса",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CourseDto")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Курс изменен",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="success",
     *                     type="bool",
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=405,
     *         description="Курс с данным кодом уже существует",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Данный курс не найден",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                 ),
     *             ),
     *        )
     *     )
     * )
     * @Route("/{code}/edit", name="course_edit", methods={"POST"})
     */
    public function edit(string $code, Request $request, CourseRepository $courseRepository): Response
    {
        try {
            $serializer = SerializerBuilder::create()->build();
            $courseDto = $serializer->deserialize($request->getContent(), CourseDto::class, 'json');

            $course = $courseRepository->findOneBy(['code' => $code]);
            if (!$course) {
                $dataResponse = [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Данный курс не найден',
                ];
                throw new \Exception($dataResponse['message'], $dataResponse['code']);
            }

            $courseDuplicate = $courseRepository->findOneBy(['code' => $courseDto->getCode()]);
            if ($courseDuplicate && $code !== $courseDuplicate->getCode()) {
                $dataResponse = [
                    'code' => Response::HTTP_METHOD_NOT_ALLOWED,
                    'message' => 'Курс с данным кодом уже существует в системе',
                ];
                throw new \Exception($dataResponse['message'], $dataResponse['code']);
            }

            $course->fromDtoEdit($courseDto);
            $this->getDoctrine()->getManager()->flush();

            $dataResponse = [
                'code' => Response::HTTP_OK,
                'success' => true,
            ];
        } catch (\Exception $e) {
            $dataResponse = [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        $response = new Response();
        $response->setStatusCode($dataResponse['code']);
        $response->setContent($serializer->serialize($dataResponse, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);
        return $response;
    }
}