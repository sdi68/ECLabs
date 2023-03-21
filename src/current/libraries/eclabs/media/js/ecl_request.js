/*
 * @package         Econsult Labs Library
 * @version         1.0.0
 *
 * @author          ECL <info@econsultlab.ru>
 * @link            https://econsultlab.ru
 * @copyright       Copyright © 2023 ECL All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

/**
 * http request class
 */
class ECLRequest extends ECL {
    /**
     * Конструктор
     * @param params Объект параметров
     */
    constructor(params) {
        super(params.debug_mode ?? false);
        /**
         * Объект лоадера
         * @type {ECLModalLoader|ECLSimpleLoader|null}
         * @protected
         */
        this._loader = null;

        /**
         * Параметры по-умолчанию
         * @type {object}
         * @protected
         */
        this._defaultParams = {
            debug_mode: false,
            loaderType: ECL_LOADER_TYPE_IMG
        }

        this.setLoader(params);

        /**
         * Параметры запроса по-умолчанию
         * @type {object}
         * @protected
         */
        this._requestParams = {
            // Метод запроса
            method: "POST",
            // Заголовок запроса
            headers: {
                'Cache-Control': 'no-cache',
                'Content-Type': 'application/json'
            },
            // Полученные по запросу данные
            url: '',
            // Функция, вызываемая при удачном запросе
            success_callback: null,
            // Функция, вызываемая при ошибочном запросе
            fail_callback: null,
            request_data: null
        };

        /**
         * Метод запроса
         * @type {string}
         * @protected
         */
        this._method = "POST";

        /**
         * Заголовок запроса
         * @type {object}
         * @protected
         */
        this._headers = {
            'Cache-Control': 'no-cache',
            'Content-Type': 'application/json'
        };

        /**
         * URL запроса
         * @type {string}
         * @private
         */
        this._url = "";

        /**
         * Полученные по запросу данные
         * @type {object|null}
         * @protected
         */
        this._request_data = null;

        /**
         * Функция, вызываемая при удачном запросе
         * @type {function|null}
         * @protected
         */
        this._success_callback = null;

        /**
         * Функция, вызываемая при ошибочном запросе
         * @type {function|null}
         * @protected
         */
        this._fail_callback = null;
    }

    /**
     * Устанавливает тип лоадера
     * @param params Параметры лоадера
     * @public
     */
    setLoader(params) {
        let loaderType;
        if (typeof params.loaderType !== "undefined") {
            loaderType = params.loaderType;
        } else {
            loaderType = this._defaultParams.loaderType;
        }
        switch (loaderType) {
            case ECL_LOADER_TYPE_MODAL:
                this._loader = new ECLModalLoader(params);
                break;
            case ECL_LOADER_TYPE_IMG:
            default:
                this._loader = new ECLSimpleLoader(params);
                break;

        }
    }

    /**
     * Отправить запрос
     * @param params    Параметры запроса
     * @returns {boolean}
     * @public
     */
    sendRequest(params) {
        this._initialize(params);

        if (typeof params.content !== "undefined") {
            this._loader.setContent(params.content);
        }

        if (this._url === "" || typeof this._request_data !== "object") {
            this.debug('sendRequest', '_url', this._url);
            this.debug('sendRequest', '_request_data', this._request_data);
            if (typeof this._fail_callback === "function")
                this._fail_callback({'error': 'Ошибка данных'});
            return false;
        }

        var _this = this;
        Joomla.request({
            url: this._url,
            method: this._method,
            headers: this._headers,
            data: JSON.stringify(this._request_data),
            onBefore: function (xhr) {
                return _this._onBefore(xhr);
            },
            onSuccess: function (response, xhr) {
                return _this._onSuccess(response, xhr)
            },
            onError: function (xhr) {
                return _this._onError(xhr)
            },
            onComplete: function (xhr) {
                return _this._onComplete(xhr)
            },
        });
        return true;
    }

    /**
     * Вызывается перед выполнением запроса
     * @param xhr
     * @private
     */
    _onBefore(xhr) {
        this._ajaxLock();
    }

    /**
     * Вызывается при успешном выполнении запроса
     * @param response  Ответ на запрос
     * @param xhr
     * @private
     */
    _onSuccess(response, xhr) {
        //Проверяем пришли ли ответы
        this.debug('_onSuccess', 'response', response);
        if (this.jVersion <= 4)
            this._ajaxUnLock();
        if (typeof this._success_callback === "function")
            this._success_callback(response);
    }

    /**
     * Вызывается при ошибке выполнения запроса
     * @param xhr
     * @private
     */
    _onError(xhr) {
        this.debug('_onError', 'xhr', xhr);
        if (this.jVersion <= 4)
            this._ajaxUnLock();
        if (typeof this._fail_callback === "function")
            this._fail_callback(xhr);
    }

    /**
     * Вызывается всегда после выполнения запроса.
     * Работает начиная с Joomla 4
     * @param xhr
     * @private
     */
    _onComplete(xhr) {
        this._ajaxUnLock();
    }

    /**
     * Инициализация класса
     * @param params    Параметры использования класса
     * @private
     */
    _initialize(params) {
        if (typeof params.url !== "undefined")
            this._url = params.url;

        if (typeof params.request_data !== "undefined")
            this._request_data = params.request_data;

        if (typeof params.success_callback === "function")
            this._success_callback = params.success_callback;

        if (typeof params.fail_callback === "function")
            this._fail_callback = params.fail_callback;

    }

    /**
     * Блокировка окна при выполнении запроса
     * @private
     */
    _ajaxLock() {
        this._loader.show();
    }

    /**
     * Разблокировка окна после выполнения запроса
     * @private
     */
    _ajaxUnLock() {
        this._loader.hide();
    }
}
