<?php

namespace SqlFtw\Error;

use SqlFtw\Analyzer\AnalyzerRule;

class Error
{

    /** @var Severity::* */
    public int $severity;

    public string $identifier;

    public string $message;

    public int $position;

    /** @var Repair::* */
    public int $repair;

    /** @var class-string{AnalyzerRule}|null */
    public ?string $rule = null; // filled by Analyzer

    /**
     * @param Severity::* $severity
     * @param Repair::* $repair
     */
    public function __construct(
        int $severity,
        string $identifier,
        string $message,
        int $position,
        int $repair = Repair::NO
    )
    {
        $this->severity = $severity;
        $this->identifier = $identifier;
        $this->message = $message;
        $this->position = $position;
        $this->repair = $repair;
    }

    public static function lexer(string $identifier, string $message, int $position): self
    {
        return new self(Severity::LEXER_ERROR, $identifier, $message, $position);
    }

    public static function parser(string $identifier, string $message, int $position): self
    {
        return new self(Severity::PARSER_ERROR, $identifier, $message, $position);
    }

    public static function parserWarning(string $identifier, string $message, int $position): self
    {
        return new self(Severity::PARSER_WARNING, $identifier, $message, $position);
    }

    public static function critical(string $identifier, string $message, int $position): self
    {
        return new self(Severity::CRITICAL, $identifier, $message, $position);
    }

    public static function error(string $identifier, string $message, int $position, int $repair = Repair::NO): self
    {
        return new self(Severity::ERROR, $identifier, $message, $position, $repair);
    }

    public static function warning(string $identifier, string $message, int $position): self
    {
        return new self(Severity::WARNING, $identifier, $message, $position);
    }

    public static function notice(string $identifier, string $message, int $position): self
    {
        return new self(Severity::NOTICE, $identifier, $message, $position);
    }

    public static function skipNotice(string $identifier, string $message, int $position): self
    {
        return new self(Severity::SKIP_WARNING, $identifier, $message, $position);
    }

    /**
     * @param list<self> $errors
     * @return string
     */
    public static function summarize(array $errors): string
    {
        usort($errors, function (self $a, self $b): int {
            return $b->severity <=> $a->severity;
        });

        $messages = [];
        foreach ($errors as $error) {
            $messages[] = Severity::$labels[$error->severity] . ": [{$error->identifier}] {$error->message}";
        }

        return implode("\n", $messages);
    }

}
