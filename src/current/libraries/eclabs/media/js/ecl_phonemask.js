/**
 * Класс установки маски телефонного номера
 * Вызываем к примеру так: new ECLPhoneMask("input[name *= phone]",'+7(___) ___-____',true)
 * @version 1.0.0
 */
class ECLPhoneMask extends ECL {
    /**
     * Конструктор класса
     * @param selector  Селектор поля ввода телефонного номера
     * @param mask  Маска телефонного номера
     * @param debug_mode    Режим отладки
     * @since 1.0.0
     */
    constructor(selector = "phone", mask = "+7 (___) ___ ____", debug_mode = false) {
        super(debug_mode);
        this._phoneMask = mask;
        this._selector = selector;
        this._initialize();
    }

    /**
     * Инициализация
     * @private
     * @since 1.0.0
     */
    _initialize() {
        const elems = document.querySelectorAll(this._selector);
        var _this = this;
        for (const elem of elems) {
            elem.addEventListener("input", function (e) {
                _this._mask(e)
            });
            elem.addEventListener("focus", function (e) {
                _this._mask(e)
            });
            elem.addEventListener("blur", function (e) {
                _this._mask(e)
            });
        }
    }

    /**
     * Обработка ввода на соответствие маске
     * @param event Событие поля ввода
     * @private
     * @since 1.0.0
     */
    _mask(event) {
        let _input = event.currentTarget;
        const keyCode = event.keyCode;
        const template = this._phoneMask,
            def = template.replace(/\D/g, ""),
            val = _input.value.replace(/\D/g, "");
        this.debug("_mask", "template", template);
        let i = 0,
            newValue = template.replace(/[_\d]/g, function (a) {
                return i < val.length ? val.charAt(i++) || def.charAt(i) : a;
            });
        i = newValue.indexOf("_");
        if (i !== -1) {
            newValue = newValue.slice(0, i);
        }
        let reg = template.substr(0, _input.value.length).replace(/_+/g,
            function (a) {
                return "\\d{1," + a.length + "}";
            }).replace(/[+()]/g, "\\$&");
        reg = new RegExp("^" + reg + "$");
        if (!reg.test(_input.value) || _input.value.length < 5 || keyCode > 47 && keyCode < 58) {
            _input.value = newValue;
        }
        if (event.type === "blur" && _input.value.length < 5) {
            _input.value = "";
        }
    }
}
