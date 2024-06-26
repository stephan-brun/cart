import { dispatchCustomEvent } from './helper/dispatch_custom_event';
import { createHtmlElementFromString, replaceHtmlElementByIdentifier } from './helper/html_helper';

document.addEventListener('DOMContentLoaded', () => {
  // Listen in order form for setting of payment or shipping methods
  function setAjaxEventListener (parentId, targetClass) {
    // Use parent of parentId because element of parentId itself will be replaced.
    // Listening direct for it would mean losing the EventListener.
    document.querySelector(parentId).parentElement.addEventListener('click', (event) => {
      if (!event.target.classList.contains(targetClass)) {
        return;
      }
      event.preventDefault();
      const actionUrl = event.target.getAttribute('href');

      fetch(actionUrl, {
        method: 'GET'
      })
        .then((response) => response.text())
        .then((response) => {
          const responseAsHtml = createHtmlElementFromString(response);
          replaceHtmlElementByIdentifier(responseAsHtml, '#checkout-step-shipping-method');
          replaceHtmlElementByIdentifier(responseAsHtml, '#checkout-step-payment-method');
          replaceHtmlElementByIdentifier(responseAsHtml, '#checkout-step-summary');

          const eventName = targetClass === 'set-payment'
            ? 'extcode:set-payment'
            : 'extcode:set-shipping';

          dispatchCustomEvent(
            eventName,
            {
              response
            }
          );
        });
    });
  }

  setAjaxEventListener('#checkout-step-payment-method', 'set-payment');
  setAjaxEventListener('#checkout-step-shipping-method', 'set-shipping');
});
