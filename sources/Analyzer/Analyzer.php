<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Analyzer;

use Generator;
use SqlFtw\Error\Error;
use SqlFtw\Error\Severity;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\ParserException;
use SqlFtw\Session\SessionUpdater;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\InvalidEnumValueException;
use SqlFtw\Sql\SqlMode;
use function array_values;
use function get_class;
use function iterator_to_array;

class Analyzer
{

    private Parser $parser;

    private AnalyzerContext $context;

    private SessionUpdater $sessionUpdater;

    /** @var array<class-string<Command>, list<AnalyzerRule>> */
    private array $ruleMap = [];

    /**
     * @param non-empty-list<AnalyzerRule> $rules
     */
    public function __construct(
        array $rules,
        Parser $parser,
        AnalyzerContext $context,
        ?SessionUpdater $sessionUpdater = null
    ) {
        $this->parser = $parser;
        $this->context = $context;
        $this->sessionUpdater = $sessionUpdater ?? new SessionUpdater($context->platform, $context->session, $context->resolver);

        foreach ($rules as $rule) {
            foreach ($rule->getNodes() as $node) {
                $this->ruleMap[$node][] = $rule;
            }
        }
    }

    /**
     * @param string $sql
     * @return list<AnalyzerResult>
     */
    public function analyzeAll(string $sql): array
    {
        return array_values(iterator_to_array($this->analyze($sql)));
    }

    public function analyzeSingle(string $sql): AnalyzerResult
    {
        $sqlMode = $this->context->session->getMode();
        $command = $this->parser->parseSingle($sql);

        return $this->process($command, $sqlMode);
    }

    /**
     * @return Generator<AnalyzerResult>
     */
    public function analyze(string $sql): Generator
    {
        /** @var Command $command */
        foreach ($this->parser->parse($sql) as $command) {
            $sqlMode = $this->context->session->getMode();
            $result = $this->process($command, $sqlMode);

            $failed = $command->getErrors() !== [];
            foreach ($result->errors as $error) {
                if ($error->severity >= Severity::CRITICAL) {
                    $failed = true;
                }
            }
            try {
                if (!$failed) {
                    $this->sessionUpdater->processCommand($command, $command->getTokenList());
                }
            } catch (InvalidEnumValueException $e) {
                $command->addError(Error::critical("enum.invalidValue", $e->getMessage(), 0));
            } catch (ParserException $e) {
                $command->addError($e->toError());
            }

            yield $result;
        }
    }

    public function process(Command $command, SqlMode $sqlMode, int $flags = 0): AnalyzerResult
    {
        $errors = [];
        foreach ($this->ruleMap as $node => $rules) {
            if (!$command instanceof $node) {
                continue;
            }
            foreach ($rules as $rule) {
                $ruleErrors = $rule->process($command, $this->context, $flags);
                /** @var Error $ruleError */
                foreach ($ruleErrors as $ruleError) {
                    $ruleError->rule = get_class($rule);
                    $errors[] = $ruleError;
                }
            }
        }

        if ($errors !== []) {
            //rd($errors);
        }
        return new AnalyzerResult($command, $sqlMode, $errors);
    }

}
