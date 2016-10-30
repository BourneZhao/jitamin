<?php

/*
 * This file is part of Hiject.
 *
 * Copyright (C) 2016 Hiject Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hiject\Api\Middleware;

use JsonRPC\Exception\AccessDeniedException;
use JsonRPC\Exception\AuthenticationFailureException;
use JsonRPC\MiddlewareInterface;
use Hiject\Core\Base;

/**
 * Class AuthenticationApiMiddleware
 */
class AuthenticationMiddleware extends Base implements MiddlewareInterface
{
    /**
     * Execute Middleware
     *
     * @access public
     * @param  string $username
     * @param  string $password
     * @param  string $procedureName
     * @throws AccessDeniedException
     * @throws AuthenticationFailureException
     */
    public function execute($username, $password, $procedureName)
    {
        $this->dispatcher->dispatch('app.bootstrap');

        if ($this->isUserAuthenticated($username, $password)) {
            $this->userSession->initialize($this->userModel->getByUsername($username));
        } elseif (! $this->isAppAuthenticated($username, $password)) {
            $this->logger->error('API authentication failure for '.$username);
            throw new AuthenticationFailureException('Wrong credentials');
        }
    }

    /**
     * Check user credentials
     *
     * @access public
     * @param  string  $username
     * @param  string  $password
     * @return boolean
     */
    private function isUserAuthenticated($username, $password)
    {
        return $username !== 'jsonrpc' &&
        ! $this->userLockingModel->isLocked($username) &&
        $this->authenticationManager->passwordAuthentication($username, $password);
    }

    /**
     * Check administrative credentials
     *
     * @access public
     * @param  string  $username
     * @param  string  $password
     * @return boolean
     */
    private function isAppAuthenticated($username, $password)
    {
        return $username === 'jsonrpc' && $password === $this->getApiToken();
    }

    /**
     * Get API Token
     *
     * @access private
     * @return string
     */
    private function getApiToken()
    {
        if (defined('API_AUTHENTICATION_TOKEN')) {
            return API_AUTHENTICATION_TOKEN;
        }

        return $this->configModel->get('api_token');
    }
}
