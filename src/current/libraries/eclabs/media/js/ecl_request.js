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
    constructor(debug_mode = false) {
        super();
        // Оверлэй при выполнении запроса
        this._ajaxlockPane = document.getElementById("skm_LockPane");
        // Элемент лоадера, во время выполнения запроса
        this._ajaxLoader = document.getElementById("ajaxLoading");
        // Режим отладки
        this._debug_mode = debug_mode;
        // Флаг использования оверлэя во время выполнения запроса
        this._useOverlay = true;
        // Селектор элемента, где располагается лоадер
        this._loaderContainerSelector = "";
        // Источник изображения спиннера лоадера
        this._spinnerSrc = "";
        // Метод запроса
        this._method = "POST";
        // Заголовок запроса
        this._headers = {
            'Cache-Control': 'no-cache',
            'Content-Type': 'application/json'
        };
        // URL запроса
        this._url = "";
        // Полученные по запросу данные
        this._request_data = null;
        // Функция, вызываемая при удачном запросе
        this._success_callback = null;
        // Функция, вызываемая при ошибочном запросе
        this._fail_callback = null;

        // Параметры по-умолчанию
        this._default = {
            debug_mode: false,
            useOverlay: true,
            loaderContainerSelector: "",
            spinnerSrc: "/media/eclabs/images/loading.gif"
        };

        this.setDebugMode(this._debug_mode);
    }

    /**
     * Отправить запрос
     * @param params    Параметры запроса
     * @returns {boolean}
     */
    sendRequest(params) {
        this._initialize(params);

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
        if (typeof this._fail_callback === "function")
            this._fail_callback(xhr);
    }

    /**
     * Вызывается всегда после выполнения запроса
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
        if (typeof params.debug_mode !== "undefined")
            this._debug_mode = params.debug_mode;
        else
            this._debug_mode = this._default.debug_mode;

        if (typeof params.useOverlay !== "undefined")
            this._useOverlay = params.useOverlay;
        else
            this._useOverlay = this._default.useOverlay;

        if (typeof params.loaderContainerSelector !== "undefined")
            this._loaderContainerSelector = params.loaderContainerSelector;
        else
            this._loaderContainerSelector = this._default.loaderContainerSelector;

        if (typeof params.spinnerSrc !== "undefined")
            this._spinnerSrc = params.spinnerSrc;
        else
            this._spinnerSrc = this._default.spinnerSrc;

        if (typeof params.url !== "undefined")
            this._url = params.url;

        if (typeof params.request_data !== "undefined")
            this._request_data = params.request_data;

        if (typeof params.success_callback === "function")
            this._success_callback = params.success_callback;

        if (typeof params.fail_callback === "function")
            this._fail_callback = params.fail_callback;

        if (this._ajaxLoader === null) {
            this._buildAJAXLoader();
        }
    }

    /**
     * Построение оверлэя и лоадера
     * @private
     */
    _buildAJAXLoader() {
        const _loader = document.createElement('div');
        _loader.id = "ajaxLoading";
        const _img = document.createElement('img');
        _img.setAttribute('src', this._spinnerSrc);
        _loader.appendChild(_img);

        if (this._useOverlay) {
            // Используется overlay
            const _lockPane = document.createElement('div');
            _lockPane.id = "ecl_LockPane";
            _lockPane.classList.add('LockOff');
            _loader.appendChild(_lockPane);
        }

        let _loaderContainer = document.getElementsByTagName("body")[0];
        if (this._loaderContainerSelector) {
            _loaderContainer = document.querySelector(this._loaderContainerSelector);
            if (typeof _loaderContainer !== "undefined") {
                _loader.classList.add('ecl-embedded');
            } else {
                _loaderContainer = document.getElementsByTagName("body")[0];
            }
        }
        _loaderContainer.appendChild(_loader);

        this._ajaxlockPane = document.getElementById("ecl_LockPane");
        this._ajaxLoader = document.getElementById("ajaxLoading");
        this._ajaxLoader.style.display = 'none';
    }

    /**
     * Блокировка окна при выполнении запроса
     * @private
     */
    _ajaxLock() {
        if (this._useOverlay) {
            if (this._ajaxlockPane.classList.contains('LockOff'))
                this._ajaxlockPane.classList.remove('LockOff');
            if (!this._ajaxlockPane.classList.contains('LockOn'))
                this._ajaxlockPane.classList.add('LockOn');
        }
        this._ajaxLoader.style.display = '';
    }

    /**
     * Разблокировка окна после выполнения запроса
     * @private
     */
    _ajaxUnLock() {
        if (this._useOverlay) {
            if (!this._ajaxlockPane.classList.contains('LockOff'))
                this._ajaxlockPane.classList.add('LockOff');
            if (this._ajaxlockPane.classList.contains('LockOn'))
                this._ajaxlockPane.classList.remove('LockOn');
        }
        this._ajaxLoader.style.display = 'none';
    }
}
