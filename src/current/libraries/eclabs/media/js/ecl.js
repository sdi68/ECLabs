/*
 * @package        Econsult Labs Library
 * @version          2.0.1
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2025 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
/**
 * Common js class
 */
class ECL {
    constructor(debug_mode = false) {
        /**
         * Флаг режима отладки
         * @type {boolean}
         * @protected
         */
        this._debug_mode = debug_mode;

        /**
         * Версия Joomla
         * @type {number}
         */
        this.jVersion = 4;
        if (typeof ecl_jversion !== "undefined") {
            this.jVersion = ecl_jversion;
        }

    }

    /**
     * Get the debug mode flag
     * @public
     * @return {boolean}
     * @since 1.0.19
     */
    get debug_mode() {
        return this._debug_mode;
    }

    /**
     * Set the debug mode flag
     * @public
     * @param {boolean} value
     * @since 1.0.19
     */

    set debug_mode(value) {
        this._debug_mode = value;
    }

    /**
     * Remove all events from the DOM element
     * @public
     * @param element   DOM Element
     */
    static clearAllEvents(element) {
        if (element) {
            element.replaceWith(element.cloneNode(true));
        }
    }

    /**
     * Set debug mode
     * @param debug_mode    on/off debug mode
     * @public
     * @deprecated since version 1.0.19
     */
    setDebugMode(debug_mode = false) {
        this._debug_mode = debug_mode;
    }

    /**
     * output debug info
     * @param method   Method name
     * @param name  Variable name
     * @param value Variable value
     * @public
     */
    debug(method = '', name = '', value) {
        if (this._debug_mode) {
            console.log(this.constructor.name + '.' + method + '.' + name, value);
        }
    }

    /**
     * Getting dom element
     * @param selector  Selector
     * @param parent    Parent element
     * @param single    Single or array
     * @public
     * @returns {*|*[]}
     */
    getElement(selector, parent = document, single = true) {

        return single ? parent.querySelector(selector) : [...parent.querySelectorAll(selector)];
    }

    /**
     * Getting current joomla version
     * @public
     * @returns int
     */
    getJVersion() {
        return this.jVersion;
    }

    /**
     * Getting class name, assigned with current Joomla version
     * @public
     * @returns string
     */
    getJVersionClass() {
        return 'version-' + this.jVersion;
    }

    /**
     * Check when jQuery is loaded
     * @public
     * @returns boolean
     */
    checkJQuery() {
        return !!window.jQuery;
    }

    /**
     * Remove all events from the DOM element
     * @public
     * @param element   DOM Element
     * @deprecated use ECL.clearAllEvents();
     */
    removeAllEvents(element) {
        if (element) {
            element.replaceWith(element.cloneNode(true));
        }
    }

    /**
     * Analog sprintf function php
     * @param string    Template witch %s, %d
     * @param args      Any values to be in template replaced
     * @return {string}
     * @public
     * @since 1.0.24
     */
    sprintf(string, ...args) {
        const newString = Joomla.Text._(string);
        let i = 0;
        return newString.replace(/%((%)|s|d)/g, (m) => {
            let val = args[i];
            if (m === '%d') {
                val = parseFloat(val);
                if (Number.isNaN(val)) {
                    val = 0;
                }
            }
            i += 1;
            return val;
        });
    }
}
