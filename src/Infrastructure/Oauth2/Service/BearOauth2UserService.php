<?php

namespace GuardsmanPanda\LarabearAuth\Infrastructure\Oauth2\Service;

use Carbon\Carbon;
use GuardsmanPanda\LarabearAuth\Infrastructure\Oauth2\Dto\OidcToken;
use GuardsmanPanda\LarabearAuth\Infrastructure\Oauth2\Model\BearOauth2Client;
use GuardsmanPanda\LarabearAuth\Infrastructure\Oauth2\Model\BearOauth2User;

class BearOauth2UserService {
    public static function createOrUpdateFromCodeWithOidc(string $code, BearOauth2Client $client, string $redirectUri): BearOauth2User {
        $resp = BearOauth2ClientService::exchangeCode(code: $code, client: $client, redirect_uri: $redirectUri);
        $token = OidcToken::fromJwt(jwt: $resp['id_token'], applicationId: $client->oauth2_client_id);
        $user = BearOauth2User::where(column: 'oauth2_client_id', operator: '=', value: $token->issuedToClientId)
            ->where(column: 'oauth2_user_identifier', operator: '=', value: $token->userIdentifier)
            ->first() ?? new BearOauth2User();

        $user->oauth2_user_identifier = $token->userIdentifier;
        $user->oauth2_client_id = $token->issuedToClientId;
        $user->oauth2_user_email = $token->email;
        $user->oauth2_user_name = $token->name;

        $user->encrypted_user_access_token = $resp['access_token'];
        $user->encrypted_user_refresh_token = $resp['refresh_token'] ?? null;

        $user->oauth2_scope = $resp['scope'];
        foreach (explode(',', $resp['scope']) as $scope) {
            $user->oauth2_scope_jsonb->$scope = Carbon::now()->toDateString();
        }

        $user->save();
        return $user;
    }
}