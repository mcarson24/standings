<?php

namespace StandingsApp;

class DatabaseAdapter
{
	protected $connection;

	public function __construct(\PDO $connection)
	{
		$this->connection = $connection;
	}

	// public function store($table, $teams)
	// {
	// 	$this->connection->query(
	// 		'INSERT INTO teams(team_id, rank, won, lost, first_name, last_name, games_back, last_ten, conference, division, win_percentage, games_played)
	// 		VALUES(:team_id, :rank, :won, :lost, :first_name, :last_name, :games_back, :last_ten, :conference, :division, :win_percentage, :games_played)',
	// 		$teams
	// 	);
	// }

	public function fetchAll($table)
	{
		return $this->connection->query("SELECT * FROM {$table}")->fetchAll();
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
