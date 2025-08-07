/*
 * @package        Econsult Labs Library
 * @version          __DEPLOYMENT_VERSION__
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2025 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

document.addEventListener("DOMContentLoaded", function () {
    let keysFields = document.querySelectorAll('[input-key="container"]');
    if (keysFields) {
        keysFields.forEach(function (container) {
            let show = container.querySelector('[input-key="show"]'),
                generate = container.querySelector('[input-key="generate"]'),
                field = container.querySelector('[input-key="field"]'),
                key = container.querySelector('[input-key="key"]'),
                length = container.getAttribute('data-length') * 1;

            // Show key
            show.addEventListener('click', function (element) {
                element.preventDefault();
                key.innerText = field.value;
                key.style.display = '';
            });

            // Generate
            generate.addEventListener('click', function (element) {
                element.preventDefault();
                key.innerText = '';
                key.style.display = 'none';
                length = length < 10 ? 10 : length;
                field.value = create_UUID(length);
                // Вызываем событие изменения токена
                let event = new Event('change');
                field.dispatchEvent(event);
            });
        });
    }
});

/**
 * Генерация UUID
 * @return {string}
 * @param {int} length  Число генерируемых символов
 */
function create_UUID(length) {
    let _mask = "";
    for (let i=0;i<length;i++){
        if((i/2)*2 === i){
            _mask += 'x';
        } else {
            _mask += 'y';
        }
    }

    return _mask.replace(/[xy]/g, function(c) {
        let r = Math.random() * 16 | 0;
        let v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}