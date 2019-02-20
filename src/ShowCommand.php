<?php

namespace StandingsApp;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowCommand extends Command
{
	protected $divisions = [
		'MLB' => 'Major League Baseball',
		'ALE' => 'American League East',
		'ALC' => 'American League Central',
		'ALW' => 'American League West',
		'NLE' => 'National League East',
		'NLC' => 'National League Central',
		'NLW' => 'National League West'
	];

	protected $tableHeaders = [
		'mlb' => ['Pos', 'Div', 'Team', 'GP', 'W', 'L', 'GB', 'W%', 'L-10'],
		'div' => ['Pos', 'Team', 'GP', 'W', 'L', 'GB', 'W%', 'L-10']
	];

	/**
	 * Configure and set up the command.
	 * 
	 * @return void
	 */
	public function configure()
	{
		$this->setName('show')
			 ->setDescription('Show the current MLB standings')
			 ->addOption('div', 'd', InputOption::VALUE_OPTIONAL, "Get standings for a single division ['ALE', 'ALC', 'ALW', 'NLE', 'NLC', 'NLW']", 'MLB');
	}

	/**
	 * Execute the command.
	 * 
	 * @param  InputInterface  $input 
	 * @param  OutputInterface $output
	 * @return void
	 */
	public function execute(InputInterface $input, OutputInterface $output)
	{	
		if (! $this->updatedWithinTheLastDay()) {
			$this->clearStandings();

			// Load standings from api
			$teams = $this->fetchStandings();
			
			// trim out unwanted information from array
			$teams = $this->truncate($teams);

			$this->storeData($teams);
		}
		
		$inputOutput = new SymfonyStyle($input, $output);

		if (!$teams = $this->fetchLeagueStandings($input->getOption('div'))) {
			$inputOutput->warning('That division does not exist.');
			exit(0);
		}

		$inputOutput->title($this->divisions[$input->getOption('div')]);
		$inputOutput->table(
			$input->getOption('div') == 'MLB' ? $this->tableHeaders['mlb'] : $this->tableHeaders['div'],
			$teams);
	}

	/**
	 * Fetch the standings from the API.
	 * 
	 * @return array
	 */
	private function fetchStandings()
	{
		$client = new Client;

		$response = json_decode($client->request('GET', 'https://erikberg.com/mlb/standings.json')->getBody(), true);
		
		return $response['standing'];
	}

	/**
	 * Remove unwanted information from the array.
	 * 
	 * @param  array $teams 
	 * @return array
	 */
	private function truncate($teams)
	{
		$desired_values = [
			'team_id',
			'first_name',
			'last_name',
			'games_played',
			'won',
			'lost',
			'rank',
			'conference',
			'division',
			'win_percentage',
			'games_back',
			'last_ten'
		];		

		return array_map(function($team) use ($desired_values) {
			return array_intersect_key($team, array_flip($desired_values));
		}, $teams);
	}

	private function storeData($teams)
	{
		array_map(function($team) {
			$this->database->query(
				"INSERT INTO teams(team_id, rank, won, lost, first_name, last_name, games_back, last_ten, conference, division, win_percentage, games_played)
				VALUES(:team_id, :rank, :won, :lost, :first_name, :last_name, :games_back, :last_ten, :conference, :division, :win_percentage, :games_played)",
				$team
			);
		}, $teams);

		$this->database->query(
			"INSERT INTO updates(updated_at)
			VALUES(:updated_at);",
			[Carbon::now()]
		);
	}

	/**
	 * Determines if the standings have been updated within
	 * the last day.
	 * 
	 * @return boolean
	 */
	private function updatedWithinTheLastDay()
	{
		return Carbon::now()->diffInDays(Carbon::parse($this->database->lastUpdate())) < 1;
	}

	/**
	 * Clears the standings data from the teams table.
	 * 
	 * @return void
	 */
	private function clearStandings()
	{
		$this->database->clearStandings();
	}

	/**
	 * Fetch the standings from the database.
	 * 
	 * @param  string $division 
	 * @return array
	 */
	private function fetchLeagueStandings($division = '')
	{
		return $this->database->fetchLeagueStandings($division);
	}
}
