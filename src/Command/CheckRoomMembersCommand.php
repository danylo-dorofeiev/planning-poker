<?php

namespace App\Command;

use App\Service\RoomMemberStatusChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:room-members:check',
    description: 'Checks online status of room members'
)]
class CheckRoomMembersCommand extends Command
{
    public function __construct(
        private readonly RoomMemberStatusChecker $checker,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $result = $this->checker->check();

        if ($result['members'] > 0) {
            $output->writeln(sprintf(
                'Updated %d members in %d rooms.',
                $result['members'],
                $result['rooms']
            ));
        }

        return Command::SUCCESS;
    }
}
