<?php
declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="user")
 * @ORM\Entity()
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="user_id")
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", name="balance", precision=13, scale=2)
     */
    private $balance;

    /**
     * @return string
     */
    public function getBalance(): string
    {
        return $this->balance;
    }

    /**
     * @param string $balance
     */
    public function setBalance(string $balance): void
    {
        $this->balance = $balance;
    }
}