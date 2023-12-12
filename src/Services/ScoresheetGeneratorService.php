<?php
namespace Mattford\WsmOpenScoresheet\Services;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ScoresheetGeneratorService
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

    private const HOME_TEAM_NAME_CELL = 'B8';
    private const AWAY_TEAM_NAME_CELL = 'H8';
    private const MATCH_START_ROW = 11;
    private const HOME_TEAM_START_COL = 'D';
    private const AWAY_TEAM_START_COL = 'I';

    public function __construct(
        private array $teams,
        private array $pattern = self::DEFAULT_PATTERN,
    ) {}

    public function generate(): string
    {
        $teams = $this->teams;
        $spreadsheet = IOFactory::load(BASE_PATH . '/resources/assets/base.xlsx');
        $worksheet = $spreadsheet->getSheet(0);

        $worksheet->getCell(self::HOME_TEAM_NAME_CELL)->setValue($teams[0]['name']);
        $worksheet->getCell(self::AWAY_TEAM_NAME_CELL)->setValue($teams[1]['name']);

        $games = GameGeneratorService::generateGames($teams, $this->pattern);

        $row = self::MATCH_START_ROW;
        foreach ($games as $game) {
            $col = self::HOME_TEAM_START_COL;
            foreach ($game[0] as $homePlayer) {
                $worksheet->getCell("$col$row")->setValue($homePlayer);
                $col = Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString($col)+1);
            }
            $col = self::AWAY_TEAM_START_COL;
            foreach ($game[1] as $awayPlayer) {
                $worksheet->getCell("$col$row")->setValue($awayPlayer);
                $col = Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString($col)+1);
            }
            $row++;
        }

        $resource = fopen('php://temp', 'w');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($resource);

        return stream_get_contents($resource, null, 0);
    }
}