function WPFB_PostBrowser(inputId, titleId)
{
	var postId = document.getElementById(inputId).value;
	var pluginUrl = (typeof(wpfbConf.ajurl) == 'undefined') ? "../wp-content/plugins/wp-filebase/" : (wpfbConf.ajurl+"/../");
	var browserWindow = window.open(pluginUrl+"wpfb-postbrowser.php?post=" + postId + "&inp_id=" + inputId + "&tit_id=" + titleId, "PostBrowser", "width=300,height=400,menubar=no,location=no,resizable=no,status=no,toolbar=no,scrollbars=yes");
	browserWindow.focus();
}

function WPFB_AddTplVar(select, input)
{
	if(select.selectedIndex == 0 || select.options[select.selectedIndex].value == '')
		return;
		
	var tag = '%' + select.options[select.selectedIndex].value + '%';
	var inputEl = select.form.elements[input];
	
	if (document.selection)
	{
		inputEl.focus();
		sel = document.selection.createRange();
		sel.text = tag;
	}
	else if (inputEl.type == 'textarea' && typeof(inputEl.selectionStart) != 'undefined' && (inputEl.selectionStart || inputEl.selectionStart == '0'))
	{
		var startPos = inputEl.selectionStart;
		var endPos = inputEl.selectionEnd;
		inputEl.value = inputEl.value.substring(0, startPos) + tag + inputEl.value.substring(endPos, inputEl.value.length);
	}
	else
	{
		inputEl.value += tag;
	}
	
	if(typeof(WPFB_PreviewTpl) == 'function') WPFB_PreviewTpl(inputEl);
	
	select.selectedIndex = 0;
}

function WPFB_ShowHide(el, show)
{
	var newCs = '';
	var cs = el.className.split(' ');
	// remove hidden class
	for (var i = 0; i < cs.length; ++i)
	{
		if(cs[i] != 'hidden')
		newCs += cs[i] + ' ';
	}
	if(!show)
		newCs += 'hidden';
	else
		newCs = newCs.substring(0, newCs.length - 1);
	el.className = newCs;
}

function WPFB_CheckBoxShowHide(checkbox, name)
{
	var chk = checkbox.checked;
	var input = checkbox.form.elements[name];
	if(!input) input = document.getElementById(name);
	if(input)
		WPFB_ShowHide(input, chk);
	
	// show/hide labels
	if(checkbox.form) {
		var lbs = checkbox.form.getElementsByTagName('label');
		for(var l = 0; l < lbs.length; ++l)
		{
			if(lbs[l].htmlFor == name)
				WPFB_ShowHide(lbs[l], chk);
		}
	}
}


if('undefined' !== typeof jQuery) {
jQuery(document).ready(function($) {
	jQuery('.wpfb-cat-select').change(function(e) {
		var s = jQuery(e.target);
		var o = s.find(":selected");
		if(!o||!o.length||!o.hasClass('add-cat'))
			return true;
		var pid = o.val().substr(1);//rm '+'
		var inp = jQuery('<input type=text />');
		var form = jQuery('<form action="" class="wpfb-new-cat-inline"></form>');
		form.insertBefore(s.hide()).append(inp.attr('placeholder',o.text().trim().substr(1).trim()).width(s.width()).css({'font-size': s.css('font-size')}));
		inp.focus();

		var submit = function(e) {
			var t = jQuery(e.target); var submitting = t.is('form');
			var cat_name = inp.val();
			if(cat_name !== '' && !inp.prop('disabled')) {
				inp.prop('disabled', true).addClass('loading');
				jQuery.ajax({url: wpfbConf.ajurl, type:"POST",dataType:'json',
					data:{action:'new-cat', cat_name:cat_name, cat_parent:pid},
					success: (function(data){
						if(data.error) {
							alert(data.error);
							s.val(pid);
						} else {
							jQuery('<option value='+data.id+'>'+o.text().substr(0,o.text().indexOf('+'))+data.name+'</option><option value="+'+data.id+'" class="add-cat">&nbsp;&nbsp; '+o.text()+'</option>').insertBefore(jQuery('select.wpfb-cat-select').children('option[value="+'+pid+'"]'));
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
});
}