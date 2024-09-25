<?php
namespace Mattford\WsmOpenScoresheet\Services;

class CalendarExportService
{
    public function buildVEvent(array $game): string
    {
        $eventTitle = 'League Match: ' . implode(' vs ', $game['teams']);
        $startTime = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $game['date']);
        $fields = [];
        $fields['DTSTART'] = $startTime->format("Ymd\THis\Z");
        $fields['DTEND'] = $startTime->add(new \DateInterval('PT2H'))->format("Ymd\THis\Z");
        $fields['LOCATION'] = $game['location']['name'];
        $fields['GEO'] = implode(',', $game['location']['geolocation']);
        $fields['UID'] = $startTime->format("Ymd\THis\Z") . "@scoresheet.wsmbadminton.co.uk";
        $fields['SUMMARY'] = $eventTitle;
        $fields['TRANSP'] = 'OPAQUE';
        $fields['DTSTAMP'] = date("Ymd\THis");

        return "BEGIN:VEVENT\r\n" .
            implode("\r\n", array_map(fn($key, $value) => "$key:$value", array_keys($fields), $fields)) .
            "\r\nEND:VEVENT\r\n";
    }
}