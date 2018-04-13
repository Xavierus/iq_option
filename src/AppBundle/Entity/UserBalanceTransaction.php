<?php
declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="user_balance_transaction")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\Repository\UserBalanceTransactionRepository")
 */
class UserBalanceTransaction
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="user_balance_transaction_id")
     */
    private $userBalanceTransactionId;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="user_id")
     */
    private $user;

    /**
     * @ORM\Column(type="decimal", name="sum", precision=13, scale=2)
     */
    private $sum;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="user_balance_transaction_state_id")
     */
    private $stateId;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="user_balance_transaction_type_id")
     */
    private $typeId;

    /**
     * @param mixed $userBalanceTransactionId
     */
    public function setUserBalanceTransactionId($userBalanceTransactionId): void
    {
        $this->userBalanceTransactionId = $userBalanceTransactionId;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param mixed $sum
     */
    public function setSum($sum): void
    {
        $this->sum = $sum;
    }

    /**
     * @param int $stateId
     */
    public function setStateId(int $stateId): void
    {
        $this->stateId = $stateId;
    }

    /**
     * @param int $typeId
     */
    public function setTypeId(int $typeId): void
    {
        $this->typeId = $typeId;
    }
}