/**
 * @package        Econsult Labs Library
 * @version          1.0.19
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2024 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

document.addEventListener("DOMContentLoaded", function () {
    let taskurlFields = document.querySelectorAll('[input-taskurl="container"]');
    if (taskurlFields) {
        taskurlFields.forEach(function (container) {
            let token_field_name = container.getAttribute('data-token_field'),
                task_url = container.getAttribute('data-task_url'),
                field = container.querySelector('[input-taskurl="field"]');
            let _token_field = document.querySelector('[name="jform['+ token_field_name +']"]');
            if(_token_field) {
                /**
                 * Обновляем URL после изменения токена
                 *
                 * @param value Значение токена
                 */
                const update_task = function(value){
                    if (value) {
                        field.textContent = task_url + '&token=' + value;
                        field.classList.add("alert");
                        field.classList.add("alert-success");
                    } else {
                        // Ошибка, нет токена
                        field.textContent = Joomla.Text._("ECL_TASKURL_ERROR_NEED_TOKEN");
                        field.classList.add("alert");
                        field.classList.add("alert-danger");
                    }
                }
                update_task(_token_field.value);
                // Отслеживаем изменение токена при его генерации.
                _token_field.addEventListener('change', function (element) {
                    element.preventDefault();
                    update_task(element.currentTarget.value);
                });
            }
        });
    }
});