/*
 * @package        Econsult Labs Library
 * @version          1.0.0
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2023 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
/**
 * Common js class
 */
class ECL {
    constructor(debug_mode = false) {
        // Режим отладки
        this._debug_mode = debug_mode;
        // Версия Joomla
        this.jVersion = 4;
        if (typeof this.ecl_jversion !== "undefined") {
            this.jVersion = ecl_jversion;
        }

    }

    /**
     * Set debug mode
     * @param debug_mode    on/off debug mode
     * @public
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
            console.log(method + '.' + name, value);
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
}
