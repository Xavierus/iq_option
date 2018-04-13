<?php
declare(strict_types=1);

namespace AppBundle\Service\Tools;

class MoneyCalculatorService
{
    /**
     * @param string $value1
     * @param string $value2
     * @return string
     */
    public function sum(string $value1, string $value2): string
    {
        $result = $this->obfuscateValue($value1) + $this->obfuscateValue($value2);
        return substr((string)$result, 0, -2) . '.' . substr((string)$result, -2);
    }

    /**
     * @param string $value1
     * @param string $value2
     * @return string
     */
    public function substract(string $value1, string $value2): string
    {
        $result = $this->obfuscateValue($value1) - $this->obfuscateValue($value2);
        return substr((string)$result, 0, -2) . '.' . substr((string)$result, -2);
    }

    /**
     * @param string $value
     * @return int
     */
    private function obfuscateValue(string $value): int
    {
        return (int)str_replace('.', '', $value);
    }
}