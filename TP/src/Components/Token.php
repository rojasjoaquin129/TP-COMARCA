<?php

namespace Components;

use \Firebase\JWT\JWT;
use Components\GenericResponse;

require __DIR__ . '/../../vendor/autoload.php';

class Token
{
    private static $key = 'comanda';

    public static function getToken($id, $email, $area = null)
    {
        $payload = array(
            'data' => [
                'email' => $email,
                'id' => $id,
                'area' => $area
            ]
        );

        return JWT::encode($payload, Token::$key);
    }

    public static function getRole($token)
    {
        if ($token && !empty($token[0])) {
            $decoded = JWT::decode($token[0], Token::$key, array('HS256'));
            return $decoded->data->area;
        } else {
            return null;
        }
    }

    public static function getEmail($token)
    {
        if ($token && !empty($token[0])) {
            $decoded = JWT::decode($token[0], Token::$key, array('HS256'));
            return $decoded->data->email;
        } else {
            return null;
        }
    }

    public static function getId($token)
    {
        if ($token && !empty($token[0])) {
            $decoded = JWT::decode($token[0], Token::$key, array('HS256'));
            return $decoded->data->id;
        } else {
            return null;
        }
    }

    public static function validateToken($token)
    {
        $decoded = JWT::decode($token, Token::$key, array('HS256'));
        return true;
    }

    /**
     * @deprecated
     *
     * @return UserRole
     */
    public static function isInRole($token, $role)
    {
        try {
            $decoded = JWT::decode($token, Token::$key, array('HS256'));

            if ($decoded->data != null) {

                $currentRole = $decoded->data->role ?? '';

                if ($currentRole && !empty($currentRole)) {
                    return $currentRole == $role;;
                }
            }

            return GenericResponse::obtain(false, 'Unauthorized.');
        } catch (\Exception $e) {
            return GenericResponse::obtain(false, 'Unauthorized.');
        }
    }
}