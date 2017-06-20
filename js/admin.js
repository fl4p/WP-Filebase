function WPFB_PopupCenter(url, title, w, h) {
    // Fixes dual-screen position                         Most browsers      Firefox
    var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
    var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;

    width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
    height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

    var left = ((width / 2) - (w / 2)) + dualScreenLeft;
    var top = ((height / 2) - (h / 2)) + dualScreenTop;
    var newWindow = window.open(url, title, 'scrollbars=yes, menubar=no,location=no,resizable=no,status=no,toolbar=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);

    // Puts focus on the newWindow
    if (window.focus) {
        newWindow.focus();
    }

    return newWindow;
}

function WPFB_PostBrowser(inputId, titleId) {
    var postId = document.getElementById(inputId).value;
    var pluginUrl = (typeof(wpfbConf.ajurl) == 'undefined') ? "../wp-content/plugins/wp-filebase/" : (wpfbConf.ajurl + "/../");
    var browserWindow = WPFB_PopupCenter(wpfbConf.ajurl + "&wpfb_action=postbrowser-main&post=" + postId + "&inp_id=" + inputId + "&tit_id=" + titleId, "PostBrowser", 300, 400);
    browserWindow.focus();
}

function WPFB_AddTplVar(select, input) {
    if (select.selectedIndex == 0 || select.options[select.selectedIndex].value == '')
        return;

    var tag = '%' + select.options[select.selectedIndex].value + '%';
    var inputEl = select.form.elements[input];

    if (document.selection) {
        inputEl.focus();
        sel = document.selection.createRange();
        sel.text = tag;
    }
    else if (inputEl.type == 'textarea' && typeof(inputEl.selectionStart) != 'undefined' && (inputEl.selectionStart || inputEl.selectionStart == '0')) {
        var startPos = inputEl.selectionStart;
        var endPos = inputEl.selectionEnd;
        inputEl.value = inputEl.value.substring(0, startPos) + tag + inputEl.value.substring(endPos, inputEl.value.length);
    }
    else {
        inputEl.value += tag;
    }

    if (typeof(WPFB_PreviewTpl) == 'function') WPFB_PreviewTpl(inputEl);

    select.selectedIndex = 0;
}

function WPFB_ShowHide(el, show) {
    var newCs = '';
    var cs = el.className.split(' ');
    // remove hidden class
    for (var i = 0; i < cs.length; ++i) {
        if (cs[i] != 'hidden')
            newCs += cs[i] + ' ';
    }
    if (!show)
        newCs += 'hidden';
    else
        newCs = newCs.substring(0, newCs.length - 1);
    el.className = newCs;
}

function WPFB_CheckBoxShowHide(checkbox, name) {
    var chk = checkbox.checked;
    var input = checkbox.form.elements[name];
    if (!input) input = document.getElementById(name);
    if (input)
        WPFB_ShowHide(input, chk);

    // show/hide labels
    if (checkbox.form) {
        var lbs = checkbox.form.getElementsByTagName('label');
        for (var l = 0; l < lbs.length; ++l) {
            if (lbs[l].htmlFor == name)
                WPFB_ShowHide(lbs[l], chk);
        }
    }
}

function WPFB_VersionCompare(v1, v2, options) {
    var lexicographical = options && options.lexicographical,
        zeroExtend = options && options.zeroExtend,
        v1parts = v1.split('.'),
        v2parts = v2.split('.');

    function isValidPart(x) {
        return (lexicographical ? /^\d+[A-Za-z]*$/ : /^\d+$/).test(x);
    }

    if (!v1parts.every(isValidPart) || !v2parts.every(isValidPart)) {
        return NaN;
    }

    if (zeroExtend) {
        while (v1parts.length < v2parts.length) v1parts.push("0");
        while (v2parts.length < v1parts.length) v2parts.push("0");
    }

    if (!lexicographical) {
        v1parts = v1parts.map(Number);
        v2parts = v2parts.map(Number);
    }

    for (var i = 0; i < v1parts.length; ++i) {
        if (v2parts.length == i) {
            return 1;
        }

        if (v1parts[i] == v2parts[i]) {
            continue;
        }
        else if (v1parts[i] > v2parts[i]) {
            return 1;
        }
        else {
            return -1;
        }
    }

    if (v1parts.length != v2parts.length) {
        return -1;
    }

    return 0;
}


if ('undefined' !== typeof jQuery) {
    jQuery(document).ready(function ($) {
        jQuery('.wpfb-cat-select').change(function (e) {
            var s = jQuery(e.target);
            var o = s.find(":selected");
            if (!o || !o.length || !o.hasClass('add-cat'))
                return true;
            var pid = o.val().substr(1);//rm '+'
            var inp = jQuery('<input type=text />');
            var form = jQuery('<form action="" class="wpfb-new-cat-inline"></form>');
            form.insertBefore(s.hide()).append(inp.attr('placeholder', o.text().trim().substr(1).trim()).width(s.width()).css({'font-size': s.css('font-size')}));
            inp.focus();

            var submit = function (e) {
                var t = jQuery(e.target);
                var submitting = t.is('form');
                var cat_name = inp.val();
                if (cat_name !== '' && !inp.prop('disabled')) {
                    inp.prop('disabled', true).addClass('loading');
                    jQuery.ajax({
                        url: wpfbConf.ajurl, type: "POST", dataType: 'json',
                        data: {wpfb_action: 'new-cat', cat_name: cat_name, cat_parent: pid},
                        success: (function (data) {
                            if (data.error) {
                                alert(data.error);
                                s.val(pid);
                            } else {
                                jQuery('<option value=' + data.id + '>' + o.text().substr(0, o.text().indexOf('+')) + data.name + '</option><option value="+' + data.id + '" class="add-cat">&nbsp;&nbsp; ' + o.text() + '</option>').insertBefore(jQuery('select.wpfb-cat-select').children('option[value="+' + pid + '"]'));
                                s.val(data.id);
                            }
                            form.remove();
                            s.show();
                        })
                    });
                } else {
                    form.remove();
                    s.show();
                }

                return !submitting;
            };

            form.submit(submit);
            inp.blur(submit);
        });

        var usrAutoComplete =  jQuery(".wpfb-user-autocomplete");
        usrAutoComplete.length && usrAutoComplete.autocomplete && usrAutoComplete.autocomplete({
            source: function (request, response) {
                jQuery.ajax({
                    url: wpfbConf.ajurl, dataType: "json",
                    data: {wpfb_action: "usersearch", name_startsWith: request.term},
                    success: function (data) {
                        response(jQuery.map(data, function (user) {
                            user.toString = (function () {
                                return this.login;
                            });
                            return {label: user.login + " (" + user.name + ")", value: user}
                        }));
                    }
                });
            },
            minLength: 2,
            open: function () {
                jQuery(this).removeClass("ui-corner-all").addClass("ui-corner-top");
            },
            close: function () {
                jQuery(this).removeClass("ui-corner-top").addClass("ui-corner-all");
            }
        });
    });
}


