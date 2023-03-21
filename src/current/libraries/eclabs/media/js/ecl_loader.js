/**
 * Тип лоадера простой
 * @type {number}
 */
const ECL_LOADER_TYPE_IMG = 0;
/**
 * Тип лоадера модальный
 * @type {number}
 */
const ECL_LOADER_TYPE_MODAL = 1;

/**
 * Базовый класс лоадера
 */
class ECLLoader extends ECL {
    /**
     * Конструктор класса
     * @param params Параметры лоадера
     */
    constructor(params) {
        super();
        /**
         * ID loadera
         * @type {string|null}
         * @protected
         */
        this._loaderID = null;
        /**
         * Объект лоадера
         * @type {Element|null}
         * @protected
         */
        this._loader = null;
        /**
         * Селектор контейнера лоадера
         * @type {string|null}
         * @protected
         */
        this._containerSelector = null;

        /**
         * Контейнер лоадера
         * @type {Element|null}
         * @protected
         */
        this._container = null;

        /**
         * Путь к файлу спинера
         * @type {string|null}
         * @protected
         */
        this._spinnerSrc = null;

        /**
         * Параметры по-умолчанию
         * @type {object}
         * @protected
         */
        this._default = {
            debug_mode: false,
            loaderID: "ajaxLoading",
            containerSelector: "body",
            spinnerSrc: '/media/eclabs/images/spinner_green.svg'
        };
    }

    /**
     * Инициализация лоадера
     * @param params    Объект параметров лоадера
     * @protected
     */
    _initialize(params) {
        if (typeof params.debug_mode !== "undefined")
            this._debug_mode = params.debug_mode;
        else
            this._debug_mode = this._default.debug_mode;

        if (typeof params.loaderID !== "undefined")
            this._loaderID = params.loaderID;
        else
            this._loaderID = this._default.loaderID;

        this._loader = document.getElementById(this._loaderID);

        if (typeof params.containerSelector !== "undefined")
            this._containerSelector = params.containerSelector;
        else
            this._containerSelector = this._default.containerSelector;

        this._container = this._getElementByTagOrSelector(this._containerSelector);

        if (typeof params.spinnerSrc !== "undefined")
            this._spinnerSrc = params.spinnerSrc;
        else
            this._spinnerSrc = this._default.spinnerSrc;

    }

    /**
     * Получает элемент по селектору или тэгу body. Если не body - значит селектор.
     * @param selector Селектор
     * @returns {HTMLBodyElement|*}
     * @protected
     */
    _getElementByTagOrSelector(selector) {
        if (selector === 'body') {
            return document.getElementsByTagName("body")[0];
        } else {
            return document.querySelector(selector);
        }
    }

    /**
     * Добавляет лоадер в контейнер лоадера.
     * @protected
     */
    _appendLoader() {
        this._container.appendChild(this._loader);
        this._loader = document.getElementById(this._loaderID);
    }

    /**
     * Отобразить лоадер. Переопределяется в потомках.
     * @public
     */
    show() {
    }

    /**
     * Скрыть лоадер. Переопределяется в потомках.
     * @public
     */
    hide() {
    }

    /**
     * Изменить контент в лоадере. Переопределяется в потомках
     * @public
     * @param content Строка контента (html)
     */
    setContent(content = "") {
    }

    /**
     * Изменить контент поля результата в лоадере. Переопределяется в потомках
     * @public
     * @param result Строка результата
     */
    setResult(result = "") {
    }

}

/**
 * Класс простого лоадера. Спиннер и оверлей
 */
class ECLSimpleLoader extends ECLLoader {
    /**
     * Конструктор
     * @param params Объект параметров лоадера
     */
    constructor(params) {
        super(params);

        /**
         * Оверлэй при выполнении запроса
         * @type {Element}
         * @private
         */
        this._overlay = null;

        /**
         * ID оверлэя
         * @type {string|null}
         * @private
         */
        this._overlayID = null;
        /**
         * Флаг использования оверлэя.
         * @type {boolean}
         * @private
         */
        this._useOverlay = true;

        /**
         * Параметры по умолчанию
         * @type {object}
         * @private
         */
        let sParams = {
            overlayID: "ajaxLockPane",
            useOverlay: true,
        }
        this._default = Object.assign({}, sParams, this._default);

        this._initialize(params);

    }

    /**
     * Инициализация простого лоадера
     * @param params    Объект параметров лоадера
     * @private
     */
    _initialize(params) {
        super._initialize(params);

        if (typeof params.overlayID !== "undefined")
            this._overlayID = params.overlayID;
        else
            this._overlayID = this._default.overlayID;

        if (typeof params.useOverlay !== "undefined")
            this._useOverlay = params.useOverlay;
        else
            this._useOverlay = this._default.useOverlay;

        this._overlay = document.getElementById(this._overlayID);

        if (this._loader === null) {
            this._buildLoader();
        }

    }

    /**
     * Формирует блок простого лоадера
     * @private
     */
    _buildLoader() {
        this._loader = document.createElement('div');
        this._loader.id = "ajaxLoading";

        if (!this._useOverlay)
            this._loader.classList.add('ecl-embedded');

        const _img = document.createElement('img');
        _img.setAttribute('src', this._spinnerSrc);
        this._loader.appendChild(_img);

        // overlay
        if (this._useOverlay) {
            this._overlay = document.createElement('div');
            this._overlay.id = this._overlayID;
            this._overlay.classList.add('LockOff');
            this._loader.appendChild(this._overlay);
        }

        this._appendLoader();
        this._loader.style.display = 'none';
        this._overlay = document.getElementById(this._overlayID);
    }

    /**
     * Отображает лоадер
     * @public
     */
    show() {
        if (this._useOverlay) {
            if (this._overlay.classList.contains('LockOff'))
                this._overlay.classList.remove('LockOff');
            if (!this._overlay.classList.contains('LockOn'))
                this._overlay.classList.add('LockOn');
        }
        this._loader.style.display = '';
    }

    /**
     * Скрывает лоадер
     * @public
     */
    hide() {
        if (this._useOverlay) {
            if (!this._overlay.classList.contains('LockOff'))
                this._overlay.classList.add('LockOff');
            if (this._overlay.classList.contains('LockOn'))
                this._overlay.classList.remove('LockOn');
        }
        this._loader.style.display = 'none';
    }
}

/**
 * Класс лоадера с модальным окном.
 * В модальном окне отображается спинер и необходимая информация
 */
class ECLModalLoader extends ECLLoader {
    /**
     * Конструктор
     * @param params Параметры лоадера с модальным окном
     */
    constructor(params) {
        super(params);
        /**
         * Отображаемый в окне контент
         * @type {string|null}
         * @private
         */
        this._content = null;

        /**
         * ID тела модального окна
         * @type {string|null}
         * @private
         */
        this._modalBodyID = null;

        /**
         * Html код тела модального окна
         * @type {string|null}
         * @private
         */
        this._modalBODY = null;

        /**
         * Селектор для вывода контента в модальное окно
         * @type {string}
         * @private
         */
        this._contentSelector = "ecl-fields";

        /**
         * Селектор для вывода результата
         * @type {string}
         * @private
         */
        this._resultsSelector = "results-alert";

        /**
         * Объект модального окна
         * @type {ECLModal|null}
         * @private
         */
        this._eclmodal = null;

        /**
         * Флаг ,что нужно скрывать лоадер автоматически
         * @type {boolean}
         * @private
         */
        this._autoHide = true;

        /**
         * Класс элемента окна для вывода спинера
         * @type {string}
         * @private
         */
        this._spinnerParentClass = "ecl-spinner";

        /**
         * Параметры лоадера по-умолчанию
         * @type {object}
         * @private
         */
        const _defaultParams = {
            content: "",
            modalBodyID: "modal-loader",
            autoHide: true
        };

        this._default = Object.assign({}, _defaultParams, this._default);

        this._initialize(params);

        let modalParams = {
            debug_mode: this._debug_mode,
            wrapId: 'eclModal',
            dialogClass: '',
            hideHeader: true,
            hideFooter: false,
            hiddenClass: 'hidden',
            sourceHTMLblockClass: 'ecl-modal',
            saveBtnCaption: "",
            content: this._modalBODY,
            title: "",
            shown: "",
            hidden: ""
        };
        this._eclmodal = new ECLModal(modalParams);

    }

    /**
     * Инициализация модального лоадера
     * @param params    Объект параметров лоадера
     * @private
     */
    _initialize(params) {
        super._initialize(params);

        if (typeof params.content !== 'undefined') {
            this._content = params.content;
        } else {
            this._content = this._default.content;
        }

        if (typeof params.modalBodyID !== 'undefined') {
            this._modalBodyID = params.modalBodyID;
        } else {
            this._modalBodyID = this._default.modalBodyID;
        }

        if (typeof params.autoHide !== 'undefined') {
            this._autoHide = params.autoHide;
        } else {
            this._autoHide = this._default.autoHide;
        }
        this._buildModalBody();

    }

    /**
     * Формирует блок тела модального окна лоадера
     * @private
     */
    _buildModalBody() {
        let _html = "";
        let _containerClass = "container-fluid";
        let _columnClass = "span";
        let _hiddenClass = "hidden";
        switch (this.jVersion) {
            case '4':
                _containerClass = "container";
                _columnClass = "col-sm-";
                _hiddenClass = "d-none";
                break;
            case '3':
            default:
        }

        this._modalBODY = "<div id = \"" + this._modalBodyID + "\" class=\"body-wrap " + _containerClass + "\">\n" +
            "\t<div class=\"row\">\n" +
            "\t\t<div class=\"" + _columnClass + "9 " + this._contentSelector + "\">" + this._content + "</div>\n" +
            "\t\t<div class=\"" + this._spinnerParentClass + " " + _columnClass + "3 text-center align-self-center\">" +
            "\t\t\t<img src = \"" + this._spinnerSrc + "\" />" +
            "\t\t</div>\n" +
            "\t</div>\n" +
            "\t<div class=\"" + _columnClass + "12 results_group mb-3 " + _hiddenClass + "\">\n" +
            "\t\t<div class=\"" + this._resultsSelector + "\"></div>\n" +
            "\t</div>\n" +
            "</div>\n";
    }

    /**
     * Отображает лоадер
     * @public
     */
    show() {
        this._eclmodal.show();
        this._showSpinner(true);
        if (!this._autoHide) {
            this._enableButtons(false);
        }
    }

    /**
     * Скрывает лоадер
     * @public
     */
    hide() {
        if (this._autoHide) {
            this._eclmodal.hide();
        } else {
            this._showSpinner(false);
            this._enableButtons(true);
        }
    }

    /**
     * Устанавливает доступность кнопок модального окна
     * @param enabled Разрешить (true) или запретить доступность кнопок
     * @private
     */
    _enableButtons(enabled = true) {
        const _modal = this._eclmodal.getModalElement();
        let _btns = this.getElement('.modal-footer button', _modal, false);
        if (_btns) {
            _btns.forEach(function (btn) {
                if (enabled)
                    btn.removeAttributeNode('disabled');
                else
                    btn.setAttribute('disabled', 'disabled');
            });
        }
    }

    /**
     * Показать или скрыть спинер
     * @param show  Показать (true) или скрыть (false)
     * @private
     */
    _showSpinner(show = true) {
        const _modal = this._eclmodal.getModalElement();
        let spinner = this.getElement('.' + this._spinnerParentClass, _modal, true);
        spinner.style.display = (show ? 'block' : 'none');
    }

    /**
     * Вывести контент в модальное окно
     * @param content Контент для вывода (html)
     * @public
     */
    setContent(content = "") {
        let _contentElement = this.getElement('.' + this._contentSelector, this._eclmodal.getModalElement(), true);
        _contentElement.innerHTML = content.trim();
    }

    /**
     * Вывести результат в модальное окно
     * @param result Контент для вывода (html)
     * @public
     */
    setResult(result = "") {
    }
}
