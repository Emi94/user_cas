<?php

namespace OCA\UserCAS\Command;

use OCA\UserCAS\Service\LoggingService;
use OCA\UserCAS\Service\UserService;

use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OC\Files\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;


/**
 * Class CreateUser
 *
 * @package OCA\UserCAS\Command
 *
 * @author Felix Rupp <kontakt@felixrupp.com>
 * @copyright Felix Rupp <kontakt@felixrupp.com>
 *
 * @since 1.7.0
 */
class CreateUser extends Command
{

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var IUserManager
     */
    protected $userManager;

    /**
     * @var IGroupManager
     */
    protected $groupManager;

    /**
     * @var IMailer
     */
    protected $mailer;


    /**
     * @param UserService $userService
     * @param LoggingService $loggingService
     * @param IUserManager $userManager
     * @param IGroupManager $groupManager
     * @param IMailer $mailer
     */
    public function __construct(UserService $userService, IUserManager $userManager, IGroupManager $groupManager, IMailer $mailer)
    {
        parent::__construct();

        $this->userService = $userService;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->mailer = $mailer;
    }


    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('usercas:create-user')
            ->setDescription('adds a user')
            ->addArgument(
                'uid',
                InputArgument::REQUIRED,
                'User ID used to login (must only contain a-z, A-Z, 0-9, -, _ and @).'
            )
            ->addOption(
                'display-name',
                null,
                InputOption::VALUE_OPTIONAL,
                'User name used in the web UI (can contain any characters).'
            )
            ->addOption(
                'email',
                null,
                InputOption::VALUE_OPTIONAL,
                'Email address for the user.'
            )
            ->addOption(
                'group',
                'g',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'The groups the user should be added to (The group will be created if it does not exist).'
            )
            ->addOption(
                'quota',
                'q',
                InputOption::VALUE_OPTIONAL,
                'The quota the user should get either as numeric value in bytes or as a human readable string (e.g. 1G for 1 Gigabyte)'
            )
            ->addOption(
                'enabled',
                'e',
                InputOption::VALUE_OPTIONAL,
                'Set user enabled'
            );
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $uid = $input->getArgument('uid');
        if ($this->userManager->userExists($uid)) {
            $output->writeln('<error>The user "' . $uid . '" already exists.</error>');
            return 1;
        }

        // Validate email before we create the user
        if ($input->getOption('email')) {
            // Validate first
            if (!$this->mailer->validateMailAddress($input->getOption('email'))) {
                // Invalid! Error
                $output->writeln('<error>Invalid email address supplied</error>');
                return 1;
            } else {
                $email = $input->getOption('email');
            }
        } else {
            $email = null;
        }

        # Register Backend
        $this->userService->registerBackend();

        /**
         * @var IUser
         */
        $user = $this->userService->create($uid);

        if ($user instanceof IUser) {

            $output->writeln('<info>The user "' . $user->getUID() . '" was created successfully</info>');
        } else {

            $output->writeln('<error>An error occurred while creating the user</error>');
            return 1;
        }

        # Set displayName
        if ($input->getOption('display-name')) {

            $user->setDisplayName($input->getOption('display-name'));
            $output->writeln('Display name set to "' . $user->getDisplayName() . '"');
        }

        # Set email if supplied & valid
        if ($email !== null) {

            $user->setEMailAddress($email);
            $output->writeln('Email address set to "' . $user->getEMailAddress() . '"');
        }

        # Set Groups
        $groups = $input->getOption('group');

        if (!empty($groups)) {

            // Make sure we init the Filesystem for the user, in case we need to
            // init some group shares.
            Filesystem::init($user->getUID(), '');
        }

        foreach ($groups as $groupName) {

            $group = $this->groupManager->get($groupName);
            if (!$group) {
                $this->groupManager->createGroup($groupName);
                $group = $this->groupManager->get($groupName);
                $output->writeln('Created group "' . $group->getGID() . '"');
            }
            $group->addUser($user);
            $output->writeln('User "' . $user->getUID() . '" added to group "' . $group->getGID() . '"');
        }

        # Set Quota
        $quota = $input->getOption('quota');

        if(!empty($quota)) {

            if(is_numeric($quota)) {

                $quota = \OCP\Util::humanFileSize(intval($quota));
            }

            $this->userService->updateQuota($user, FALSE, $quota);
            $output->writeln('Quota set to "' . $user->getQuota() . '"');
        }

        # Set enabled
        $enabled = $input->getOption('enabled');

        if (!empty($enabled)) {

            $user->setEnabled(boolval($enabled));
            $output->writeln('Enabled set to "' . $user->isEnabled() . '"');
        }
    }
}