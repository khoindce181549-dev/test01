<?php
namespace Quizably\Integration;

defined( 'ABSPATH' ) || exit;

abstract class AbstractIntegration implements IntegrationInterface
{
    /**
     * @return array{status:string,response:string|null,error:string|null}
     */
    protected function ok(string $response = ''): array
    {
        return ['status' => 'sent', 'response' => $response, 'error' => null];
    }

    /**
     * @return array{status:string,response:string|null,error:string|null}
     */
    protected function fail(string $error): array
    {
        return ['status' => 'failed', 'response' => null, 'error' => $error];
    }

    /**
     * @return array{status:string,response:string|null,error:string|null}
     */
    protected function retry(string $error): array
    {
        return ['status' => 'retry', 'response' => null, 'error' => $error];
    }
}
