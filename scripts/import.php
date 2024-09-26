<?php
require_once('../vendor/autoload.php');
if (count($argv) < 3) {
    echo "Usage: php import.php <path-to-excel> <output-path>\n";
    exit(1);
}

[, $inputPath, $outputPath] = $argv;

$locations = [
    'HALC' => [
        'id' => 1,
        "name" => "Health and Active Living Skills Centre",
        "geolocation" => [51.32240956437253, -2.968114090631636]
    ],
    'WHA' => [
        'id' => 2,
        "name" => "Winterstoke Hundred Academy",
        "geolocation" => [51.34183363118676, -2.9341558447081884]
    ],
    'Priory' => [
        'id' => 3,
        "name" => "Priory School",
        "geolocation" => [51.36322795511998, -2.9096256007781056],
    ],
    'HANS' => [
        'id' => 4,
        "name" => "Hans Price Academy",
        "geolocation" => [51.33820538704243, -2.9640120872850546],
    ],
    'Lock P' => [
        'id' => 5,
        "name" => "Locking Parklands",
        "geolocation" => [51.33707521248316, -2.9108037314613693],
    ]
];

$divisions = [];

$cols = ['Day', 'Date', 'Time', 'Venue', 'Div', null, 'Team 1', null, null, null, 'Team 2'];

$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputPath);
$startRow = 6;
// loop through rows and gather up all the games
foreach ($spreadsheet->getActiveSheet()->getRowIterator($startRow) as $row) {
    $cellIterator = $row->getCellIterator();
    $data = [];
    $colIndex = 0;
    foreach ($cellIterator as $cell) {
        if (!empty($cols[$colIndex])) {
            $data[$cols[$colIndex]] = trim($cell->getValue());
        }
        $colIndex++;
    }
    if (empty($data['Team 1'])) {
        continue;
    }
    $division = $data['Div'];
    if (!isset($divisions[$division])) {
        $divisions[$division] = [
            'id' => count($divisions),
            'name' => 'Division ' . $division,
            'teams' => [],
            'fixtures' => [],
        ];
    }
    $when = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((int)$data['Date']);
    $hour = (int) $data['Time'];
    $when->setTime($hour + 12, 0);
    $game = [];
    $game['date'] = $when->format('Y-m-d\TH:i:s\Z');
    $game['teams'] = [$data['Team 1'], $data['Team 2']];
    foreach ($game['teams'] as $idx => $team) {
        [$name, $players] = parseTeam($team);
        if (!isset($divisions[$division]['teams'][$name])) {
            $divisions[$division]['teams'][$name] = [
                'id' => count($divisions[$division]['teams']),
                'name' => $name,
                'players' => $players
            ];
        }
        $game['teams'][$idx] = $divisions[$division]['teams'][$name]['id'];
    }
    $game['location'] = $locations[$data['Venue']]['id'];
    $divisions[$division]['fixtures'][] = $game;
}

ksort($divisions);
$locations = array_values($locations);
$divisions = array_values(array_map(function ($division) {
    $division['teams'] = array_values($division['teams']);
    return $division;
}, $divisions));

file_put_contents($outputPath, json_encode(compact('locations', 'divisions'), JSON_PRETTY_PRINT));

function parseTeam(string $in): array
{
    $lastDash = strrpos($in, ' - ');
    $name = trim(substr($in, 0, $lastDash));
    $players = explode(',', trim(substr($in, $lastDash + 3)));
    return [trim($name), array_map('trim', $players)];
}