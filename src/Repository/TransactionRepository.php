<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    /**
     * @return Transaction[] Returns an array of Transaction objects
     */
    public function findTransactionsByFilter(
        User $user,
        Request $request,
        CourseRepository $courseRepository
    ): array {
        $types = [
            'payment' => 1,
            'deposit' => 2,
        ];

        $type = $request->query->get('type');
        $courseCode = $request->query->get('course_code');
        $skipExpired = $request->query->get('skip_expired');

        $query = $this->createQueryBuilder('t')
            ->andWhere('t.userBilling = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('t.createdAt', 'DESC');

        if ($type) {
            $numberType = $types[$type];
            $query->andWhere('t.typeOperation = :type')
                ->setParameter('type', $numberType);
        }
        if ($courseCode) {
            $course = $courseRepository->findOneBy(['code' => $courseCode]);
            $value = $course ? $course->getId() : null;
            $query->andWhere('t.course = :courseId')
                ->setParameter('courseId', $value);
        }
        if ($skipExpired) {
            $query->andWhere('t.expiresAt is null or t.expiresAt >= :today')
                ->setParameter('today', new DateTime());
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @return Transaction[]
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function findRentalEndingCourses(User $user): array
    {
        $connect = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT * FROM transaction t
            INNER JOIN course c ON c.id = t.course_id
            WHERE t.type_operation = 1 
            AND t.user_billing_id = :user_id 
            AND t.expires_at::date = (now()::date + '1 day'::interval)
            ORDER BY t.created_at DESC
            ";
        $stmt = $connect->prepare($sql);
        $stmt->execute([
            'user_id' => $user->getId(),
        ]);

        return $stmt->fetchAllAssociative();
    }

    /**
     * @return Transaction[]
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function findPaidCoursesAtMonth(User $user): array
    {
        $connect = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT c.title, c.course_type, count(t.course_id), sum(t.amount) 
            FROM transaction t INNER JOIN course c ON c.id = t.course_id 
            WHERE t.type_operation = 1 AND t.user_billing_id = :user_id
            AND (t.created_at::date between (now()::date - '1 month'::interval) AND now()::date) 
            GROUP BY c.title, c.course_type, t.course_id
            ";
        $stmt = $connect->prepare($sql);
        $stmt->execute([
            'user_id' => $user->getId(),
        ]);

        return $stmt->fetchAllAssociative();
    }
}
