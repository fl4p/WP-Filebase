// gets the file id of the a-element linking to the file
function wpfb_getLinkFileId(el) {
    el = jQuery(el);
    var fid = el.attr('wpfbfid');
    if (fid && fid > 0)
        return fid;
    var fi = wpfb_getFileInfo(el.attr('href'));
    if (fi != null)
        return fi.id;
    return 0;
}

function wpfb_menuEdit(menuItem, menu) {
    var fid = wpfb_getLinkFileId(menu.target);
    if (fid > 0)
        window.location = wpfbConf.fileEditUrl + fid + '&redirect_to=' + escape(window.location.href);
}


function wpfb_fileDelete(fid) {
    jQuery.ajax({
        type: 'POST',
        url: wpfbConf.ajurl,
        data: {wpfb_action: 'delete', file_id: fid},
        success: (function (data) {
            console.log(data);
            if (data != '-1') {
                jQuery('a[href="'+data.url+'"],a[data-url="'+data.url+'"]').each(function() {
                    console.log(this);
                    var el = jQuery(this);
                    el.css("textDecoration", "line-through");
                    el.unbind('click').click((function () {
                        return false;
                    }));
                    el.fadeTo('slow', 0.3);
                });

                jQuery('.wpfb-file-'+data.id).each(function() {
                    console.log(this);
                    var el = jQuery(this);
                    el.unbind('click').click((function () { return false; }));
                    el.fadeTo('slow', 0.3);
                });


            }
        })
    });
}

function wpfb_menuDel(menuItem, menu) {

    var fid = wpfb_getLinkFileId(menu.target);
    if (fid > 0 && confirm('Do you really want to delete this file?'))
    {
        jQuery('body').css('cursor', 'wait');

        jQuery.ajax({
            type: 'POST',
            url: wpfbConf.ajurl,
            data: {wpfb_action: 'delete', file_id: fid},
            async: false,
            success: (function (data) {
                if (data != '-1') {
                    var el = jQuery(menu.target);
                    el.css("textDecoration", "line-through");
                    el.unbind('click').click((function () {
                        return false;
                    }));
                    el.fadeTo('slow', 0.3);
                }
            })
        });

        jQuery('body').css('cursor', 'default');
    }
}

function wpfb_addContextMenu(el, url) {
    if (typeof (wpfbContextMenu) != 'undefined')
        el.contextMenu(wpfbContextMenu, {theme: 'osx', shadow: false, showTransition: 'fadeIn', hideTransition: 'fadeOut', file_url: url});
}

function wpfb_toggleContextMenu() {
    wpfbConf.cm = !wpfbConf.cm;
    jQuery.ajax({url: wpfbConf.ajurl, data: {wpfb_action: 'toggle-context-menu'}, async: false});
    return true;
}

function wpfb_print(obj, ret) {
    var str = ' ' + obj + ':', t;
    for (var k in obj) {
        t = typeof (obj[k]);
        str += ' [' + k + ':' + t + '] = ' + ((t == 'string' || t == 'array') ? obj[k] : wpfb_print(obj[k], true)) + '\n';
    }
    if (typeof (ret) == 'undefined' || !ret)
        alert(str);
    return str;
}

function wpfb_newCatInput(el, pid) {
    var el = jQuery(el);
    var f = el.prev("form");
    var inp = f.children("input[name='cat_name']");

    if (f.data('setup') != 1) {
        var submit = function (e) {
            var t = jQuery(e.target);
            var submitting = t.is('form');
            var cat_name = inp.val();
            if (cat_name !== '') {
                inp.val('');
                el.closest('li').before('<li class="hasChildren"><span class="placeholder"></span></li>');
                var lip = el.closest('li').prev('li');
                var tv = el.parents('.treeview').first();
                var set = tv.data("settings");
                jQuery.ajax({url: wpfbConf.ajurl, type: "POST",
                    data: {wpfb_action: 'new-cat', cat_name: cat_name, cat_parent: pid, args: set.ajax.data, is_admin: (typeof (adminpage) !== 'undefined') ? 1 : 0},
                    success: (function (data) {
                        if (data.error) {
                            alert(data.error);
                            lip.remove();
                        } else {
                            lip.attr('id', data.id_str)
                                    .children('span')
                                    .removeClass('placeholder')
                                    .addClass(data.classes)
                                    .html(data.text)
                                    .after('<ul style="display: none;"><li class="last"><span class="placeholder">&nbsp;</span></li></ul>')
                                    ;
                            lip.prepareBranches(set).applyClasses(set, tv.data("toggler"));
                        }
                    }),
                    error: (function (jqXHR, textStatus, errorThrown) {
                        alert(errorThrown+' - '+jqXHR.responseText);
                        console.log(jqXHR.responseText);
                        lip.remove();
                    })});
            }
            f.hide();
            el.parent().children('a,span').show();

            return !submitting;
        };

        f.submit(submit).data('setup', 1);
        inp.blur(submit);
    }

    f.show();
    inp.val('').focus();
    el.parent().children('a,span').hide();

    return false;
}

function wpfb_treeviewAddFile(ev, pid)
{
    var tv = jQuery(ev.target).parents('.treeview').first();
    jQuery('#' + tv.attr('id') + '-btn').trigger(ev);
    console.log(ev);

    if ('undefined' === typeof (pid) || (!pid && pid !== 0))
        return true;

    jQuery('#' + tv.attr('id') + '-btn').data('cat_id', pid);


    var up = tv.data('uploader');
    up.settings.multipart_params["btn_cat_id"] = pid;
    return false;
}

function wpfb_fileBrowserTargetId(e, cat_or_file)
{
    var t = ('object' === typeof (e.target)) ? jQuery(e.target) : jQuery(e);


    var fbEl = jQuery(t).parents('ul.treeview,ul.filebrowser,ul').first();
    var idp = wpfb_getFileBrowserIDP(fbEl);

    var tid = t.prop("id");
    var pl = idp.length + cat_or_file.length;
    if (t.prop('tagName') === 'LI' && tid.substr(0, pl + 1) === (idp + cat_or_file + "-"))
        return parseInt(tid.substr(pl + 1));
    var p = t.parents('li[id^="' + idp + cat_or_file + '-"][id!="' + idp + cat_or_file + '-0"]');
    if (p && p.length)
        return parseInt(p.prop("id").substr(pl + 1));

    var params = wpfb_getFileBrowserParams(fbEl);

    if(params && typeof params.base != 'undefined')
        return params.base;

    return -1;
}

function wpfb_getFileBrowserIDP(id) {
    var set = (('object' === typeof (id)) ? id : jQuery('#' + id)).data("settings");
    //return (set && set.ajax && set.ajax.data.idp) ? set.ajax.data.idp : 'wpfb-';
    if (set && set.ajax && set.ajax.data.idp)
        return set.ajax.data.idp;
    if (set && set.id_prefix)
        return set.id_prefix;
    return 'wpfb-';
}

function wpfb_getFileBrowserParams(id) {
    var set = (('object' === typeof (id)) ? id : jQuery('#' + id)).data("settings");
    if (set && set.ajax)
        return set.ajax.data;
    return {};
}