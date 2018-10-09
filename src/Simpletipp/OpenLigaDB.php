<?php

namespace Simpletipp;

class OpenLigaDB
{
    const OPENLIGA_DB_API_URL = 'https://www.openligadb.de/api';

    public static function getMatchGoals(string $shortcut, string $saison, string $matchId)
    {
        return static::call("/getmatchdata", [$shortcut, $saison, $matchId]);
    }

    public static function getMatches(string $shortcut, string $saison)
    {
        return static::call("/getmatchdata", [$shortcut, $saison]);
    }

    public static function getLeagueTeams(string $shortcut, string $saison)
    {
        return static::call("/getavailableteams", [$shortcut, $saison]);
    }

    public static function getLastChangeByMatchDay(string $shortcut, string $saison, string $groupId)
    {
        return static::call("/getlastchangedate", [$shortcut, $saison, $groupId]);
    }

    public static function getTeamsEncounters(string $shortcut, string $saison, string $teamId1, string $teamId2)
    {
        return static::call("/getmatchdata", [$shortcut, $saison, $teamId1, $teamId2]);
    }

    public static function getGoalGetters(string $shortcut, string $saison)
    {
        return static::call("/getgoalgetters", [$shortcut, $saison]);
    }

    public static function getHighscore(string $shortcut, string $saison)
    {
        return static::call("/getbltable", [$shortcut, $saison]);
    }

    public static function getLeagueData(string $shortcut, string $saison)
    {
        $result = static::call("/getmatchdata", [$shortcut, $saison]);
        if (is_array($result) && count($result) > 0) {
            return (Object) [
                "id" => $result[0]['LeagueId'],
                "name" => $result[0]['LeagueName'],
            ];
        }
        return null;
    }

    private function call(string $apiRoute, array $params)
    {
        $apiRoute .= is_array($params) ? "/" . implode("/", $params) : "";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, static::OPENLIGA_DB_API_URL . $apiRoute);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($output, true);
        return $result;
    }
}
