<?php

namespace App\Integrations\Banking\Account;

use App\Integrations\Banking\Gateway;
use App\Repositories\Account\FindByUser;
use App\Exceptions\InternalErrorException;

class Find extends Gateway
{
    /**
     * Id do usuário
     *
     * @var string
     */
    protected string $userId;

    /**
     * Id externo da conta
     *
     * @var string
     */
    protected string $externalId;

    public function __construct(string $userId)
    {
        $this->userId = $userId;
    }

    /**
     * Busca os dados de conta
     *
     * @return void
     */
    protected function findAccountData(): void
    {
        $account = (new FindByUser($this->userId))->handle();

        if (is_null($account)) {
            throw new InternalErrorException(
                'ACCOUNT_NOT_FOUND',
                161001001
            );
        }

        $this->externalId = $account['external_id'];
    }

    /**
     * Constroi a url da request
     *
     * @return string
     */
    protected function requestUrl(): string
    {
        return "accounts/$this->externalId";
    }

    /**
     * Modifica o status de uma conta
     *
     * @return array
     */

     //este métodod não realiza alteração do status de uma conta, apenas busca seus dados e formata o retorno
    public function handle(): array
    {
        $this->findAccountData();
        $url = $this->requestUrl();

        $request = $this->sendRequest(
            method: 'get',
            url:    $url,
            action: 'FIND_ACCOUNT',
            params: []
        );

        return $this->formatDetailsResponse($request);
    }
}
