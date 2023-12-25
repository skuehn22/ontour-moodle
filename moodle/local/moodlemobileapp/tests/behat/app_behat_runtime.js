(function() {
    // Set up the M object - only pending_js is implemented.
    window.M = window.M ? window.M : {};
    const M = window.M;
    M.util = M.util ? M.util : {};
    M.util.pending_js = M.util.pending_js ? M.util.pending_js : []; // eslint-disable-line camelcase

    /**
     * Logs information from this Behat runtime JavaScript, including the time and the 'BEHAT'
     * keyword so we can easily filter for it if needed.
     *
     * @param {string} text Information to log
     */
    const log = function() {
        const now = new Date();
        const nowFormatted = String(now.getHours()).padStart(2, '0') + ':' +
                String(now.getMinutes()).padStart(2, '0') + ':' +
                String(now.getSeconds()).padStart(2, '0') + '.' +
                String(now.getMilliseconds()).padStart(2, '0');
        console.log('BEHAT: ' + nowFormatted, ...arguments); // eslint-disable-line no-console
    };

    /**
     * Run after several setTimeouts to ensure queued events are finished.
     *
     * @param {function} target function to run
     * @param {number} count Number of times to do setTimeout (leave blank for 10)
     */
    const runAfterEverything = function(target, count) {
        if (count === undefined) {
            count = 10;
        }
        setTimeout(function() {
            count--;
            if (count == 0) {
                target();
            } else {
                runAfterEverything(target, count);
            }
        }, 0);
    };

    /**
     * Adds a pending key to the array.
     *
     * @param {string} key Key to add
     */
    const addPending = function(key) {
        // Add a special DELAY entry whenever another entry is added.
        if (window.M.util.pending_js.length == 0) {
            window.M.util.pending_js.push('DELAY');
        }
        window.M.util.pending_js.push(key);

        log('PENDING+: ' + window.M.util.pending_js);
    };

    /**
     * Removes a pending key from the array. If this would clear the array, the actual clear only
     * takes effect after the queued events are finished.
     *
     * @param {string} key Key to remove
     */
    const removePending = function(key) {
        // Remove the key immediately.
        window.M.util.pending_js = window.M.util.pending_js.filter(function(x) { // eslint-disable-line camelcase
            return x !== key;
        });
        log('PENDING-: ' + window.M.util.pending_js);

        // If the only thing left is DELAY, then remove that as well, later...
        if (window.M.util.pending_js.length === 1) {
            runAfterEverything(function() {
                // Check there isn't a spinner...
                checkUIBlocked();

                // Only remove it if the pending array is STILL empty after all that.
                if (window.M.util.pending_js.length === 1) {
                    window.M.util.pending_js = []; // eslint-disable-line camelcase
                    log('PENDING-: ' + window.M.util.pending_js);
                }
            });
        }
    };

    /**
     * Adds a pending key to the array, but removes it after some setTimeouts finish.
     */
    const addPendingDelay = function() {
        addPending('...');
        removePending('...');
    };

    // Override XMLHttpRequest to mark things pending while there is a request waiting.
    const realOpen = XMLHttpRequest.prototype.open;
    let requestIndex = 0;
    XMLHttpRequest.prototype.open = function() {
        const index = requestIndex++;
        const key = 'httprequest-' + index;

        try {
            // Add to the list of pending requests.
            addPending(key);

            // Detect when it finishes and remove it from the list.
            this.addEventListener('loadend', function() {
                removePending(key);
            });

            return realOpen.apply(this, arguments);
        } catch (error) {
            removePending(key);
            throw error;
        }
    };

    let waitingBlocked = false;

    /**
     * Checks if a loading spinner is present and visible; if so, adds it to the pending array
     * (and if not, removes it).
     */
    const checkUIBlocked = function() {
        const blocked = document.querySelector('span.core-loading-spinner, ion-loading, .click-block-active');
        if (blocked && blocked.offsetParent) {
            if (!waitingBlocked) {
                addPending('blocked');
                waitingBlocked = true;
            }
        } else {
            if (waitingBlocked) {
                removePending('blocked');
                waitingBlocked = false;
            }
        }
    };

    // It would be really beautiful if you could detect CSS transitions and animations, that would
    // cover almost everything, but sadly there is no way to do this because the transitionstart
    // and animationcancel events are not implemented in Chrome, so we cannot detect either of
    // these reliably. Instead, we have to look for any DOM changes and do horrible polling. Most
    // of the animations are set to 500ms so we allow it to continue from 500ms after any DOM
    // change.

    let recentMutation = false;
    let lastMutation;

    /**
     * Called from the mutation callback to remove the pending tag after 500ms if nothing else
     * gets mutated.
     *
     * This will be called after 500ms, then every 100ms until there have been no mutation events
     * for 500ms.
     */
    const pollRecentMutation = function() {
        if (Date.now() - lastMutation > 500) {
            recentMutation = false;
            removePending('dom-mutation');
        } else {
            setTimeout(pollRecentMutation, 100);
        }
    };

    /**
     * Mutation callback, called whenever the DOM is mutated.
     */
    const mutationCallback = function() {
        lastMutation = Date.now();
        if (!recentMutation) {
            recentMutation = true;
            addPending('dom-mutation');
            setTimeout(pollRecentMutation, 500);
        }
        // Also update the spinner presence if needed.
        checkUIBlocked();
    };

    // Set listener using the mutation callback.
    const observer = new MutationObserver(mutationCallback);
    observer.observe(document, {attributes: true, childList: true, subtree: true});

    /**
     * Check if an element is visible.
     *
     * @param {HTMLElement} element Element
     * @param {HTMLElement} container Container
     * @returns {boolean} Whether the element is visible or not
     */
    const isElementVisible = (element, container) => {
        if (element.getAttribute('aria-hidden') === 'true' || getComputedStyle(element).display === 'none')
            return false;

        const parentElement = getParentElement(element);
        if (parentElement === container)
            return true;

        if (!parentElement)
            return false;

        return isElementVisible(parentElement, container);
    };

    /**
     * Check if an element is selected.
     *
     * @param {HTMLElement} element Element
     * @param {HTMLElement} container Container
     * @returns {boolean} Whether the element is selected or not
     */
    const isElementSelected = (element, container) => {
        const ariaCurrent = element.getAttribute('aria-current');
        if (
            (ariaCurrent && ariaCurrent !== 'false') ||
            (element.getAttribute('aria-selected') === 'true') ||
            (element.getAttribute('aria-checked') === 'true')
        )
            return true;

        const parentElement = getParentElement(element);
        if (!parentElement || parentElement === container)
            return false;

        return isElementSelected(parentElement, container);
    };

    /**
     * Finds elements within a given container with exact info.
     *
     * @param {HTMLElement} container Parent element to search the element within
     * @param {string} text Text to look for
     * @return {Array} Elements containing the given text with exact boolean.
     */
    const findElementsBasedOnTextWithinWithExact = (container, text) => {
        const elements = [];
        const attributesSelector = `[aria-label*="${text}"], a[title*="${text}"], img[alt*="${text}"]`;

        for (const foundByAttributes of container.querySelectorAll(attributesSelector)) {
            if (!isElementVisible(foundByAttributes, container))
                continue;

            const exact = foundByAttributes.title == text || foundByAttributes.alt == text || foundByAttributes.ariaLabel == text;
            elements.push({ element: foundByAttributes, exact: exact });
        }

        const treeWalker = document.createTreeWalker(
            container,
            NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_DOCUMENT_FRAGMENT | NodeFilter.SHOW_TEXT,
            {
                acceptNode: node => {
                    if (
                        node instanceof HTMLStyleElement ||
                        node instanceof HTMLLinkElement ||
                        node instanceof HTMLScriptElement
                    )
                        return NodeFilter.FILTER_REJECT;

                    if (
                        node instanceof HTMLElement && (
                            node.getAttribute('aria-hidden') === 'true' || getComputedStyle(node).display === 'none'
                        )
                    )
                        return NodeFilter.FILTER_REJECT;

                    return NodeFilter.FILTER_ACCEPT;
                }
            },
        );

        let currentNode;
        while (currentNode = treeWalker.nextNode()) {
            if (currentNode instanceof Text) {
                if (currentNode.textContent.includes(text)) {
                    elements.push({ element: currentNode.parentElement, exact: currentNode.textContent.trim() == text });
                }

                continue;
            }

            const labelledBy = currentNode.getAttribute('aria-labelledby');
            const labelElement = labelledBy && container.querySelector(`#${labelledBy}`);
            if (labelElement && labelElement.innerText && labelElement.innerText.includes(text)) {
                elements.push({ element: currentNode, exact: labelElement.innerText.trim() == text });

                continue;
            }

            if (currentNode.shadowRoot) {
                for (const childNode of currentNode.shadowRoot.childNodes) {
                    if (
                        !(childNode instanceof HTMLElement) || (
                            childNode instanceof HTMLStyleElement ||
                            childNode instanceof HTMLLinkElement ||
                            childNode instanceof HTMLScriptElement
                        )
                    ) {
                        continue;
                    }

                    if (childNode.matches(attributesSelector)) {
                        const exact = childNode.title == text || childNode.alt == text || childNode.ariaLabel == text;
                        elements.push({ element: childNode, exact: exact});

                        continue;
                    }

                    elements.push(...findElementsBasedOnTextWithinWithExact(childNode, text));
                }
            }
        }

        return elements;
    };

    /**
     * Finds elements within a given container.
     *
     * @param {HTMLElement} container Parent element to search the element within.
     * @param {string} text Text to look for.
     * @return {HTMLElement[]} Elements containing the given text.
     */
     const findElementsBasedOnTextWithin = (container, text) => {
        const elements = findElementsBasedOnTextWithinWithExact(container, text);

        // Give more relevance to exact matches.
        elements.sort((a, b) => {
            return b.exact - a.exact;
        });

        return elements.map(element => element.element);
    };

    /**
     * Given a list of elements, get the top ancestors among all of them.
     *
     * This will remote duplicates and drop any elements nested within each other.
     *
     * @param {Array} elements Elements list.
     * @return {Array} Top ancestors.
     */
    const getTopAncestors = function(elements) {
        const uniqueElements = new Set(elements);

        for (const element of uniqueElements) {
            for (otherElement of uniqueElements) {
                if (otherElement === element) {
                    continue;
                }

                if (element.contains(otherElement)) {
                    uniqueElements.delete(otherElement);
                }
            }
        }

        return Array.from(uniqueElements);
    };

    /**
     * Get parent element, including Shadow DOM parents.
     *
     * @param {HTMLElement} element Element.
     * @return {HTMLElement} Parent element.
     */
    const getParentElement = function(element) {
        return element.parentElement || (element.getRootNode() && element.getRootNode().host) || null;
    };

    /**
     * Get closest element matching a selector, without traversing up a given container.
     *
     * @param {HTMLElement} element Element.
     * @param {string} selector Selector.
     * @param {HTMLElement} container Topmost container to search within.
     * @return {HTMLElement} Closest matching element.
     */
    const getClosestMatching = function(element, selector, container) {
        if (element.matches(selector)) {
            return element;
        }

        if (element === container || !element.parentElement) {
            return null;
        }

        return getClosestMatching(element.parentElement, selector, container);
    };

    /**
     * Function to find top container element.
     *
     * @param {string} containerName Whether to search inside the a container name.
     * @return {HTMLElement} Found top container element.
     */
    const getCurrentTopContainerElement = function (containerName) {
        let topContainer;
        let containers;

        switch (containerName) {
            case 'html':
                containers = document.querySelectorAll('html');
                break;
            case 'toast':
                containers = document.querySelectorAll('ion-app ion-toast.hydrated');
                containers = Array.from(containers).map(container => container.shadowRoot.querySelector('.toast-container'));
                break;
            case 'alert':
                containers = document.querySelectorAll('ion-app ion-alert.hydrated');
                break;
            case 'action-sheet':
                containers = document.querySelectorAll('ion-app ion-action-sheet.hydrated');
                break;
            case 'modal':
                containers = document.querySelectorAll('ion-app ion-modal.hydrated');
                break;
            case 'popover':
                containers = document.querySelectorAll('ion-app ion-popover.hydrated');
                break;
            case 'user-tour':
                containers = document.querySelectorAll('core-user-tours-user-tour.is-active');
                break;
            default:
                // Other containerName or not implemented.
                const containerSelector = 'ion-alert, ion-popover, ion-action-sheet, ion-modal, core-user-tours-user-tour.is-active, page-core-mainmenu, ion-app';
                containers = document.querySelectorAll(containerSelector);
        }

        if (containers.length > 0) {
            // Get the one with more zIndex.
            topContainer =  Array.from(containers).reduce((a, b) => {
                return  getComputedStyle(a).zIndex > getComputedStyle(b).zIndex ? a : b;
            }, containers[0]);
        }

        if (containerName == 'page' || containerName == 'split-view content') {
            // Find non hidden pages inside the container.
            let pageContainers = topContainer.querySelectorAll('.ion-page:not(.ion-page-hidden)');
            pageContainers = Array.from(pageContainers).filter((page) => {
                return !page.closest('.ion-page.ion-page-hidden');
            });

            if (pageContainers.length > 0) {
                // Get the more general one to avoid failing.
                topContainer = pageContainers[0];
            }

            if (containerName == 'split-view content') {
                topContainer = topContainer.querySelector('core-split-view ion-router-outlet');
            }
        }

        return topContainer;
    }

    /**
     * Function to find elements based on their text or Aria label.
     *
     * @param {object} locator Element locator.
     * @param {string} containerName Whether to search only inside a specific container.
     * @return {HTMLElement} Found elements
     */
    const findElementsBasedOnText = function(locator, containerName) {
        let topContainer = getCurrentTopContainerElement(containerName);

        let container = topContainer;

        if (locator.within) {
            const withinElements = findElementsBasedOnText(locator.within);

            if (withinElements.length === 0) {
                throw new Error('There was no match for within text')
            } else if (withinElements.length > 1) {
                const withinElementsAncestors = getTopAncestors(withinElements);

                if (withinElementsAncestors.length > 1) {
                    throw new Error('Too many matches for within text');
                }

                topContainer = container = withinElementsAncestors[0];
            } else {
                topContainer = container = withinElements[0];
            }
        }

        if (topContainer && locator.near) {
            const nearElements = findElementsBasedOnText(locator.near);

            if (nearElements.length === 0) {
                throw new Error('There was no match for near text')
            } else if (nearElements.length > 1) {
                const nearElementsAncestors = getTopAncestors(nearElements);

                if (nearElementsAncestors.length > 1) {
                    throw new Error('Too many matches for near text');
                }

                container = getParentElement(nearElementsAncestors[0]);
            } else {
                container = getParentElement(nearElements[0]);
            }
        }

        do {
            const elements = findElementsBasedOnTextWithin(container, locator.text);
            const filteredElements = locator.selector
                ? elements.map(element => getClosestMatching(element, locator.selector, container)).filter(element => !!element)
                : elements;

            if (filteredElements.length > 0) {
                return filteredElements;
            }
        } while (container !== topContainer && (container = getParentElement(container)) && container !== topContainer);

        return [];
    };

    /**
     * Make sure that an element is visible and wait to trigger the callback.
     *
     * @param {HTMLElement} element Element.
     * @param {Function} callback Callback called when the element is visible, passing bounding box parameter.
     */
    const ensureElementVisible = function(element, callback) {
        const initialRect = element.getBoundingClientRect();

        element.scrollIntoView(false);

        requestAnimationFrame(function () {
            const rect = element.getBoundingClientRect();

            if (initialRect.y !== rect.y) {
                setTimeout(function () {
                    callback(rect);
                }, 300);
                addPendingDelay();

                return;
            }

            callback(rect);
        });

        addPendingDelay();
    };

    /**
     * Press an element.
     *
     * @param {HTMLElement} element Element to press.
     */
    const pressElement = function(element) {
        ensureElementVisible(element, function(rect) {
            // Simulate a mouse click on the button.
            const eventOptions = {
                clientX: rect.left + rect.width / 2,
                clientY: rect.top + rect.height / 2,
                bubbles: true,
                view: window,
                cancelable: true,
            };

            // Events don't bubble up across Shadow DOM boundaries, and some buttons
            // may not work without doing this.
            const parentElement = getParentElement(element);

            if (parentElement && parentElement.matches('ion-button, ion-back-button')) {
                element = parentElement;
            }

            // There are some buttons in the app that don't respond to click events, for example
            // buttons using the core-supress-events directive. That's why we need to send both
            // click and mouse events.
            element.dispatchEvent(new MouseEvent('mousedown', eventOptions));

            setTimeout(() => {
                element.dispatchEvent(new MouseEvent('mouseup', eventOptions));
                element.click();
            }, 300);

            // Mark busy until the button click finishes processing.
            addPendingDelay();
        });
    };

    /**
     * Function to find and click an app standard button.
     *
     * @param {string} button Type of button to press
     * @return {string} OK if successful, or ERROR: followed by message
     */
    const behatPressStandard = function(button) {
        log('Action - Click standard button: ' + button);

        // Find button
        let foundButton = null;

        switch (button) {
            case 'back':
                foundButton = findElementsBasedOnText({ text: 'Back' })[0];
                break;
            case 'main menu': // Deprecated name.
            case 'more menu':
                foundButton = findElementsBasedOnText({
                    text: 'More',
                    near: { text: 'Messages' },
                })[0];
                break;
            case 'user menu' :
                foundButton = findElementsBasedOnText({ text: 'User account' })[0];
                break;
            case 'page menu':
                foundButton = findElementsBasedOnText({ text: 'Display options' })[0];
                break;
            default:
                return 'ERROR: Unsupported standard button type';
        }

        // Click button
        pressElement(foundButton);

        return 'OK';
    };

    /**
     * When there is a popup, clicks on the backdrop.
     *
     * @return {string} OK if successful, or ERROR: followed by message
     */
    const behatClosePopup = function() {
        log('Action - Close popup');

        let backdrops = Array.from(document.querySelectorAll('ion-backdrop'));
        backdrops = backdrops.filter(function(backdrop) {
            return !!backdrop.offsetParent;
        });

        if (!backdrops.length) {
            return 'ERROR: Could not find backdrop';
        }
        if (backdrops.length > 1) {
            return 'ERROR: Found too many backdrops';
        }
        const backdrop = backdrops[0];
        backdrop.click();

        // Mark busy until the click finishes processing.
        addPendingDelay();

        return 'OK';
    };

    /**
     * Function to find an arbitrary element based on its text or aria label.
     *
     * @param {object} locator Element locator.
     * @param {string} containerName Whether to search only inside a specific container content.
     * @return {string} OK if successful, or ERROR: followed by message
     */
    const behatFind = function(locator, containerName) {
        log('Action - Find', { locator, containerName });

        try {
            const element = findElementsBasedOnText(locator, containerName)[0];

            if (!element) {
                return 'ERROR: No matches for text';
            }

            log('Action - Found', { locator, containerName, element });
            return 'OK';
        } catch (error) {
            return 'ERROR: ' + error.message;
        }
    };

    /**
     * Scroll an element into view.
     *
     * @param {object} locator Element locator.
     * @return {string} OK if successful, or ERROR: followed by message
     */
    const behatScrollTo = function(locator) {
        log('Action - scrollTo', { locator });

        try {
            let element = findElementsBasedOnText(locator)[0];

            if (!element) {
                return 'ERROR: No matches for text';
            }

            element = element.closest('ion-item') ?? element.closest('button') ?? element;

            element.scrollIntoView();

            log('Action - Scrolled to', { locator, element });
            return 'OK';
        } catch (error) {
            return 'ERROR: ' + error.message;
        }
    }

    /**
     * Load more items form an active list with infinite loader.
     *
     * @return {string} OK if successful, or ERROR: followed by message
     */
     const behatLoadMoreItems = async function() {
        log('Action - loadMoreItems');

        try {
            const infiniteLoading = Array
                .from(document.querySelectorAll('core-infinite-loading'))
                .find(element => !element.closest('.ion-page-hidden'));

            if (!infiniteLoading) {
                return 'ERROR: There isn\'t an infinite loader in the current page';
            }

            const initialOffset = infiniteLoading.offsetTop;
            const isLoading = () => !!infiniteLoading.querySelector('ion-spinner[aria-label]');
            const isCompleted = () => !isLoading() && !infiniteLoading.querySelector('ion-button');
            const hasMoved = () => infiniteLoading.offsetTop !== initialOffset;

            if (isCompleted()) {
                return 'ERROR: All items are already loaded';
            }

            infiniteLoading.scrollIntoView({ behavior: 'smooth' });

            // Wait 100ms
            await new Promise(resolve => setTimeout(resolve, 100));

            if (isLoading() || isCompleted() || hasMoved()) {
                return 'OK';
            }

            infiniteLoading.querySelector('ion-button').click();

            // Wait 100ms
            await new Promise(resolve => setTimeout(resolve, 100));

            return (isLoading() || isCompleted() || hasMoved()) ? 'OK' : 'ERROR: Couldn\'t load more items';
        } catch (error) {
            return 'ERROR: ' + error.message;
        }
    }

    /**
     * Check whether an item is selected or not.
     *
     * @param {object} locator Element locator.
     * @return {string} YES or NO if successful, or ERROR: followed by message
     */
    const behatIsSelected = function(locator) {
        log('Action - Is Selected', locator);

        try {
            const element = findElementsBasedOnText(locator)[0];

            return isElementSelected(element, document.body) ? 'YES' : 'NO';
        } catch (error) {
            return 'ERROR: ' + error.message;
        }
    }

    /**
     * Function to press arbitrary item based on its text or Aria label.
     *
     * @param {object} locator Element locator.
     * @return {string} OK if successful, or ERROR: followed by message
     */
    const behatPress = function(locator) {
        log('Action - Press', locator);

        let found;
        try {
            found = findElementsBasedOnText(locator)[0];

            if (!found) {
                return 'ERROR: No matches for text';
            }
        } catch (error) {
            return 'ERROR: ' + error.message;
        }

        pressElement(found);

        return 'OK';
    };

    /**
     * Gets the currently displayed page header.
     *
     * @return {string} OK: followed by header text if successful, or ERROR: followed by message.
     */
    const behatGetHeader = function() {
        log('Action - Get header');

        let titles = Array.from(document.querySelectorAll('.ion-page:not(.ion-page-hidden) > ion-header h1'));
        titles = titles.filter(function(title) {
            return isElementVisible(title, document.body);
        });

        if (titles.length > 1) {
            return 'ERROR: Too many possible titles';
        } else if (!titles.length) {
            return 'ERROR: No title found';
        } else {
            const title = titles[0].innerText.trim();
            return 'OK:' + title;
        }
    };

    /**
     * Sets the text of a field to the specified value.
     *
     * This currently matches fields only based on the placeholder attribute.
     *
     * @param {string} field Field name
     * @param {string} value New value
     * @return {string} OK or ERROR: followed by message
     */
    const behatSetField = function(field, value) {
        log('Action - Set field ' + field + ' to: ' + value);

        const found = findElementsBasedOnText({ text: field, selector: 'input, textarea, [contenteditable="true"]' })[0];
        if (!found) {
            return 'ERROR: No matches for text';
        }

        // Functions to get/set value depending on field type.
        let setValue;
        let getValue;
        switch (found.nodeName) {
            case 'INPUT':
            case 'TEXTAREA':
                setValue = function(text) {
                    found.value = text;
                };
                getValue = function() {
                    return found.value;
                };
                break;
            case 'DIV':
                setValue = function(text) {
                    found.innerHTML = text;
                };
                getValue = function() {
                    return found.innerHTML;
                };
                break;
        }

        // Pretend we have cut and pasted the new text.
        let event;
        if (getValue() !== '') {
            event = new InputEvent('input', {bubbles: true, view: window, cancelable: true,
                inputType: 'devareByCut'});
            setTimeout(function() {
                setValue('');
                found.dispatchEvent(event);
            }, 0);
        }
        if (value !== '') {
            event = new InputEvent('input', {bubbles: true, view: window, cancelable: true,
                inputType: 'insertFromPaste', data: value});
            setTimeout(function() {
                setValue(value);
                found.dispatchEvent(event);
            }, 0);
        }

        return 'OK';
    };

    /**
     * Get an Angular component instance.
     *
     * @param {string} selector Element selector
     * @param {string} className Constructor class name
     * @return {object} Component instance
     */
    const behatGetAngularInstance = function(selector, className) {
        log('Action - Get Angular instance ' + selector + ', ' + className);

        const activeElement = Array.from(document.querySelectorAll(`.ion-page:not(.ion-page-hidden) ${selector}`)).pop();

        if (!activeElement || !activeElement.__ngContext__) {
            return null;
        }

        return activeElement.__ngContext__.find(node => node?.constructor?.name === className);
    };

    // Make some functions publicly available for Behat to call.
    window.behat = {
        pressStandard : behatPressStandard,
        closePopup : behatClosePopup,
        find : behatFind,
        scrollTo : behatScrollTo,
        loadMoreItems: behatLoadMoreItems,
        isSelected : behatIsSelected,
        press : behatPress,
        setField : behatSetField,
        getHeader : behatGetHeader,
        getAngularInstance: behatGetAngularInstance,
    };
})();
