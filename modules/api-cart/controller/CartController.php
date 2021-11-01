<?php
/**
 * CartController
 * @package api-cart
 * @version 0.0.1
 */

namespace ApiCart\Controller;

use Cart\Model\Cart;
use LibFormatter\Library\Formatter;

class CartController extends \Api\Controller
{
    public function singleAction()
    {
        if (!$this->app->isAuthorized())
            return $this->resp(401);

        $cond = [];

        if ($this->user->isLogin())
            $cond['user'] = $this->user->id;
        elseif($user = $this->req->get('user'))
            $cond['user'] = $user;

        if (!isset($cond['user'])) {
            return $this->resp(401, 'Required `user` field is not set');
        }

        $cart = Cart::getOne($cond);
        if (!$cart) {
            $cart_id = Cart::create([
                'user' => $cond['user']
            ]);
            $cart = Cart::getOne(['id' => $cart_id]);
        }

        $cart = Formatter::format('cart', $cart, ['user']);

        return $this->resp(0, $cart);
    }
}