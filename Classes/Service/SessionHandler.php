<?php

namespace Extcode\Cart\Service;

/*
 * This file is part of the package extcode/cart.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Extcode\Cart\Domain\Model\Cart\Cart;
use Extcode\Cart\Domain\Model\Order\AbstractAddress;
use Extcode\Cart\Event\Session\AfterRestoreAddressEvent;
use Extcode\Cart\Event\Session\AfterRestoreCartEvent;
use Extcode\Cart\Event\Session\BeforeWriteAddressEvent;
use Extcode\Cart\Event\Session\BeforeWriteCartEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class SessionHandler implements SingletonInterface
{
    protected $prefixKey = 'cart_';
    private FrontendUserAuthentication $frontendUserAuthentication;

    public function __construct(
        protected EventDispatcherInterface $eventDispatcher
    ) {
        $this->getFrontendUserAuthenticationFromRequest();
    }

    /**
     * restore a Cart object from session
     */
    public function restoreCart(string $key): ?Cart
    {
        $sessionData = $this->frontendUserAuthentication->getKey('ses', $this->prefixKey . $key);

        if (is_string($sessionData)) {
            $cart = unserialize($sessionData);
            if ($cart instanceof Cart) {
                $afterRestoreCartEvent = new AfterRestoreCartEvent($cart);
                $this->eventDispatcher->dispatch($afterRestoreCartEvent);
                return $cart;
            }
        }

        return null;
    }

    /**
     * writes a Cart object to session
     */
    public function writeCart(string $key, Cart $cart): void
    {
        $beforeWriteCartEvent = new BeforeWriteCartEvent($cart);
        $this->eventDispatcher->dispatch($beforeWriteCartEvent);
        $sessionData = serialize($beforeWriteCartEvent->getCart());

        $this->frontendUserAuthentication->setKey('ses', $this->prefixKey . $key, $sessionData);
        $this->frontendUserAuthentication->storeSessionData();
    }

    /**
     * removes a Cart object from session
     */
    public function clearCart(string $key): void
    {
        $this->frontendUserAuthentication->setKey('ses', $this->prefixKey . $key, null);
        $this->frontendUserAuthentication->storeSessionData();
    }

    /**
     * restore an AbstractAddress object from session
     */
    public function restoreAddress(string $key): ?AbstractAddress
    {
        $sessionData = $this->frontendUserAuthentication->getKey('ses', $this->prefixKey . $key);

        if (is_string($sessionData)) {
            $address = unserialize($sessionData);
            if ($address instanceof AbstractAddress) {
                $afterRestoreAddressEvent = new AfterRestoreAddressEvent($address);
                $this->eventDispatcher->dispatch($afterRestoreAddressEvent);
                return $address;
            }
        }

        return null;
    }

    /**
     * writes an AbstractAddress object to session
     */
    public function writeAddress(string $key, AbstractAddress $address): void
    {
        $beforeWriteAddressEvent = new BeforeWriteAddressEvent($address);
        $this->eventDispatcher->dispatch($beforeWriteAddressEvent);
        $sessionData = serialize($beforeWriteAddressEvent->getAddress());

        $this->frontendUserAuthentication->setKey('ses', $this->prefixKey . $key, $sessionData);
        $this->frontendUserAuthentication->storeSessionData();
    }

    private function getFrontendUserAuthenticationFromRequest(): void
    {
        $request = $GLOBALS['TYPO3_REQUEST'];
        $this->frontendUserAuthentication = $request->getAttribute('frontend.user');
    }
}
