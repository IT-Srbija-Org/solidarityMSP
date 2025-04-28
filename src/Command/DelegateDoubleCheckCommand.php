<?php

namespace App\Command;

use App\Entity\DamagedEducator;
use App\Entity\DamagedEducatorPeriod;
use App\Entity\School;
use App\Entity\UserDelegateSchool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'app:send-email-to-delegate',
    description: 'Send email to delegate to double check educators',
)]
class DelegateDoubleCheckCommand extends Command
{
    private int $lastId = 0;

    public function __construct(private EntityManagerInterface $entityManager, private MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->section('Command started at '.date('Y-m-d H:i:s'));

        $items = $this->getItems1();
        $output->writeln('Total: '.count($items));

        foreach ($items as $item) {
            $userDelegate = $this->getDelagete($item->getSchool());

            $output->writeln('Send email to: '.$userDelegate->getUser()->getEmail().' for educator '.$item->getName().' at '.date('Y-m-d H:i:s'));
            $this->sendEmail($userDelegate->getUser()->getEmail(), $item);
            $this->deleteDamagedEducator($item);
        }

        $items = $this->getItems2();
        $output->writeln('Total: '.count($items));

        foreach ($items as $item) {
            $userDelegate = $this->getDelagete($item->getSchool());

            if (!empty($userDelegate)) {
                $output->writeln('Send email to: '.$userDelegate->getUser()->getEmail().' for educator '.$item->getName().' at '.date('Y-m-d H:i:s'));
                $this->sendEmail($userDelegate->getUser()->getEmail(), $item);
            }

            $this->deleteDamagedEducator($item);
        }

        $io->success('Command finished at '.date('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }

    public function getDelagete(School $school)
    {
        return $this->entityManager->getRepository(UserDelegateSchool::class)
            ->createQueryBuilder('e')
            ->where('e.school = :school')
            ->setParameter(':school', $school)
            ->getQuery()
            ->getSingleResult();
    }

    public function deleteDamagedEducator(DamagedEducator $damagedEducator)
    {
        $entity = $this->entityManager->getRepository(DamagedEducator::class)->find($damagedEducator->getId());
        $entity->setStatus(DamagedEducator::STATUS_DELETED);
        $entity->setStatusComment('Automatski obrisan zbog neispravnog broja računa.');

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function sendEmail(string $email, DamagedEducator $damagedEducator): void
    {
        $message = (new TemplatedEmail())
            ->to($email)
            ->subject('Problem sa podacima o oštećinima')
            ->htmlTemplate('email/delegate_educator_problems.html.twig')
            ->context(['damagedEducator' => $damagedEducator]);

        try {
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }

    public function getItems1(): array
    {
        $period = $this->entityManager->getRepository(DamagedEducatorPeriod::class)->findOneBy([
            'month' => 2,
            'year' => 2025,
            'type' => DamagedEducatorPeriod::TYPE_SECOND_HALF,
        ]);

        return $this->entityManager->getRepository(DamagedEducator::class)
            ->createQueryBuilder('e')
            ->where('e.period = :period')
            ->setParameter(':period', $period)
            ->andWhere('e.accountNumber LIKE :prefix')
            ->setParameter('prefix', '150%')
            ->getQuery()
            ->getResult();
    }

    public function getItems2(): array
    {
        $period = $this->entityManager->getRepository(DamagedEducatorPeriod::class)->findOneBy([
            'month' => 2,
            'year' => 2025,
            'type' => DamagedEducatorPeriod::TYPE_SECOND_HALF,
        ]);

        return $this->entityManager->getRepository(DamagedEducator::class)
            ->createQueryBuilder('e')
            ->where('e.period = :period')
            ->setParameter(':period', $period)
            ->andWhere('e.accountNumber LIKE :prefix')
            ->setParameter('prefix', '840%')
            ->getQuery()
            ->getResult();
    }
}
