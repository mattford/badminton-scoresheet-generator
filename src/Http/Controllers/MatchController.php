<?php
namespace Mattford\WsmOpenScoresheet\Http\Controllers;

use Mattford\WsmOpenScoresheet\Services\GameGeneratorService;
use Mattford\WsmOpenScoresheet\Services\ResultImageGeneratorService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class MatchController extends Controller
{
    public function view(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $rules = [
            'team_1_name' => 'required|min:1',
            'team_1_players' => 'required|min:3',
            'team_2_name' => 'required|min:1',
            'team_2_players' => 'required|min:3',
            'team_1_players.0' => 'string|min:1',
            'team_1_players.1' => 'string|min:1',
            'team_1_players.2' => 'string|min:1',
            'team_2_players.0' => 'string|min:1',
            'team_2_players.1' => 'string|min:1',
            'team_2_players.2' => 'string|min:1',
        ];
        $messages = [
            'team_1_name.required' => 'The Home team name is required',
            'team_1_name.min' => 'The Home team name is required',
            'team_2_name.required' => 'The Away team name is required',
            'team_2_name.min' => 'The Away team name is required',
            'team_1_players.0.required' => 'The player name is required',
            'team_1_players.1.required' => 'The player name is required',
            'team_1_players.2.required' => 'The player name is required',
            'team_1_players.0.min' => 'The player name is required',
            'team_1_players.1.min' => 'The player name is required',
            'team_1_players.2.min' => 'The player name is required',
            'team_2_players.0.required' => 'The player name is required',
            'team_2_players.1.required' => 'The player name is required',
            'team_2_players.2.required' => 'The player name is required',
            'team_2_players.0.min' => 'The player name is required',
            'team_2_players.1.min' => 'The player name is required',
            'team_2_players.2.min' => 'The player name is required',
        ];

        $errors = $this->validate($data, $rules, $messages);
        $twig = Twig::fromRequest($request);
        if (!empty($errors)) {
            return $twig->render($response, 'default.twig', [
                'errors' => $errors,
                'data' => $data,
            ]);
        }
        $teams = [
            [
                'name' => $data['team_1_name'],
                'players' => $data['team_1_players'],
            ],
            [
                'name' => $data['team_2_name'],
                'players' => $data['team_2_players']
            ]
        ];
        $games = GameGeneratorService::generateGames($teams);
        $data = array_merge($data, compact('teams', 'games'));
        return $twig->render($response, 'game.twig', [
            'data' => $data,
        ]);
    }

    public function generateResult(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $rules = [
            'team_1_name' => 'required|min:1',
            'team_1_players' => 'required|min:3',
            'team_2_name' => 'required|min:1',
            'team_2_players' => 'required|min:3',
            'team_1_players.0' => 'string|min:1',
            'team_1_players.1' => 'string|min:1',
            'team_1_players.2' => 'string|min:1',
            'team_2_players.0' => 'string|min:1',
            'team_2_players.1' => 'string|min:1',
            'team_2_players.2' => 'string|min:1',
            'scores' => 'required|min:9',
        ];
        $messages = [
            'team_1_name.required' => 'The Home team name is required',
            'team_1_name.min' => 'The Home team name is required',
            'team_2_name.required' => 'The Away team name is required',
            'team_2_name.min' => 'The Away team name is required',
            'team_1_players.0.required' => 'The player name is required',
            'team_1_players.1.required' => 'The player name is required',
            'team_1_players.2.required' => 'The player name is required',
            'team_1_players.0.min' => 'The player name is required',
            'team_1_players.1.min' => 'The player name is required',
            'team_1_players.2.min' => 'The player name is required',
            'team_2_players.0.required' => 'The player name is required',
            'team_2_players.1.required' => 'The player name is required',
            'team_2_players.2.required' => 'The player name is required',
            'team_2_players.0.min' => 'The player name is required',
            'team_2_players.1.min' => 'The player name is required',
            'team_2_players.2.min' => 'The player name is required',
            'scores.required' => 'Scores are required',
            'scores.min' => 'Scores are required',
        ];

        $errors = $this->validate($data, $rules, $messages);
        $twig = Twig::fromRequest($request);
        $teams = [
            [
                'name' => $data['team_1_name'],
                'players' => $data['team_1_players'],
            ],
            [
                'name' => $data['team_2_name'],
                'players' => $data['team_2_players']
            ]
        ];
        $gameList = GameGeneratorService::generateGames($teams);
        if (!empty($errors)) {
            $teams = [
                [
                    'name' => $data['team_1_name'],
                    'players' => $data['team_1_players'],
                ],
                [
                    'name' => $data['team_2_name'],
                    'players' => $data['team_2_players']
                ]
            ];
            $games = $gameList;
            $data = array_merge($data, compact('teams', 'games'));
            return $twig->render($response, 'game.twig', [
                'errors' => $errors,
                'data' => $data,
            ]);
        }

        $finalScores = [0, 0];
        foreach ($data['scores'] as [$home, $away]) {
            $idx = $home > $away ? 0 : 1;
            $finalScores[$idx]++;
        }

        $input = [
            'teams' => [$data['team_1_name'], $data['team_2_name']],
            'scores' => $finalScores,
            'games' => array_map(function ($players, $scores) {
                return array_merge($players[0], $scores, $players[1]);
            }, $gameList, $data['scores']),
        ];
        header('Content-Type: image/png');
        $imageService = new ResultImageGeneratorService();
        $imageService->generate($input);

        exit();
    }
}