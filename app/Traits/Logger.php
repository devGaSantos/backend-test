<?php

namespace App\Traits;

use Throwable;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use App\Exceptions\BaseException;
use Illuminate\Support\Facades\Log;

trait Logger
{
    /**
     * Manipula a exception para deixar mais legivel
     *
     * @param Throwable $e
     *
     * @return array
     */
    protected static function beautifyException(Throwable $e): array
    {
        return [
            'msg'  => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ];
    }

    /**
     * Cria um log padrão para o Chronos
     *
     * @param string         $description      Não é usado no filtro, pode ser descritivo
     * @param string         $action           Usado no filtro, texto simples
     * @param mixed          $value            Conteúdo do log
     * @param Throwable|null $error            Erro ocorrido (caso seja um log de erro)
     * @param string|null    $userId           ID do usuário, usuário logado do JWT caso null
     * @param string|null    $companyId        ID da empresa, empresa do usuário logado do JWT caso null
     * @param string|null    $entityId         ID da entidade
     * @param string|null    $entity           Entidade
     * @param string         $logLevel         Nivel do log: DEBUG/ERROR/INFO/CRITICAL/WARNING
     * @param string         $logType          Tipo de log: SERVER/AUDIT
     * @param Carbon|null    $requestDatetime  Datetime do ínicio de uma request
     * @param Carbon|null    $responseDatetime Datetime da response de uma request
     *
     * @return array
     */
    public function createLog(
        string $description,
        string $action,
        $value, //ausencia de tipagem
        Throwable $error = null,// uso de nullable é depreciado, seria interessante usarmos ? ou | null ex. ?Throwable $error = null ou Throwable|null $error = null
        string $idUser = null,// uso de nullable é depreciado, seria interessante usarmos ? ou | null ex. ?string $idUser = null ou string|null $idUser = null
        string $idCompany = null,// uso de nullable é depreciado, seria interessante usarmos ? ou | null ex. ?string $idCompany = null ou string|null $idCompany = null
        string $entityId = null,// uso de nullable é depreciado, seria interessante usarmos ? ou | null ex. ?string $entityId = null ou string|null $entityId = null
        string $entity = null,// uso de nullable é depreciado, seria interessante usarmos ? ou | null ex. ?string $entity = null ou string|null $entity = null
        string $logLevel = 'DEBUG',
        string $logType = 'SERVER',
        Carbon $requestDatetime = null,// uso de nullable é depreciado, seria interessante usarmos ? ou | null ex. ?Carbon $requestDatetime = null ou Carbon|null $requestDatetime = null
        Carbon $responseDatetime = null// uso de nullable é depreciado, seria interessante usarmos ? ou | null ex. ?Carbon $responseDatetime = null ou Carbon|null $responseDatetime = null
    ): array {
        try {
            $value    = is_array($value) ? $value : [$value];
            $uuid     = Uuid::uuid4()->toString();
            $logLevel = mb_strtoupper($logLevel);

            if ($error) {
                $errorLog = self::beautifyException($error);
                $value    = array_merge($value, compact('errorLog'));
            }

            //método inexistente, é necessário realizar sua implementação
            $getUserResponse = $this->getUserFromJwt();
            $requestDuration = null;

            if ($requestDatetime && $responseDatetime) {
                $requestDuration = (float) $requestDatetime->diffInMicroseconds($responseDatetime)
                    / Carbon::MICROSECONDS_PER_MILLISECOND;
            }


            // Em vez de construir tudo em um array gigante, podemos separar em métodos menores:
            // - buildUserContext() - dados do usuário
            // - buildRequestContext() - dados da request  
            // - buildTimingContext() - dados de timestamp
            // - buildLogContext() - dados do log
            // podemos também armazenar request() ->url() em uma variavel evitando que seja chamado 3 vezes
            $context = [
                'description'                   => $description,
                'action'                        => $action,
                'entity_id'                     => $entityId,
                'entity'                        => mb_strtoupper($entity),
                'log_type'                      => mb_strtoupper($logType),
                'log_level'                     => $logLevel,
                'user_id'                       => $idUser ?: data_get($getUserResponse, 'user_id'),
                'company_id'                    => $idCompany ?: data_get($getUserResponse, 'company_id'),
                'admin_user_id'                 => data_get($getUserResponse, 'admin_user_id'),
                'value'                         => $value,
                'uuid'                          => $uuid,
                'date'                          => Carbon::now(),
                'request_datetime'              => $requestDatetime?->format('Y-m-d\TH:i:s.uP'),
                'response_datetime'             => $responseDatetime?->format('Y-m-d\TH:i:s.uP'),
                'request_duration_milliseconds' => $requestDuration,
                'origin'                        => request()->headers->get('origin'),
                'referer'                       => request()->headers->get('referer'),
                'url'                           => request()->url(),
                'ip'                            => request()->server('REMOTE_ADDR'),
                'endpoint'                      => [
                    'url'    => request()->url(),
                    'method' => request()->getMethod(),
                ],
            ];

            Log::channel('log_service')->{$logLevel}($context['description'], $context);
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Falha ao enviar Log',
                'data'    => $e,
            ];
        }

        return [
            'success' => true,
            'message' => 'Log feito com sucesso',
            'data'    => $context,
            'uuid'    => $uuid,
        ];
    }

    /**
     * Tratamento padrão de exceptions
     *
     * @param Throwable   $exception
     * @param mixed|null  $data
     * @param string|null $idEntity
     * @param string|null $entity
     * @param string      $level
     *
     * @return void
     */
    public function defaultErrorHandling(
        Throwable $exception,
        $data = null,
        string $idEntity = null, // uso de nullable é depreciado, seria interessante usarmos ? ou | null ex. ?string $idEntity = null ou string|null $idEntity = null
        string $entity = null, // uso de nullable é depreciado, seria interessante usarmos ? ou | null ex. ?string $entity = null ou string|null $entity = null
        string $level = 'ERROR'
    ): void {
        // Caso seja um erro esperado BaseException, continua sem criar log
        // de erro inesperado
        if ($exception instanceof BaseException) {
            throw $exception;
        }

        $description = get_called_class();

        // Formatando o nome do método que o erro ocorreu
        $trace    = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $function = $trace[count($trace) - 1]['function'];
        $action   = mb_strtoupper(Str::snake($function)) . '_ERROR';

        $this->createLog(
            $description,
            $action,
            $data,
            $exception,
            null,
            null,
            $idEntity,
            $entity,
            $level
        );

        //pode expor dados sensíveis, seria interessante filtrar e avaliar a necessidade do log
        dump($exception);

        // Para evitar propagação de log duplicado, o erro é propagado como
        // BaseException
        throw new BaseException(
            'UNKNOW_ERROR_TRY_AGAIN',
            0
        );
    }
}
