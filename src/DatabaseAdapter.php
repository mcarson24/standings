<?php

namespace StandingsApp;

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

	public function fetchLeagueStandings()
	{
		return $this->connection->query("
			SELECT 
				rank AS Pos,
				conference || division AS Div,
				first_name || ' ' || last_name AS Team,
				games_played AS GP,
				won AS W,
				lost AS L,
				games_back as GB,
				printf('%.3f', win_percentage) AS 'W%',
				last_ten AS 'L-10'
		 	FROM teams"
		 )->fetchAll();
	}

	public function lastUpdate()
	{
		return $this->connection->query("SELECT updated_at
			FROM updates
			ORDER BY id DESC
			LIMIT 1;
		")->fetchColumn();
	}

	public function query($sql, $parameters)
	{
		$this->connection->prepare($sql)->execute($parameters);
	}
}
