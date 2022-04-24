<?php

declare(strict_types=1);

namespace Chirickello\Auth\Middleware;

use Chirickello\Auth\Support\Session\Session;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware implements MiddlewareInterface
{
    private string $dir;
    private string $name;
    private DateTimeZone $gmt;

    public function __construct(string $dir, string $name = 'sid')
    {
        $this->dir = $dir;
        $this->name = $name;
        $this->gmt = new DateTimeZone('GMT');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $sid = $this->getSessionId($request);
        $session = $this->getSession($sid);
        $request = $request->withAttribute('__session__', $session);
        $response = $handler->handle($request);

        $expires = $this->getExpires();
        $session->beforeSave();
        $this->saveSession($session, $sid, $expires);

        $requestUri = $request->getUri();
        $https = $requestUri->getScheme() === 'https';
        $domain = $requestUri->getHost();
        $cookie = $this->createCookie($sid, $expires, $domain, $https);
        return $response->withAddedHeader('Set-Cookie', $cookie);
    }

    private function getSession(string $sid): Session
    {
        $filename = $this->getFilename($sid);
        if (!file_exists($filename)) {
            touch($filename);
            return $this->createSession();
        }
        $raw = file_get_contents($filename);
        if (empty($raw)) {
            return $this->createSession();
        }
        $data = unserialize($raw);
        $session = $data['payload'] ?? null;
        $expires = (int)$data['expires'] ?? 0;
        if ($expires < time()) {
            $session = null;
            unlink($filename);
        }
        if (!$session instanceof Session) {
            return $this->createSession();
        }
        return $session;
    }

    private function getSessionId(ServerRequestInterface $request): string
    {
        $sid = $request->getCookieParams()[$this->name] ?? null;
        if (!is_null($sid)) {
            return $sid;
        }
        do {
            $sid = uniqid('', true);
            $filename = $this->getFilename($sid);
            $exist = file_exists($filename);
        } while ($exist);
        touch($filename);
        return $sid;
    }

    private function getFilename(string $sid): string
    {
        return $this->dir . DIRECTORY_SEPARATOR . $this->name . '_' . $sid;
    }

    private function createSession(): Session
    {
        return new Session();
    }

    private function saveSession(Session $session, string $sid, DateTimeImmutable $expires): void
    {
        $filename = $this->getFilename($sid);
        $data = serialize([
            'payload' => $session,
            'expires' => $expires->getTimestamp(),
        ]);
        file_put_contents($filename, $data);
    }

    private function createCookie(string $sid, DateTimeImmutable $expires, string $domain, bool $secure): string
    {
        $cookie = $this->name . '=' . $sid;
        $cookie .= '; Expires=' . $expires->setTimezone($this->gmt)->format(DateTimeInterface::RFC7231);
        $cookie .= '; Domain=' . $domain;
        $cookie .= '; Path=/';
        $cookie .= '; SameSite=Lax';
        if ($secure) {
            $cookie .= '; Secure';
        }
        $cookie .= '; HttpOnly';
        return $cookie;
    }

    private function getExpires(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat('U', (string)(time() + 86400));
    }
}
