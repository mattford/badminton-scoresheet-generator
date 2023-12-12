<?php
namespace Mattford\WsmOpenScoresheet\Services;

class GameGeneratorService
{
    private const DEFAULT_PATTERN = [
        [[1, 2], [1, 2]],
        [[1, 3], [1, 3]],
        [[2, 3], [2, 3]],
        [[1, 2], [1, 3]],
        [[1, 3], [1, 2]],
        [[2, 3], [2, 1]],
        [[2, 1], [2, 3]],
        [[3, 1], [3, 2]],
        [[3, 2], [3, 1]],
    ];

    public static function generateGames(array $teams, array $pattern = self::DEFAULT_PATTERN): array
    {
        $games = [];
        foreach ($pattern as $gamePattern) {
            $game = [];
            foreach ($gamePattern[0] as $homePlayerNumber) {
                $game[0][] = $teams[0]['players'][$homePlayerNumber-1];
            }
            foreach ($gamePattern[1] as $awayPlayerNumber) {
                $game[1][] = $teams[1]['players'][$awayPlayerNumber-1];
            }
            $games[] = $game;
        }
        return $games;
    }
}