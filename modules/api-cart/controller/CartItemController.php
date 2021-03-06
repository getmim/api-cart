<?php
/**
 * CartItemController
 * @package api-cart
 * @version 0.0.2
 */

namespace ApiCart\Controller;

use Cart\Model\Cart;
use Cart\Model\CartItem as CItem;
use LibFormatter\Library\Formatter;
use LibForm\Library\Form;
use Product\Model\Product;
use Cart\Library\Cart as _Cart;
use LibUser\Library\Fetcher;

class CartItemController extends \Api\Controller
{
    protected string $error;

    protected function getCart(): ?object
    {
        $cond = [];

        if ($this->user->isLogin())
            $cond['user'] = $this->user->id;
        elseif($user = $this->req->get('user'))
            $cond['user'] = $user;
        elseif($identifier = $this->req->get('identifier'))
            $cond['identifier'] = $identifier;

        if (!isset($cond['user']) && !isset($cond['identifier'])) {
            $this->error = 'Required `user` or `identifier` field is not set';
            return null;
        }

        if (isset($cond['user'])) {
            $user = Fetcher::getOne([
                'id' => $cond['user'],
                'status' => ['__op', '>', 0]
            ]);
            if (!$user) {
                $this->error = 'User not found';
                return null;
            }
        }

        $cart = Cart::getOne($cond);
        if (!$cart) {
            $cart_id = Cart::create($cond);
            $cart = Cart::getOne(['id' => $cart_id]);
        }

        return $cart;
    }

    public function createAction()
    {
        if (!$this->app->isAuthorized())
            return $this->resp(401);

        $cart = $this->getCart();
        if (!$cart) {
            return $this->resp(400, $this->error);
        }

        $form = new Form('api-cart.item.create');
        if (!($valid = $form->validate())) {
            return $this->resp(422, $form->getErrors());
        }

        $product = Product::getOne(['id' => $valid->product]);
        $product->price = json_decode($product->price);

        $price = (float)$product->price->main;
        $quantity = (int)$valid->quantity;
        $total = $quantity * $price;

        // check if the product already exists on cart
        $cond = [
            'cart' => $cart->id,
            'product' => $valid->product
        ];
        $cart_item = CItem::getOne($cond);
        if (!$cart_item) {
            $cart_item_id = CItem::create([
                'cart' => $cart->id,
                'product' => $valid->product,
                'quantity' => $quantity,
                'price' => $price,
                'total' => $total
            ]);
            if (!$cart_item_id) {
                return $this->resp(500, CItem::lastError());
            }
        } else {
            $cart_item_id = $cart_item->id;
            $set = [
                'quantity' => $quantity,
                'price' => $price,
                'total' => $total
            ];
            CItem::set($set, ['id' => $cart_item_id]);
        }

        $cart_item = CItem::getOne(['id' => $cart_item_id]);

        _Cart::calculate($cart);

        $cart_item = Formatter::format('cart-item', $cart_item, ['cart', 'product']);

        return $this->resp(0, $cart_item);
    }

    public function indexAction()
    {
        if (!$this->app->isAuthorized())
            return $this->resp(401);

        $cart = $this->getCart();
        if (!$cart) {
            return $this->resp(400, $this->error);
        }

        $items = CItem::get(['cart' => $cart->id]) ?? [];
        if ($items) {
            $items = Formatter::formatMany('cart-item', $items, ['product']);
        }

        return $this->resp(0, $items);
    }

    public function removeAction()
    {
        if (!$this->app->isAuthorized())
            return $this->resp(401);

        $cart = $this->getCart();
        if (!$cart) {
            return $this->resp(400, $this->error);
        }

        $id = $this->req->param->id;
        $cart_item = CItem::getOne(['cart' => $cart->id, 'id' => $id]);

        if ($cart_item) {
            CItem::remove(['id' => $id]);
            _Cart::calculate($cart);
        }

        return $this->resp(0);
    }
}
