<?php

namespace StandingsApp;

use Carbon\Carbon;

class DatabaseAdapter
{
	protected $connection;

	public function __construct(\PDO $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Fetch the data from the teams table.
	 * 
	 * @param  string $division 
	 * @return array           
	 */
	public function fetchLeagueStandings($division = '')
	{
		$sql = "SELECT rank AS Pos, ";

		if (! $this->wantsDivisionData($division)) $sql .= "conference || division AS Div, ";

		$sql .= "first_name || ' ' || last_name AS Team,
				games_played AS GP,
				won AS W,
				lost AS L,
				games_back as GB,
				printf('%.3f', win_percentage) AS 'W%',
				last_ten AS 'L-10'
		 		FROM teams ";

		if ($this->wantsLeagueData($division)) {
			$sql .= "WHERE conference || division LIKE '{$division}%' ";
		}
		if ($this->wantsDivisionData($division)) {
			$sql .= "WHERE conference || division == '{$division}' 
					 ORDER BY games_back ";
		}
		if (! $this->wantsDivisionData($division)) {
			$sql .= "ORDER BY win_percentage DESC";
		}

		return $this->connection->query($sql)->fetchAll();
	}

	/**
	 * Store new data in the teams table. And set updated_at attribute.
	 * 
	 * @param  array $teams 
	 * @return void        
	 */
	public function storeData($teams)
	{
		array_map(function($team) {
			$this->query(
				"INSERT INTO teams(team_id, rank, won, lost, first_name, last_name, games_back, last_ten, conference, division, win_percentage, games_played)
				VALUES(:team_id, :rank, :won, :lost, :first_name, :last_name, :games_back, :last_ten, :conference, :division, :win_percentage, :games_played)",
				$team
			);
		}, $teams);

		$this->query(
			"INSERT INTO updates(updated_at)
			VALUES(:updated_at);",
			[Carbon::now()]
		);
	}

	/**
	 * Determine the last time the teams table
	 * was updated.
	 * 
	 * @return string
	 */
	public function lastUpdate()
	{
		return $this->connection->query("SELECT updated_at
			FROM updates
			ORDER BY id DESC
			LIMIT 1;
		")->fetchColumn();
	}

	/**
	 * Clear the standings data from the table, so new
	 * data can replace it.
	 * 
	 * @return void
	 */
	public function clearStandings()
	{
		$this->query("DELETE FROM teams WHERE team_id > 'aaa'");
	}

	/**
	 * Run a SQL query without returning 
	 * any data.
	 * 
	 * @param  string $sql        
	 * @param  array  $parameters 
	 * @return void             
	 */
	public function query($sql, $parameters = [])
	{
		$this->connection->prepare($sql)->execute($parameters);
	}

	/**
	 * Determines if the user wants data for a league.
	 * 
	 * @param  string $division 
	 * @return boolean           
	 */
	private function wantsLeagueData($division)
	{
		return in_array($division, ['AL', 'NL']);
	}

	/**
	 * Determines if the user wants data for a division.
	 * 
	 * @param  string $division 
	 * @return boolean           
	 */
	private function wantsDivisionData($division)
	{
		return in_array($division, ['ALE', 'ALC', 'ALW', 'NLE', 'NLC', 'NLW']);
	}
}
