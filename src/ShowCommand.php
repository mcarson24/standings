<?php

namespace StandingsApp;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowCommand extends Command
{
	public function configure()
	{
		$this->setName('show')
			 ->setDescription('Show the current MLB standings');
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{
		// Load standings from apc_inc

		// show all divisons standings as a table
	}
}
