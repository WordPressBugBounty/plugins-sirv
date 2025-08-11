jQuery(function ($) {
    $(document).ready(function () {

        //------------globals vars--------------------
        let isSyncing = false;
        let isNewAccount = false;
        let isThumbsAjax = false;
        let preventedSizes = '';
        let sirvJsCompressedSizes = {};
        let isStopViewSyncing = false;
        //--------------------------------------------

        function Validator(){};
        Validator.prototype.invalidValidate = function(fieldValue, validatorFunc, limitValue=null, callback=null){
            if(validatorFunc(fieldValue, limitValue)){
                if(!!callback) callback();
                return true;
            }

            return false;
        }

        Validator.prototype.minLenght = function(fieldValue, limitValue){
            return fieldValue.length <= limitValue;
        }

        Validator.prototype.maxLenght = function(fieldValue, limitValue){
            return fieldValue.length >= limitValue;
        }

        Validator.prototype.lenghtBetween = function(fieldValue, limitValue){
            return fieldValue.length < limitValue.min || fieldValue.length > limitValue.max;
        }

        Validator.prototype.empty = function(fieldValue){
            return fieldValue === '';
        }

        Validator.prototype.email = function(fieldValue){
            //let regex = /^((?!\.)[\w-_.]*[^.])(@\w+)(\.\w+(\.\w+)?[^.\W])$/i;
            //let regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/i;
            let regex = /^[a-z0-9!#$%&'"*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'"*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i;
            return !regex.test(fieldValue);
        }

        Validator.prototype.name = function(fieldValue){
            let regex = /^[a-zA-Z ,.'-]+$/;
            return !regex.test(fieldValue);
        }

        Validator.prototype.accountSuffix = function(fieldValue){
            let regex = /^.*-(srv[0-9]*|direct|cdn)$/i;
            return regex.test(fieldValue);
        }

        Validator.prototype.upperCase = function(fieldValue){
            let regex = /[A-Z]+/;
            return regex.test(fieldValue);
        }

        Validator.prototype.equalString = function(fieldValue, stringsArr){
            let result = [];
            result = stringsArr.filter(function(str){return fieldValue.indexOf(str) !== -1});
            return result.length > 0;
        }


        //add scrollable menu for help tab
        $(window).on('scroll', function () {
            if ($(window).scrollTop() > 170 && $(window).width() > 767) {
                $('.sirv-help-menu').addClass('sirv-help-menu-fixed');
                $('.sirv-help-data').addClass('sirv-help-data-margin');
            } else {
                $('.sirv-help-menu').removeClass('sirv-help-menu-fixed');
                $('.sirv-help-data').removeClass('sirv-help-data-margin');
            }
        });


        // Add smooth scrolling on all links inside the navbar
        $(".sirv-help-menu a").on('click', function (event) {
            // Make sure this.hash has a value before overriding default behavior
            if (this.hash !== "") {
                // Prevent default anchor click behavior
                event.preventDefault();

                // Store hash
                var hash = this.hash;

                // Using jQuery's animate() method to add smooth page scroll
                // The optional number (800) specifies the number of milliseconds it takes to scroll to the specified area
                $('html, body').animate({
                    scrollTop: $(hash).offset().top
                }, 800, function () {

                    // Add hash (#) to URL when done scrolling (default click behavior)
                    window.location.hash = hash;
                });
            } // End if
        });


        $('.sirv-wrapped-nav .nav-tab-wrapper > a').on('click', function (e) {
            changeTab(e, $(this));
        });
        $(".sirv-show-settings-tab").on("click", function (e) {
            changeTab(e, $(".nav-tab-sirv-settings"));
        });

        $('.sirv-show-sync-tab').on('click', function (e) {
            changeTab(e, $('.nav-tab-sirv-cache'));
        });


        function changeTab(e, $object) {
            if (!!e) e.preventDefault();
            $('.sirv-tab-content').removeClass('sirv-tab-content-active');
            $('.sirv-wrapped-nav .nav-tab-wrapper > a').removeClass('nav-tab-active');
            $('.sirv-tab-content' + $object.attr('href')).addClass('sirv-tab-content-active');
            $object.addClass("nav-tab-active").trigger("blur");
            $('#active_tab').val($object.attr('href'));
            let hash = $object.attr('data-link');
            window.location.hash = hash;

            document.dispatchEvent(new CustomEvent('options_tab_changed', {
                detail: {
                    hash: hash
                }
            }));
        }


        function getTabFromUrlHash(){
            let urlHash = window.location.hash.replace('#', '');
            urlHash = urlHash.replace('sirv-', '');
            urlHash = urlHash == 'undefined' ? 'settings' : urlHash;
            if (!!urlHash) changeTab(undefined, $('.nav-tab-sirv-' + urlHash));
        }


        $('.sirv-toogle-pass').on('click', tooglePassVisibility);
        function tooglePassVisibility() {
            let $passInput = $('.sirv-pass');
            let $toggleIcon = $('.sirv-toogle-pass span.dashicons');
            let inputType = $passInput.attr('type');

            if (inputType == 'password') {
                $passInput.attr('type', 'text');
                $toggleIcon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                $passInput.attr('type', 'password');
                $toggleIcon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
            $passInput.trigger('focus');
        }


        $('.sirv-pass').on('keyup', function (e) {
            if (e.keyCode == 13) {
                $('.sirv-init').trigger('click');
            }
        });

        $("input[name=SIRV_OTP_TOKEN]").on('input', sirvOtpFire);
        function sirvOtpFire(){
            let otpToken = $(this).val();
            if(otpToken.length == 6){
                sirvOtp();
            }
        }


        $('.sirv-init').on('click', sirvInitAccount);
        function sirvInitAccount(){
            hideMessages(".sirv-error");

            let name = $('input[name=SIRV_NAME]').val().trim().split(' ');

            let data = {};
            data['action'] = 'sirv_init_account';
            data['_ajax_nonce'] = sirv_options_data.ajaxnonce,
            data['email'] = $('input[name=SIRV_EMAIL]').val().trim();
            data['pass'] = $('input[name=SIRV_PASSWORD]').val().trim();
            data['firstName'] = name[0] || '';
            data['lastName'] = name[1] || '';
            data['accountName'] = $('input[name=SIRV_ACCOUNT_NAME]').val().trim();
            data['isNewAccount'] = $('input[name=SIRV_ACCOUNT_NAME]').is(':visible') ? 1 : 0;


            let validator = new Validator();

            if(validator.invalidValidate(data['email'], validator.empty)){
                showMessage('.sirv-error', 'Please specify email.');
                return;
            }

            if(validator.invalidValidate(data['pass'], validator.empty)){
                showMessage('.sirv-error', 'Please specify password.');
                return;
            }

            if(!!data['isNewAccount']){
                if(validator.invalidValidate(data['email'], validator.email)){
                    showMessage('.sirv-error', 'Please enter correct email.');
                    return;
                }

                let restirictedEmailDomains = ['mail.ru'];
                if(validator.invalidValidate(data['email'], validator.equalString, restirictedEmailDomains)){
                    showMessage('.sirv-error', 'Please use a company email address (not '+ restirictedEmailDomains.join(', ') +')');
                    return;
                }

                if(validator.invalidValidate(data['pass'], validator.upperCase)){
                    showMessage('.sirv-error', 'Uppercase symbols does not permitted to use in the password.');
                    return;
                }

                if(validator.invalidValidate(data['pass'], validator.lenghtBetween, {min: 8, max: 64})){
                    showMessage('.sirv-error', 'Choose a password at least 8 characters long and less than 64 characters.');
                    return;
                }

                if(validator.invalidValidate(data['firstName'], validator.empty)){
                    showMessage('.sirv-error', 'Please specify your first name.');
                    return;
                }

                if(validator.invalidValidate(data['firstName'], validator.name)){
                    showMessage('.sirv-error', 'Please enter correct first name.');
                    return;
                }

                if(validator.invalidValidate(data['firstName'], validator.lenghtBetween, {min: 2, max: 35})){
                    showMessage('.sirv-error', 'First name must be 2-35 characters.');
                    return;
                }

                if(validator.invalidValidate(data['lastName'], validator.empty)){
                    showMessage('.sirv-error', 'Please specify your last name.');
                    return;
                }

                if(validator.invalidValidate(data['lastName'], validator.name)){
                    showMessage('.sirv-error', 'Please enter correct last name.');
                    return;
                }

                if(validator.invalidValidate(data['lastName'], validator.lenghtBetween, {min: 2, max: 35})){
                    showMessage('.sirv-error', 'Last name must be 2-35 characters.');
                    return;
                }

                if(validator.invalidValidate(data['accountName'], validator.empty)){
                    showMessage('.sirv-error', 'Please specify account name.');
                    return;
                }

                if(validator.invalidValidate(data['accountName'], validator.lenghtBetween, {min: 6, max: 30})){
                    showMessage('.sirv-error', 'Account name must be 6-30 characters. It may contain letters, numbers or hyphens (no spaces).');
                    return;
                }

                if(validator.invalidValidate(data['accountName'], validator.accountSuffix)){
                    let accName = data['accountName'];
                    showMessage('.sirv-error', 'Account name <b>' + accName + '</b> is not permitted. You could try <b>'+ accName.replace('-', '') +'</b> instead.');
                    return;
                }
            }

            $.ajax({
                url: ajaxurl,
                data: data,
                type: 'POST',
                dataType: "json",
                beforeSend: function (){
                    $('.sirv-connect-account-wrapper').addClass('sirv-loading');
                },
            }).done(function (res) {
                //debug
                //console.log(res);

                $('.sirv-connect-account-wrapper').removeClass('sirv-loading');

                if(!!res && !!res.error){
                    let error = res.error;

                    const regExPattern = new RegExp(/(?:port\s?)443/im);
                    if(regExPattern.test(error)){
                        error = "Port 443 (HTTPS) on your WordPress server is blocking requests to api.sirv.com. Check your firewall or other server settings to allow requests to api.sirv.com.";
                    }

                    showMessage('.sirv-error', error);
                }else if(!!res && !!res.isOtpToken){
                    showOtpInput();
                }else if(!!res && !!res.allow_users){
                    showUsersList(res);
                }

            }).fail(function (jqXHR, status, error) {
                $('.sirv-connect-account-wrapper').removeClass('sirv-loading');
                console.log("Error during ajax request: " + error);
                showMessage('.sirv-error', "Error during ajax request: " + error);
            });
        }


        function showOtpInput(){
            let $button = $("input.sirv-login-action");

            $(".sirv-otp-code").show();
            $("input[name=SIRV_OTP_TOKEN]").focus();
            $(".sirv-field").hide();
            $button.removeClass("sirv-init");
            $button.addClass("sirv-otp");
            $button.val("Verify");
            $button.off("click");
            $button.on("click", sirvOtp);
        }


        function showUsersList(res){
            let $button = $("input.sirv-login-action");
            $button.removeClass('sirv-init');
            $button.removeClass("sirv-otp");
            $button.addClass('sirv-log-in');
            $button.val("Connect account");
            $button.off('click');
            $button.on('click', sirvLogin);

            $(".sirv-otp-code").hide();
            $('.sirv-select').show();
            $('.sirv-field').hide();

            //$('select[name="sirv_account"]').append('<option value="none" disabled>Choose...</option>');

            for(let i in res.allow_users){
                $('select[name="sirv_account"]').append('<option value="' + res.allow_users[i].token + '">' + res.allow_users[i].alias + '</option>');
            }

            if(!!res.deny_users && res.deny_users.length > 0){
                $('select[name="sirv_account"]').append('<option value="none" disabled>---------------------------------</option>');
                $('select[name="sirv_account"]').append('<option value="none" disabled>Accounts with an insufficient role</option>');
                for (let i in res.deny_users) {
                    $('select[name="sirv_account"]').append('<option value="' + res.deny_users[i].token + '" disabled>' + res.deny_users[i].alias + ' ('+ res.deny_users[i].role +')</option>');
                }
            }

            if (res.allow_users.length == 1) {
                $('select[name="sirv_account"] option').last().prop('selected', 'true').closest('select').trigger('change');
                $('.sirv-log-in').trigger('click');
                $('.sirv-log-in').prop('disabled', true);
            }
        }


        function sirvOtp(){
            hideMessages(".sirv-error");

            let data = {};

            data['action'] = 'sirv_get_users_list';
            data['_ajax_nonce'] = sirv_options_data.ajaxnonce;
            data["email"] = $("input[name=SIRV_EMAIL]").val().trim();
            data["pass"] = $("input[name=SIRV_PASSWORD]").val().trim();
            data["otpToken"] = $("input[name=SIRV_OTP_TOKEN]").val().trim();

            if(data.otpToken.length < 6){
                showMessage('.sirv-error', 'Not enough symbols');
                return;
            }

            if (data.otpToken.length > 6) {
                showMessage('.sirv-error', 'To many symbols');
                return;
            }

            if (!Number.isInteger(+data.otpToken)){
                showMessage('.sirv-error', 'Incorrect value. You can use only integers.');
                return;
            }

            $.ajax({
                url: ajaxurl,
                data: data,
                type: 'POST',
                dataType: "json",
                beforeSend: function(){
                    hideMessages(".sirv-error");
                    $('.sirv-connect-account-wrapper').addClass('sirv-loading');
                },
            }).done(function (res) {
                //debug
                //console.log(res);

                $('.sirv-connect-account-wrapper').removeClass('sirv-loading');

                if( !!res && !!res.error ){
                    showMessage('.sirv-error', res.error);
                }else if(!!res && !!res.allow_users){
                    showUsersList(res);
                }
            }).fail(function (jqXHR, status, error) {
                $('.sirv-connect-account-wrapper').removeClass('sirv-loading');
                console.log("Error during ajax request: " + error);
                showMessage('.sirv-error', "Error during ajax request: " + error);
            });
        }


        function sirvLogin() {
            hideMessages(".sirv-error");

            const selectedValue= $('select[name="sirv_account"]').val();
            const selectedText = $('select[name=sirv_account] option:selected').text();

            let data = {};

            data['action'] = 'sirv_setup_credentials';
            data['_ajax_nonce'] = sirv_options_data.ajaxnonce;
            data['email'] = $('input[name=SIRV_EMAIL]').val();
            data['sirv_account'] = selectedValue;

            if( selectedValue == 'none'){
                showMessage('.sirv-error', "Please choose account first");
                return;
            }

            $(this).prop('disabled', true);
            $("select[name=sirv_account]").prop("disabled", true);
            $(".sirv-load-acc-spinner").css({ display: "flex" });
            $(".sirv-loading-acc-name").text(selectedText);

            $.ajax({
                url: ajaxurl,
                data: data,
                type: 'POST',
                dataType: "json",
                beforeSend: function(){
                    $('.sirv-connect-account-wrapper').addClass('sirv-loading');
                },
            }).done(function (res) {
                //debug
                //console.log(res);

                $('.sirv-connect-account-wrapper').removeClass('sirv-loading');

                if( !!res && !!res.error ){
                    showMessage('.sirv-error', res.error);
                }

                window.location.href = window.location.href.replace(/\#.*/i, '');

            }).fail(function (jqXHR, status, error) {
                $('.sirv-connect-account-wrapper').removeClass('sirv-loading');
                console.log("Error during ajax request: " + error);
                showMessage('.sirv-error', "Error during ajax request: " + error);
            });
        }


        function hideMessages(selector_container) {
            $(selector_container).empty();
        }


        function showMessage(container_selector, message, type='error', isShowCloseButton=false){
            //type: error, info, warning, success
            const allowedTypesToClose = ['info','success'];
            let $container = $(container_selector);

            const closeButtonHTML = inArray(type, allowedTypesToClose) || isShowCloseButton ? `<div><button class="sirv-push-message-close" type="button">&times;</button></div>` : '';

            const html = `
                <div class="sirv-push-message-container sirv-push-message-${type}">
                    <div class="sirv-push-message sirv-push-message-${type}-icon">
                        ${message}
                    </div>
                    ${closeButtonHTML}
                </div>
            `;

            $container.append(html);
        }


        function inArray(val, arr) {
            return arr.indexOf(val) !== -1;
        }


        $("body").on("click", ".sirv-push-message-close", pushMessageClose);
        function pushMessageClose(e){
            $(this).closest(".sirv-push-message-container").remove();
        }


        $('.sirv-disconnect').on('click', disconnectAccount);
        function disconnectAccount(event){
            event.preventDefault();

            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_disconnect',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function(){
                    hideMessages(".sirv-error");
                    $('.sirv-connect-account-wrapper').addClass('sirv-loading');

                },
            }).done(function (res){
                //debug
                //console.log(res);

                if(!!res.error){
                    showMessage('.sirv-error', res.error);
                }

                $('.sirv-connect-account-wrapper').removeClass('sirv-loading');

                if (!!res && !!res.disconnected) {
                    window.location.href = window.location.href.replace(/\#.*/i, '');
                }

            }).fail(function(jqXHR, status, error){
                $('.sirv-connect-account-wrapper').removeClass('sirv-loading');
                console.log("Error during ajax request: " + error);
                showMessage(".sirv-error", error);
            });
        }


        function continueBtn(){
            console.log("continueBtn run");
        }


        $(".sirv-clear-cache").on("click", clearCache);
        function clearCache(e){
            e.preventDefault();

            let type = $(this).attr('data-type');

            if(type == 'all' || type == 'synced'){
                sirvUIShowConfirmDialog(
                    "Sirv plugin cache",
                    "The plugin keeps track of which images have been copied to Sirv. Clearing the cache will mean slightly slower loading of images next time they are requested.",
                    deleteCache,
                    [type],
                );
            }else{
                deleteCache(type);
            }
        }


        function deleteCache(cacheType){
            const spinnerSelector = getClearCacheSpinnerSelector(cacheType);

            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_clear_cache',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                    clean_cache_type: cacheType,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function () {
                    hideMessages(".sirv-sync-messages");
                    $('.sync-errors').hide();
                    $(spinnerSelector).show();
                }
            }).done(function (data) {
                //debug
                //console.log(data);

                if(!!data.error){
                    showMessage('.sirv-sync-messages', data.error);
                }

                updateCacheInfo(data);
                showMessage('.sirv-sync-messages', getMessage(cacheType), 'success');
                $(spinnerSelector).hide();

            }).fail(function (jqXHR, status, error) {
                console.log("Error during ajax request: " + error);
                showMessage('.sirv-sync-messages', "Error during ajax request: " + error);
                $(spinnerSelector).hide();
            });
        }


        function getClearCacheSpinnerSelector(type){
            let cssSelector = '';

            switch (type) {
                case 'synced':
                    cssSelector = ".sirv-synced-clear-cache-action .sirv-traffic-loading-ico";
                    break;

                case 'failed':
                    cssSelector = ".sirv-failed-clear-cache-action .sirv-traffic-loading-ico";
                    break;

                case 'garbage':
                    cssSelector = '.sirv-discontinued-images span.sirv-traffic-loading-ico';
                    break;

                default:
                    break;
            }

            return cssSelector;
        }


        function getMessage(type){
            const sMessage = 'Synced images have been deleted from Sirv cache';
            const fMessage = 'Failed images have been deleted from Sirv cache';
            const aMessage = 'Sirv cache has been cleared';
            const gMessage = 'Old images cleared from Sirv plugin cache';
            let message = '';

            switch (type) {
                case 'synced':
                    message = sMessage;
                    break;
                case 'failed':
                    message = fMessage;
                    break;
                case 'all':
                    message = aMessage;
                    break;
                case 'garbage':
                    message = gMessage;
                    break;
                default:
                    break;
            }

            return message;
        }


        function updateCacheActionItems(data){
            if(!!data){
                let isSynced  = data.SYNCED.count*1 > 0 ? true : false;
                let isFailed  = data.FAILED.count*1 > 0 ? true : false;
                let isGarbage = data.garbage_count*1 > 0 ? true : false;

                $('.sirv-old-cache-count').text(data.garbage_count);

                if (isGarbage){
                    $('.sirv-discontinued-images').show();
                }else{
                    $('.sirv-discontinued-images').hide();
                }

                if(isSynced){
                    $(".sirv-synced-clear-cache-action").show();
                }else{
                    $(".sirv-synced-clear-cache-action").hide();
                }

                if(isFailed){
                    $(".sirv-failed-clear-cache-action").show();
                }else{
                    $(".sirv-failed-clear-cache-action").hide();
                }
            }
        }


        function updateCacheInfo(data){
            if(!!data){
                let isGarbage = data.garbage_count*1 > 0 ? true : false;
                if(data.q*1 > data.total_count*1){
                    data.q = isGarbage
                        ? data.q*1 - data.garbage_count*1 > data.total_count
                            ? data.total_count
                            : data.q * 1 - data.garbage_count * 1
                        : data.total_count;
                }

                $('.sirv-progress-data__complited--text').html(data.q_s);
                $('.sirv-progress-data__complited--size').html(data.size_s);
                $('.sirv-progress-data__queued--text').html(data.queued_s);
                $('.sirv-progress-data__failed--text').html(data.FAILED.count_s);

                $('.sirv-progress__text--percents').html(data.progress + '%');
                $('.sirv-progress__text--complited span').html(data.q_s + ' out of ' + data.total_count_s);

                $('.sirv-progress__bar--line-complited').css('width', data.progress_complited + '%');
                $('.sirv-progress__bar--line-queued').css('width', data.progress_queued + '%');
                $('.sirv-progress__bar--line-failed').css('width', data.progress_failed + '%');

                if(data.FAILED.count*1 > 0){
                    $('.failed-images-block').show();
                    $('.failed-images-block a').show();
                }else{
                    $('.failed-images-block').hide();
                }

                if ( (data.q*1 + data.FAILED.count*1) == data.total_count*1) {
                    if (data.FAILED.count * 1 == 0) {
                        //manageElement('input[name=sirv-sync-images]', true, text = '100% synced', button = true);
                        setButtonSyncState('syncedAll');
                    } else {
                        //manageElement('input[name=sirv-sync-images]', true, text = 'Synced', button = true);
                        setButtonSyncState("synced");
                    }
                }else{
                    //manageElement('input[name=sirv-sync-images]', false, text = 'Sync images', button = true);
                    setButtonSyncState("sync");
                }

                updateCacheActionItems(data);
            }

        }


        //send the message from plugin to support@sirv.com
        $('#send-email-to-sirv').on('click', function () {
            //fields
            let name = $('#sirv-writer-name').val();
            let contactEmail = $('#sirv-writer-contact-email').val();
            let summary = $('#sirv-summary').val();
            let messageText = $('#sirv-text').val();

            //messages;
            let proccessingSendMessage = '<span class="sirv-traffic-loading-ico sirv-no-lmargin"></span> Sending message. This may take some time...';
            let messageSent = 'Your message has been sent.';
            let ajaxError = 'Error during AJAX request. Please try to send the message again or use the <a target="_blank" href="https://sirv.com/contact/">Sirv contact form here</a> <br><br>Error Message: ';
            let sendingError = 'Something went wrong. The most likely reason is that Sendmail is not installed/configured. Please try to send the message again or use the <a target="_blank" href="https://sirv.com/contact/">Sirv contact form here</a>';
            //form messages
            let emptyFields = '<span style="color: red;">Please fill form fields.</span>';
            let incorrectEmail = '<span style="color: red;">Email is incorrect. Please check email field.</span>';

            let generatedViaWP = '\n\n\n---\nThis message was sent from the Sirv WordPress plugin form at ' + document.location.hostname;


            let formMessages = '';

            if (summary == '' || messageText == '' || name == '' || contactEmail == '') {
                formMessages += emptyFields + '\n';
            }

            if (contactEmail.match(/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,3}$/i) == null && contactEmail != '') {
                formMessages += incorrectEmail + '\n';
            }

            if (formMessages != '') {
                $(".sirv-mail-errors-view").html(formMessages);
                return false;
            }

            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_send_message',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                    name: name,
                    emailFrom: contactEmail,
                    summary: summary,
                    text: `Contact name: ${name}\nContact Email: ${contactEmail}\n\n${messageText} ${generatedViaWP}`,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function () {
                    $(".sirv-mail-errors-view").empty();
                    $(".sirv-feedback-msg").empty();
                    $('.sirv-show-result').html(proccessingSendMessage);
                }
            }).done(function (data) {
                //debug
                //console.log(data);

                $(".sirv-show-result").hide();

                if(data.error){
                    showMessage(".sirv-feedback-msg", data.error);
                }

                if (data.result == '1') {
                    showMessage(".sirv-feedback-msg", messageSent, 'success');
                } else {
                    showMessage(".sirv-feedback-msg", sendingError);
                }

                //clear contact form fields
                $('#sirv-writer-name').val('');
                $('#sirv-writer-contact-email').val('');
                $('#sirv-summary').val('');
                $('#sirv-text').val('');

            }).fail(function (jqXHR, status, error) {
                $(".sirv-show-result").hide();
                console.error("Error during ajax request: " + error);
                showMessage(".sirv-feedback-msg", ajaxError + error);
            });

        });


        //sanitize folder name on sirv
        $('#sirv-save-options').on('submit', function () {
            let folderOnSirv = $("[name='SIRV_FOLDER']").val();
            let sanitizedFolderOnSirv = folderOnSirv == '' ? 'WP_' + get_current_domain() : folderOnSirv.replace(/^[\/]*(.*?)[\/]*$/ig, '$1');
            if(sanitizedFolderOnSirv == '') sanitizedFolderOnSirv = 'WP_' + get_current_domain();
            $("[name='SIRV_FOLDER']").val(sanitizedFolderOnSirv);

            return true;
        });


        function get_current_domain(){
            const url = window.location.href;

            return get_domain(url);
        }


        function get_domain(url){
            const urlData = new URL(url);

            return urlData.hostname || "";
        }


        $('input[name=SIRV_USE_SIRV_RESPONSIVE]').on('change', showResponsiveWarning);
        function showResponsiveWarning(){
            let checked = $(this).val();
            let $placeholderWrap = $('.sirv-hide-placeholder');
            let $msg = $('.sirv-responsive-msg');

            if(checked == '1'){
                $msg.slideDown();
                $placeholderWrap.show();
            }else{
                $msg.slideUp();
                $placeholderWrap.hide();
            }
        }


        $('.sirv-option-edit').on('click', optionEdit);
        function optionEdit(e){
            e.preventDefault();

            const $button = $(this);
            const type = $button.attr('data-type')
            const $showValuePart = $(this).parent().find('.sirv-text-to-input-option-text-part');
            const $inputValuePart = $(this).parent().find('.sirv-text-to-input-option-input-part');
            const $input = $inputValuePart.find('input');

            if (type === 'render'){
                $button.attr('data-type', 'edit');
                $button.text('Cancel');
                $showValuePart.hide();
                $inputValuePart.show();
            }

            if (type == 'edit'){
                $button.attr("data-type", "render");
                $button.text("Change");
                $input.val($input.attr('data-restore-value'));
                $showValuePart.show();
                $inputValuePart.hide();
            }
        }


        $(".sirv-sync-images").on("click", initializeMassSync);
        function initializeMassSync(){
            isSyncing = true;
            $('.sirv-sync-messages').empty();
            setButtonSyncState('syncing');
            $('.sirv-progress__bar--line-complited').addClass('sirv-progress-bar-animated');
            $('.sirv-queue').html('Processing (1/2): calculating images in queue...');
            $('.sirv-processing-message').show();
            $('.sync-errors').hide();
            $(".sirv-synced-clear-cache-action").hide();
            $(".sirv-failed-clear-cache-action").hide();
            $('.failed-images-block').hide();
            $('.failed-images-block a').hide();

            massSyncImages();
        }


       //$('.sirv-sync-images').on('click', massSyncImages);
        function massSyncImages(){
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_process_sync_images',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                    sirv_sync_uncached_images: true,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function(){
                    //code here
                },
            }).done(function(data){
                //debug
                //console.log(data);

                if (!!data) {
                    if(!!data.error){
                        showMessage('.sirv-sync-messages', data.error);
                    }

                    $('.sirv-progress-data__complited--text').html(data.q_s);
                    $('.sirv-progress-data__complited--size').html(data.size_s);
                    $('.sirv-progress-data__queued--text').html(data.queued_s);
                    $('.sirv-progress-data__failed--text').html(data.FAILED.count_s);

                    $('.sirv-progress__text--percents').html(data.progress + '%');
                    $('.sirv-progress__text--complited span').html(data.q_s + ' out of ' + data.total_count_s);

                    $('.sirv-progress__bar--line-complited').css('width', data.progress_complited + '%');
                    $('.sirv-progress__bar--line-queued').css('width', data.progress_queued + '%');
                    $('.sirv-progress__bar--line-failed').css('width', data.progress_failed + '%');

                    //let processing_q = data.total_count * 1 - data.q * 1 - data.FAILED.count * 1;
                    //$('.sirv-queue').html('Images in queue: ' + processing_q);
                    $('.sirv-queue').html('Processing (2/2): syncing images...');

                    if(!!data && !isSyncing){
                        setButtonSyncState('sync');
                        $('.sirv-processing-message').hide();
                        $('.sirv-progress__bar--line-complited').removeClass('sirv-progress-bar-animated');
                        updateCacheActionItems(data);
                        return;
                    }

                    if (data.FAILED.count) {
                        $('.failed-images-block').show();
                        $('.failed-images-block a').show();
                    }

                    if (!!data.status && data.status.isStopSync){
                        manageElement('input[name=sirv-sync-images]', true, 'Can\'t sync', true);
                        showMessage('.sirv-sync-messages', data.status.errorMsg);
                        $('.sirv-processing-message').hide();
                        $('.sirv-progress__bar--line-complited').removeClass('sirv-progress-bar-animated');
                        return;
                    }

                    if ((data.q * 1 + data.FAILED.count * 1 ) < data.total_count * 1) {
                        massSyncImages();
                    } else {
                        $('.sirv-progress__bar--line-complited').removeClass('sirv-progress-bar-animated');
                        $('.sirv-processing-message').hide();
                        if (data.FAILED.count * 1 == 0){
                            //manageElement('input[name=sirv-sync-images]', disableFlag = true, text = '100% synced', button = true);
                            setButtonSyncState("syncedAll");
                        }else{
                            //manageElement('input[name=sirv-sync-images]', disableFlag = true, text = 'Synced', button = true);
                            setButtonSyncState("synced");
                        }
                        isSyncing = false;
                        updateCacheActionItems(data);
                    }
                }
            }).fail(function(jqXHR, status, error){
                console.error("status: ", status);
                console.error("Error message: " + error);
                console.error("http code", `${jqXHR.status} ${jqXHR.statusText}`);

                showAjaxErrorMessage(jqXHR, status, error, '.sirv-sync-messages');
                showMessage('.sirv-sync-messages', "Please reload this page and try again.", 'warning');
                $('.sirv-processing-message').hide();
                $('.sirv-progress__bar--line-complited').removeClass('sirv-progress-bar-animated');
                //manageElement('input[name=sirv-sync-images]', disableFlag = false, text = 'Sync images', button = true);
                setButtonSyncState("sync");
                isSyncing = false;
            });
        }


        function showAjaxErrorMessage(jqXHR, status, error, selector_container) {
            const errorTitle = `<b>Error during ajax request</b>`;

            let errorText = !!error
                ? `<p>Error message: "${error}"</p>`
                : `<p>Error message: Unknown error. If this continues, please inform the <a target="_blank" rel="noopener" href="https://sirv.com/help/support/#support">Sirv support team</a></p>\n`;

            let httpCodeText = "";

            if(jqXHR.status !== 200){
                httpCodeText = `<p>HTTP CODE: ${jqXHR.status} ${jqXHR.statusText}</p>`;
            }

            showMessage(selector_container, `${errorTitle}<br>${errorText}${httpCodeText}`);
        }


        function setButtonSyncState(state){
            //state: sync, syncing, syncedAll
            //manageElement('input[name=sirv-sync-images]', disableFlag = state, text = 'Syncing', button = true);

            const buttonSelector = ".sirv-sync-images";
            const $syncButtonEl = $(buttonSelector);

            $syncButtonEl.off('click');

            switch (state) {
                case 'sync':
                    $(buttonSelector).on("click", initializeMassSync);
                    manageElement(buttonSelector, false, 'Sync items', true);
                    break;
                case 'syncing':
                    $(buttonSelector).on("click", stopMassSync);
                    manageElement(buttonSelector, false, 'Stop sync', true);
                    break;

                case 'stoping':
                    manageElement(buttonSelector, true, 'Stoping', true);
                    break;
                case 'synced':
                    manageElement(buttonSelector, true, 'Synced', true);
                    break;
                case 'syncedAll':
                    //$(buttonSelector).on("click", stopMassSync);
                    manageElement(buttonSelector, true, '100% synced', true);
                    break;
            }
        }

        //issue when last items syncing in the last porion and we press stop button. In this case all items will be synced and we need show 100% synced
        function stopMassSync(){
            isSyncing = false;
            $(".sirv-queue").html("Processing: stopping sync process...");
            setButtonSyncState('stoping');
        }


        function get_formated_num(num){
            return new Intl.NumberFormat('en-IN').format(num);
        }


        $('.sirv-get-error-data').on('click', getErrorData);
        function getErrorData(e){
            e.preventDefault();
            e.stopPropagation();


            let $link = $(this);
            let reportType = $link.attr('data-type');
            let errorId = $link.attr('data-error-id');
            let $ajaxAnimation = $(this).next();

            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_get_error_data',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                    error_id: errorId,
                    report_type: reportType,
                },
                type: 'POST',
                dataType: 'text',
                beforeSend: function () {
                    $ajaxAnimation.show();
                    $link.text('Generating...');
                },
            }).done(function (data) {
                //debug
                //console.log(data);

                $ajaxAnimation.hide();

                if (!!data) {
                    if(!!data.error){
                        showMessage('.sirv-sync-messages', data.error);
                    }

                    if(reportType == 'html'){
                        $link.text('Open HTML report');
                        $link.attr('href', data);
                        $link.attr('target', '_blank');
                        $('.sirv-get-error-data:contains(Open HTML report)').off('click', getErrorData);
                        window.open($link.attr('href'));
                    }else{
                        $link.text('CSV downloaded');
                        let filename = 'failed_images_error_'+ errorId + '.csv';
                        $('.sirv-get-error-data:contains(CSV downloaded)').off('click', getErrorData);
                        let a = document.createElement('a');
                        /* let url = window.URL.createObjectURL(data);
                        a.href = url;
                        a.download = 'failed_images_list.txt';
                        a.style.display = 'none';
                        document.body.append(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(url); */
                        a.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(data));
                        a.setAttribute('download', filename);

                        a.style.display = 'none';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    }

                }
            }).fail(function (jqXHR, status, error) {
                $ajaxAnimation.hide();
                $link.text('An error occurred');
                console.error("Error during ajax request: " + error);
                showMessage('.sirv-sync-messages', "Error during ajax request: " + error);
            });
        }

        $('.failed-images-block a').on('click', getErrorsData);
        function getErrorsData(e){
            e.preventDefault();

            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_get_errors_info',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function () {
                    $('.failed-images-block span.sirv-traffic-loading-ico').show();
                    $('.failed-images-block a').hide();
                    $('.sirv-fetch-errors').empty();
                    $('.sirv-get-error-data').off('click', getErrorData);
                },
            }).done(function (data) {
                //debug
                //console.log(data);

                if (!!data) {
                    if(!!data.error){
                        showMessage('.sirv-sync-messages', data.error);
                    }

                    let documentFragment = $(document.createDocumentFragment());
                    for (let i in data) {
                        if(data[i]['count'] > 0){
                            let solution = !!data[i]['error_desc'] ? 'Solution: ' + data[i]['error_desc'] : '';
                            documentFragment.append('<tr><td><span class="sirv-error-title">' + i + '</span><br><span class="sirv-error-solution">'+ solution + '</span></td><td>' + data[i]['count'] + '</td>'+
                            '<td><a class="sirv-get-error-data" data-type="html" data-error-id="' + data[i]['error_id'] + '" href="#">Generate HTML report</a>' +
                            ' <span class="sirv-traffic-loading-ico sirv-get-fimages-list" style="display:none;"></span><br/>' +
                            '<a class="sirv-get-error-data" data-type="csv" data-error-id="' + data[i]['error_id'] + '" href="#">Download CSV</a>' +
                            ' <span class="sirv-traffic-loading-ico sirv-get-fimages-list" style="display:none;"></span>' +
                            '</td></tr>');
                        }
                    }

                    $('.sirv-fetch-errors').append(documentFragment);
                    $('.sirv-get-error-data').on('click', getErrorData);
                    $('.failed-images-block span.sirv-traffic-loading-ico').hide();
                    $('.sync-errors').show();
                }
            }).fail(function (jqXHR, status, error) {
                console.error("Error during ajax request: " + error);
                showMessage('.sirv-sync-messages', "Error during ajax request: " + error);
            });
        }


        $('.sirv-crop-radio').on('change', onCropChange);
        function onCropChange(e){
            e.preventDefault();
            e.stopPropagation();

            let $dataObj = $('#sirv-crop-sizes');
            let data = JSON.parse($dataObj.val());
            let $rObj = $(this);
            if(!!data){
                data[$rObj.attr('name')] = $rObj.val();
                $dataObj.val(JSON.stringify(data));
            }
        }

        $(".sirv-thumb-size").on("change", onThumbSizeChange);
        function onThumbSizeChange(e) {
            e.preventDefault();
            e.stopPropagation();


            let $preventedSizesEl = $("#sirv-prevented-sizes-hidden");
            let preventedSizes = JSON.parse($preventedSizesEl.val());
            let $changedRadio = $(this);
            let isCheckedDelete = $changedRadio.val() == 'delete';
            if (isCheckedDelete && !!preventedSizes) {
                preventedSizes[$changedRadio.attr("data-name")] = $changedRadio.attr("data-size");
            }else{
                delete preventedSizes[$changedRadio.attr("data-name")];
            }

            let curPreventedSizesStr = JSON.stringify(preventedSizes);
            if(isPreventedSizesChanged(curPreventedSizesStr)){
                $(".sirv-save-prevented-sizes-wrap").fadeIn();
            }else{
                $(".sirv-save-prevented-sizes-wrap").fadeOut();
            }

            $preventedSizesEl.val(curPreventedSizesStr);
        }

        $(".sirv-thumb-size-delete-all").on("change", onSelectUnselectAll);
        function onSelectUnselectAll(e) {
            e.preventDefault();
            e.stopPropagation();

            let isChecked = $(this).is(':checked');
            let $thumbs = $(".sirv-thumb-size");
            let $preventedSizesEl = $("#sirv-prevented-sizes-hidden");
            let preventedSizes = JSON.parse($preventedSizesEl.val());

            $.each($thumbs, function(index, element){
                let $elem = $(element);
                if(isChecked){
                    if($elem.val() == 'delete')
                    $elem.prop("checked", true);
                    preventedSizes[$elem.attr("data-name")] = $elem.attr("data-size");
                }else{
                    if($elem.val() == 'keep')
                    $(element).prop("checked", true);
                    preventedSizes = {};
                }
            });

            let curPreventedSizesStr = JSON.stringify(preventedSizes);
            if (isPreventedSizesChanged(curPreventedSizesStr)) {
                $(".sirv-save-prevented-sizes-wrap").fadeIn();
            } else {
                $(".sirv-save-prevented-sizes-wrap").fadeOut();
            }

            $preventedSizesEl.val(curPreventedSizesStr);
        }


        function isPreventedSizesChanged(curPreventedSizes){
            return curPreventedSizes.length !== getStoredPreventedSizes().length;
        }


        function getStoredPreventedSizes(){
            return preventedSizes;
        }


        function storePreventedSizesOnLoad(){
            preventedSizes = $("#sirv-prevented-sizes-hidden").val();
        }


        $('.sirv-delete-wp-thumbs, .sirv-regenerate-wp-thumbs').on('click', manageWPThumbs);
        function manageWPThumbs(event, $curBt = null) {
            let $curButton = $curBt || $(this);

            let curPreventedSizes = $("#sirv-prevented-sizes-hidden").val();
            if(isPreventedSizesChanged(curPreventedSizes)){
                hideMessages(".sirv-thumb-messages");
                showMessage('.sirv-thumb-messages', 'Please save your updated settings before trying to '+ $curButton.attr('data-type') +' operation.', 'warning');
                return false;
            }

            let preventedSizesStr = $("#sirv-prevented-sizes-hidden").val();
            let preventedSizesObj = JSON.parse(preventedSizesStr);

            if($curButton.attr('data-type') === 'delete'){
                if(Object.keys(preventedSizesObj).length === 0){
                    hideMessages(".sirv-thumb-messages");
                    showMessage('.sirv-thumb-messages', 'No thumbnails available to delete. Choose which thumbs should be deleted.', 'warning');
                    return false;
                }
            }

            if ($curButton.attr("data-type") === "regenerate"){
                let sizesCount = $(".sirv-thumbs-sizes .sirv-crop-row__checkboxes").length;
                if(Object.keys(preventedSizesObj).length === sizesCount){
                    hideMessages(".sirv-thumb-messages");
                    showMessage('.sirv-thumb-messages', 'No thumbnails available to regenerate. Choose which thumbs should be created.', 'warning');
                    return false;
                }
            }

            $(".sirv-progress-wrapper").show();

            if (!isThumbsAjax) {
                isThumbsAjax = true;
                $(".sirv-thumbs-progressbar").addClass("sirv-progress-bar-animated");
                $curButton.attr('data-pause', 'false');
                $curButton.prop('disabled', false);

                thumbsProcessing({type: $curButton.attr('data-type')});
            } else {
                $curButton.attr('data-pause', 'true');
                $curButton.prop("disabled", true);
                $curButton.val("Pausing...");
                return;
            }
        }

        $(".sirv-thumbs-continue-processing").on('click', continueThumbsProcessing);
        function continueThumbsProcessing(){
            let type = $(this).attr('data-type');
            let $curButton = type == 'delete' ? $('.sirv-delete-wp-thumbs') : $('.sirv-regenerate-wp-thumbs');
            $(".sirv-processing-thumb-images-msg").hide();
            manageWPThumbs(null, $curButton);
        }


        $(".sirv-save-prevented-sizes").on('click', savePreventedSizes);
        function savePreventedSizes(e){
            e.preventDefault();

            const $button = $(this);

            let preventedSizesStr = $("#sirv-prevented-sizes-hidden").val();

            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_save_prevented_sizes',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                    sizes: preventedSizesStr,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function() {
                    hideMessages(".sirv-thumb-messages");

                    $button.val('Saving...');
                    $button.prop('disabled', true);
                }
            }).done(function (data) {
                //debug
                //console.log(data);

                if(!!data.error){
                    showMessage('.sirv-thumb-messages', data.error);
                }

                if(data.status == 'saved'){
                    storePreventedSizesOnLoad();
                    $button.parent().hide();
                    $button.val("Save updated settings");
                    $button.prop("disabled", false);
                    showMessage('.sirv-thumb-messages', 'Thumbnail settings saved. Now you can delete or regenerate thumbnails.', 'success');
                }


            }).fail(function (jqXHR, status, error) {
                console.log("Error during ajax request: " + error);
                showMessage('.sirv-thumb-messages', "Error during ajax request: " + error);
                $button.val("Save updated settings");
                $button.prop("disabled", false);
            });
        }


        $('.sirv-thumbs-cancel-processing').on('click', cancelThumbProcessing);
        function cancelThumbProcessing(){
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_cancel_thumbs_process',
                    _ajax_nonce:sirv_options_data.ajaxnonce,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function() {
                    hideMessages(".sirv-thumb-messages");
                    $('.sirv-thumbs-continue-processing').prop('disabled', true);
                    $(".sirv-thumbs-cancel-processing").prop("disabled", true);
                    $(".sirv-thumbs-cancel-processing").val("Canceling...");
                }
            }).done(function (data) {
                //debug
                //console.log(data);
                if(data.error){
                    showMessage('.sirv-thumb-messages', data.error);
                }

                if(data.status == 'canceled'){
                    showMessage('.sirv-thumb-messages', 'Operation '+ data.type +' was canceled', 'success');
                    $(".sirv-processing-thumb-images-msg").hide();

                    $(".sirv-regenerate-wp-thumbs").prop('disabled', false);
                    $(".sirv-delete-wp-thumbs").prop("disabled", false);
                    $('.sirv-progress-wrapper').hide();

                    set_default_thumbs_progress_data();
                }


            }).fail(function (jqXHR, status, error) {
                console.log("Error during ajax request: " + error);
                showMessage('.sirv-thumb-messages', "Error during ajax request: " + error);
            });
        }


        function set_default_thumbs_progress_data() {
            $(".sirv-thumbs-progress-percents").text(0);
            $(".sirv-thumbs-processed_ids").text(0);
            $(".sirv-thumbs-processed-files-count").text(0);
            $(".sirv-thumbs-progressbar").css("width", "0%");
        }


        function thumbsProcessing(data){
            let $curButton = data.type == 'delete' ? $('.sirv-delete-wp-thumbs') : $('.sirv-regenerate-wp-thumbs');
            let type = $curButton.attr("data-type");
            let isPause = $curButton.attr("data-pause") == "true";

            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_thumbs_process',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                    type: type,
                    pause: isPause,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function () {
                    hideMessages(".sirv-thumb-messages");
                    if(!isPause) $curButton.val("Pause");

                    let operationedTxt = type == 'delete'? 'Deleted' : 'Regenerated';

                    $(".sirv-thumbs-operation-type").text(operationedTxt);

                    if(type == 'delete'){
                        $(".sirv-regenerate-wp-thumbs").prop('disabled', true);
                    }else{
                        $(".sirv-delete-wp-thumbs").prop("disabled", true);
                    }

                }
            }).done(function (data) {
                //debug
                //console.log(data);
                if(data.error){
                    showMessage('.sirv-thumb-messages', data.error);
                }

                if(data.status == 'processing'){
                    $(".sirv-thumbs-progress-percents").text(data.percent_finished);
                    $(".sirv-thumbs-processed_ids").text(data.processed_ids);
                    $(".sirv-thumbs-cached_ids").text(data.ids_count);
                    $(".sirv-thumbs-processed-files-count").text(data.files_count);
                    $(".sirv-thumbs-progressbar").css('width', data.percent_finished + '%');
                    thumbsProcessing(data);
                }

                if(data.status == 'pause'){
                    $curButton.val('Continue');
                    $curButton.prop("disabled", false);
                    $(".sirv-thumbs-progressbar").removeClass("sirv-progress-bar-animated");

                    isThumbsAjax = false;
                }

                if(data.status == 'finished'){
                    $(".sirv-thumbs-progressbar").removeClass("sirv-progress-bar-animated");
                    $(".sirv-regenerate-wp-thumbs").prop('disabled', false);
                    $(".sirv-regenerate-wp-thumbs").val('Regenerate thumbnails');
                    $(".sirv-delete-wp-thumbs").prop("disabled", false);
                    $(".sirv-delete-wp-thumbs").val('Delete thumbnails');
                    $(".sirv-progress-wrapper").hide();

                    set_default_thumbs_progress_data();

                    let operationedTxt = data.type == 'delete' ? 'deleted ' : 'regenerated ';
                    let sizeTxt = data.type == 'delete' ? ' with total size '+ getFormatedFileSize(data.files_size): '';
                    let thumbsTxt = data.type == 'regenerate' ? ' (any existing thumbnails were skipped)' : '';

                    showMessage('.sirv-thumb-messages', 'Completed: '+ data.files_count +' thumbnails have been ' + operationedTxt  + sizeTxt + thumbsTxt, 'success');

                    isThumbsAjax = false;
                }

            }).fail(function (jqXHR, status, error) {
                $(".sirv-thumbs-progressbar").removeClass("sirv-progress-bar-animated");
                isThumbsAjax = false;

                console.log("Error during ajax request: " + error);
                showMessage('.sirv-thumb-messages', "Error during ajax request: " + error);

            });
        }


        $('.sirv-hide-show-a').on('click', toogleBlockVisivility);
        function toogleBlockVisivility(e){
            e.preventDefault();
            e.stopPropagation();

            let showMsgTxt = $(this).attr('data-show-msg');
            let hideMsgTxt = $(this).attr('data-hide-msg');
            let showIconCls = $(this).attr('data-icon-show') || '';
            let hideIconCls = $(this).attr('data-icon-hide') || '';
            let status = $(this).attr('data-status') == 'true';

            let showMsg = showIconCls ? '<span class="'+ showIconCls +'"></span>' + showMsgTxt : showMsgTxt;
            let hideMsg = hideIconCls ? '<span class="'+ hideIconCls +'"></span>' + hideMsgTxt : hideMsgTxt;

            let selector = $(this).attr('data-selector');

            if (status){
                $(selector).slideUp();
                $(this).html(showMsg);
            }else{
                $(selector).slideDown();
                $(this).html(hideMsg)
            }

            $(this).attr('data-status', !status);
        }


        $(".sirv-clear-view-cache").on("click", emptyViewCacheTable);
        function emptyViewCacheTable(e){

            e.preventDefault();
            e.stopPropagation();

            let type = $(this).attr('data-type');
            const $spinner = $(this).siblings("span.sirv-traffic-loading-ico");

            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_empty_view_cache',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                    type: type,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function () {
                    $spinner.show();
                    hideMessages(".sirv-show-view-cache-messages");
                }
            }).done(function (data) {
                //debug
                //console.log(data);

                $spinner.hide();

                if(!!data?.error){
                    showMessage('.sirv-show-view-cache-messages', data.error);
                }

                if(!!data.result){
                    //toastr.success(`${data.result} records have been deleted`, "", {preventDuplicates: true, timeOut: 4000, positionClass: "toast-top-center"});
                    showMessage(".sirv-show-view-cache-messages", `Cache records have been deleted`, 'success');
                }

                if (!!data?.sync_data){
                    updateViewSyncData(data.sync_data);
                }


            }).fail(function (jqXHR, status, error) {
                console.error("status: ", status);
                console.error("Error message: " + error);
                console.error("http code", `${jqXHR.status} ${jqXHR.statusText}`);

                showAjaxErrorMessage(jqXHR, status, error, '.sirv-show-view-cache-messages');

                $spinner.hide();
            });
        }


        function updateEmptyViewCacheData(syncData){
            const content = syncData.view_cache["SUCCESS"] * 1;
            const noContent = (syncData.view_cache["EMPTY"] * 1) + (syncData.view_cache["FAILED"] * 1);
            const total = syncData.total * 1;
            const unsynced = total - (syncData.synced * 1);

            $(".sirv-view-data-content").text(content);
            $(".sirv-view-data-no-content").text(noContent);
            $(".sirv-view-data-content-unsynced").text(unsynced);
            $(".sirv-view-data-content-total").text(total);
        }


        $(".sync-all-view-data-show-dialog").on('click', showSyncSyncViewFilesDialog);
        function showSyncSyncViewFilesDialog(){
            $("#sirv-sync-view-files").show();
        }


        $(".sync-all-view-data-hide-dialog-action").on('click', hideSyncSyncViewFilesDialog);
        function hideSyncSyncViewFilesDialog(){
            $("#sirv-sync-view-files").hide();
            $(".sirv-sync-view-files-messages").empty();
        }


        $("body").on("click", '.sirv-sync-view-files-action__start', initializeMassViewSync);
        function initializeMassViewSync(){
            $("#sirv-sync-view-files .sync-all-view-data-hide-dialog-action").prop('disabled', true);
            $("#sirv-sync-view-files .sirv-sync-view-files-show-status").text("Processing: syncing...");
            $("#sirv-sync-view-files .sirv-sync-view-files-status").show();
            $("#sirv-sync-view-files .sirv-progress-bar-component-line__complited").addClass("sirv-progress-bar-animated");
            manageElement(".sirv-sync-view-files-action", false, 'Stop');
            $(".sirv-sync-view-files-action").removeClass('sirv-sync-view-files-action__start').addClass('sirv-sync-view-files-action__stop');

            massViewSync();
        }


        $("body").on("click", '.sirv-sync-view-files-action__stop', stopMassViewSync);
        function stopMassViewSync(){
            isStopViewSyncing = true;
            $("#sirv-sync-view-files .sirv-sync-view-files-show-status").text("Processing: stopping...");
            manageElement(".sirv-sync-view-files-action", true, 'Stopping...');
            $(".sirv-sync-view-files-action").removeClass('sirv-sync-view-files-action__stop');
        }


        function massViewSync(){
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_sync_view_files',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function () {
                    //$("#sirv-sync-view-files .sirv-sync-view-files-show-status").text("Processing: syncing...");
                }
            }).done(function (response) {
                //debug
                //console.log(response);

                if(!!response?.error){
                    showMessage('.sirv-sync-view-files-messages', response.error);
                }

                if(isStopViewSyncing){
                    isStopViewSyncing = false;

                    updateViewSyncData(response);
                    setViewSyncToInitialState();

                    return;
                }

                if(response.progress === 100){
                    updateViewSyncData(response);
                    setViewSyncToInitialState();
                }else{
                    updateViewSyncData(response);
                    massViewSync();
                }

            }).fail(function (jqXHR, status, error) {
                console.error("status: ", status);
                console.error("Error message: " + error);
                console.error("http code", `${jqXHR.status} ${jqXHR.statusText}`);

                showAjaxErrorMessage(jqXHR, status, error, '.sirv-sync-view-files-messages');

                setViewSyncToInitialState();
            });
        }


        function updateViewSyncData(data){
            $("#sirv-sync-view-files .sirv-progress-bar-component-text__percents").text(`${data.progress}%`);
            $("#sirv-sync-view-files .sirv-progress-bar-component-text__complited span").text(`${data.synced} of ${data.total}`);
            $("#sirv-sync-view-files .sirv-progress-bar-component-line__complited").css({'width': `${data.progress}%`});

            updateEmptyViewCacheData(data);
        }


        function setViewSyncToInitialState(){
            $("#sirv-sync-view-files .sync-all-view-data-hide-dialog-action").prop('disabled', false);
            $("#sirv-sync-view-files .sirv-sync-view-files-status").hide();
            $("#sirv-sync-view-files .sirv-sync-view-files-show-status").text("Processing: syncing...");
            $("#sirv-sync-view-files .sirv-progress-bar-component-line__complited").removeClass("sirv-progress-bar-animated");

            manageElement(".sirv-sync-view-files-action", false, 'Sync Sirv folders');
            $(".sirv-sync-view-files-action").removeClass('sirv-sync-view-files-action__stop').addClass('sirv-sync-view-files-action__start');
        }


        $(".sirv-clean-old-view-cache").on('click', clearOldViewFilesCache);
        function clearOldViewFilesCache(e){
            e.preventDefault();

            const $button = $(this);


            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_clear_old_view_files_cache',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function (){
                    $button.prop('disabled', true);
                    $button.html('<span class="sirv-traffic-loading-ico"></span>Deleting...');
                },
            }).done(function (res) {
                //debug
                //console.log(res);

                if(!!res.error){
                    $(".sirv-show-view-cache-messages").empty();
                    showMessage(".sirv-show-view-cache-messages", res.error);
                    return;
                }

                if(!!res.rows_affected){
                    $(".sirv-show-view-cache-messages").empty();
                    showMessage(".sirv-show-view-cache-messages", `${res.rows_affected} record(s) deleted`, 'success');

                    if(!!res.cache_data){
                        updateViewSyncData(res.cache_data);
                    }
                }

            }).fail(function (jqXHR, status, error) {
                console.error("status: ", status);
                console.error("Error message: " + error);
                console.error("http code", `${jqXHR.status} ${jqXHR.statusText}`);

                $(".sirv-show-view-cache-messages").empty();
                showAjaxErrorMessage(jqXHR, status, error, '.sirv-show-view-cache-messages');
            });
        }


        $('.sync-css').on('click', findCssImagesPrepare);
        function findCssImagesPrepare(){
            let custom_path = $('.sirv-custom-backcss-path-rb:checked').val() == 'custom' ? $('#sirv-custom-backcss-path-text').val() : '';
            let isCustom = $('.sirv-custom-backcss-path-rb:checked').val() == 'custom' ? true : false;
            let isEmptyCustomPath = $('#sirv-custom-backcss-path-text').val().length > 0 ? false : true;

            if(isCustom && isEmptyCustomPath){
                $('.sync-css').siblings('span.sirv-show-empty-view-result').text('Please fill custom path.');
                $('.sync-css').siblings('span.sirv-show-empty-view-result').show();

                return;
            }
            $.ajax({
                url: ajaxurl,
                data: {
                    "action": 'sirv_css_images_prepare_process',
                    "_ajax_nonce": sirv_options_data.ajaxnonce,
                    "custom_path": custom_path
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function () {
                    $('textarea[name=SIRV_CSS_BACKGROUND_IMAGES]').val();
                    $('.sync-css').siblings('span.sirv-traffic-loading-ico').show();
                    $('.sync-css').siblings('span.sirv-show-empty-view-result').text('Preparing process...');
                    $('.sync-css').siblings('span.sirv-show-empty-view-result').show();

                    $('.sirv-css-sync-data-img-count').text('Processing...');
                    $('.sirv-skipped-images-wrap').hide();
                    $('.sirv-css-sync-data-img-count-skipped').text('');
                    //$('.sirv-show-skip-data-list').hide();
                    $('.sirv-skip-images-list').hide();
                }
            }).done(function (response) {
                //debug
                //console.log(response);

                if(!!response){
                    updateCssSyncData(response);

                    if(!!response.error){
                        $('.sync-css').siblings('span.sirv-traffic-loading-ico').hide();
                        $('.sync-css').siblings('span.sirv-show-empty-view-result').text(response.error);
                        $('textarea[name=SIRV_CSS_BACKGROUND_IMAGES]').val();
                    }else{
                        cssImagesStartProcess();
                        setTimeout(cssImagesProcessing, 1000);

                    }
                }

            }).fail(function (jqXHR, status, error) {
                console.log("Error during ajax request: " + error);
                //showMessage('.sirv-sync-messages', "Error during ajax request: " + error);
                $('.sync-css').siblings('span.sirv-traffic-loading-ico').hide();
                $('.sync-css').siblings('span.sirv-show-empty-view-result').text("Error during ajax request: " + error);
                $('.sync-css').siblings('span.sirv-show-empty-view-result').show();
            });
        }


        function cssImagesStartProcess(){
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_css_images_proccess',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function () {
                    $('.sync-css').siblings('span.sirv-traffic-loading-ico').show();
                    $('.sync-css').siblings('span.sirv-show-empty-view-result').text('Starting process...');
                    $('.sync-css').siblings('span.sirv-show-empty-view-result').show();
                }
            }).done(function (response) {
                //debug
                //console.log(response);

                if(!!response){
                    if (!!response.error){
                        $('.sync-css').siblings('span.sirv-traffic-loading-ico').hide();
                        $('.sync-css').siblings('span.sirv-show-empty-view-result').text(response.error);

                        //updateCssSyncData(response);

                        //setTimeout(function () { $('.sync-css').siblings('span.sirv-show-empty-view-result').hide();}, 3000);
                    }else{
                        /* $('.sync-css').siblings('span.sirv-show-empty-view-result').text(response.msg);
                        updateCssSyncData(response);

                        if(response.status == 'sync'){
                            setTimeout(cssImagesProcessing, 1000);
                        }else{
                            $('.sync-css').siblings('span.sirv-traffic-loading-ico').hide();
                            cssImagesUpdateFrontCssData();
                        } */
                    }

                    //setTimeout(function () { $('.sync-css').siblings('span.sirv-show-empty-view-result').hide();}, 3000);
                }

            }).fail(function (jqXHR, status, error) {
                console.log("Error during ajax request: " + error);
                //showMessage('.sirv-sync-messages', "Error during ajax request: " + error);
                $('.sync-css').siblings('span.sirv-traffic-loading-ico').hide();
                $('.sync-css').siblings('span.sirv-show-empty-view-result').text("Error during ajax request: " + error);
                $('.sync-css').siblings('span.sirv-show-empty-view-result').show();
            });
        }


        function cssImagesProcessing(){
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_css_images_processing',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                },
                type: 'POST',
                dataType: "json",
                /*  beforeSend: function () {
                } */
            }).done(function (response) {
                //debug
                //console.log(response);

                if(!!response){
                    if (!!response.error){
                        $('.sync-css').siblings('span.sirv-traffic-loading-ico').hide();
                        $('.sync-css').siblings('span.sirv-show-empty-view-result').text(response.error);

                        updateCssSyncData(response);
                    }else{
                        $('.sync-css').siblings('span.sirv-show-empty-view-result').text(response.msg);
                        updateCssSyncData(response);

                        if(response.status == 'sync'){
                            setTimeout(cssImagesProcessing, 500);
                        }else{
                            $('.sync-css').siblings('span.sirv-traffic-loading-ico').hide();
                            cssImagesUpdateFrontCssData();
                        }
                    }
                }

            }).fail(function (jqXHR, status, error) {
                console.log("Error during ajax request: " + error);
                //showMessage('.sirv-sync-messages', "Error during ajax request: " + error);
                $('.sync-css').siblings('span.sirv-traffic-loading-ico').hide();
                $('.sync-css').siblings('span.sirv-show-empty-view-result').text("Error during ajax request: " + error);
                $('.sync-css').siblings('span.sirv-show-empty-view-result').show();
            });
        }


        function cssImagesUpdateFrontCssData(){
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_css_images_get_data',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function () {
                    /* $('.sync-css').siblings('span.sirv-traffic-loading-ico').show();
                    $('.sync-css').siblings('span.sirv-show-empty-view-result').text('Loading css data...');
                    $('.sync-css').siblings('span.sirv-show-empty-view-result').show(); */
                }
            }).done(function (response) {
                //debug
                //console.log(response);

                if(response.error){
                    $('.sync-css').siblings('span.sirv-traffic-loading-ico').hide();
                    $('.sync-css').siblings('span.sirv-show-empty-view-result').text(response.error);
                    $('.sync-css').siblings('span.sirv-show-empty-view-result').show();
                    return;
                }

                $('.sync-css').siblings('span.sirv-traffic-loading-ico').hide();

                if(!!response && !!response.css_data){
                    //$('.sync-css').siblings('span.sirv-show-empty-view-result').text("Css data loaded.");

                    $('textarea[name=SIRV_CSS_BACKGROUND_IMAGES]').val(response.css_data);
                    $('.sirv-save-css-code').prop('disabled', true);
                    $('.sirv-css-sync-bg-img-txtarea-wrap textarea').one('input', enableSaveCssCodeButton);
                    //$('.sirv-css-sync-bg-img-txtarea-wrap').slideUp();
                    $('.sirv-hide-show-a[data-selector=".sirv-css-sync-bg-img-txtarea-wrap"]').click();
                }

            }).fail(function (jqXHR, status, error) {
                console.log("Error during ajax request: " + error);
                //showMessage('.sirv-sync-messages', "Error during ajax request: " + error);
                $('.sync-css').siblings('span.sirv-traffic-loading-ico').hide();
                $('.sync-css').siblings('span.sirv-show-empty-view-result').text("Error during ajax request: " + error);
                $('.sync-css').siblings('span.sirv-show-empty-view-result').show();
            });
        }


        $('.sirv-custom-backcss-path-rb').on('change', function(){
            let activeValue = $(this).val();

            if(activeValue == 'theme'){
                //$('.sirv-custom-backcss-path-text-wrap').slideUp();
                $('.sirv-custom-backcss-path-text-tr').hide();
            }else{
                //$('.sirv-custom-backcss-path-text-wrap').slideDown();
                $('.sirv-custom-backcss-path-text-tr').show();
                addInputCssPathPadding();
                $('#sirv-custom-backcss-path-text').focus();
            }
        });

        $('.sirv-css-sync-bg-img-txtarea-wrap textarea').one('input', enableSaveCssCodeButton);
        function enableSaveCssCodeButton(){
            $('.sirv-save-css-code').prop('disabled', false);
        }


        function updateCssSyncData(data){
            //console.log(data);
            let imgData = getCSSImgsData(data);
            $('.sirv-css-sync-data-theme').text(data.theme);
            $('.sirv-css-sync-data-date').text(data.last_sync_str);
            $('.sirv-css-sync-data-domain').text(data.img_domain);
            renderCSSImgsData(imgData, data.skipped_images);
            //console.log(new Date(+data.last_sync * 1000).toString());
            /* if(!!data.error){
                $('.sirv-css-sync-data-img-count').text(data.error);
                return;
            } */
            //$('.sirv-css-sync-data-img-count').text(!!data.img_count ? data.img_count : 0);
        }


        function renderCSSImgsData(data, skippedImages){
            $('.sirv-css-sync-data-img-count').text(data.sync_data);
            if(!!data.skip_data){
                let skippedFilesStr = getSkippedImagesAsString(skippedImages);
                $('.sirv-css-sync-data-img-count-skipped').html(data.skip_data);
                $('.sirv-skipped-images-wrap').show();
                //$('.sirv-show-skip-data-list').show();
                $('textarea.sirv-skip-images-list').val(skippedFilesStr);
            }
        }


        function getCSSImgsData(data){
            let cssCount = +data.css_files_count;
            let syncedImagesCount = +data.img_count;
            let skippedImagesCount = data.skipped_images.length;

            let imgData = {"sync_data" : '', "skip_data" : ''};

            let $msg = {
                'no_css' : 'No CSS files found',
                'one_css' : ' CSS file',
                'few_css' : ' CSS files',
                'one_synced' : ' image synced',
                'few_synced' : ' images synced',
                'one_skipped' : ' image skipped',
                'few_skipped' : ' images skipped',
            };

            if (cssCount == 0){
                imgData.sync_data = $msg.no_css;
                return imgData;
            }

            let cssText = cssCount > 1 ? cssCount + $msg.few_css : cssCount + $msg.one_css;
            let syncText = syncedImagesCount > 1 || syncedImagesCount == 0 ? syncedImagesCount + $msg.few_synced : syncedImagesCount + $msg.one_synced;
            let skippedText = skippedImagesCount > 1 || skippedImagesCount == 0 ? skippedImagesCount + $msg.few_skipped : skippedImagesCount + $msg.one_skipped;

            let syncFullText =  syncText + ', from ' + cssText;
            let skippedFullText = skippedImagesCount > 0 ? skippedText : '';

            imgData.sync_data = syncFullText;
            imgData.skip_data = skippedFullText;

            return imgData;
        }


        function getSkippedImagesAsString(sImages){
            return sImages.join('\n');
        }


        function isJsonString(str) {
            try {
                JSON.parse(str);
            } catch (e) {
                return false;
            }
            return true;
        }

        $('.sirv-stat-refresh').on('click', refreshStats);
        function refreshStats(e){
            e.preventDefault();

            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_refresh_stats',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function (){
                    $('.sirv-stats-messages').empty();
                    $('.sirv-stats-container').addClass('sirv-loading');
                },
            }).done(function (data) {
                //debug
                //console.log(data);

                $('.sirv-stats-container').removeClass('sirv-loading');
                if (!!data) {
                    if(data.error){
                        showMessage('.sirv-stats-messages', data.error);
                    }

                    window.abc = data.traffic.traffic;
                    $('.sirv-stat-last-update').html(data.lastUpdate);
                    renderStorage(data.storage);
                    renderTransfer(data.traffic);
                    renderApiUsage(data.limits);
                }
            }).fail(function (jqXHR, status, error) {
                console.error("Error during ajax request: " + error);
                showMessage('.sirv-stats-messages', "Error during ajax request: " + error);
                $('.sirv-stats-container').removeClass('sirv-loading');
            });
        }


        function renderStorage(storage){
            if(!!storage){
                $('.sirv-allowance').html(storage.allowance_text);
                $('.sirv-st-used').html(storage.used_text + '<span> (' + storage.used_percent + '%)</span>');
                $('.sirv-st-available').html(storage.available_text + '<span> (' + storage.available_percent + '%)</span>');
                $('.sirv-st-files').html(storage.files);
            }
        }


        function renderTransfer(traffic){
            if(!!traffic){
                $('.sirv-trf-month').html(traffic.allowance_text);

                let traffics = sortTraffic(traffic.traffic);

                let trafficFragment = $(document.createDocumentFragment());


                for (let i in traffics) {
                    let tr = $('<tr class="small-padding">' +
                                '<th><label>' + traffics[i].month + '</label></th>'+
                                '<td><span>' + traffics[i].size_text + '</span></td>'+
                                '<td>'+
                                    '<div class="sirv-progress-bar-holder"><div class="sirv-progress-bar"><div>'+
                                        '<div style = "width:' + traffics[i].percent_reverse + '%;"></div>' +
                                    '</div></div></div>'+
                                '</td>'+
                            '</tr>'
                            );
                    trafficFragment.append(tr);
                }

                let $trafficRows = $('.traffic-wrapper').children();
                for (let i = 1; i < $trafficRows.length; i++) {
                    $trafficRows[i].remove();
                }

                $($trafficRows[0]).after(trafficFragment);
            }
        }


        function sortTraffic(traffic){
            let keys = Object.keys(traffic);
            let tmpArr = [];

            for (let index in keys) {
                let key = keys[index];
                traffic[key]['month'] = key;
                tmpArr.push(traffic[key]);
            }

            tmpArr.sort(function (a, b) {
                return a.order - b.order
            })

            return tmpArr;
        }


        function renderApiUsage(limits){

            if(!!limits){
                let apiFragment = $(document.createDocumentFragment());
                let apiTypes = Object.keys(limits);

                for (let i in apiTypes) {
                    let limit = limits[apiTypes[i]];

                    let count = limit.count > 0 ? limit.count : '-';
                    let used = limit.count > 0 ? ' (' + limit.used + ')' : '';
                    //let reset = limit.count > 0 ? limit.reset_str + ' <span class="sirv-limits-reset-local">(' + renderTime(limit.reset_timestamp) + ')</span>' : '-';
                    let reset = limit.count > 0 ? limit.count_reset_str + ' <span class="sirv-grey">(' + limit.reset_str + ')</span>' : '-';
                    let isLimitReached = limit.count >=limit.limit ? 'style="color: red;"' : '';

                    let limitRow = $('<tr '+ isLimitReached +'>\n<td>' +
                                    limit.type + '</td>\n<td>' +
                                    limit.limit + '</td>\n<td>'+
                        count + used +'</td>\n<td><span class="sirv-limits-reset" data-timestamp="'+ limit.reset_timestamp+'" >' + reset + '</span></td></tr>\n');
                    apiFragment.append(limitRow);
                }
                $('.sirv-api-usage-content').empty();
                $('.sirv-api-usage-content').append(apiFragment);

            }
        }


        function setCorrectTime(){
            let $times = $('.sirv-limits-reset');
            $.each($times, function(index, elem){
                let el = $(elem);
                let timestamp = el.attr('data-timestamp');
                el.html(el.text() + ' <span class="sirv-limits-reset-local">(' + renderTime(timestamp) + ')</span>');
            });
        }


        function renderTime(timestamp){
            let jsTimeStamp = timestamp * 1000;
            let d = new Date(jsTimeStamp);

            let h = d.getHours();
            let m = d.getMinutes();
            let s = d.getSeconds();
            return zeroInTime(h) + ':' + zeroInTime(m) + ':' + zeroInTime(s) + ' ' + convertTimeZoneOffset(d.getTimezoneOffset());
        }


        function zeroInTime(t){
            let ct = t < 10 ? '0' + t : t;
            return ct;
        }


        function convertTimeZoneOffset(offset){
            let timeOffset = Math.abs(offset);
            timeOffset = timeOffset > 0 ? timeOffset / 60 : timeOffset;

            return '+0' + timeOffset + ':00';
        }


        function getFormatedFileSize(bytes) {
            let negativeFlag = false;
            let position = 0;
            let units = [" Bytes", " KB", " MB", " GB", " TB"];

            bytes = parseInt(bytes);

            if (bytes < 0) {
                bytes = Math.abs(bytes);
                negativeFlag = true;
            }

            while (bytes >= 1000 && (bytes / 1000) >= 1) {
                bytes /= 1000;
                position++;
            }

            if (negativeFlag) bytes *= -1;

            bytes = bytes % 1 == 0 ? bytes : bytes.toFixed(2);

            return bytes + units[position];

        }


        //modify form action before send settings data
        $("form#sirv-save-options").on('submit', sendSettings);
        function sendSettings(e){
            let activeTab = $('.nav-tab-active').attr('href');

            let $form = $("form#sirv-save-options");
            let action = $form.attr('action');

            if (!!activeTab) action += activeTab;

            $form.attr('action', action);

            return true;
        }


        function manageElement(selector, disableFlag, text, button, css, html) {
            if (disableFlag !== 'none') {
                $(selector).prop('disabled', disableFlag);
            }
            if (typeof text !== 'undefined' && text !== 'none') {
                if (typeof button !== 'undefined' && button !== 'none') {
                    $(selector).val(text);
                } else {
                    $(selector).html(text);
                }

            }
            if (typeof css !== 'undefined' && css !== 'none') {
                $(selector).css(css);
            }
            if (typeof html !== 'undefined' && html !== 'none') {
                $(selector).html(html);
            }
        }


        function setProfiles(){
            manageSelect('#sirv-shortcodes-profiles', '#sirv-shortcodes-profiles-val', false);
            manageSelect('#sirv-cdn-profiles', '#sirv-cdn-profiles-val', false);
            manageSelect('#sirv-woo-product-profiles', '#sirv-woo-product-profiles-val', false);
            manageSelect('#sirv-woo-category-profiles', '#sirv-woo-category-profiles-val', false);
            manageSelect('#sirv-woo-product-mobile-profiles', '#sirv-woo-product-mobile-profiles-val', false);
            //not profile
            manageSelect('#sirv-woo-product-ttl', '#sirv-woo-product-ttl-val', false);
        }


        $('#sirv-shortcodes-profiles').on('change', function () {
            manageSelect('#sirv-shortcodes-profiles', '#sirv-shortcodes-profiles-val', true);
        });
        $('#sirv-cdn-profiles').on('change', function () {
            manageSelect('#sirv-cdn-profiles', '#sirv-cdn-profiles-val', true);
        });

        $('#sirv-woo-product-profiles').on('change', function () {
            manageSelect('#sirv-woo-product-profiles', '#sirv-woo-product-profiles-val', true);
        });

        $('#sirv-woo-category-profiles').on('change', function () {
            manageSelect('#sirv-woo-category-profiles', '#sirv-woo-category-profiles-val', true);
        });

        $('#sirv-woo-product-mobile-profiles').on('change', function () {
            manageSelect('#sirv-woo-product-mobile-profiles', '#sirv-woo-product-mobile-profiles-val', true);
        });

        $('#sirv-woo-product-ttl').on('change', function () {
            manageSelect('#sirv-woo-product-ttl', '#sirv-woo-product-ttl-val', true);

            let isMessageExist = $("#sirv-woo-product-ttl").prev(".sirv-push-message-warning").length > 0;

            if(+$(this).val() === 1){
                if(isMessageExist) return;
                $("#sirv-woo-product-ttl").parent().prepend(
                    `<div class="sirv-push-message-container sirv-push-message-warning">
                        <div class="sirv-push-message sirv-push-message-warning-icon">
                            Disabled cache will cause requests to Sirv on every product page load. Do not use it on a live production site - it could slow down page loading. 7 days or 1 month is recommended.
                        </div>
                </div>`
                );
            }else{
                if (!isMessageExist) return;
                $("#sirv-woo-product-ttl").prev(".sirv-push-message-container")[0].remove();
            }
        });


        function manageSelect(selector, valueElem, onChange = false) {
            let $valElem = $(valueElem);

            if (onChange) {
                $valElem.val($(selector + ' option:selected').val());
            } else {
                $(selector + " option[value='" + $valElem.val() + "']").prop('selected', true);
            }

        }


        //$('.sirv-switch').on('change', function () {
        $('.sirv-switch-acc-login').on('click', switchAccLogin);
        function switchAccLogin(e){

            e.preventDefault();
            e.stopPropagation();
            $(this).trigger( "blur");

            isNewAccount = !isNewAccount;

            let passText = isNewAccount ? 'Choose password' : 'Password';
            let buttonText = isNewAccount ? 'Create account' : 'Connect account';
            let accText = isNewAccount ? 'Choose account name' : 'Account name';
            let accLabelText = isNewAccount ? 'Already have an account?' : 'Don\'t have an account?';
            let linkText = isNewAccount ? 'Sign in' : 'Create account';

            $('.sirv-pass-field').text(passText);
            $('.sirv-acc-field').text(accText);
            $('.sirv-init').val(buttonText);
            $('.sirv-acc-label').text(accLabelText);
            $('.sirv-switch-acc-login').text(linkText);

            let $hide = $('.sirv-block-hide');
            let $visible = $('.sirv-block-visible');
            $hide.addClass('sirv-block-visible').removeClass('sirv-block-hide');
            $visible.addClass('sirv-block-hide').removeClass('sirv-block-visible');
        }


        $(document).on('options_tab_changed', onOptionsTabChanged);
        function onOptionsTabChanged(event){
            if(!!event.detail.hash && event.detail.hash == 'cache'){
                addInputCssPathPadding();
            }
        }


        function addInputCssPathPadding(){
            let $constPath = $('.sirv-input-const-text');
            if(!!$constPath.length){
                let $cssPathInput = $('#sirv-custom-backcss-path-text');
                let constPathClientRect = $constPath[0].getBoundingClientRect();
                let constPathWidth = constPathClientRect['width'];

                $cssPathInput.css('padding-left', constPathWidth);
            }
        }


        $('input[name=sirv-woo-pin-video], input[name=sirv-woo-pin-spin], input[name=sirv-woo-pin-image], input[name=sirv-woo-pin-model]').on('click', updatePinData);
        $('#sirv-woo-pin-input-template').on('input', updatePinData);
        function updatePinData(){
            let video = $('input[name=sirv-woo-pin-video]:checked').val();
            let spin = $('input[name=sirv-woo-pin-spin]:checked').val();
            let image = $('input[name=sirv-woo-pin-image]:checked').val();
            let model = $("input[name=sirv-woo-pin-model]:checked").val();
            let image_template = $('#sirv-woo-pin-input-template').val();

            if(image !== 'no'){
                $('.sirv-woo-pin-input-wrapper').show();
            }else{
                $('.sirv-woo-pin-input-wrapper').hide();
            }

            $('#sirv-woo-pin-gallery').val(JSON.stringify({'video': video, 'spin': spin, 'image': image, 'model': model, 'image_template' : image_template}));
        }


        $('input[name=sirv-woo-cat-content-images], input[name=sirv-woo-cat-content-videos], input[name=sirv-woo-cat-content-spins]').on('click', updateCatContentData);
        function updateCatContentData(){
            const video = $('input[name=sirv-woo-cat-content-videos]').is(':checked') ? 'yes' : 'no';
            const spin = $('input[name=sirv-woo-cat-content-spins]').is(':checked') ? 'yes' : 'no';
            const image = $('input[name=sirv-woo-cat-content-images]').is(':checked') ? 'yes' : 'no';

            $('#sirv-woo-cat-content-hidden').val(JSON.stringify({'video': video, 'spin': spin, 'image': image}));
        }

        $('input[name=sirv_woo_cat_swap_method_bullets], input[name=sirv_woo_cat_swap_method_arrows]').on('click', updateCatSwapData);
        function updateCatSwapData(){
            const bullets = $('input[name=sirv_woo_cat_swap_method_bullets]').is(':checked') ? 'yes' : 'no';
            const arrows = $('input[name=sirv_woo_cat_swap_method_arrows]').is(':checked') ? 'yes' : 'no';

            $("#sirv-woo-cat-swap-method-hidden").val(JSON.stringify({ arrows: arrows, bullets: bullets }));
        }

        $("input[name=SIRV_WOO_CAT_ITEMS]").on('change', manageWooCatItemsState);
        function manageWooCatItemsState() {
            wooCatItemsState($(this).val());
        }


        function wooCatItemsState(activeItem){
            const $sirv_woo_cat_content_id = $('#sirv_woo_cat_content_id');
            const $sirv_woo_cat_source_id = $("#sirv_woo_cat_source_id");
            const $sirv_woo_cat_swap_method_id = $('#sirv_woo_cat_swap_method_id');
            const $sirv_woo_cat_zoom_on_hover_id = $('#sirv_woo_cat_zoom_on_hover_id');
            switch (activeItem * 1) {
                case 1:
                    $sirv_woo_cat_content_id.show();
                    $sirv_woo_cat_source_id.show();
                    $sirv_woo_cat_swap_method_id.hide();
                    $sirv_woo_cat_zoom_on_hover_id.show();
                    break;
                case 2:
                    $sirv_woo_cat_content_id.hide();
                    $sirv_woo_cat_source_id.show();
                    $sirv_woo_cat_swap_method_id.hide();
                    $sirv_woo_cat_zoom_on_hover_id.hide();
                    break;
                case 3:
                case 4:
                case 1000:
                    $sirv_woo_cat_content_id.show();
                    $sirv_woo_cat_source_id.show();
                    $sirv_woo_cat_swap_method_id.show();
                    $sirv_woo_cat_zoom_on_hover_id.show();
                    break;

                default:
                    break;
            }
        }


        function initializeWooCatItemsState(){
            const activeItem = $("input[name=SIRV_WOO_CAT_ITEMS]:checked").val() || 1;
            wooCatItemsState(activeItem);
        }

        //-----------------------smv order----------------------------
        $("#sirv-smv-order-items").sortable({
            items: "> li:not(:last)",
            cursor: "move",
            scrollSensitivity: 40,
            opacity: 1,
            scroll: false,
            placeholder: "sirv-smv-order-sortable-placeholder",
            stop: function (event, ui) {
                recalcSmvOrderData();
            },
        });

        $(".sirv-smv-order-item-add").on("click", showOrderSelect);
        function showOrderSelect(e) {
            $(".sirv-smv-order-select").show();
        }

        $(".sirv-smv-order-select-item").on("click", function (e) {
            e.stopPropagation();
            const type = $(this).attr("data-item-type");
            const text = $(this).text();

            addOrderItem(type, text);
            $(".sirv-smv-order-select").hide();
        });

        function addOrderItem(type, text) {
            const $addItem = $(".sirv-smv-order-item-add");

            const itemHTML = `
        <li class="sirv-smv-order-item sirv-smv-order-item-changeble sirv-no-select-text" data-item-type="${type}">
            <div class="sirv-smv-order-item-dots">⠿</div>
            <div class="sirv-smv-order-item-title"><span>${text}</span></div>
            <div class="sirv-smv-order-item-delete"><span class="dashicons dashicons-trash"></span></div>
        </li>`;

            $addItem.before(itemHTML);
            recalcSmvOrderData();
        }

        $(document).on("click", ".sirv-smv-order-item-delete", deleteOrderItem);
        function deleteOrderItem(e) {
            const $item = $(this).parent();
            $item.remove();

            recalcSmvOrderData();
        }

        function recalcSmvOrderData() {
            const data = [];
            $(".sirv-smv-order-item-changeble").each(function () {
                data.push($(this).attr("data-item-type"));
            });

            $("#sirv-woo-smv-content-order").val(JSON.stringify(data));
        }
        //--------------------END smv order---------------------------


        $('input[name=SIRV_FOLDER]').on('input', showWarningOnFolderChange);
        function showWarningOnFolderChange() {
            $('.sirv-warning-on-folder-change').removeClass("sirv-hide");
        }


        $("input[name=SIRV_HTTP_AUTH_CHECK]").on('change', onAuthCheckChange);
        function onAuthCheckChange(){
            let isChecked = $("input[name=SIRV_HTTP_AUTH_CHECK]").is(':checked');
            let $authCredBlock = $(".sirv-http-auth-credentials");

            if(isChecked){
                $authCredBlock.fadeIn();
            }else{
                $authCredBlock.fadeOut();
            }
        }


        $(".sirv-calc-library-size-action").on("click", getMediaStorageSizeNew);
        function getMediaStorageSizeNew(){
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_wp_media_library_size_new',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function (){
                    hideMessages(".sirv-sync-messages");
                    $(".sirv-calc-library-size-action").prop("disabled", true);
                    $(".sirv-calc-library-size-show-analizing").css({'display': 'flex',});
                    $(".sirv-calc-media-size-data").hide();
                },
            }).done(function (res) {
                //debug
                //console.log(res);

                if(res.error){
                    showMessage(".sirv-sync-messages", res.error);
                    console.error(res.error);
                }

                if(res.status == "processing"){
                    $(".sirv-calc-library-size-analizing-progress").text(`${res.progress}%`);
                    getMediaStorageSizeNew();
                }

                if(res.status == "done"){
                    const approximately_symbol = res.calc_type == 'approximately' ? '~' : '';
                    $(".sirv-calc-library-size-action").prop("disabled", false);
                    $(".sirv-calc-library-size-show-analizing").hide();
                    $(".sirv-calc-library-size-analizing-progress").text("0%");
                    $(".sirv-calc-media-size-data").show();
                    $(".sirv-calc-media-size-approx_symbol").text(approximately_symbol);
                    $(".sirv-calc-library-size-show-size").text(res.formatted_size);
                    $(".sirv-calc-library-size-show-count").text(`(${res.img_count} media items)`);
                    $(".sirv-calc-library-size-show-date").text(res.date);
                }

            }).fail(function (jqXHR, status, error) {
                console.log("Error during ajax request: " + error);
                showMessage(".sirv-sync-messages", error);

                $(".sirv-calc-library-size-action").prop("disabled", false);
                $(".sirv-calc-library-size-show-analizing").hide();
                $(".sirv-calc-library-size-analizing-progress").text("0%");
                $(".sirv-calc-media-size-data").show();

                $(".sirv-calc-library-size-show-size").text("");
                $(".sirv-calc-library-size-show-date").text("");
            });
        }


        //-----------------------sirv js modules--------------------------------
        function debounce(func, timeout = 1000){
            let timer;
            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => { func.apply(this, args); }, timeout);
            };
        }


        const debauncedGetSirvJsCompressedSize = debounce(getSirvJsCompressedSize);

        $("input[name=sirv_js_module]").on("change", manageSirvJsModulesLoad);
        function manageSirvJsModulesLoad(){
            const $optionStore = $("#sirv-js-modules-store");
            const $all = $("#all-js-modules-switch");
            const $modules = $("input[name=sirv_js_module]");
            const allModules = ['lazyimage','zoom','spin','hotspots','video', 'gallery', 'model'];

            let isNotAllActiveModules = false;

            let activeModules = [];

            function manageAllState($selector, activeModules){
                let isSelectorChecked = $selector.is(":checked");

                if(activeModules.length == allModules.length && !isSelectorChecked){
                    $selector.prop('checked', true);
                    $selector.prop('disabled', true);
                }

                if (activeModules.length < allModules.length && isSelectorChecked) {
                    $selector.prop("checked", false);
                    $selector.prop("disabled", false);
                }

            }

            $modules.each(function(){
                if ($(this).is(":checked")){
                    activeModules.push($(this).attr('data-module'));
                }
            });


            if(activeModules.length === 0){
                isNotAllActiveModules = true;
                hideMessages(".js-modules-messages");
                showMessage('.js-modules-messages', 'Please select at least one feature', 'warning');
            }else{
                hideMessages(".js-modules-messages");
            }

            manageAllState($all, activeModules);

            let urlModulesStr = activeModules.join(",");
            setSirvJsCompressedSize(urlModulesStr);

            const modulesStr = isNotAllActiveModules ? allModules.join(",") : activeModules.join(",");
            $optionStore.val(modulesStr);

        }


        $("#all-js-modules-switch").on("change", manageSirvJsModulesLoadAll);
        function manageSirvJsModulesLoadAll(){
            const $modules = $("input[name=sirv_js_module]");
            let isChecked = $(this).is(':checked');

            if(isChecked){
                $(this).prop('disabled', true);
                $modules.each(function () {
                    $(this).prop("checked", isChecked);
                });
            }
            manageSirvJsModulesLoad();
        }


        function setSirvJsCompressedSize(modules){
            if(!!!modules){
                $(".sirv-compressed-js-val").text("0 Kb");
                return;
            }
            if (!!sirvJsCompressedSizes[modules]){
                $(".sirv-compressed-js-val").text(getSirvJsBudleSizeStr(sirvJsCompressedSizes[modules]));
            }else{
                $(".sirv-compressed-js-spinner").show();
                $(".sirv-compressed-js-val").text("Calculating...");
                //debounce
                debauncedGetSirvJsCompressedSize(modules);
            }

        }


        function getSirvJsBudleSizeStr(sizes){
            return `${sizes.compressed_s} (unzipped ${sizes.uncompressed_s})`;
        }


        function getSirvJsCompressedSize(modules){
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_get_js_module_size',
                    modules: modules,
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function (){
                  //code here
                },
            }).done(function (res) {
                //debug
                //console.log(res);

                if(res.error){
                    console.error(res.error);
                }

                $(".sirv-compressed-js-spinner").hide();

                if(!!res.error){
                    $(".sirv-compressed-js-val").text("Error: " + res.error);
                }else{
                    $(".sirv-compressed-js-val").text(`${res.compressed_s} (unzipped ${res.uncompressed_s})`);
                    sirvJsCompressedSizes[modules] = {compressed_s: res.compressed_s, uncompressed_s: res.uncompressed_s};
                }
            }).fail(function (jqXHR, status, error) {
                $(".sirv-compressed-js-spinner").hide();

                console.log("Error during ajax request: " + error);
                $(".sirv-compressed-js-val").text('Error: ' + error);
            });
        }
         //-----------------------END sirv js modules------------------


        //manage SIRV_ENABLE_CDN option change
        $("input[name=SIRV_ENABLE_CDN]").on("change", manageEnableCDNOption);
        function manageEnableCDNOption(){
            const status = $(this).val();
            const isEnabled = status === '1' ? true : false;

            const $parseImages = $("input[name=SIRV_PARSE_STATIC_IMAGES]");
            const $parseVideos = $("input[name=SIRV_PARSE_VIDEOS]");

            if(isEnabled){
                $("input[name=SIRV_PARSE_STATIC_IMAGES][value=1]").prop('disabled', false);
                $("input[name=SIRV_PARSE_VIDEOS][value=on]").prop('disabled', false);
            }else{
                $("input[name=SIRV_PARSE_STATIC_IMAGES][value=1]").prop('disabled', true);
                $("input[name=SIRV_PARSE_STATIC_IMAGES][value=2]").prop('checked', true);
                $("input[name=SIRV_PARSE_VIDEOS][value=on]").prop('disabled', true);
                $("input[name=SIRV_PARSE_VIDEOS][value=off]").prop('checked', true);
            }
        }


        //migrate woo additional variation images data
        $(".sirv-migrate-wai-data").on('click', migrateWAIData);
        function migrateWAIData(){
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_migrate_wai_data',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function (){
                    $(".sirv-wai-bar-line-complited").addClass("sirv-progress-bar-animated");
                    $(".sirv-migrate-wai-data").prop('disabled', true);
                    $(".sirv-migrate-wai-data").text('Migrating...');
                },
            }).done(function (res) {
                //debug
                //console.log(res);

                if(res.error){
                    $('.sirv-wai-bar-line-complited').removeClass('sirv-progress-bar-animated');
                    updateWAIProcessingStatus(res);
                    showMessage('.sirv-migrate-wai-data-messages', res.error);
                    return;
                }

                if(res.synced_percent == 100){
                    $('.sirv-wai-bar-line-complited').removeClass('sirv-progress-bar-animated');
                    const $block = $('.migrate-woo-additional-images-wrapper');
                    $block.empty();
                    $block.append(
                        `<span class="sirv-option-responsive-text">If you use the WooCommerce Additional Variation Images plugin, you can migrate images from that plugin into Sirv. You don\'t need that plugin if you use Sirv.</span>
                        <p>All images have been migrated. You may wish to uninstall the WooCommerce Additional Variation Images plugin.</p>`
                    );
                }else{
                    updateWAIProcessingStatus(res);
                    migrateWAIData();
                }
            }).fail(function (jqXHR, status, error) {
                $('.sirv-wai-bar-line-complited').removeClass('sirv-progress-bar-animated');
                $(".sirv-migrate-wai-data").prop("disabled", false);
                $(".sirv-migrate-wai-data").text("Migrate");

                showMessage('.sirv-migrate-wai-data-messages', error);

                console.error("Error during ajax request: " + error);
            });
        }


        function updateWAIProcessingStatus(data){
            //messages .sirv-migrate-wai-data-messages
            //button .sirv-migrate-wai-data
            //percent .sirv-wai-progress-text-persents
            //count .sirv-wai-progress-text-complited
            //bar .sirv-wai-bar-line-complited

            $(".sirv-wai-progress-text-persents").text(data.synced_percent_text);
            $(".sirv-wai-progress-text-complited span").text(`${data.synced} out of ${data.all}`);
            $(".sirv-wai-bar-line-complited").css('width', data.synced_percent_text);
        }


        $(".sirv-scrollbox").on("scroll", scrollShadows);
        function scrollShadows(){
            const content = document.querySelector(".sirv-scrollbox");
            const wrapper = document.querySelector(".sirv-scrollbox-parent");
            const shadowTop = document.querySelector(".sirv-shadow-top");
            const shadowBottom = document.querySelector(".sirv-shadow-bottom");

            let contentScrollHeight = content.scrollHeight - wrapper.offsetHeight;

            let currentScroll = $(this).scrollTop() / contentScrollHeight;
            shadowTop.style.opacity = currentScroll;
            shadowBottom.style.opacity = 1 - currentScroll;
        }


        $(".sirv-option-show-path-filters-action").on("click", showPathFiltersHelpWindow);
        function showPathFiltersHelpWindow(){
            sirvUIShowInformDialog("How to use filters",
                `
                    <style>
                        .sirv-option-show-path-filters-help table td {
                            vertical-align: baseline;
                        }
                    </style>
                    <div class="sirv-option-show-path-filters-help">
                        <p>
                            A filter is required if Sirv folders are named with truncated parts of your SKUs. This folder naming technique is recommended if you have more than 10,000 products. <a target="_blank" href="https://sirv.com/help/articles/naming-folders-images/">Learn more</a>.
                        </p>
                        <p>
                            <h3>Filters</h3>

                            <table width="100%">
                                <tbody>
                                    <tr>
                                        <td width="35%"><b>first:{length}</b></td>
                                        <td>Number of characters from beginning.</td>
                                    </tr>
                                    <tr>
                                        <td><b>last:{length}</b></td>
                                        <td>Number of characters from end.</td>
                                    </tr>
                                    <tr>
                                        <td><b>chars:{position}:{length}</b></td>
                                        <td>Number of characters from a position. Position can be negative, to count from end of the string. </td>
                                    </tr>
                                </tbody>
                            </table>
                        </p>
                        <p>
                            <h3>Examples</h3>

                            Specify either the product SKU <code>{product-sku}</code> or variation SKU <code>{variation-sku}</code> and your chosen filter.
                            For example, the product SKU <b>abcdef</b> could be truncated like so:
                            <table width="100%">
                                <tbody>
                                    <tr>
                                        <td width="50%"><code>{product-sku first:3}</code></td>
                                        <td>Will return <b>abc</b></td>
                                    </tr>
                                    <tr>
                                        <td><code>{product-sku last:3}</code></td>
                                        <td>Will return <b>def</b></td>
                                    </tr>
                                    <tr>
                                        <td><code>{product-sku chars:1:3}</code></td>
                                        <td>Will return <b>abc</b></td>
                                    </tr>
                                    <tr>
                                        <td><code>{product-sku chars:2:4}</code></td>
                                        <td>Will return <b>bcde</b></td>
                                    </tr>
                                    <tr>
                                        <td><code>{product-sku chars:-2:2}</code></td>
                                        <td>Will return <b>ef</b></td>
                                    </tr>
                                    <tr>
                                        <td><code>{product-sku chars:-3:1}</code></td>
                                        <td>Will return <b>d</b></td>
                                    </tr>
                                </tbody>
                            </table>
                        </p>
                        <p>
                            <h3>Full example</h3>

                            The most popular folder naming convention starts with a top level folder such as products, then a truncated SKU, then the full SKU. Example:

                            <pre><code>/products/{product-sku first:3}/{product-sku}/</code></pre>
                        </p>
                    <div>
                `
            );
        }


        $("input[name=SIRV_WOO_IS_USE_VIEW_FILE]").on("change", manageViewFileContentOption);
        function manageViewFileContentOption(){
            const $status = $(this).val();

            if($status == 'on'){
                manageElement('.sync-all-view-data-show-dialog', false);
            }else{
                manageElement('.sync-all-view-data-show-dialog', true);
            }

        }


        $(".sirv-clean-smv-html-cache").on("click", deleteSmvHtmlCache);
        function deleteSmvHtmlCache(){
            const button = $(this);
            const buttonSelector = ".sirv-clean-smv-html-cache";

            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_clear_smv_html_cache',
                    _ajax_nonce: sirv_options_data.ajaxnonce,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function (){
                    hideMessages(".sirv-smv-html-cache-messages");

                    manageElement(buttonSelector, true, 'Clearing cache...');
                    button.siblings(".sirv-traffic-loading-ico").show();
                },
            }).done(function (res) {
                //debug
                //console.log(res);

                manageElement(buttonSelector, false, "Clear cache");
                button.siblings(".sirv-traffic-loading-ico").hide();

                if(res.error){
                    showMessage(".sirv-smv-html-cache-messages", res.error);
                }

                if (!!res.affected_rows){
                    showMessage(".sirv-smv-html-cache-messages", `${res.affected_rows} rows have been deleted`, 'success');
                }

                $(".sirv-smv-html-cache-count").text(res.cache_count);

            }).fail(function (jqXHR, status, error) {
                manageElement(buttonSelector, false, "Clear cache");
                button.siblings(".sirv-traffic-loading-ico").hide();

                console.error("Error during ajax request: " + error);
                showAjaxErrorMessage(jqXHR, status, error, '.sirv-smv-html-cache-messages');

            });
        }

        $("input[name=SIRV_WOO_SMV_CACHE_IS_ENABLE]").on("change", manageSmvHtmlCacheOption);
        function manageSmvHtmlCacheOption(){
            const $status = $(this).val();

            if($status == 'on'){
                manageElement(".sirv-clean-smv-html-cache", false);
            }else{
                manageElement(".sirv-clean-smv-html-cache", true);
            }

        }

        //-----------------------initialization-----------------------
        setProfiles();
        getTabFromUrlHash();
        //setCorrectTime();
        //showResponsiveWarning();
        addInputCssPathPadding();
        storePreventedSizesOnLoad();
        onAuthCheckChange();
        initializeWooCatItemsState();

    }); //domready end
});
