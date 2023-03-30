/*
 * @package        Econsult Labs Library
 * @version          1.0.1
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2023 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// необходимо использовать при подключении
// $wa->useScript('bootstrap.modal');

/**
 * Класс модального окна
 * Требуется bootstrap
 */
class ECLModal extends ECL {
    /**
     * Конструктор
     * @param params    Объект параметров окна
     */
    constructor(params) {
        if (typeof params.debug_mode === "undefined") {
            params.debug_mode = false;
        }
        super(params.debug_mode);

        /**
         * Параметры по-умолчанию
         * @type {Object}
         * @private
         */
        this._default = {
            wrapId: 'eclModal',
            dialogClass: '',
            hideHeader: false,
            hideFooter: false,
            hiddenClass: 'hidden',
            saveBtnCaption: Joomla.Text._('JAPPLY'),
            content: "",
            title: "",
            shown: "",
            hidden: ""
        };

        /**
         * HTML код модального окна
         * @type {string|null}
         * @private
         */
        this._htmlWrap = null;

        /**
         * ID модального окна
         * @type {string}
         * @private
         */
        this._wrapId = "";

        /**
         * класс блока modal-dialog
         * @type {string}
         * @private
         */
        this._dialogClass = "";

        /**
         * Скрыть или показать заголовок окна
         * @type {boolean}
         * @private
         */
        this._hideHeader = false;

        /**
         * Скрыть или показать подвал окна
         * @type {boolean}
         * @private
         */
        this._hideFooter = false;

        /**
         * Класс скрытия подвала или заголовка
         * @type {string}
         * @private
         */
        this._hiddenClass = '';

        /**
         * Надпись на кнопке "Отправить"
         * @type {string}
         * @private
         */
        this._saveBtnCaption = "";

        /**
         * Контент для вывода в модальном окне
         * @type {string}
         * @private
         */
        this._content = "";

        /**
         * Заголовок модального окна
         * @type {string}
         * @private
         */
        this._title = "";

        /**
         * Наименование callback функции при открытии окна
         * @type {string}
         * @private
         */
        this._shown = "";

        /**
         * Наименование callback функции при закрытии окна
         * @type {string}
         * @private
         */
        this._hidden = "";

        /**
         * Элемент модального окна
         * @type {Element|null}
         * @private
         */
        this._modal = null;

        this._initialize(params);
    }

    /**
     * Инициализация параметров окна
     * @private
     * @param params Объект параметров модального окна
     */
    _initialize(params) {
        if (typeof params.wrapId !== "undefined")
            this._wrapId = params.wrapId;
        else
            this._wrapId = this._default.wrapId;

        if (typeof params.dialogClass !== "undefined")
            this._dialogClass = params.dialogClass;
        else
            this._dialogClass = this._default.dialogClass;

        if (typeof params.hideHeader !== "undefined")
            this._hideHeader = params.hideHeader;
        else
            this._hideHeader = this._default.hideHeader;

        if (typeof params.hideFooter !== "undefined")
            this._hideFooter = params.hideFooter;
        else
            this._hideFooter = this._default.hideFooter;

        if (typeof params.hiddenClass !== "undefined")
            this._hiddenClass = params.hiddenClass;
        else
            this._hiddenClass = this._default.hiddenClass;

        if (typeof params.content !== "undefined")
            this._content = params.content;
        else
            this._content = this._default.content;

        if (typeof params.title !== "undefined")
            this._title = params.title;
        else
            this._title = this._default.title;

        if (typeof params.shown !== "undefined")
            this._shown = params.shown;
        else
            this._shown = this._default.shown;

        if (typeof params.hidden !== "undefined")
            this._hidden = params.hidden;
        else
            this._hidden = this._default.hidden;

        if (typeof params.saveBtnCaption !== "undefined")
            this._saveBtnCaption = params.saveBtnCaption;
        else
            this._saveBtnCaption = this._default.saveBtnCaption;

        let _modal = document.getElementById(this._wrapId);
        if (!_modal) {
            this._buildHtmlWrap();
            const eclModal = document.createRange().createContextualFragment(this._htmlWrap);
            if (typeof bootstrap !== "undefined") {
                if (eclModal) {
                    let bsModal = bootstrap.Modal.getInstance(eclModal);

                    if (bsModal) {
                        bsModal.dispose();
                    } // Append the modal before closing body tag
                    document.body.appendChild(eclModal); // Modal was moved so it needs to be re initialised
                }
            } else if (typeof jQuery !== "undefined") {
                document.body.appendChild(eclModal); // Modal was moved so it needs to be re initialised
            } else {
                return;
            }
        } else {
            // Изменяем модальное окно по полученным настройкам

            let _el = this.getElement('.modal-dialog', _modal, true);
            _el.removeAttribute('class');
            _el.setAttribute('class', 'modal-dialog ' + this._dialogClass);

            _el = this.getElement('.modal-header', _modal, true);
            _el.removeAttribute('class');
            _el.setAttribute('class', 'modal-header ' + (this._hideHeader ? this._hiddenClass : ''));

            _el = this.getElement('.modal-footer', _modal, true);
            _el.removeAttribute('class');
            _el.setAttribute('class', 'modal-footer ' + (this._hideFooter ? this._hiddenClass : ''));

            _el = this.getElement('.modal-footer #ecl-modal-send', _modal, true);
            _el.setAttribute('class', 'btn btn-primary ' + (this._saveBtnCaption === "" ? this._hiddenClass : ''));
            _el.innerHTML = this._saveBtnCaption;

            _el = this.getElement('.modal-body', _modal, true);
            _el.innerHTML = this._content;

        }
        this._create();
    }


    /**
     * Сформировать обертку модального окна
     * @private
     */
    _buildHtmlWrap() {
        let _data_dismiss = 'data-bs-dismiss="modal"';
        if (this.jVersion !== 4) {
            _data_dismiss = 'data-dismiss="modal"';
        }
        this._htmlWrap = '<div class="modal fade ' + this.getJVersionClass() + '" id="' + this._wrapId + '" tabIndex="-1" aria-labelledby="' + this._wrapId + 'Label" aria-hidden="true">' +
            '<div class="modal-dialog ' + this._dialogClass + '">' +
            '<div class="modal-content">' +
            '<div class="modal-header ' + (this._hideHeader ? this._hiddenClass : '') + '">' +
            '<h5 class="modal-title" id="' + this._wrapId + 'Label"></h5>' +
            '</div>' +
            '<div class="modal-body">' + this._content + '</div>' +
            '<div class="modal-footer ' + (this._hideFooter ? this._hiddenClass : '') + '">' +
            '<a href="#" class="btn btn-secondary" ' + _data_dismiss + '>' + Joomla.Text._('JCLOSE') + '</button>' +
            //(this._saveBtnCaption ? '<a type="button" class="btn btn-primary" id="ecl-modal-send">' + this._saveBtnCaption + '</a>':'') +
            '<a type="button" class="btn btn-primary ' + (this._saveBtnCaption === "" ? this._hiddenClass : '') + '" id="ecl-modal-send">' + this._saveBtnCaption + '</a>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>';
        this.debug("_buildHtmlWrap", "this._htmlWrap", this._htmlWrap);
    }

    /**
     * Создание объекта модального окна
     * @private
     */
    _create() {
        let _this = this;
        const modal = document.getElementById(this._wrapId);
        document.querySelector('#' + this._wrapId + ' #' + this._wrapId + 'Label').innerHTML = this._title;

        if (typeof bootstrap !== "undefined") {
            if (window.bootstrap && window.bootstrap.Modal && !window.bootstrap.Modal.getInstance(modal)) {
                Joomla.initialiseModal(modal, {
                    isJoomla: true
                });
            }

            // Событие открытия окна
            if (typeof window[this._shown] === "function") {
                modal.addEventListener('shown.bs.modal', function (e) {
                    window[_this._shown](e);
                });
            }

            // Событие закрытия окна
            if (typeof window[this._hidden] === "function") {
                modal.addEventListener('hidden.bs.modal', function (e) {
                    window[_this._hidden](e);
                });
            }

            // Отображение модального окна
            this._modal = window.bootstrap.Modal.getInstance(modal);
        } else if (typeof jQuery !== "undefined") {

            this._modal = jQuery('#' + this._wrapId);

            // Событие открытия окна
            if (typeof window[this._shown] === "function") {
                this._modal.on('shown', function (e) {
                    window[_this._shown](e);
                });
            }

            // Событие закрытия окна
            if (typeof window[this._hidden] === "function") {
                this._modal.on('hidden', function (e) {
                    window[_this._hidden](e);
                });
            }
        } else {
            this._modal = null;
        }
        this.debug("_create", "this._modal", this._modal);
    }

    /**
     * Возвращает элемент модального окна
     * @returns {Element}
     * @public
     */
    getModalElement() {
        return this._modal[0];
    }

    /**
     * Показать модальное окно
     */
    show() {
        this.debug("show", "this._modal", this._modal);
        if (this._modal !== null) {
            switch (true) {
                case typeof jQuery !== "undefined":
                    this._modal.modal('show');
                    break;
                case typeof bootstrap !== "undefined":
                    this._modal.show();
                    break;
                default:
                    this.debug("show", "Открытие окна не возможно",);
            }
        }
    }

    /**
     * Скрыть модальное окно
     */
    hide() {
        this.debug("hide", "this._modal", this._modal);
        if (this._modal !== null) {
            switch (true) {
                case typeof jQuery !== "undefined":
                    this._modal.modal('hide');
                    break;
                case typeof bootstrap !== "undefined":
                    this._modal.hide();
                    break;
                default:
                    this.debug("hide", "Закрытие окна не возможно",);
            }
        }
    }

    /**
     * Привязать модальное окно к ссылке
     * @param paramsAttributeSelector   Атрибут ссылки для привязки
     * @public
     */
    static bindModal(paramsAttributeSelector = 'data-eclmodal') {
        let modals = document.querySelectorAll('[' + paramsAttributeSelector + ']');
        if (modals) {
            modals.forEach(function (element) {
                // Получаем параметры мобильного окна
                let _params = null;
                if (element.getAttribute(paramsAttributeSelector)) {
                    try {
                        _params = JSON.parse(atob(element.getAttribute(paramsAttributeSelector)));
                    } catch (e) {
                        _params = false;
                    }

                    if (_params) {
                        element.addEventListener('click', function (e) {
                            e.preventDefault();
                            let myModal = new ECLModal(_params);
                            myModal.show();
                        });
                    }
                }
            });
        }
    }
}


document.addEventListener('DOMContentLoaded', function () {
    ECLModal.bindModal('data-eclmodal');
});
