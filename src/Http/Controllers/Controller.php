<?php
namespace Mattford\WsmOpenScoresheet\Http\Controllers;

use Illuminate\Support\Arr;
use Slim\Psr7\Response;

class Controller
{
    public function validate(array $data, array $rules, array $messages = []): array
    {
        $errors = [];
        foreach ($rules as $key => $ruleList) {
            $thisErrors = [];
            $ruleList = explode('|', $ruleList);
            foreach ($ruleList as $rule) {
                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $args = explode(',', $ruleParts[1] ?? '');
                $value = Arr::get($data, $key);
                switch ($ruleName) {
                    case 'required':
                        if (!isset($value)) {
                            $thisErrors[] = $messages[$key.'.required'] ?? "The $key value is required";
                        }
                        break;
                    case 'string':
                        if (!is_string($value)) {
                            $thisErrors[] = $messages[$key.'.string'] ?? "The $key value must be a string";
                        }
                        break;
                    case 'min':
                        $min = $args[0];
                        if (is_string($value) && strlen($value) < $min) {
                            $thisErrors[] = $messages[$key.'.min'] ?? "The $key value must be at least $min characters";
                        }
                        break;
                }
            }
            if (!empty($thisErrors)) {
                $errors[$key] = $thisErrors;
            }
        }
        return $errors;
    }
    public function fileResponse(Response $response, string $content, string $filename): Response
    {
        $response = $response->withHeader('Pragma', 'public')
            ->withHeader('Expires', 0)
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Cache-Control', 'private')
            // TODO: Needs to be calculated if this fn will return other files.
            ->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->withHeader('Content-Disposition', 'attachment;filename=' . $filename)
            ->withHeader('Content-Transfer-Encoding', 'binary');
        $response->getBody()->write($content);
        return $response;
    }
}