<?php
namespace Mattford\WsmOpenScoresheet\Http\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class FixturesController extends Controller
{
    public function view(Request $request, Response $response)
    {
        $data = json_decode(file_get_contents(BASE_PATH . '/resources/assets/season8.json'), true);

        $locations = [];
        foreach ($data['locations'] as $location) {
            $locations[$location['id']] = $location['name'];
        }
        $divisions = array_map(function ($division) {
            $teamNames = [];
            foreach ($division['teams'] as $team) {
                $teamNames[$team['id']] = $team['name'] . ' (' . implode(', ', $team['players']) . ')';
            }
            $division['fixtures'] = array_map(function ($fixture) use ($teamNames) {
                $dt = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $fixture['date']);
                $fixture['dt'] = $dt;
                $fixture['date'] = $dt->format('d/m/Y g:i A');
                $fixture['location'] = $fixture['location'] === 1 ? 'HALC' : 'WHA';
                $fixture['team_names'] = array_map(fn($id) => $teamNames[$id], $fixture['teams']);
                return $fixture;
            }, $division['fixtures']);
            usort($division['fixtures'], fn($a, $b) => $a['dt'] <=> $b['dt']);
            return $division;
        }, $data['divisions']);
        $twig = Twig::fromRequest($request);
        return $twig->render($response, 'fixtures.twig', ['data' => compact('locations', 'divisions')]);
    }

}