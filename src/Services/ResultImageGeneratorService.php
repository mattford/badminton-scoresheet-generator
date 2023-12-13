<?php
namespace Mattford\WsmOpenScoresheet\Services;

use GdImage;

class ResultImageGeneratorService
{
    public array $dimensions = [1920, 1080];

    public function generate(array $game, $out = null): void
    {
        [$width, $height] = $this->dimensions;
        $image = imagecreate($width, $height);

        $colours = [
            'white' => imagecolorallocate($image, 255, 255, 255),
            'red' => imagecolorallocate($image, 255, 0, 0),
            'green' => imagecolorallocate($image, 0, 176, 80),
            'blue' => imagecolorallocate($image, 0, 112, 192),
            'darkBlue' => imagecolorallocate($image, 0, 32, 96),
            'yellow' => imagecolorallocate($image, 255, 255, 0),
            'black' => imagecolorallocate($image, 0, 0, 0),
        ];

        $padding = 50;

        $row = $padding;
        $colStart = $padding;
        $colEnd = $width - $padding;

        $headHeight = $this->scaleHeight(100, $height);
        $paddedWidth = $width - ($padding*2);

        imagesetthickness($image, 2);

        $this->bounded($colStart, $row, $paddedWidth, $headHeight, function ($x, $y, $width, $height) use ($image, $colours) {
            imagerectangle($image, $x, $y, $x+$width, $y + $height, $colours['black']);
            $this->bounded($x+2, $y+2, $width - 4, $height - 4, function ($x, $y, $width, $height) use ($image, $colours) {
                $this->bounded($x, $y, $width / 2, $height / 2, function ($x, $y, $width, $height) use ($image, $colours) {
                    imagefilledrectangle($image, $x, $y, $x + $width, $y + $height, $colours['red']);
                    $this->drawTextCentered($image, $x + ($width / 2), $y + ($height / 2) - 5, 'WSM Open', $colours['white'], 36, 'calibri-bold.ttf');
                });
                $this->bounded($x + ($width / 2) + 1, $y, ($width / 2) - 1, $height / 2, function ($x, $y, $width, $height) use ($image, $colours) {
                    imagefilledrectangle($image, $x, $y, $x + $width, $y + $height, $colours['green']);
                    $this->drawTextCentered($image, $x + ($width / 2), $y + ($height / 2) - 5, 'Badminton League', $colours['white'], 36, 'calibri-bold.ttf');
                });
                $this->bounded($x, $y + ($height / 2) + 1, $width, $height / 2, function ($x, $y, $width, $height) use ($image, $colours) {
                    imagefilledrectangle($image, $x, $y, $x + $width,$y + $height, $colours['blue']);
                    $this->drawTextCentered($image, $x + ($width / 2), $y + ($height / 2), 'Scoresheet', $colours['white'], 36, 'calibri-bold.ttf');
                });

            });
        });

        $row += $headHeight + $this->scaleHeight(50, $height);

        $teamsHeight = $headHeight * .90;

        $this->bounded($colStart, $row, $paddedWidth, $teamsHeight, function ($x, $y, $width, $height) use ($image, $colours, $game) {
            imagerectangle($image, $x, $y, $x + ($width * .40), $y + $height, $colours['black']);
            $this->bounded($x+2, $y+2, ($width - 2) * .40, $height - 4, function ($x, $y, $width, $height) use ($image, $colours, $game) {
                imagefilledrectangle($image, $x, $y, $x + $width, $y + ($height / 2), $colours['darkBlue']);
                $this->drawTextCentered($image, $x + ($width * .5), $y + ($height * .25), 'Home Team', $colours['white'], 18, 'calibri-bold.ttf');
                $this->drawTextCentered($image, $x + ($width * .5), $y + ($height * .75), $game['teams'][0], $colours['black'], 18);
            });
            $this->drawTextCentered($image, $x + ($width * .45), $y + ($height * .5), $game['scores'][0], $colours['black'], 40, 'calibri-bold.ttf');

            imagerectangle($image, $x + ($width * .60), $y, $x + $width, $y + $height, $colours['black']);
            $this->bounded($x + 2 + ($width * .60), $y + 2, ($width - 2) * .4, $height - 4, function ($x, $y, $width, $height) use ($image, $colours, $game) {
                imagefilledrectangle($image, $x, $y, $x + $width, $y + ($height / 2), $colours['darkBlue']);
                $this->drawTextCentered($image, $x + ($width * .5), $y + ($height * .25), 'Away Team', $colours['white'], 18, 'calibri-bold.ttf');
                $this->drawTextCentered($image, $x + ($width * .5), $y + ($height * .75), $game['teams'][1], $colours['black'], 18);
            });
            $this->drawTextCentered($image, $x + ($width * .55), $y + ($height * .5), $game['scores'][1], $colours['black'], 40, 'calibri-bold.ttf');
        });

        $eachGameHeight = $this->scaleHeight(50, $height);

        $row += $teamsHeight + $this->scaleHeight(50, $height);

        $totalHeight = $eachGameHeight * (count($game['games']) + 1);

        imagerectangle($image, $colStart, $row, $colEnd, $row + $totalHeight, $colours['black']);
        $this->bounded($colStart + 2, $row + 2, $paddedWidth, $totalHeight - 4, function ($x, $y, $width, $height) use ($image, $colours, $eachGameHeight, $game) {
            // 40% 20% 40%
            imagefilledrectangle($image, $x, $y, $x + $width, $y + $eachGameHeight, $colours['darkBlue']);
            imagesetthickness($image, 1);
            $intervals = [.20, .20, .10, .10, .20, .20];
            $xOffset = 0;
            foreach ($intervals as $interval) {
                $xOffset += ($width * $interval);
                imageline($image, $x + $xOffset, $y, $x + $xOffset, $y + $height,  $colours['black']);
            }

            $rows = array_merge([['Player', 'Player', '', '', 'Player', 'Player']], $game['games']);
            foreach ($rows as $idx => $row) {
                $c = $idx === 0 ? $colours['white'] : $colours['black'];
                $f = $idx === 0  ? 'calibri-bold.ttf' : 'calibri-regular.ttf';
                $yOffset = $y + (0.5 + $idx) * $eachGameHeight;
                $bottomYOffset = $y + ($idx + 1) * $eachGameHeight;
                imageline($image, $x, $bottomYOffset, $x + $width, $bottomYOffset, $colours['black']);
                $this->drawTextCentered($image, $x + ($width * .1), $yOffset, $row[0], $c, 18, $f);
                $this->drawTextCentered($image, $x + ($width * .3), $yOffset, $row[1], $c, 18, $f);
                $this->drawTextCentered($image, $x + ($width * .45), $yOffset, $row[2], $c, 22, 'calibri-bold.ttf');
                $this->drawTextCentered($image, $x + ($width * .55), $yOffset, $row[3], $c, 22, 'calibri-bold.ttf');
                $this->drawTextCentered($image, $x + ($width * .70), $yOffset, $row[4], $c, 18, $f);
                $this->drawTextCentered($image, $x + ($width * .90), $yOffset, $row[5], $c, 18, $f);
            }
            imagesetthickness($image, 2);
        });

        $text = 'On completion of the match, please post a picture of the completed scoresheet on the "WSM Open Badminton League" Facebook page along with as many pictures / videos of the game as possible.';
        $row += $totalHeight + $this->scaleHeight(50, $height);
        $callToActionHeight = 2 * $eachGameHeight;

        imagerectangle($image, $colStart, $row, $colEnd, $row + $callToActionHeight, $colours['black']);
        $this->bounded($colStart + 2, $row + 2, $paddedWidth - 4, $callToActionHeight - 4, function ($x, $y, $width, $height) use ($image, $colours, $text) {
            imagefilledrectangle($image, $x, $y, $x + $width, $y + $height, $colours['yellow']);
            $this->drawTextCentered($image, $x+($width / 2), $y+($height * .25), $text, $colours['black'], 18, 'calibri-bold.ttf', $width - 10);
        });

        imagepng($image, $out);
        imagedestroy($image);
    }

    private function bounded(int $x, int $y, int $width, int $height, callable $fn): void
    {
        $fn($x, $y, $width, $height);
    }

    private function scaleHeight(int $v, int $dim): int
    {
        return ($v / 1080) * $dim;
    }

    private function drawTextCentered(GdImage $image, $x, $y, $text, $colour, $size = 36, $font = 'calibri-regular.ttf', $maxWidth = 0): void
    {
        $fontPath = __DIR__ . '/../../resources/assets/' . $font;
        $bbox = imagettfbbox($size, 0, $fontPath, $text);

        $width = $bbox[2] - $bbox[0];
        $height = $bbox[1] - $bbox[7];

        if ($maxWidth > 0) {
            // need to wrap the text
            $words = preg_split('/\b/', $text);

            $lines = $line = [];
            while (!empty($words)) {
                $newLine = $line;
                $word = array_shift($words);
                $newLine[] = $word;
                $bbox = imagettfbbox($size, 0, $fontPath, implode('', $newLine));

                $width = $bbox[2] - $bbox[0];
                if ($width <= $maxWidth) {
                    $line = $newLine;
                } elseif (!empty($line)) {
                    $lines[] = implode('', $line);
                    $line = [$word];
                }
            }
            if (!empty($line)) {
                $lines[] = implode('', $line);
            }
            foreach ($lines as $idx => $line) {
                $yOffset = $y + ($idx * $height);
                $this->drawTextCentered($image, $x, $yOffset, $line, $colour, $size, $font);
            }
            return;
        }

        $x -= $width/2;
        $y += $height/2;

        imagettftext($image, $size, 0, $x, $y, $colour, $fontPath, $text);
    }
}
