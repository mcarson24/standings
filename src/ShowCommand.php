<?php

namespace StandingsApp;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Symfony\Component\Console\Helper\Table;
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
		$teams = $this->database->fetchAll('teams');

		if (! $this->updatedWithinTheLastDay()) {
			// Load standings from api
			$teams = $this->fetchStandings();
			
			// trim out unwanted information from array
			$teams = $this->truncate($teams);

			// $this->storeData($teams);
		}

		// show all standings as a table
		$table = new Table($output);

		$table->setHeaders([
			'Pos', 'W', 'L', 'City', 'Name', 'Team ID', 'GB', 'L-10', 'Lg', 'Div', 'W%', 'GP'
		])
			 ->setRows($teams)
			 ->render();

	}

	private function fetchStandings()
	{
		$client = new Client;

		$response = json_decode($client->request('GET', 'https://erikberg.com/mlb/standings.json')->getBody(), true);

		return $response['standing'];
	}

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
	}

	private function updatedWithinTheLastDay()
	{
		return Carbon::now()->diffInDays(Carbon::parse($this->database->lastUpdate())) < 1;
	}
}
