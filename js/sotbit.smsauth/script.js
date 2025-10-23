BX.ready(function () {
    window.Smsauth = function (params) {
        this.config = params.option;

        this.isDinamic = params.isDinamic;
        this.formType = params.formType;
        this.defaultCountry = params.defaultCountry;

        this.formMode = params.formMode;
        this.fieldType = params.fieldType;

        this.blockCode = params.blockCode;
        this.form = params.form;

        this.mode = params.mode ? params.mode : 'debug';
        this.inviteTitle = params.inviteTitle || 'Установка пароля';

        this.fieldValue = '';
        this.fieldValid = false;
        this.isSuccessCode = false;
        this.blockReapitCode = false;
        this.isSendCode = false;

        // timer
        this.timerId = null;
        this.deadline = null;

        this.modeField = this.getMode();
        this.init();
        // custom
        this.stage = 1;
        this.hash = '';
    };

    window.Smsauth.prototype = {

        getMode: function () {
            if (this.fieldType == 'email') {
                return this.config.modeEmailAuthorization;
            } else {
                return this.config.modeSmsAuthorization;
            }
        },

        init: function () {
            this.formNode = document.querySelector('#' + this.form);
            this.currentFieldNode = this.formNode.querySelector("[data-type=" + this.fieldType + "]");
            this.parent = this.formNode.closest('noindex');
            this.backBtn = this.parent && this.parent.querySelector('[data-stage-back]');
            if (this.modeField != 'password' || (this.formType == 'forgot_password' && this.fieldType == 'phone')) {
                this.blockCodeNode = this.formNode.querySelector('#' + this.blockCode);
                this.fieldEdit = this.blockCodeNode.querySelector('[data-type="edit-field"]');
                this.codeFieldNode = this.blockCodeNode.querySelector('[name="CONFIRMATION_CODE"]');

                this.fieldCodeEdit = this.formNode.querySelector('[data-type="edit-code"]');
                this.blockTimerBlock = this.formNode.querySelector('[data-type="timer-edit"]');
                this.blockTimer = this.blockTimerBlock.querySelector('.text-muted');
                this.blockFieldChangeLink = this.formNode.querySelector('[data-type="regist-email"]');
                this.btnTooglePassword = this.formNode.querySelectorAll('button[data-toogle-password]');

                this.messageTitlefield = null;
                this.imgTitlefield = null;

                if (this.isDinamic) {
                    this.fieldCodeEdit = this.blockCodeNode.querySelector('[data-type="edit-code"]');
                    this.messageTitlefield = this.blockCodeNode.querySelector('[data-type="message-label-field"]');
                    this.imgTitlefield = this.blockCodeNode.querySelector('[data-type="img-label-field"]');
                    this.$hours = this.blockCodeNode.querySelector('.timer__hours');
                    this.$minutes = this.blockCodeNode.querySelector('.timer__minutes');
                    console.log(this.formNode.querySelector('.timer__minutes'))
                    this.$seconds = this.blockCodeNode.querySelector('.timer__seconds');
                } else {

                    this.$hours = this.formNode.querySelector('.timer__hours');
                    this.$minutes = this.formNode.querySelector('.timer__minutes');
                    this.$seconds = this.formNode.querySelector('.timer__seconds');
                }
                this.blockTimer = this.formNode.querySelector('.block-timer');


            }
            this.hash = this.getHash();
            if (this.hash && this.hash.length > 0 && this.hash.includes('@')) {
                this.authTitle = document.querySelector('.auth__title');
                this.subtitle = document.querySelector('.auth__subtitle');
                this.userInput = document.querySelector('[name="USER_LOGIN"]');
                this.hashSubmit = this.formNode.querySelector('[type="submit"]');
                if (this.authTitle && this.userInput) {
                    this.authTitle.textContent = this.inviteTitle;
                    this.subtitle.textContent = 'Подтвердите адрес электронной почты';
                    this.hashSubmit.value = 'Подтвердить'
                    this.userInput.value = this.hash;
                    setTimeout(() => {
                        this.userInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }, 0)
                }
            }
            this.addEvents();


        },

        addEvents: function () {

            this.formNode.querySelectorAll('input').forEach(function (input) {
                input.addEventListener('keydown', function (e, ev) {
                    if (e.keyCode === 13) {
                        e.preventDefault()
                        e.stopImmediatePropagation()
                        return false
                    }
                })
            })

            if (this.fieldType == 'phone') {
                if (this.currentFieldNode) {
                    this.currentFieldNode.addEventListener('input', (e) => {
                        let strReplase = '';
                        for (let i = 0; i < this.mask.mask.length; i++) {
                            strReplase += this.mask.mask[i];
                            if (!(this.mask.mask[i + 1] > 0)) {
                                break;
                            }
                        }
                        const str = e.data && e.data.slice((this.mask.mask.lastIndexOf(strReplase) + strReplase.length), e.data.length);
                        str && this.mask.setValue(str);
                    });
                }

                BX.MaskedInput.prototype.onInputPaste = function (pastedData) {

                    let strReplase = '';
                    for (let i = 0; i < this.mask.length; i++) {
                        strReplase += this.mask[i];
                        if (!(this.mask[i + 1] > 0)) {
                            break;
                        }
                    }
                    let str = '';

                    if (pastedData.includes(strReplase)) {
                        str = pastedData.slice((this.mask.lastIndexOf(strReplase) + strReplase.length), pastedData.length);
                    } else {
                        strReplase = strReplase.slice(1, (strReplase.length - 1));
                        pastedData.includes(strReplase);
                        if (pastedData.includes(strReplase)) {
                            str = pastedData.slice((this.mask.lastIndexOf(strReplase) + strReplase.length), pastedData.length);
                        } else {
                            str = pastedData;
                        }
                    }

                    this.setValue(str);
                };

                if (!this.defaultCountry) {
                    this.defaultCountryMask = this.getCookie('country').toUpperCase() ? this.getCookie('country').toUpperCase() : 'RU';
                    this.defaultCountryFlag = this.getCookie('country').toUpperCase() ? this.getCookie('country').toUpperCase() : 'RU';
                } else {
                    this.defaultCountryMask = this.defaultCountry.toUpperCase();
                    this.defaultCountryFlag = this.defaultCountry;
                }

                const maskPhone = new BX.MaskedInput({
                    mask: window.phoneMasks[this.defaultCountryMask],
                    input: this.currentFieldNode,
                    definitions: [{
                        "rule": "[0-9]",
                        "char": "#",
                    }],
                    placeholder: '_',
                });

                this.currentFieldNode.type = 'text'; // to fool the initializer params.node.type !== 'text'

                const phone = new BX.PhoneNumber.Input({
                    node: this.currentFieldNode,
                    // flagNode: BX('flag'),
                    flagSize: 24,
                    // defaultCountry: this.defaultCountryFlag,
                    defaultCountry: "RU",
                    onChange: function (e) {
                        maskPhone.setValue('');
                        document.cookie = "country=" + e.country.toLowerCase();
                        maskPhone.setMask(window.phoneMasks[e.country]);
                    }
                });

                // this.currentFieldNode.type = 'tel'; // to fool the initializer params.node.type !== 'text'

                if (this.modeField != 'password' || (this.formType == 'forgot_password' && this.fieldType == 'phone')) {
                    this.mask = maskPhone;
                    BX.addCustomEvent(maskPhone, 'change', BX.delegate(this.changeField, this));
                }

            } else {
                if (this.modeField != 'password') {
                    if (this.fieldType == 'email' && this.isDinamic) {
                        if (!!this.currentFieldNode) {
                            this.currentFieldNode.addEventListener('focusout', (e) => {
                                this.changeField(e);
                            });
                        }
                    } else {
                        this.currentFieldNode.addEventListener('input', (e) => {
                            this.changeField(e);
                        });
                    }

                }

            }

            if (this.codeFieldNode) {
                this.codeFieldNode.addEventListener('input', (e) => {
                    this.changeCode(e);
                });
            }

            if (this.fieldEdit) {
                this.fieldEdit.addEventListener('click', (e) => {
                    this.editFieldValue(e);
                });
            }

            if (this.fieldCodeEdit) {
                this.fieldCodeEdit.addEventListener('click', (e) => {

                    if (this.fieldCodeEdit.closest('[data-type="timer-edit"]')) {
                        this.sendCode();
                        this.fieldCodeEdit.style.display = 'none';
                    } else {
                        this.editCode(e);
                    }
                });
            }


            if (this.formNode && this.modeField != 'password') {
                BX.bind(this.formNode, 'submit', this.submitForm.bind(this, this));
            } else {
                if (this.formType == 'forgot_password' && this.fieldType == 'phone') {
                    BX.bind(this.formNode, 'submit', this.submitForm.bind(this, this));
                } else {
                    BX.bind(this.formNode, 'submit', this.submitFormPassword.bind(this, this));
                }
            }

            if (this.btnTooglePassword) {
                for (let btn of this.btnTooglePassword) {
                    btn.addEventListener('click', function () {
                        const inputPassword = event.target.closest('.form-control-feedback-end')?.querySelector('input');

                        if (!inputPassword) return;

                        const type = inputPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                        this.querySelector('i').classList.toggle('ph-eye-slash');
                        inputPassword.setAttribute('type', type);
                    })
                }
            }

            if (this.backBtn) {
                this.backBtn.addEventListener('click', () => {
                    this.stage -= 1;
                    this.stageShow();
                    this.formNode.querySelector('.js-form-validate-btn').classList.remove('disabled');
                })
            }
        },

        encodeUTF16LE: function (str) {
            var byteArray = new Uint8Array(str.length * 2);
            for (var i = 0; i < str.length; i++) {
                byteArray[i * 2] = str.charCodeAt(i) // & 0xff;
                byteArray[i * 2 + 1] = str.charCodeAt(i) >> 8 // & 0xff;
            }
            return byteArray[0];

        },

        getCookie: function (cname) {
            let name = cname + "=";
            let decodedCookie = decodeURIComponent(document.cookie);
            let ca = decodedCookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) == ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return "";
        },

        changeField: function (e = null) {
            let isValid = false;

            if (this.fieldType == 'email') {
                this.fieldValue = this.currentFieldNode.value.replaceAll(' ', '');
                isValid = this.validateEmail(this.currentFieldNode.value);
            }

            if (this.fieldType == 'phone') {

                let strReplase = '';
                for (let i = 0; i < this.mask.mask.length; i++) {
                    strReplase += this.mask.mask[i];
                    if (!(this.mask.mask[i + 1] > 0)) {
                        break;
                    }
                }
                // console.log(e, strReplase,)
                // str = e.slice((this.mask.mask.lastIndexOf(strReplase) + strReplase.length), e.length);
                // console.log(str)

                //this.mask.setValue(str);

                if (this.mask.test(this.mask.getValue())) {
                    this.fieldValue = this.currentFieldNode.value.replaceAll(' ', '');
                    this.fieldValue = this.fieldValue.replace('(', '').replace(')', '').replaceAll('-', '');
                    isValid = true;
                }
            }

            if (isValid) {
                this.fieldValid = true;
                if (this.isDinamic) {
                    this.sendCode();
                }
            } else {
                //this.viewedError(this.currentFieldNode.parentNode, [BX.message('REGISTER_SUBMIT_ERROR_VALID')]);
            }
        },

        sendCode: function () {
            const SmsAuth = this;
            let pass = '';

            if (this.formNode.querySelector('[name="USER_PASSWORD"]')) {
                pass = this.formNode.querySelector('[name="USER_PASSWORD"]').value;
            }

            BX.ajax.runAction('sotbit:smsauth.' + this.mode + '.sendCode', {
                data: {
                    fieldValue: this.fieldValue,
                    type: this.formType,
                    typeField: this.fieldType,
                    password: pass
                },
            }).then(function (response) {
                SmsAuth.fieldValid = true;
                SmsAuth.isSendCode = true;
                const data = JSON.parse(response.data);
                SmsAuth.replaseLableField(data.includes('@') ? data : SmsAuth.phoneNumberMask(data));
                SmsAuth.hiddenBlockField();
                SmsAuth.viewedBlockCode();
                SmsAuth.timerStart();
                SmsAuth.hash = SmsAuth.getHash();
                if (SmsAuth.hash && SmsAuth.hash.length > 0 && SmsAuth.hash.includes('@')) {
                    const description = document.querySelector('[data-type="message-label-field"]');
                    if (description) {
                        description.textContent = 'На указанный номер телефона отправляется одноразовый код (SMS).';
                    }
                }

                if (SmsAuth.formType == 'autorization') {
                    // SmsAuth.stageShow();
                    SmsAuth.formNode.querySelector('[type="submit"]').setAttribute('value', BX.message('AUTH_SUBMIT_BTN'));
                }

                if (SmsAuth.formType == 'registration' && SmsAuth.isDinamic === false) {
                    SmsAuth.formNode.querySelector('[type="submit"]').textContent = BX.message('REGISTER_SYSTEM_AUTH_REGISTRATION');
                }
                SmsAuth.hiddenError();
            }, function (response) {
                let result = 0;
                for (let i = 0; i < response.errors.length; i++) {
                    const error = response.errors[i];
                    if (error.code === "not_active") {
                        result++;
                        break;
                    }
                }

                if (result > 0) {
                    const btnResend = document.querySelector('[data-mail-resend]');
                    btnResend.style.display = 'block';
                    SmsAuth.formNode.querySelector('.js-form-validate-btn').classList.add('disabled');
                }

                SmsAuth.viewedError(SmsAuth.currentFieldNode, response.errors);
            });
        },

        stageShow: function () {
            this.parent.querySelectorAll("[data-stage]").forEach(el =>
                Number(el.getAttribute('data-stage')) === this.stage
                    ? el.style.display = 'block'
                    : el.style.display = 'none'
            );
        },

        phoneNumberMask: function (str) {
            let result = '';
            for (let i = 0; i < str.length; i++) {
                const letter = str.charAt(i);
                if (i === 1 || i === 4) {
                    result += letter + ' ';
                } else if (i === 5 || i === 6 || i === 8) {
                    result += '*';
                } else if (i === 7 || i === 9) {
                    result += '*-';
                } else {
                    result += letter;
                }
            }
            return result;
        },

        changeCode: function (e) {
            let elem = e.target;

            if (elem.value.length > elem.maxLength)
                elem.value = elem.value.slice(0, elem.maxLength);

            if ((elem.value.length == elem.maxLength) && (this.isSuccessCode === false)) {

                const SmsAuth = this;
                document.body.classList.add('loading');
                BX.ajax.runAction('sotbit:smsauth.' + this.mode + '.checkCode', {
                    data: {
                        code: elem.value,
                        fieldValue: this.fieldValue,
                    },
                }).then(function (response) {
                    SmsAuth.isSuccessCode = true;
                    document.body.classList.remove('loading');
                    if (SmsAuth.formType !== "forgot_password") {
                        SmsAuth.authorize();
                    }

                    if (SmsAuth.isDinamic) {
                        BX.hide(SmsAuth.codeFieldNode.closest('.col-md-12'));
                        BX.hide(SmsAuth.fieldEdit);
                        SmsAuth.hiddenError();

                        BX.show(SmsAuth.imgTitlefield);
                        SmsAuth.imgTitlefield.style.display = 'inline-block';
                        SmsAuth.messageTitlefield.innerHTML = SmsAuth.fieldType == 'email' ? BX.message('REGISTER_FIELD_EMAIL_TO_SUCCESS') : BX.message('REGISTER_FIELD_PHONE_TO_SUCCESS');
                    }
                }, function (response) {
                    document.body.classList.remove('loading');
                    SmsAuth.viewedError(SmsAuth.codeFieldNode, response.errors);
                });
            }
        },

        editFieldValue: function (e) {
            if (this.isSendCode) {
                const smsObject = this;
                BX.ajax.runAction('sotbit:smsauth.' + this.mode + '.deleteCode', {
                    data: {
                        fieldValue: this.fieldValue
                    },
                }).then(function (response) {
                    smsObject.isSendCode = false;
                    smsObject.hiddenError();
                    smsObject.viewedBlockField();
                    smsObject.hiddenedBlockCode();

                    if (BX.message('BTN_SEND_CODE')) {
                        smsObject.formNode.querySelector('[type="submit"]').setAttribute('value', BX.message('BTN_SEND_CODE'));
                    }
                    smsObject.timerStart();
                }, function (response) {
                    //console.log(response);
                });
            }
        },

        editCode: function (e) {
            if (this.blockReapitCode) {
                const SmsAuth = this;

                if (this.formNode.querySelector('[name="USER_PASSWORD"]')) {
                    pass = this.formNode.querySelector('[name="USER_PASSWORD"]').value;
                }

                BX.ajax.runAction('sotbit:smsauth.' + this.mode + '.sendCode', {
                    data: {
                        fieldValue: this.fieldValue,
                        type: this.formType,
                        typeField: this.fieldType,
                        password: pass
                    },
                }).then(function (response) {
                    SmsAuth.isSendCode = true;
                    SmsAuth.replaseLableField(JSON.parse(response.data));
                    SmsAuth.hiddenEditCode();
                    SmsAuth.timerStart();
                }, function (response) {
                    console.log(response);
                });
            }
        },

        submitForm: function (item, e = null) {

            e.stopImmediatePropagation();
            if (item.isDinamic && item.isSuccessCode) {
                return true;
            } else {
                e.preventDefault();
            }

            if (item.fieldValid === false) {
                // // if(item.currentFieldNode.parentNode.tagName !=)
                //  console.log(BX.message('REGISTER_SUBMIT_ERROR_VALID'))
                //  item.viewedError(item.currentFieldNode.parentNode, [BX.message('REGISTER_SUBMIT_ERROR_VALID')]);
            }

            if (item.isSendCode) {
                if (item.isSuccessCode) {

                    if ((item.formType == 'autorization') && (item.config.enableAuthorization == 'on' || item.config.useStandardFieldsAuthorization == 'on')) {
                        item.authorize();
                    }

                    if (item.formType == 'registration') {
                        item.registrationSubmit();
                    }
                    if (item.formType == 'forgot_password') {
                        item.formNode.submit();
                    }
                } else {
                    item.viewedError(item.codeFieldNode, [BX.message('REGISTER_SUBMIT_ERROR_VALID')]);
                    return false;
                }
            } else {

                if (item.fieldValue) {
                    if (e.submitter.name === "resend") {
                        item.resendMailCode();
                    } else {
                        item.sendCode();
                    }
                } else {
                    item.viewedError(item.currentFieldNode.parentNode, [BX.message('EMPTY_FIELD')]);
                }
            }
        },

        resendMailCode: function () {
            const self = this;
            BX.ajax.runAction('sotbit:smsauth.' + this.mode + '.resendMail', {
                data: {
                    fieldValue: this.fieldValue,
                    typeField: this.fieldType
                },
            }).then(function (response) {
                // location.reload();
                if (self.formType !== 'autorization') {
                    self.stage++;
                    self.stageShow();
                }
            }, function (response) {
                console.log(response);
            });
        },

        submitFormPassword: function (item, e = null) {
            e.preventDefault();
            if (item.formType == 'forgot_password') {
                item.forgotPasswordonSubmit();
            } else {
                item.registrationSubmit();
            }
        },

        registrationSubmit: function () {
            const formInputs = {};
            this.formNode.querySelectorAll('input').forEach(input => formInputs[input.name] = input);

            if (formInputs.USER_CONFIRM_PASSWORD) {
                formInputs.USER_CONFIRM_PASSWORD.value = formInputs.USER_PASSWORD.value;
            }

            if (formInputs.USER_EMAIL) {
                formInputs.USER_LOGIN.value = formInputs.USER_EMAIL.value;
            } else {
                formInputs.USER_LOGIN.value = formInputs.USER_LOGIN.value.replace('(', '').replace(')', '').replaceAll('-', '');
            }

            // if(formInputs.USER_PHONE_NUMBER){
            //     formInputs.USER_LOGIN.value = formInputs.USER_LOGIN.value.replace('(', '').replace(')', '').replaceAll('-', '');
            //     formInputs.USER_PHONE_NUMBER.value = formInputs.USER_LOGIN.value;
            // }

            this.formNode.submit();
        },

        forgotPasswordonSubmit: function () {
            const formInputs = {};
            this.formNode.querySelectorAll('input').forEach(input => formInputs[input.name] = input);
            formInputs.USER_LOGIN.value = formInputs.USER_EMAIL?.value ? formInputs.USER_EMAIL.value : formInputs.USER_PHONE_NUMBER.value.replace('(', '').replace(')', '').replaceAll('-', '');
            if (formInputs.USER_PHONE_NUMBER) {
                formInputs.USER_PHONE_NUMBER.value = '';
            }

            this.formNode.submit();
        },

        authorize: function () {
            document.body.classList.add('loading');
            BX.ajax.runAction('sotbit:smsauth.' + this.mode + '.authorize', {
                data: {
                    fieldValue: this.fieldValue,
                    typeField: this.fieldType
                },
            }).then(function (response) {
                // location.reload();
                window.location.href = '/admin_panel/';
                console.log(response);
            }, function (response) {
                console.log(response);
                document.body.classList.remove('loading');
            });
        },

        countdownTimer: function () {
            const diff = this.deadline - new Date();

            if (diff <= 0) {
                this.hiddenTimer();
                this.viewedEditCode();
                this.blockReapitCode = true;
                clearInterval(this.timerId);
            }

            const hours = diff > 0 ? Math.floor(diff / 1000 / 60 / 60) % 24 : 0;
            const minutes = diff > 0 ? Math.floor(diff / 1000 / 60) % 60 : 0;
            const seconds = diff > 0 ? Math.floor(diff / 1000) % 60 : 0;


            if (hours != 0)
                this.$hours.textContent = hours < 10 ? '0' + hours + ':' : hours + ':';

            this.$minutes.textContent = minutes < 10 ? '0' + minutes + ':' : minutes + ':';

            this.$seconds.textContent = seconds < 10 ? '0' + seconds : seconds;
        },

        replaseLableField: function (labelValue) {
            if (this.blockCodeNode.querySelector('[data-type="label-field"]')) {
                this.blockCodeNode.querySelector('[data-type="label-field"]').innerHTML = labelValue;
            }
        },

        timerStart: function () {
            let date = new Date();
            let deadlene = date.getSeconds() + Number(this.config.breakTime);
            date.setSeconds(deadlene)
            this.deadline = date;
            this.countdownTimer();
            this.timerId = setInterval(this.countdownTimer.bind(this), 1000);
            this.viewedTimer();
        },

        hiddenError: function () {
            let arrErrorNode = document.querySelectorAll('.alert-code-phone');

            if (arrErrorNode) {
                for (let i = 0; i < arrErrorNode.length; i++) {
                    arrErrorNode[i].remove();
                }
            }
        },

        hiddenTimer: function () {
            BX.hide(this.blockTimer);
        },

        viewedTimer: function () {
            this.blockTimer.removeAttribute('style');
            BX.show(this.blockTimer);
        },

        viewedEditCode: function () {
            this.fieldCodeEdit.removeAttribute('style');
            BX.show(this.fieldCodeEdit);
        },

        hiddenEditCode: function () {
            BX.hide(this.fieldCodeEdit);
        },

        validateEmail: function () {
            const EMAIL_REGEXP = /^[A-Za-z0-9_.-]+@[A-Za-z0-9_.-]+\.[A-Za-z]{2,4}$/;
            return EMAIL_REGEXP.test(this.fieldValue);
        },

        viewedError: function (target, errors) {
            this.hiddenError();
            if (!!errors) {
                nodeError = document.createElement("span");
                nodeError.className = "alert-code-phone";
                nodeError.innerHTML = '';

                for (let i = 0; i < Object.keys(errors).length; i++) {
                    if (!!errors[i].message) {

                        tmp = document.createElement("div");
                        tmp.innerHTML = errors[i].message;
                        nodeError.append(tmp);
                    } else {
                        if (!!errors[i]) {
                            tmp = document.createElement("div");
                            tmp.innerHTML = errors[i];
                            nodeError.append(tmp);
                        }
                    }
                }
                nodeError.style.display = 'block';
                if (!!errors[0].message) {
                    if (target.parentNode.parentNode.tagName != 'FORM') {
                        target.parentNode.parentNode.append(nodeError);
                    } else {
                        target.parentNode.append(nodeError);
                    }

                } else {
                    if (!!errors[0]) {
                        target.parentNode.append(nodeError);
                    }
                }

            }
        },

        hiddenBlockField: function () {
            BX.hide(this.currentFieldNode.closest('.system_auth__input-group'));
            if (this.modeField == 'password_code' && this.formType != 'forgot_password') {
                if (this.formNode.querySelector('[data-type="password"]')) {
                    BX.hide(this.formNode.querySelector('[data-type="password"]'));
                }
            }
        },

        viewedBlockField: function () {
            BX.show(this.currentFieldNode.closest('.system_auth__input-group'));

            if (this.modeField == 'password_code' && this.formType != 'forgot_password') {
                if (this.formNode.querySelector('[data-type="password"]')) {
                    BX.show(this.formNode.querySelector('[data-type="password"]'));
                }
            }
        },

        hiddenedBlockCode: function () {
            BX.hide(this.blockCodeNode);
            this.codeFieldNode.value = '';
        },

        viewedBlockCode: function () {
            BX.show(this.blockCodeNode);
        },

        getHash: function () {
            if (location.hash) {
                return location.hash.replace("#", "");
            }
        },

        setHash: function (hash) {
            hash = hash ? `#${hash}` : window.location.href.split("#")[0];
            history.pushState("", "", hash);
        }
    }
});