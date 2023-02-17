/*
 * @package         Econsult Labs Library
 * @subpackage   Econsult Labs system plugin
 * @version         1.0.0
 *
 * @author          ECL <info@econsultlab.ru>
 * @link            https://econsultlab.ru
 * @copyright       Copyright © 2023 ECL All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
/**
 * Обработка по AJAX информации о расширении и генерация блока версии для xml поля about
 */
class ECLVersion extends ECLRequest{
    constructor(debug_mode=false) {
        super();
        this._version_container_id='';
        this.setDebugMode(debug_mode);

    }

    renderVersionBlock(request_data,container_id) {
        this._version_container_id = container_id;
        this.debug('renderVersionBlock','container_id',container_id);
        const params = {
            debug_mode: this._debug_mode,
            useOverlay: false,
            loaderContainerSelector: "#ecl-authorize:not(.ecl-modal) .ecl-spinner",
            spinnerSrc: "/media/eclabs/images/spinner_green.svg",
            url:"index.php?option=com_ajax&plugin=eclabs&group=system&format=json",
            request_data:request_data,
            success_callback:this._render,
            fail_callback:null,
        };
        this.debug('renderVersionBlock','params',params);

        this.sendRequest(params);
    }

    _render(response){
        this.debug('renderVersionBlock','response',response);
        if (response) {
            const _data = JSON.parse(response);
            if(_data) {
                if(typeof _data.response !== "undefined" && typeof _data.response.html !== "undefined") {
                    document.getElementById(this._version_container_id).innerHTML = _data.response.html;

                    // Выводим сообщение о результате в модальном окне
                    let results_group = this.getElement("#ecl-authorize:not(.ecl-modal) .results_group");
                    console.log(getEl("#ecl-authorize:not(.ecl-modal) .results_group"));
                    let alert_container = this.getElement(".results-alert",results_group);
                    let alert = Joomla.Text._("ECLUPDATEINFO_STATUS_SUCCESS_TEXT");
                    let alert_class = "alert alert-success";
                    if(typeof _data.response.update_info.error !== "undefined") {
                        alert = _data.response.update_info.error.message;
                        alert_class = "alert alert-danger";
                    }
                    var wrapper = document.createElement('div')
                    wrapper.innerHTML = '<div class="' + alert_class + ' alert-dismissible" role="alert">' + alert + '</div>'
                    results_group.classList.remove('d-none');
                    alert_container.appendChild(wrapper);

                    // Привязываем открытие модального окна
                    const _btn = Joomla.Text._('JSUBMIT');
                    let params = {
                        hideHeader:false,
                        saveBtnCaption: _btn
                    };
                    let eclm = new ECLModal();
                    eclm.initialize(params);
                }
            }
        }
    }
}

// Получить элемент
const getEl = (selector, parent = document, single = true) => single ? parent.querySelector(selector) : [...parent.querySelectorAll(selector)];

function showAuthorization(e) {
    let element = document.querySelector('#' + e.target.id + ' #ecl-modal-send');
    console.log('element', element);
    element.addEventListener('click', function (e) {
        e.preventDefault();
        const _inpParent = getEl('.ecl-fields', e.currentTarget.parentElement.parentElement, true);
        let _user = getEl('#user', _inpParent, true).value;
        let _password = getEl('#password', _inpParent, true).value;
        let _element_name = getEl('#element_name', _inpParent, true).value;
        let _extension_info = JSON.parse(getEl('#extension_info', _inpParent, true).value);

        let _request_data = {
            action: "renderVersionBlock",
            user_data: {ECL: {user: _user, password: _password}},
            element_name: _element_name,
            extension_info: _extension_info
        };
        const _container_id = "version-"+_element_name;
        let eclv = new ECLVersion(true);
        eclv.renderVersionBlock(_request_data,_container_id);
    });
}
