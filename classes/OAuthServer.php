<?php

/**
 * OAuth 2.0 Server
 */

namespace FSA\OAuth;

class OAuthServer
{

    const CODE_EXPIRED_IN = 3600;
    const ACCESS_TOKEN_EXPIRED_IN = 3600;
    const REFRESH_TOKEN_EXPIRED_IN = 2592000;
    
    private $client;

    public function __construct(
        private AuthorizationCodeStorageInterface $code_storage,
        private AccessTokenStorageInterface $access_token_storage,
        private RefreshTokenStorageInterface $refresh_token_storage,
        private ServerClientsStorageInterface $clients_storage
    ) {
    }

    public function grantAccess(array $scope = null, string $realm = 'neuron')
    {
        $bearer = getenv('HTTP_AUTHORIZATION');
        if (!$bearer) {
            header("WWW-Authenticate: Bearer realm=\"$realm\"");
            throw new HtmlException(401, 'Unauthorized');
        }
        $list = explode(' ', $bearer);
        if (sizeof($list) != 2) {
            header("WWW-Authenticate: Bearer realm=\"$realm\"");
            throw new HtmlException(401, 'Unauthorized');
        }
        if (!strcasecmp($bearer, 'Bearer')) {
            header("WWW-Authenticate: Bearer realm=\"$realm\"");
            throw new HtmlException(401, 'The access token required');
        }
        $token_info = $this->access_token_storage->get($list[1]);
        if (!$token_info) {
            header("WWW-Authenticate: Bearer realm=\"$realm\",error=\"invalid_token\",error_description=\"Invalid access token\"");
            throw new HtmlException(401, 'Invalid access token');
        }
        if (is_null($scope)) {
            $this->client = $token_info;
            return $this->client;
        }
        foreach (explode(',', $token_info->scope) as $item) {
            if (array_search($item, $scope) !== false) {
                $this->client = $token_info;
                return $this->client;
            }
        }
        header("WWW-Authenticate: Bearer realm=\"$realm\",error=\"insufficient_scope\",error_description=\"The request requires higher privileges than provided by the access token.\"");
        throw new HtmlException(403, 'The request requires higher privileges than provided by the access token.');
    }

    public function getUserId()
    {
        return $this->client->user_id;
    }

    /**
     * GET response_type=code
     */
    public function requestTypeCode($user_id, array $valid_scope = null)
    {
        $client_id = filter_input(INPUT_GET, 'client_id');
        $redirect_uri = filter_input(INPUT_GET, 'redirect_uri');
        $scope = filter_input(INPUT_GET, 'scope');
        $state = filter_input(INPUT_GET, 'state');
        $client = $this->clients_storage->get($client_id);
        $response_state = ($state !== false) ? ['state' => $state] : [];
        if ($redirect_uri !== false) {
            $allow_uris = json_decode($client->redirect_uris);
            if ($allow_uris and array_search($redirect_uri, $allow_uris) === false) {
                return $redirect_uri . '?' . http_build_query(array_merge(['error' => 'invalid_request', 'error_description' => 'redirect_uri is incorrect'], $response_state));
            }
        } else {
            return $redirect_uri . '?' . http_build_query(array_merge(['error' => 'invalid_request', 'error_description' => 'redirect_uri is missing'], $response_state));
        }
        if (!$client) {
            return $redirect_uri . '?' . http_build_query(array_merge(['error' => 'invalid_request', 'error_description' => 'client_id is incorrect'], $response_state));
        }
        if (!is_null($valid_scope) and $scope) {
            foreach (explode(',', $scope) as $item) {
                if (array_search($item, $valid_scope) === false) {
                    return $redirect_uri . '?' . http_build_query(array_merge(['error' => 'invalid_scope', 'error_description' => 'The requested scope is invalid'], $response_state));
                }
            }
        } else {
            $scope = null;
        }
        $code = $this->genCode();
        $this->code_storage->set($code, ['client_uuid' => $client->uuid, 'user_id' => $user_id, 'redirect_uri' => $redirect_uri, 'scope' => $scope], self::CODE_EXPIRED_IN);
        return $redirect_uri . '?' . http_build_query(array_merge(['code' => $code], $response_state));
    }

    /**
     * GET response_type=token
     */
    public function requestTypeToken()
    {
        throw new HtmlException(405, 'Запрос токена не реализован.');
    }

    /**
     * POST grant_type=authorization_code
     */
    public function grantTypeAuthorizationCode($token_type = 'bearer')
    {
        $code = filter_input(INPUT_POST, 'code');
        $redirect_uri = filter_input(INPUT_POST, 'redirect_uri');
        $client_id = filter_input(INPUT_POST, 'client_id');
        $client_secret = filter_input(INPUT_POST, 'client_secret');
        $code_info = $this->code_storage->get($code);
        if (!$code_info) {
            throw new HtmlException(400, 'invalid_grant', 'code is invalid, expired, revoked');
        }
        $client = $this->clients_storage->get($client_id);
        if (!$client or $client->id != $client_id or !password_verify($client_secret, $client->secret)) {
            throw new HtmlException(400, 'invalid_client', 'client_id is incorrect');
        }
        if ($redirect_uri !== false) {
            if ($code_info->redirect_uri != $redirect_uri) {
                throw new HtmlException(400, 'invalid_grant', 'redirect_uri is incorrect');
            }
        } else {
            if (isset($code_info->redirect_uri)) {
                throw new HtmlException(400, 'invalid_grant', 'redirect_uri is missing');
            }
        }
        $access_token = $this->genAccessToken();
        $refresh_token = $this->genRefreshToken();
        $this->access_token_storage->set($access_token, ['client_id' => $client_id, 'user_id' => $code_info->user_id, 'scope' => $code_info->scope], self::ACCESS_TOKEN_EXPIRED_IN);
        $this->refresh_token_storage->set($refresh_token, ['access_token' => $access_token, 'token_type' => $token_type, 'client_id' => $client_id, 'user_id' => $code_info->user_id, 'scope' => $code_info->scope], self::REFRESH_TOKEN_EXPIRED_IN);
        return [
            "access_token" => $access_token,
            "token_type" => $token_type,
            "expires_in" => self::ACCESS_TOKEN_EXPIRED_IN,
            "refresh_token" => $refresh_token,
            "scope" => $code_info->scope
        ];
    }

    /**
     * POST grant_type=password
     */
    public function grantTypePassword()
    {
        throw new HtmlException(405, 'POST запрос grant_type=password не реализован.');
    }

    /**
     * POST grant_type=refresh_token
     */
    public function grantTypeRefreshToken()
    {
        # Возможны другие типы аутентификации
        $client_id = filter_input(INPUT_POST, 'client_id');
        $client_secret = filter_input(INPUT_POST, 'client_secret');
        #$scope=filter_input(INPUT_POST, 'scope');
        # Только уменьшение scope
        $client = $this->clients_storage->get($client_id);
        if (!$client or $client->id != $client_id or !password_verify($client_secret, $client->secret)) {
            throw new HtmlException(400, 'invalid_client', 'client_id is incorrect');
        }
        $old_refresh_token = filter_input(INPUT_POST, 'refresh_token');
        $token_info = $this->refresh_token_storage->get($old_refresh_token);
        if (!$token_info) {
            throw new HtmlException(400, 'invalid_grant', 'token is invalid, expired, revoked');
        }
        $access_token = $this->genAccessToken();
        $refresh_token = $this->genRefreshToken();
        $this->access_token_storage->set($access_token, ['client_id' => $token_info->client_id, 'user_id' => $token_info->user_id, 'scope' => $token_info->scope], self::ACCESS_TOKEN_EXPIRED_IN);
        $this->refresh_token_storage->set($refresh_token, ['access_token' => $access_token, 'token_type' => $token_info->token_type, 'client_id' => $token_info->client_id, 'user_id' => $token_info->user_id, 'scope' => $token_info->scope], self::REFRESH_TOKEN_EXPIRED_IN);
        return [
            "access_token" => $access_token,
            "token_type" => $token_info->token_type,
            "expires_in" => self::ACCESS_TOKEN_EXPIRED_IN,
            "refresh_token" => $refresh_token,
            "scope" => $token_info->scope
        ];
    }

    /**
     * POST grant_type=client_credentials
     */
    public function grantTypeClientCredentials()
    {
        throw new HtmlException(405, 'Запрос ClientCredentials не реализован.');
    }

    private function genCode(): string
    {
        return $this->genRandomString(16);
    }

    private function genAccessToken(): string
    {
        return $this->genRandomString(32);
    }

    private function genRefreshToken(): string
    {
        return $this->genRandomString(32);
    }

    private function genRandomString(int $length): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(openssl_random_pseudo_bytes($length)));
    }
}
