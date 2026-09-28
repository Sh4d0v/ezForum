function paste_smilie(e, s) {
  //IE support
  if (document.selection) {
    e.focus();
    var sel = document.selection.createRange();
    sel.text = s;
    sel.select();
  }
  //MOZILLA/NETSCAPE support
  else
    if (typeof e.selectionStart === 'number') {
      var startPos = e.selectionStart;
      var endPos = e.selectionEnd;
      e.value = e.value.substring(0, startPos) + s + e.value.substring(endPos, e.value.length);
      e.selectionStart = startPos + s.length;
      e.selectionEnd = e.selectionStart;
    }
    else {
      e.value += s;
    }
  e.focus();
}

function paste_string(e, s) {
  var wrapper = s.match(/^(\[([a-z]+)(?:=[^\]]+)?\])(\[\/\2\])$/i);

  //IE support
  if (document.selection) {
    e.focus();
    var sel = document.selection.createRange();
    var selectedText = sel.text;

    if (selectedText.length > 0 && wrapper) {
      sel.text = wrapper[1] + selectedText + wrapper[3];
    } else {
      sel.text = s;
      sel.moveStart('character', s.indexOf(']') + 1 - s.length);
      sel.moveEnd('character', s.lastIndexOf('[') - s.length);
      sel.select();
    }
  }
  //MOZILLA/NETSCAPE support
  else
    if (typeof e.selectionStart === 'number') {
      var startPos = e.selectionStart;
      var endPos = e.selectionEnd;
      var selectedText = e.value.substring(startPos, endPos);

      if (selectedText.length > 0 && wrapper) {
        var wrappedText = wrapper[1] + selectedText + wrapper[3];
        e.value = e.value.substring(0, startPos) + wrappedText + e.value.substring(endPos, e.value.length);
        e.selectionStart = startPos + wrapper[1].length;
        e.selectionEnd = e.selectionStart + selectedText.length;
      } else {
        e.value = e.value.substring(0, startPos) + s + e.value.substring(endPos, e.value.length);
        e.selectionStart = startPos + s.indexOf("]") + 1;
        e.selectionEnd = startPos + s.lastIndexOf('[');
      }
    }
    else {
      e.value += s;
    }
  e.focus();
}

function paste_list(e, itemLabel) {
  var snippet = '[list]\n[*]' + itemLabel + '\n[/list]';
  var startPos = (typeof e.selectionStart === 'number') ? e.selectionStart : e.value.length;
  paste_string(e, snippet);

  if (typeof e.setSelectionRange === 'function') {
    var itemStart = startPos + snippet.indexOf(itemLabel);
    e.setSelectionRange(itemStart, itemStart + itemLabel.length);
  }
  e.focus();
}

function paste_size(e, promptText, invalidText) {
  var size = prompt(promptText, '10');
  if (size === null) return;
  size = size.replace(/^\s+|\s+$/g, '');
  if (!/^(10|12|14|16)$/.test(size)) {
    alert(invalidText);
    return;
  }
  paste_string(e, '[size=' + size + '][/size]');
}

function paste_url(e, p1, p2) {
  var u = prompt(p1, '');
  if (u === null) return; // cancelled
  var t = prompt(p2, '');
  if (t === null) t = '';
  paste_string(e, '[url=' + u + ']' + t + '[/url]');
}

function paste_email(e, p1, p2) {
  var a = prompt(p1, '');
  if (a === null) return; // cancelled
  var t = prompt(p2, '');
  if (t === null) t = '';
  paste_string(e, '[email=' + a + ']' + t + '[/email]');
}

function paste_image(e, p1) {
  var src = prompt(p1, '');
  if (src === null) return; // cancelled
  paste_string(e, '[img]' + src + '[/img]');
}

function spopup(doc, xwidth, yheight, scrollbar) {
  var _left = Math.round((screen.width - xwidth) / 2);
  var _top = Math.round((screen.height - yheight) / 2);
  var popupWin = window.open('smilies_popup.php?doc=window.opener.' + doc, "imageWin", "toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=" + scrollbar + ",resizable=0,width=" + xwidth + ",height=" + yheight + ",left=" + _left + ",top=" + _top + "");
}

function get_sel() {
  try {
    if (window.getSelection) return window.getSelection().toString();
    else if (document.getSelection) return document.getSelection().toString();
    else if (document.selection) return document.selection.createRange().text;
  } catch (e) {
    return '';
  }
  return '';
}

function quote_sel(e) {
  var sel = get_sel();
  if (!sel) return;
  paste_string(e, '[quote]' + sel + '[/quote]');
}
