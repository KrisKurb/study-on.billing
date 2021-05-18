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

class PaymentEndingNotificationCommand extends Command
{
    private $twig;
    private $mailer;
    private $manager;

    protected static $defaultName = 'payment:ending:notification';
    public function __construct(Twig $twig, MailerInterface $mailer, EntityManagerInterface $manager)
    {
        parent::__construct();
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->manager = $manager;
    }

    /**
     * @throws \Twig\Error\SyntaxError
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\LoaderError
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->manager->getRepository(User::class)->findAll();

        foreach ($users as $user) {
            $transactions = $this->manager->getRepository(Transaction::class)->findRentalEndingCourses($user);

            if ($transactions !== []) {
                $html = $this->twig->render(
                    'email/endRentCourse.html.twig',
                    ['transactions' => $transactions]
                );

                $message = (new Email())
                    ->to($user->getUserName())
                    ->from('admin@mail.ru')
                    ->subject('Уведомление об окончании срока аренды')
                    ->html($html);

                try {
                    $this->mailer->send($message);
                } catch (TransportExceptionInterface $e) {
                    $output->writeln($e->getMessage());

                    $output->writeln('Ошибка');
                    return Command::FAILURE;
                }
            }
        }

        $output->writeln('Оповещения отправлены пользователям');
        return Command::SUCCESS;
    }
}