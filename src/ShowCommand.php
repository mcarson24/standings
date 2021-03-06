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
	/**
	 * The accepted divisions.
	 * 
	 * @var array
	 */
	protected $divisions = [
		'MLB' => 'Major League Baseball',
		'AL'  => 'American League',
		'NL'  => 'National League',
		'ALE' => 'American League East',
		'ALC' => 'American League Central',
		'ALW' => 'American League West',
		'NLE' => 'National League East',
		'NLC' => 'National League Central',
		'NLW' => 'National League West'
	];

	/**
	 * The table headers.
	 * 
	 * @var array
	 */
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
			 ->addOption('div', 'd', InputOption::VALUE_OPTIONAL, "Get standings for a single division or league ['AL', 'NL', ALE', 'ALC', 'ALW', 'NLE', 'NLC', 'NLW']", 'MLB');
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
		$inputOutput = new SymfonyStyle($input, $output);
		$division = strtoupper($input->getOption('div'));

		if (! $this->updatedWithinLastHour()) {
			$this->database->clearStandings();

			$this->database->storeData($this->fetchStandingsFromAPI());
		}

		if (! $this->isAValidDivision($division)) {
			$inputOutput->warning("That division does not exist. Fetching MLB standings...");
			$division = 'MLB';
		}

		$inputOutput->title($this->divisions[$division]);

		$inputOutput->table(
			in_array($division, ['MLB', 'AL', 'NL']) ? $this->tableHeaders['mlb'] : $this->tableHeaders['div'],
			$this->fetchLeagueStandings($division)
		);
	}

	/**
	 * Fetch the standings from the API.
	 * 
	 * @return array
	 */
	private function fetchStandingsFromAPI()
	{
		$response = json_decode((new Client)->request('GET', 'https://erikberg.com/mlb/standings.json')->getBody(), true);
		
		return $this->truncate($response['standing']);
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

	/**
	 * Determines if the database has been updated 
	 * within the last day.
	 * 
	 * @return boolean
	 */
	private function updatedWithinLastHour()
	{
		return Carbon::now()->diffInHours(Carbon::parse($this->database->lastUpdate())) < 1;
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

	/**
	 * Determine if the desired division is valid.
	 * 
	 * @param  string  $division 
	 * @return boolean           
	 */
	private function isAValidDivision($division)
	{
		return array_key_exists($division, $this->divisions);
	}
}
