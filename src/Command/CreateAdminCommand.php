<?php

namespace App\Command;

use App\Entity\Customer;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Create an admin user')]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Admin email address')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Admin password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email    = $input->getOption('email');
        $password = $input->getOption('password');

        if (!$email || !$password) {
            $output->writeln('<error>Both --email and --password are required.</error>');
            return Command::FAILURE;
        }

        if ($this->userRepository->findOneByEmail($email) !== null) {
            $output->writeln("<error>Email {$email} is already registered.</error>");
            return Command::FAILURE;
        }

        $admin = new Customer();
        $admin->setEmail($email)
            ->setFirstName('Admin')
            ->setLastName('User')
            ->setPhone('+10000000000')
            ->setPassword($this->passwordHasher->hashPassword($admin, $password))
            ->setRoles(['ROLE_ADMIN']);

        $this->em->persist($admin);
        $this->em->flush();

        $output->writeln("<info>Admin created: {$admin->getId()}</info>");

        return Command::SUCCESS;
    }
}
