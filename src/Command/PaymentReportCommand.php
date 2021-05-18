<?php


namespace App\Command;

use App\Entity\Transaction;
use App\Entity\User;
use App\Service\Twig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PaymentReportCommand extends Command
{
    private $twig;
    private $mailer;
    private $manager;

    protected static $defaultName = 'payment:report';
    public function __construct(Twig $twig, MailerInterface $mailer, EntityManagerInterface $manager)
    {
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->manager = $manager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'email',
                null,
                'Адрес пользователя',
                'user@mail.ru'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var User $user */
        $user = $this->manager->getRepository(User::class)->findOneBy([
            'email' => $input->getArgument('email'),
        ]);

        $transactions = $this->manager->getRepository(Transaction::class)->findPaidCoursesAtMonth($user);

        if ($transactions !== []) {
            $endDate = (new \DateTime())->format('Y-m-d');
            $startDate = (new \DateTime())->modify('-1 month')->format('Y-m-d');

            $total = 0;
            foreach ($transactions as $transaction) {
                $total += $transaction['sum'];
            }

            $html = $this->twig->render(
                'email/amountReportAtMonth.html.twig',
                [
                    'transactions' => $transactions,
                    'total' => $total,
                    'endDate' => $endDate,
                    'startDate' => $startDate,
                ]
            );

            $message = (new Email())
                ->to($input->getArgument('email'))
                ->from('admin@mail.ru')
                ->subject('Отчет по данным об оплаченных курсах за месяц')
                ->html($html);

            try {
                $this->mailer->send($message);
            } catch (TransportExceptionInterface $e) {
                $output->writeln($e->getMessage());

                $output->writeln('Ошибка');
                return Command::FAILURE;
            }
        }

        $output->writeln('Отчет успешно сформирован');
        return Command::SUCCESS;
    }
}