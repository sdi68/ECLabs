/*
 * @package         Econsult Labs Library
 * @version         1.0.0
 *
 * @author          ECL <info@econsultlab.ru>
 * @link            https://econsultlab.ru
 * @copyright       Copyright © 2023 ECL All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// необходимо использовать при подключении
// $wa->useScript('bootstrap.modal');
/**
 * Modal class
 * Требуется bootstrap
 */
class ECLModal extends ECL {
    constructor(debug_mode = false) {
        super();
        // обертка модального окна
        this._htmlWrap = null;
        // Идентификатор обертки
        this._wrapId = "eclModal";
        // класс блока modal-dialog
        this._dialogClass = "";
        // Скрыть или показать заголовок окна
        this._hideHeader = false;
        // Скрыть или показать подвал окна
        this._hideFooter = false;
        // Класс скрытия подвала или заголовка
        this._hiddenClass = 'hidden';
        // Класс, которым отмечен блок для вывода в модальном окне (должен его скрывать)
        this._sourceHTMLblockClass = 'ecl-modal';
        // Надпись на кнопке "Отправить"
        this._saveBtnCaption = "";
        // Режим отладки
        this._debug_mode = debug_mode;
        this.setDebugMode(debug_mode);
    }

    /**
     * Инициализация модального окна
     * @param params    Параметры модального окна
     */

    initialize(params) {
        if (typeof params.wrapId !== "undefined")
            this._wrapId = params.wrapId;
        if (typeof params.dialogClass !== "undefined")
            this._dialogClass = params.dialogClass;
        if (typeof params.hideHeader !== "undefined")
            this._hideHeader = params.hideHeader;
        if (typeof params.hideFooter !== "undefined")
            this._hideFooter = params.hideFooter;
        if (typeof params.hiddenClass !== "undefined")
            this._hiddenClass = params.hiddenClass;
        if (typeof params.saveBtnCaption !== "undefined")
            this._saveBtnCaption = params.saveBtnCaption;
        else
            this._saveBtnCaption = Joomla.Text._('JAPPLY')
        if (typeof params.sourceHTMLblockClass !== "undefined")
            this._sourceHTMLblockClass = params.sourceHTMLblockClass;
        else
            this._sourceHTMLblockClass = 'ecl-modal';

        let modals = document.querySelectorAll('[data-eclmodal]');

        if (modals) {
            if (!document.getElementById(this._wrapId)) {
                this._buildHtmlWrap();
                const eclModal = document.createRange().createContextualFragment(this._htmlWrap);

                if (eclModal) {
                    let bsModal = bootstrap.Modal.getInstance(eclModal);

                    if (bsModal) {
                        bsModal.dispose();
                    } // Append the modal before closing body tag
                    document.body.appendChild(eclModal); // Modal was moved so it needs to be re initialised
                }
            }
            var _this = this;
            modals.forEach(function (element) {
                // Получаем заголовок окна
                let name = '';
                if (element.getAttribute('title')) {
                    name = element.getAttribute('title');
                } else if (element.getAttribute('data-title')) {
                    name = element.getAttribute('data-title');
                } else if (element.getAttribute('data-name')) {
                    name = element.getAttribute('data-name');
                }

                // Получаем ID выводимого контента
                let contentID = '';
                if (element.getAttribute('content_id')) {
                    contentID = element.getAttribute('content_id');
                } else if (element.getAttribute('data-content_id')) {
                    contentID = element.getAttribute('data-content_id');
                }

                // Получаем callback события открытия окна
                let shown = '';
                if (element.getAttribute('shown')) {
                    shown = element.getAttribute('shown');
                } else if (element.getAttribute('data-shown')) {
                    shown = element.getAttribute('data-shown');
                }

                // Получаем callback события закрытия окна
                let hidden = '';
                if (element.getAttribute('hidden')) {
                    hidden = element.getAttribute('hidden');
                } else if (element.getAttribute('data-hidden')) {
                    hidden = element.getAttribute('data-hidden');
                }

                // Открываем модальное окно
                if (contentID) {
                    element.addEventListener('click', function (e) {
                        e.preventDefault();
                        _this._openModal(name, contentID, shown, hidden);
                    });
                }

            });
        }
    }

    /**
     * Открыть модальное окно
     * @param name  Заголовок окна
     * @param contentID Id выводимого контента
     * @param shown Функция, вызываемая после открытия окна
     * @param hidden    Функция, вызываемая после закрытия окна
     * @private
     */
    _openModal(name, contentID, shown, hidden) {
        const modal = document.getElementById(this._wrapId);
        document.querySelector('#' + this._wrapId + ' #' + this._wrapId + 'Label').innerHTML = name;
        // копируем блок в окно и удаляем класс, скрывающий его у копии в окне
        let _c = document.getElementById(contentID).cloneNode(true);
        _c.classList.remove(this._sourceHTMLblockClass);
        document.querySelector('#' + this._wrapId + ' .modal-body').innerHTML = _c.outerHTML;

        if (window.bootstrap && window.bootstrap.Modal && !window.bootstrap.Modal.getInstance(modal)) {
            Joomla.initialiseModal(modal, {
                isJoomla: true
            });
        }

        // Событие открытия окна
        if (typeof window[shown] === "function") {
            modal.addEventListener('shown.bs.modal', function (e) {
                window[shown](e);
            });
        }

        // Событие закрытия окна
        if (typeof window[hidden] === "function") {
            modal.addEventListener('hidden.bs.modal', function (e) {
                window[hidden](e);
            });
        }

        // Отображение модального окна
        window.bootstrap.Modal.getInstance(modal).show();
    }

    /**
     * Сформировать обертку модального окна
     * @private
     */
    _buildHtmlWrap() {
        this._htmlWrap = '<div class="modal fade" id="' + this._wrapId + '" tabIndex="-1" aria-labelledby="' + this._wrapId + 'Label" aria-hidden="true">' +
            '<div class="modal-dialog ' + this._dialogClass + '">' +
            '<div class="modal-content">' +
            '<div class="modal-header ' + (this._hideHeader ? this._hiddenClass : '') + '">' +
            '<h5 class="modal-title" id="' + this._wrapId + 'Label"></h5>' +
            '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="' + Joomla.Text._('JCLOSE') + '"></button>' +
            '</div>' +
            '<div class="modal-body"></div>' +
            '<div class="modal-footer ' + (this._hideFooter ? this._hiddenClass : '') + '">' +
            '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + Joomla.Text._('JCLOSE') + '</button>' +
            '<button type="button" class="btn btn-primary" id="ecl-modal-send">' + this._saveBtnCaption + '</button>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>';
    }
}
