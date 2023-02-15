<?php
namespace Mattford\WsmOpenScoresheet\Http\Controllers;

use Mattford\WsmOpenScoresheet\Services\ScoresheetGeneratorService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class GenerateScoresheetController extends Controller
{
    public function generate(Request $request, Response $response)
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
        if (!empty($errors)) {
            $twig = Twig::fromRequest($request);
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
        $service = new ScoresheetGeneratorService($teams);
        $file = $service->generate();
        $fileName = sprintf('Scoresheet_%s_%s_%s.xlsx', date('Ymd'), $teams[0]['name'], $teams[1]['name']);

        return $this->fileResponse($response, $file, $fileName);
    }
}