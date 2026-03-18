<?php
/**
 * @license MIT
 *
 * Modified by checkmate on 14-February-2026 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace Checkmate\Vendor\Sabberworm\CSS\RuleSet;

use Checkmate\Vendor\Sabberworm\CSS\Rule\Rule;

/**
 * Represents a CSS item that contains `Rules`, defining the methods to manipulate them.
 */
interface RuleContainer
{
    public function addRule(Rule $ruleToAdd, ?Rule $sibling = null): void;

    public function removeRule(Rule $ruleToRemove): void;

    public function removeMatchingRules(string $searchPattern): void;

    public function removeAllRules(): void;

    /**
     * @param array<Rule> $rules
     */
    public function setRules(array $rules): void;

    /**
     * @return array<int<0, max>, Rule>
     */
    public function getRules(?string $searchPattern = null): array;

    /**
     * @return array<string, Rule>
     */
    public function getRulesAssoc(?string $searchPattern = null): array;
}
