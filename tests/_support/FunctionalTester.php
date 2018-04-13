<?php

use Symfony\Component\Routing\Router;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class FunctionalTester extends \Codeception\Actor
{
    use _generated\FunctionalTesterActions;

    public function getRouter(): Router
    {
        return $this->grabService('router');
    }

    public function assertResponseValid(): void
    {
        $fullResponse = $this->grabResponse();
        $this->seeResponseCodeIs(200);
        $this->seeResponseIsJson();
    }

    /**
     * @param int $siteId
     * @return PDO
     */
    public function getPdo(int $siteId): PDO
    {
        $provisionDb = $this->grabService('calltouch.provision.service.db_connection_resolver');
        return $provisionDb->getEmFor($siteId)->getConnection()->getWrappedConnection();
    }
}
