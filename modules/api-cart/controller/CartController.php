<?php
/**
 * CartController
 * @package api-cart
 * @version 0.0.1
 */

namespace ApiCart\Controller;

use Cart\Model\Cart;
use Cart\Model\CartItem as CItem;
use LibFormatter\Library\Formatter;
use LibUser\Library\Fetcher;
use Cart\Library\Cart as _Cart;

class CartController extends \Api\Controller
{
    public function assignAction()
    {
        if (!$this->app->isAuthorized())
            return $this->resp(401);

        if ($this->user->isLogin())
            return $this->resp(400, 'Not allowed');

        $identifier = $this->req->get('identifier');
        if (!$identifier) {
            return $this->resp(401, 'Required `identifier` field is not set');
        }

        $temp_cart = Cart::getOne([
            'identifier' => $identifier
        ]);

        if (!$temp_cart) {
            return $this->resp(401, 'Target temporary cart not found');
        }

        if (!$temp_cart->items) {
            return $this->resp(401, 'No item in temporary cart');
        }

        $user = $this->req->getBody('user');
        if (!$user) {
            return $this->resp(401, 'Required `user` field is not set');
        }

        $user = Fetcher::getOne([
            'id' => $user,
            'status' => ['__op', '>', 0]
        ]);
        if (!$user) {
            return $this->resp(400, 'User not found');
        }

        $user_cart = Cart::getOne(['user' => $user->id]);
        if (!$user_cart) {
            $cart_id = Cart::create(['user' => $user->id]);
            $user_cart = Cart::getOne(['id' => $cart_id]);
        }

        CItem::set(['cart' => $user_cart->id], ['cart' => $temp_cart->id]);
        _Cart::calculate($user_cart);
        _Cart::calculate($temp_cart);

        $this->resp(0);
    }

    public function singleAction()
    {
        if (!$this->app->isAuthorized())
            return $this->resp(401);

        $cond = [];

        if ($this->user->isLogin())
            $cond['user'] = $this->user->id;
        elseif ($user = $this->req->get('user'))
            $cond['user'] = $user;
        elseif ($identifier = $this->req->get('identifier'))
            $cond['identifier'] = $identifier;

        if (!isset($cond['user']) && !isset($cond['identifier'])) {
            return $this->resp(401, 'Required `user` or `identifier` field is not set');
        }

        if (isset($cond['user'])) {
            $user = Fetcher::getOne([
                'id' => $cond['user'],
                'status' => ['__op', '>', 0]
            ]);
            if (!$user) {
                return $this->resp(400, 'User not found');
            }
        }

        $cart = Cart::getOne($cond);
        if (!$cart) {
            $cart_id = Cart::create($cond);
            $cart = Cart::getOne(['id' => $cart_id]);
        }

        $cart = Formatter::format('cart', $cart, ['user']);

        return $this->resp(0, $cart);
    }
}
