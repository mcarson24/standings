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

	public function store($table, $teams)
	{
		$this->connection->query(
			'INSERT INTO teams(team_id, rank, won, lost, first_name, last_name, games_back, last_ten, conference, division, win_percentage, games_played)
			VALUES(:team_id, :rank, :won, :lost, :first_name, :last_name, :games_back, :last_ten, :conference, :division, :win_percentage, :games_played)',
			$teams
		);
	}

	public function fetchAll($table)
	{
		return $this->connection->query("SELECT * FROM {$table}")->fetchAll();
	}

	public function fetchLeagueStandings($division = '')
	{
		$sql = "SELECT rank AS Pos, ";

		if ($division == 'MLB') $sql .= "conference || division AS Div, ";

		$sql .= "first_name || ' ' || last_name AS Team,
				games_played AS GP,
				won AS W,
				lost AS L,
				games_back as GB,
				printf('%.3f', win_percentage) AS 'W%',
				last_ten AS 'L-10'
		 		FROM teams";

		if ($division != 'MLB') {
			$sql .= " WHERE conference || division == '{$division}'";
		}
		if ($division != 'MLB') {
			$sql .= " ORDER BY games_back";
		}
		if ($division == 'MLB') {
			$sql .= " ORDER BY win_percentage DESC";
		}

		return $this->connection->query($sql)->fetchAll();
	}

	public function lastUpdate()
	{
		return $this->connection->query("SELECT updated_at
			FROM updates
			ORDER BY id DESC
			LIMIT 1;
		")->fetchColumn();
	}

	public function clearStandings()
	{
		$this->query("DELETE FROM teams WHERE team_id > 'aaa'");
	}

	public function query($sql, $parameters = [])
	{
		$this->connection->prepare($sql)->execute($parameters);
	}
}
