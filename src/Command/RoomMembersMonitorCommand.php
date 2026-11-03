<?php

namespace App\Command;

use App\Service\RoomMemberStatusChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:room-members:monitor',
    description: 'Monitors online status of room members'
)]
class RoomMembersMonitorCommand extends Command
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
        $output->writeln('Room members monitor started.');

        while (true) {
            $result = $this->checker->check();

            if ($result['members'] > 0) {
                $output->writeln(sprintf(
                    'Updated %d members in %d rooms.',
                    $result['members'],
                    $result['rooms']
                ));
            }

            sleep(10);
        }

        return Command::SUCCESS;
    }
}
