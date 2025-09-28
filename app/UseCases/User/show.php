<?php

namespace App\UseCases\User;

use Throwable;
use App\UseCases\BaseUseCase;
use App\Repositories\User\Find;

class show extends BaseUseCase
{
    /**
     * Id do usuário
     *
     * @var string
     */
    protected string $a; // deveria ser $userId facilitar semântica e legibilidade do codigo

    /**
     * Id da empresa
     *
     * @var string
     */
    protected string $b; // deveria ser $companyId facilitar semântica e legibilidade do codigo

    /**
     * Usuário
     *
     * @var array|null
     */
    protected ?array $c; //deveria ser $user facilitar semântica e legibilidade do codigo

    public function __construct(string $a, string $b)
    {
        $this->a = $a;
        $this->b = $b;
    }

    /**
     * Encontra o usuário
     *
     * @return void
     */
    protected function find(): void
    {
        $this->c = (new Find($this->a, $this->b))->handle();
    }

    /**
     * Retorna usuário, se encontrado
     */
    public function handle(): ?array
    {
        try {
            $this->find();
        } catch (Throwable $th) {
            $this->defaultErrorHandling(
                $th,
                [
                    'a' => $this->a,
                    'b' => $this->b,
                ]
            );
        }

        return $this->c;
    }
}
