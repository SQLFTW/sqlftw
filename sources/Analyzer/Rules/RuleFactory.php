<?php

namespace SqlFtw\Analyzer\Rules;

use LogicException;
use SqlFtw\Analyzer\AnalyzerRule;
use function array_reverse;
use function class_implements;
use function explode;
use function glob;
use function str_replace;

class RuleFactory
{

    private array $classes = [];

    private array $rulesById = [];

    private array $rules = [];

    public function init(): void
    {
        foreach (glob(__DIR__ . "/*/*Rule.php") as $path) {
            $parts = array_reverse(explode("/", str_replace('\\', '/', $path)));
            $class = __NAMESPACE__ . "\\{$parts[1]}\\" . substr($parts[0], 0, -4);
            $this->register($class);
        }
    }

    /**
     * @param class-string<AnalyzerRule> $class
     */
    public function register(string $class): void
    {
        if (!class_exists($class)) {
            throw new LogicException("Rule class {$class} does not implement " . AnalyzerRule::class . " interface.");
        } elseif (class_implements($class, AnalyzerRule::class)) {
            throw new LogicException("Rule class {$class} does not implement " . AnalyzerRule::class . " interface.");
        }

        $this->classes[] = $class;
        foreach ($class::getIds() as $id) {
            $this->rulesById[$id] = $class;
        }
    }

    public function getRule(string $class): AnalyzerRule
    {
        if (!isset($this->rules[$class])) {
            if (!isset($this->classes[$class])) {
                throw new LogicException("Rule {$class} is not registered.");
            }

            $this->rules[$class] = new $class();
        }

        return $this->rules[$class];
    }

    public function getRuleByErrorId(string $id): AnalyzerRule
    {
        $class = $this->getClassByErrorId($id);

        return $this->getRule($class);
    }

    /**
     * @param string $id
     * @return string
     */
    public function getClassByErrorId(string $id): string
    {
        if (!isset($this->rulesById[$id])) {
            throw new LogicException("Rule for error {$id} not found.");
        }

        return $this->rulesById[$id];
    }

}
