<?php

namespace StandingsApp;

use Symfony\Component\Console\Command\Command as SymfonyCommand;

class Command extends SymfonyCommand
{
	protected $database;

	public function __construct(DatabaseAdapter $database)
	{
		$this->database = $database;

		parent::__construct();
	}
}
