<?php declare(strict_types = 1);

namespace SqlFtw\PhpStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use SqlFtw\Parser\TokenList;

class TokenListDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /** @var int[] */
    private $methods = [
        'consumeKeywordEnum' => 0,
        'mayConsumeKeywordEnum' => 0,
    ];

    public function getClass(): string
    {
        return TokenList::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return array_key_exists($methodReflection->getName(), $this->methods);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $argumentIndex = $this->methods[$methodReflection->getName()];
        $parametersAcceptor = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());
        if (!isset($methodCall->args[$argumentIndex])) {
            return $parametersAcceptor->getReturnType();
        }
        $argType = $scope->getType($methodCall->args[$argumentIndex]->value);

        if (!$argType instanceof ConstantStringType) {
            return $parametersAcceptor->getReturnType();
        }
        $class = $argType->getValue();

        if (strpos($class, 'may') === 0) {
            return new UnionType([
                new ObjectType($class),
                new NullType(),
            ]);
        } else {
            return new ObjectType($class);
        }
    }
}