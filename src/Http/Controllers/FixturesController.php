<?php
namespace Mattford\WsmOpenScoresheet\Http\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Mattford\WsmOpenScoresheet\Services\CalendarExportService;
use Slim\Views\Twig;

class FixturesController extends Controller
{
    public const SEASON = 9;
    public const DATA_FILE_PATH = '/resources/assets/season9.json';

    public function view(Request $request, Response $response)
    {
        $data = json_decode(file_get_contents(BASE_PATH . self::DATA_FILE_PATH), true);

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
        return $twig->render($response, 'fixtures.twig', ['season' => self::SEASON, 'data' => compact('locations', 'divisions')]);
    }

    public function export()
    {
        $divisionId = $_GET['division_id'] ?? 0;
        $teamId = $_GET['team_id'] ?? 0;
        $data = json_decode(file_get_contents(BASE_PATH . self::DATA_FILE_PATH), true);

        // Export an ICS file containing all the fixtures
        $exportService = new CalendarExportService();
        $locations = array_combine(array_column($data['locations'], 'id'), $data['locations']);
        $preparedEvents = [];
        foreach ($data['divisions'] as $division) {
            if ($division['id'] != $divisionId) {
                continue;
            }
            $teams = array_combine(array_column($division['teams'], 'id'), $division['teams']);
            foreach ($division['fixtures'] as $fixture) {
                if (!in_array($teamId, $fixture['teams'])) {
                    continue;
                }
                $fixture['location'] = $locations[$fixture['location']];
                $fixture['teams'] = array_map(fn($id) => $teams[$id]['name'], $fixture['teams']);

                $preparedEvents[] = $exportService->buildVEvent($fixture);
            }
        }

        $PRODID = "-//WSM Open Badminton Club//scoresheet.wsmbadminton.co.uk//EN";
        $FILENAME = "WSM_Open_Badminton_Club_Fixtures_" . date('m_Y');
        $data = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nCALSCALE:GREGORIAN\r\nPRODID:" . $PRODID . "\r\nMETHOD:PUBLISH\r\nX-WR-TIMEZONE:Europe/LONDON\r\n" . implode('', $preparedEvents) . "END:VCALENDAR\r\n";
        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $FILENAME . '.ics"');
        header('Content-Length: ' . strlen($data));
        header('Connection: close');
        header('Pragma: no-cache');
        echo $data;
        exit(0);
    }
}