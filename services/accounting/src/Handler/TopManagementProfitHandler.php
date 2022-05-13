<?php

declare(strict_types=1);

namespace Chirickello\Accounting\Handler;

use Chirickello\Accounting\Service\UserBalanceService\UserBalanceService;
use Chirickello\Accounting\Service\Workday\Workday;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class TopManagementProfitHandler implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;
    private Workday $workday;
    private DateTimeZone $timezone;
    private UserBalanceService $userBalanceService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        Workday $workday,
        UserBalanceService $userBalanceService,
        string $timezone = 'UTC'
    ) {
        $this->responseFactory = $responseFactory;
        $this->workday = $workday;
        $this->timezone = new DateTimeZone($timezone);
        $this->userBalanceService = $userBalanceService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $date = null;
        if (array_key_exists('date', $query)) {
            try {
                $date = DateTimeImmutable::createFromFormat(
                    'Y-m-d H:i:s', $query['date'] . ' 00:00:00',
                    $this->timezone
                );
            } catch (Throwable $exception) {
                return $this->createErrorResponse([
                    [
                        'field' => 'date',
                        'message' => 'invalid date. Use Y-m-d format',
                    ]
                ]);
            }
            if ($date->format('Y-m-d') !== $query['date']) {
                return $this->createErrorResponse([
                    [
                        'field' => 'date',
                        'message' => 'incorrect date',
                    ]
                ]);
            }
            $date->setTime(...$this->workday->getWorkdayOver());
        }
        $workday = $this->workday->workday($date);
        $end = DateTimeImmutable::createFromFormat('U', $workday->end->format('U'))->setTimezone($this->timezone);

        $profit = $this->userBalanceService->calculateTopManagementProfit($workday);
        $data = [
            'date' => $end->format('Y-m-d'),
            'profit' => $profit,
        ];
        return $this->createResponse($data);
    }

    private function createResponse(array $data): ResponseInterface
    {
        $response = $this->responseFactory
            ->createResponse()
            ->withHeader('content-type', 'application/json')
        ;
        $response->getBody()->write(json_encode($data));
        return $response;
    }

    private function createErrorResponse(array $data): ResponseInterface
    {
        $response = $this->responseFactory
            ->createResponse(422)
            ->withHeader('content-type', 'application/json')
        ;
        $response->getBody()->write(json_encode($data));
        return $response;
    }
}
