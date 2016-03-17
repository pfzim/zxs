var xhttp = [];
var k = 0;

function gi(name)
{
	return document.getElementById(name);
}

function escapeHtml(text) {
  return text
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
}

function formatbytes(bytes, decimals) {
   if(bytes == 0) return '0 B';
   var k = 1024;
   var dm = decimals || 2;
   var sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
   var i = Math.floor(Math.log(bytes) / Math.log(k));
   return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

if(!XMLHttpRequest.prototype.sendAsBinary) { 
	XMLHttpRequest.prototype.sendAsBinary = function(datastr) {  
		function byteValue(x)
		{
			return x.charCodeAt(0) & 0xff;
		}
		var ords = Array.prototype.map.call(datastr, byteValue);
		var ui8a = new Uint8Array(ords);  
		try {
			this.send(ui8a);
		}
		catch(e) {
			this.send(ui8a.buffer);
		}  
	};  
}

function f_xhr() {
  if (typeof XMLHttpRequest === 'undefined') {
	XMLHttpRequest = function() {
	  try { return new ActiveXObject("Msxml2.XMLHTTP.6.0"); }
		catch(e) {}
	  try { return new ActiveXObject("Msxml2.XMLHTTP.3.0"); }
		catch(e) {}
	  try { return new ActiveXObject("Msxml2.XMLHTTP"); }
		catch(e) {}
	  try { return new ActiveXObject("Microsoft.XMLHTTP"); }
		catch(e) {}
	  throw new Error("This browser does not support XMLHttpRequest.");
	};
  }
  return new XMLHttpRequest();
}


function f_copy(link, id) {
	var text;
	var pin = gi("pin"+id).textContent;
	if(pin.length > 0)
	{
		text = link + '    PIN: ' + pin;
	}
	else
	{
		text = link;
	}
	return f_copy0(text);
}

function f_copy0(text) {
	var textArea = document.createElement("textarea");

	textArea.style.position = 'fixed';
	textArea.style.top = 0;
	textArea.style.left = 0;

	textArea.style.width = '2em';
	textArea.style.height = '2em';

	textArea.style.padding = 0;

	textArea.style.border = 'none';
	textArea.style.outline = 'none';
	textArea.style.boxShadow = 'none';

	textArea.style.background = 'transparent';

	textArea.value = text;

	document.body.appendChild(textArea);

	textArea.select();

	try
	{
		document.execCommand('copy');
	}
	catch (err) { }

	document.body.removeChild(textArea);
	
	return false;
}

function f_popup(title, text)
{
	gi('popup').style.display='block';
	gi('fade').style.display='block';
	gi("status").textContent = text;
	gi("caption").textContent = title;
	
	return false;
}

function f_popupHTML(title, text)
{
	gi('popup').style.display='block';
	gi('fade').style.display='block';
	gi("status").innerHTML = text;
	gi("caption").textContent = title;
	
	return false;
}

function f_delete(id)
{
	if(confirm("Delete?"))
	{
		var xhr = f_xhr();
		if(xhr)
		{
			xhr.open("get", "/zxsa.php?action=delete&id="+id, true);
			xhr.onreadystatechange = function(e) {
				if(this.readyState == 4) {
					if(this.status == 200)
					{
						var result = JSON.parse(this.responseText);
						if(result.result)
						{
							f_popup("Error", result.status);
						}
						else
						{
							var row = gi("row"+id);
							row.parentNode.removeChild(row);
						}
					}
					else
					{
						f_popup("Error", "failure");
					}
				}
			};
			xhr.send(null);
		}
	}
	
	return false;
}

function f_unlink(id)
{
	if(confirm("Delete link?"))
	{
		var xhr = f_xhr();
		if (xhr)
		{
			xhr.open("get", "/zxsa.php?action=unlink&id="+id, true);
			xhr.onreadystatechange = function(e) {
					if(this.readyState == 4) {
						if(this.status == 200)
						{
							var result = JSON.parse(this.responseText);
							if(result.result)
							{
								f_popup("Error", result.status);
							}
							else
							{
								var row = gi("row"+id);
								row.parentNode.removeChild(row);
							}
						}
						else
						{
							f_popup("Error", "failure");
						}
					}
			};
			xhr.send(null);
		}
	}
	
	return false;
}

function f_rename_event(event, el, id, old)
{
	if(event == 13)
	{
		if(el.value.length != 0)
		{
			var xhr = f_xhr();
			if (xhr)
			{
				xhr.open("post", "/zxsa.php?action=rename&id="+id, true);
				xhr.onreadystatechange = function(e) {
					if(this.readyState == 4) {
						if(this.status == 200)
						{
							var result = JSON.parse(this.responseText);
							if(result.result)
							{
								gi("fname"+id).textContent = old;
								f_popup("Error", result.status);
							}
							else
							{
								gi("fname"+id).textContent = result.name;
								gi("fname"+id).onclick = function() {f_rename(gi("fname"+id), id)};
							}
						}
						else
						{
							gi("fname"+id).textContent = old;
							f_popup("Error", "failure");
						}
					}
				};
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				xhr.send("name="+encodeURIComponent(el.value));
			}
		}	
	}
	else if(event == 27)
	{
		el.onblur = null;
		var val = el.value;
		gi("fname"+id).onclick = function() {f_rename(gi("fname"+id), id)};
		gi("fname"+id).textContent = old;
	}
}

function f_rename(el, id)
{
	if(!gi('gettext'))
	{
		var val = el.textContent;
		el.onclick = '';
		el.innerHTML = '<input id="gettext" type="text" style="width: 98%" value="'+escapeHtml(val)+'" onblur="f_rename_event(13, this, '+id+', \''+escapeHtml(escapeHtml(val))+'\');" onkeydown="f_rename_event(event.keyCode, this, '+id+', \''+escapeHtml(escapeHtml(val))+'\');"/>';
		var ext = val.lastIndexOf(".");
		gi("gettext").setSelectionRange(0, (ext >= 0)?ext:val.length);
		gi("gettext").focus();
	}
	return false;
}

function f_rename_dir_event(event, el, id, old)
{
	if(event == 13)
	{
		if(el.value.length != 0)
		{
			var xhr = f_xhr();
			if (xhr)
			{
				xhr.open("post", "/zxsa.php?action=rename&id="+id, true);
				xhr.onreadystatechange = function(e) {
					if(this.readyState == 4) {
						if(this.status == 200)
						{
							var result = JSON.parse(this.responseText);
							if(result.result)
							{
								gi("fname"+id).innerHTML = '<a id="dir'+id+'" class="boldtext" href="/'+id+'/">'+old+'</a>';
								f_popup("Error", result.status);
							}
							else
							{
								gi("fname"+id).innerHTML = '<a id="dir'+id+'" class="boldtext" href="/'+id+'/">'+escapeHtml(result.name)+'</a>';
							}
						}
						else
						{
							gi("fname"+id).innerHTML = '<a id="dir'+id+'" class="boldtext" href="/'+id+'/">'+old+'</a>';
							f_popup("Error", "failure");
						}
					}
				};
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				xhr.send("name="+encodeURIComponent(el.value));
			}
		}
	}
	else if(event == 27)
	{
		el.onblur = null;
		var val = el.value;
		gi("fname"+id).innerHTML = '<a id="dir'+id+'" class="boldtext" href="/'+id+'/">'+old+'</a>';
	}
}

function f_rename_dir(id)
{
	if(!gi('gettext'))
	{
		var val = gi('dir'+id).textContent;
		gi('fname'+id).innerHTML = '<input id="gettext" type="text" style="width: 98%" value="'+escapeHtml(val)+'" onblur="f_rename_dir_event(13, this, '+id+', \''+escapeHtml(escapeHtml(val))+'\');" onkeydown="f_rename_dir_event(event.keyCode, this, '+id+', \''+escapeHtml(escapeHtml(val))+'\');"/>';
		gi("gettext").setSelectionRange(0, val.length);
		gi("gettext").focus();
	}
	return false;
}

function f_desc_event(event, el, id, old)
{
	if(event == 13)
	{
		var xhr = f_xhr();
		if (xhr)
		{
			xhr.open("post", "/zxsa.php?action=desc&id="+id, true);
			xhr.onreadystatechange = function(e) {
				if(this.readyState == 4) {
					if(this.status == 200)
					{
						var result = JSON.parse(this.responseText);
						if(result.result)
						{
							f_popup("Error", result.status);
						}
						else
						{
							gi("desc"+id).textContent = result.desc;
							gi("desc"+id).onclick = function() {f_desc(gi("desc"+id), id)};
						}
					}
					else
					{
						f_popup("Error", "failure");
					}
				}
			};
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.send("name="+encodeURIComponent(el.value));
		}
	}
	else if(event == 27)
	{
		el.onblur = null;
		var val = el.value;
		gi("desc"+id).onclick = function() {f_desc(gi("desc"+id), id)};
		gi("desc"+id).textContent = old;
	}
}

function f_desc(el, id)
{
	if(!gi('gettext'))
	{
		var val = el.textContent;
		el.onclick = '';
		el.innerHTML = '<input id="gettext" type="text" style="width: 98%" value="'+escapeHtml(val)+'" onblur="f_desc_event(13, this, '+id+', \''+escapeHtml(escapeHtml(val))+'\');" onkeydown="f_desc_event(event.keyCode, this, '+id+', \''+escapeHtml(escapeHtml(val))+'\');"/>';
		gi("gettext").setSelectionRange(0, val.length);
		gi("gettext").focus();
	}
	return false;
}

function f_desc_link_event(event, el, id, old)
{
	if(event == 13)
	{
		var xhr = f_xhr();
		if (xhr)
		{
			xhr.open("post", "/zxsa.php?action=desc_link&id="+id, true);
			xhr.onreadystatechange = function(e) {
				if(this.readyState == 4) {
					if(this.status == 200)
					{
						var result = JSON.parse(this.responseText);
						if(result.result)
						{
							f_popup("Error", result.status);
						}
						else
						{
							gi("desc"+id).textContent = result.desc;
							gi("desc"+id).onclick = function() {f_desc_link(gi("desc"+id), id)};
						}
					}
					else
					{
						f_popup("Error", "failure");
					}
				}
			};
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.send("name="+encodeURIComponent(el.value));
		}
	}
	else if(event == 27)
	{
		el.onblur = null;
		var val = el.value;
		gi("desc"+id).onclick = function() {f_desc_link(gi("desc"+id), id)};
		gi("desc"+id).textContent = old;
	}
}

function f_desc_link(el, id)
{
	if(!gi('gettext'))
	{
		var val = el.textContent;
		el.onclick = '';
		el.innerHTML = '<input id="gettext" type="text" style="width: 98%" value="'+escapeHtml(val)+'" onblur="f_desc_link_event(13, this, '+id+', \''+escapeHtml(escapeHtml(val))+'\');" onkeydown="f_desc_link_event(event.keyCode, this, '+id+', \''+escapeHtml(escapeHtml(val))+'\');"/>';
		gi("gettext").setSelectionRange(0, val.length);
		gi("gettext").focus();
	}
	return false;
}

function f_share(id)
{
	var xhr = f_xhr();
	if (xhr)
	{
		xhr.open("get", "/zxsa.php?action=share&id="+id, true);
		xhr.onreadystatechange = function(e) {
			if (this.readyState == 4) {
				if(this.status == 200)
				{
					var r = JSON.parse(this.responseText);
					if(r.result)
					{
						f_popup("Error", r.status);
					}
					else
					{
						//f_popupHTML("Link created", window.location.protocol+'//'+window.location.hostname+window.location.pathname.replace('zxs.php','link.php')+'?id='+r.id+' PIN: ' + r.pin + ' <a href="link.php?id=' + r.id + '" onclick="return f_copy0(this.href + \' PIN: ' + r.pin + '\');">Copy</a>');
						f_popupHTML("Link created", 'http://'+window.location.hostname+'/link/'+r.id+'/&nbsp;&nbsp;&nbsp;&nbsp;PIN: ' + r.pin + '&nbsp;&nbsp;&nbsp;&nbsp;<a href="/link/' + r.id + '/" onclick="return f_copy0(this.href + \'    PIN: ' + r.pin + '\');">Copy</a>');
					}
						
				}
				else
				{
					f_popup("Error", "failure");
				}
			}
		};
		xhr.send(null);
	}
	
	return false;
}

function f_pinoff(id)
{
	var xhr = f_xhr();
	if (xhr)
	{
		xhr.open("get", "/zxsa.php?action=pinoff&id="+id, true);
		xhr.onreadystatechange = function(e) {
			if(this.readyState == 4) {
				if(this.status == 200)
				{
					var result = JSON.parse(this.responseText);
					if(result.result)
					{
						f_popup("Error", result.status);
					}
					else
					{
						gi("pin"+id).textContent = result.pin;
					}
				}
				else
				{
					gi("pin"+id).textContent = result.pin;
				}
			}
		};
		xhr.send(null);
	}
	
	return false;
}

function f_pinon(id)
{
	var xhr = f_xhr();
	if (xhr)
	{
		xhr.open("get", "/zxsa.php?action=pinon&id="+id, true);
		xhr.onreadystatechange = function(e) {
			if(this.readyState == 4) {
				if(this.status == 200)
				{
					var result = JSON.parse(this.responseText);
					if(result.result)
					{
						f_popup("Error", result.status);
					}
					else
					{
						gi("pin"+id).textContent = result.pin;
					}
				}
				else
				{
					f_popup("Error", "failure");
				}
			}
		};
		xhr.send(null);
	}
	
	return false;
}

function f_select_all(el)
{
  checkboxes = document.getElementsByName('check');
  for(var i=0, n=checkboxes.length;i<n;i++) {
    checkboxes[i].checked = el.checked;
  }
}

function f_share_selected(el)
{
	var postdata = "";
	var j = 0;
	checkboxes = document.getElementsByName('check');
	for(var i=0, n=checkboxes.length;i<n;i++)
	{
		if(checkboxes[i].checked)
		{
			if(j > 0)
			{
				postdata += "&";
			}
			postdata += "fid["+j+"]="+checkboxes[i].value;
			j++;
		}
	}
	if(j > 0)
	{
		var xhr = f_xhr();
		if (xhr)
		{
			xhr.open("post", "/zxsa.php?action=share_selected", true);
			xhr.onreadystatechange = function(e) {
				if (this.readyState == 4) {
					if(this.status == 200)
					{
						var r = JSON.parse(this.responseText);
						if(r.result)
						{
							f_popup("Error", r.status);
						}
						else
						{
							//f_popup("Link created", '<a href="link.php?id=' + r.id + '">Link</a>' + ' PIN: ' + r.pin + ' <a href="link.php?id=' + r.id + '" onclick="return f_copy0(this.href + \' PIN: ' + r.pin + '\');">Copy</a>');
							//f_popupHTML("Link created", window.location.protocol+'//'+window.location.hostname+window.location.pathname.replace('zxs.php','link.php')+'?id='+r.id+' PIN: ' + r.pin + ' <a href="link.php?id=' + r.id + '" onclick="return f_copy0(this.href + \' PIN: ' + r.pin + '\');">Copy</a>');
							f_popupHTML("Link created", 'http://'+window.location.hostname+'/link/'+r.id+'/&nbsp;&nbsp;&nbsp;&nbsp;PIN: ' + r.pin + '&nbsp;&nbsp;&nbsp;&nbsp;<a href="/link/' + r.id + '/" onclick="return f_copy0(this.href + \'    PIN: ' + r.pin + '\');">Copy</a>');
						}
					}
					else
					{
						f_popup("Error", "failure");
					}
				}
			};
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.send(postdata);
		}
	}
	else
	{
		f_popup("Error", "No selection");
	}
	return false;
}

function f_delete_selected(el)
{
	if(!confirm("Delete selected?"))
	{
		return false;
	}
	
	var postdata = "";
	var j = 0;
	var rows = [];
	checkboxes = document.getElementsByName('check');
	for(var i=0, n=checkboxes.length;i<n;i++)
	{
		if(checkboxes[i].checked)
		{
			if(j > 0)
			{
				postdata += "&";
			}
			postdata += "fid["+j+"]="+checkboxes[i].value;
			rows.push(checkboxes[i].value);
			j++;
		}
	}
	if(j > 0)
	{
		var xhr = f_xhr();
		if (xhr)
		{
			xhr.open("post", "/zxsa.php?action=delete_selected", true);
			xhr.onreadystatechange = function(e) {
				if (this.readyState == 4) {
					if(this.status == 200)
					{
						var r = JSON.parse(this.responseText);
						if(r.result)
						{
							f_popup("Error", r.status);
						}
						else
						{
							for(var i=0, n=rows.length;i<n;i++)
							{
								var row = gi("row"+rows[i]);
								row.parentNode.removeChild(row);
							}
						}
					}
					else
					{
						f_popup("Error", "Delete failed");
					}
				}
			};
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.send(postdata);
		}
	}
	else
	{
		f_popup("Error", "No selection");
	}
	return false;
}

function f_mkdir_event(el, id, event)
{
	if(event == 13)
	{
		if(el.value.length != 0)
		{
			el.onblur = null;
			var xhr = f_xhr();
			if(xhr)
			{
				xhr.open("post", "/zxsa.php?action=mkdir&id="+id, true);
				xhr.onreadystatechange = function(e) {
					if(this.readyState == 4) {
						if(this.status == 200)
						{
							var result = JSON.parse(this.responseText);
							if(result.result)
							{
								f_popup("Error", result.status);
								var row = gi("rowmkdir");
								row.parentNode.removeChild(row);
							}
							else
							{
								var row = gi("rowmkdir");
								row.id = "row" + result.id;

								row.cells[0].innerHTML = '<input type="checkbox" name="check" value="' + result.id + '"/>'

								row.cells[1].id = "fname" + result.id;
								row.cells[1].innerHTML = '<a id="dir'+result.id+'" class="boldtext" href="/' + result.id + '/">' + escapeHtml(result.name) + '</a>';

								row.cells[2].textContent = '[DIR]';
								row.cells[2].onclick = function() { f_rename_dir(result.id); };
								row.cells[2].className = 'command';

								row.cells[3].innerHTML = '<a href="#" onclick="return f_share(' + result.id + ');">Share</a> <a href="#" onclick="return f_delete(' + result.id + ');">Delete</a>';

								row.cells[4].colspan = 3;
								row.cells[4].id = "desc" + result.id;
								row.cells[4].onclick = function() { f_desc(this, result.id); };
								row.cells[4].textContent = result.desc;
							}
						}
						else
						{
							f_popup("Error", "failure");
							var row = gi("rowmkdir");
							row.parentNode.removeChild(row);
						}
					}
				};
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				xhr.send("name="+encodeURIComponent(el.value));
			}
		}
	}
	else if(event == 27)
	{
		el.onblur = null;
		var row = gi("rowmkdir");
		row.parentNode.removeChild(row);
	}
}

function f_mkdir(id)
{
	if(!gi('gettext'))
	{
		var val = "New folder";
		var table = gi("table");
		var row = table.insertRow(-1);
		row.id = "rowmkdir";
		var cell = row.insertCell(0);

		cell = row.insertCell(1);
		cell.innerHTML = '<input id="gettext" type="text" style="width: 98%" value="'+val+'" onblur="f_mkdir_event(this, '+id+', 13);" onkeydown="f_mkdir_event(this, '+id+', event.keyCode);"/>';
		cell = row.insertCell(2);
		cell.textContent = '[DIR]';
		cell = row.insertCell(3);
		cell = row.insertCell(4);
		cell.colspan = 3;

		gi("gettext").setSelectionRange(0, val.length);
		gi("gettext").focus();
	}
	return false;
}

function f_expand(self, id, pid)
{
	var el = gi('expand'+id+'_'+pid);
	if(el)
	{
		el.parentNode.removeChild(el);
	}
	else
	{
		var xhr = f_xhr();
		if(xhr)
		{
			xhr.open("get", "/zxsa.php?action=expand&id="+id+"&pid="+pid, true);
			xhr.onreadystatechange = function(e) {
				if(this.readyState == 4) {
					if(this.status == 200)
					{
						var result = JSON.parse(this.responseText);
						if(result.result)
						{
							f_popup("Error", result.status);
						}
						else
						{
							var text = '<ul>';
							for(var i = 0; i < result.list.length; i++)
							{
								if(result.list[i].type)
								{
									text += '<li class="command" onclick="return f_expand(this, '+id+', '+result.list[i].id+');"><b>'+escapeHtml(result.list[i].name)+'</b></li>';
								}
								else
								{
									text += '<li>'+escapeHtml(result.list[i].name)+' (<a href="?action=download&id='+result.list[i].id+'">'+formatbytes(result.list[i].size, 2)+'</a>)</li>';
								}
							}
							text += '</ul>';
							var div = document.createElement('div');
							div.id = 'expand' + id + '_' + pid;
							div.className = 'expand-list';
							div.innerHTML = text;
							//gi("row"+id).cells[0].appendChild(div);
							//self.appendChild(div);
							self.parentNode.insertBefore(div, self.nextSibling);
						}
					}
					else
					{
						f_popup("Error", "failure");
					}
				}
			};
			xhr.send(null);
		}
	}
	return false;
}

function f_upload0(uid, id, file, k)
{
	var xhr = f_xhr();

	if(xhr)
	{
		xhr.open("POST", "/cgi-bin/upload", true);
		xhr.onreadystatechange =  function(j) {
			return function(e) {
			if(this.readyState == 4) {
				if(this.status == 200)
				{
					var result = JSON.parse(this.responseText);
					if(result.result)
					{
						gi("desc" + j).textContent =  result.status;
						gi("button" + j).textContent = "";
						//var row = gi("upload"+j);
						//row.parentNode.removeChild(row);
						xhttp[j] = null;
					}
					else
					{
						var row = gi("upload" + j);
						row.id = "row" + result.id;

						row.cells[0].innerHTML = '<input type="checkbox" name="check" value="' + result.id + '"/>'

						row.cells[1].id = "fname" + result.id;
						row.cells[1].onclick = function() { f_rename(this, result.id); };
						row.cells[1].textContent = result.name;
						row.cells[1].className = 'command';

						row.cells[2].innerHTML = '<a href="?action=download&id=' + result.id + '">' + formatbytes(result.size) + '</a>';

						row.cells[3].innerHTML = '<a href="#" onclick="return f_share(' + result.id + ');">Share</a> <a href="#" onclick="return f_delete(' + result.id + ');">Delete</a>';

						row.cells[4].id = "desc" + result.id;
						row.cells[4].onclick = function() { f_desc(this, result.id); };
						row.cells[4].textContent = result.desc;
						row.cells[4].className = 'command';

						row.cells[5].textContent = result.date;

						row.cells[6].innerHTML = '<span id="expire'+result.id+'">'+escapeHtml(result.expire)+'</span>';
						row.cells[6].onclick = function() { f_expire_cal(this, result.id); };
						row.cells[6].className = 'command';
						
						xhttp[j] = null;
					}
				}
				else
				{
					gi("desc" + j).textContent = "Upload failed (code: " + this.status + ")";
					gi("button" + j).textContent = "";
					//var row = gi("upload" + j);
					//row.parentNode.removeChild(row);
					xhttp[j] = null;
				}
			}
		}}(k);
		xhr.upload.onprogress = function(j) {
			return function(event) {
				var pr = parseInt((100*event.loaded)/event.total, 10);
				if(pr >= 100) pr = 99;
				gi("bar"+j).style.width = pr + '%';
				gi("percent"+j).textContent = pr + '%';
				gi("size"+j).textContent = formatbytes(event.loaded, 3) + '/' + formatbytes(event.total, 3);
			};
		}(k);

		xhttp[k] = xhr;
		var table = gi("table");
		var row = table.insertRow(-1);
		row.id = "upload"+k;
		var cell = row.insertCell(0);
		cell = row.insertCell(1);
		cell.textContent = file.name;
		cell = row.insertCell(2);
		cell.id = "size" + k;
		cell.textContent = '0/0';
		cell = row.insertCell(3);
		cell.id = "button" + k;
		cell.innerHTML = '<a href="#" onclick="xhttp[' + k + '].abort(); return false;">Cancel</a>';
		cell = row.insertCell(4);
		cell.id = "desc" + k;
		cell.innerHTML = '<div class="progress"><div id="bar' + k + '" class="bar"></div><div id="percent' + k + '" class="percent">0%</div></div>';
		cell = row.insertCell(5);
		cell = row.insertCell(6);

		xhr.setRequestHeader('Content-Type', 'application/octet-stream');
		xhr.setRequestHeader("X-Upload-FileName", encodeURIComponent(file.name));
		xhr.setRequestHeader("X-Upload-UID", uid);
		xhr.setRequestHeader("X-Upload-PID", id);
		xhr.send(file);
	}
}

function f_upload(uid, id, file, k, file_pos, fid)
{
	var part_size = 33554432;
	var xhr = f_xhr();

	if(xhr)
	{
		xhr.open("POST", "/cgi-bin/upload", true);
		xhr.onreadystatechange =  function(uidd, idd, f, j, fp) {
			return function(e) {
			if(this.readyState == 4) {
				if(this.status == 200)
				{
					//alert(this.responseText);
					var result = JSON.parse(this.responseText);
					if(result.result)
					{
						gi("desc" + j).textContent =  result.status;
						gi("button" + j).textContent = "";
						//var row = gi("upload"+j);
						//row.parentNode.removeChild(row);
						xhttp[j] = null;
					}
					else
					{
						fp += part_size;
						if((f.size > 268435456) && (fp < f.size))
						{
							f_upload(uidd, idd, f, j, fp, result.id);
						}
						else
						{
							var row = gi("upload" + j);
							row.id = "row" + result.id;

							row.cells[0].innerHTML = '<input type="checkbox" name="check" value="' + result.id + '"/>'

							row.cells[1].id = "fname" + result.id;
							row.cells[1].onclick = function() { f_rename(this, result.id); };
							row.cells[1].textContent = result.name;
							row.cells[1].className = 'command';

							row.cells[2].innerHTML = '<a href="?action=download&id=' + result.id + '">' + formatbytes(result.size, 2) + '</a>';

							row.cells[3].innerHTML = '<a href="#" onclick="return f_share(' + result.id + ');">Share</a> <a href="#" onclick="return f_delete(' + result.id + ');">Delete</a>';

							row.cells[4].id = "desc" + result.id;
							row.cells[4].onclick = function() { f_desc(this, result.id); };
							row.cells[4].textContent = result.desc;
							row.cells[4].className = 'command';

							row.cells[5].textContent = result.date;

							row.cells[6].innerHTML = '<span id="expire'+result.id+'">'+escapeHtml(result.expire)+'</span>';
							row.cells[6].onclick = function() { f_expire_cal(this, result.id); };
							row.cells[6].className = 'command';
							xhttp[j] = null;
						}
					}
				}
				else
				{
					gi("desc" + j).textContent = "Upload failed (code: " + this.status + ")";
					gi("button" + j).textContent = "";
					//var row = gi("upload" + j);
					//row.parentNode.removeChild(row);
					xhttp[j] = null;
				}
			}
		}}(uid, id, file, k, file_pos);
		xhr.upload.onprogress = function(j, fp, fs) {
			return function(event) {
				var pr = parseInt((100*(fp+event.loaded))/fs, 10);
				if(pr >= 100) pr = 99;
				gi("bar"+j).style.width = pr + '%';
				gi("percent"+j).textContent = pr + '%';
				gi("size"+j).textContent = formatbytes(event.loaded+fp, 3) + '/' + formatbytes(fs, 2);
			};
		}(k, file_pos, file.size);

		xhttp[k] = xhr;
		if(fid == 0)
		{
			var table = gi("table");
			var row = table.insertRow(-1);
			row.id = "upload"+k;
			var cell = row.insertCell(0);
			cell = row.insertCell(1);
			cell.textContent = file.name;
			cell = row.insertCell(2);
			cell.id = "size" + k;
			cell.textContent = '0/0';
			cell = row.insertCell(3);
			cell.id = "button" + k;
			cell.innerHTML = '<a href="#" onclick="xhttp[' + k + '].abort(); return false;">Cancel</a>';
			cell = row.insertCell(4);
			cell.id = "desc" + k;
			cell.innerHTML = '<div class="progress"><div id="bar' + k + '" class="bar"></div><div id="percent' + k + '" class="percent">0%</div></div>';
			cell = row.insertCell(5);
			cell = row.insertCell(6);
		}

		xhr.setRequestHeader('Content-Type', 'application/octet-stream');
		xhr.setRequestHeader("X-Upload-FileName", encodeURIComponent(file.name));
		xhr.setRequestHeader("X-Upload-FileSize", encodeURIComponent(file.size));
		xhr.setRequestHeader("X-Upload-UID", uid);
		xhr.setRequestHeader("X-Upload-PID", id);
		xhr.setRequestHeader("X-Upload-ID", fid);

		if((fid != 0) || (file.size > 268435456))
		{
			var blob;
			var load_size;
			if(file_pos+part_size > file.size)
			{
				load_size = file.size;
			}
			else
			{
				load_size = file_pos+part_size;
			}
			if(file.slice)
			{
				blob = file.slice(file_pos, load_size);
			}
			else if(file.webkitSlice)
			{
				blob = file.webkitSlice(file_pos, load_size);
			}
			else if(file.mozSlice)
			{
				blob = file.mozSlice(file_pos, load_size);
			}

			xhr.send(blob);
		}
		else
		{
			xhr.send(file);
		}
	}
}

function pad(num, size)
{
    var s = num+"";
    while (s.length < size) s = "0" + s;
    return s;
}

function dpm(m, y)
{
	return ((m === 2) && (((y % 4 === 0) && (y % 100 !== 0)) || (y % 400 === 0))) ? 29 : [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][m];
}

function f_expire_incday(val, del)
{
	var date = val.split('.');
	var d = parseInt(date[0], 10);
	var m = parseInt(date[1], 10);
	var y = parseInt(date[2], 10);

	while((del+d) > dpm(m, y))
	{
		del -= dpm(m, y) - d +1;
		d = 1;
		m++;
		if(m > 12)
		{
			m = 1;
			y++;
		}
	}

	if(del > 0)
	{
		d += del;
	}

	return pad(d, 2)+'.'+pad(m, 2)+'.'+pad(y, 4);
}

function f_expire_incmonth(val, del)
{
	var date = val.split('.');
	var d = parseInt(date[0], 10);
	var m = parseInt(date[1], 10);
	var y = parseInt(date[2], 10);

	y += ((m+del-1)/12) >> 0;
	m = ((m+del)%12)?((m+del)%12):12;
	if(d > dpm(m, y))
	{
		d = dpm(m, y);
	}
	
	return pad(d, 2)+'.'+pad(m, 2)+'.'+pad(y, 4);
}

function f_expire(id, date)
{
	var xhr = f_xhr();
	if (xhr)
	{
		xhr.open("post", "/zxsa.php?action=expire&id="+id, true);
		xhr.onreadystatechange = function(e) {
			if(this.readyState == 4) {
				if(this.status == 200)
				{
					var result = JSON.parse(this.responseText);
					if(result.result)
					{
						f_popup("Error", result.status);
					}
					else
					{
						gi("expire"+id).textContent = result.date;
					}
				}
				else
				{
					f_popup("Error", "failure");
				}
			}
		};
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		xhr.send("date="+encodeURIComponent(date));
	}
}


var wrapperElement = null;
var parentElement;
var str_weekd = ['Fuck', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
var str_month = ['Fuck', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    documentClick = function (event) {
        var parent;
        if (event.target !== parentElement && event.target !== wrapperElement) {
            parent = event.target.parentNode;
              while (parent !== wrapperElement && parent !== parentElement) {
                  parent = parent.parentNode;
                  if (parent === null) {
					wrapperElement.style.display = 'none';
					wrapperElement.parentNode.removeChild(wrapperElement);
					document.removeEventListener('click', documentClick, false);
					wrapperElement = null;
                      break;
                  }
                
            }
        }
    };

    calendarClick = function (event) {
        var target = event.target;
        var targetClass = target.className;
        var currentTimestamp;

		var id = wrapperElement.calendar_data.id;
		var val = gi('expire' + id).textContent;
		var close = 0;

		var dd = new Date();
		if(val.length == 0)
		{
			val = pad(dd.getDate(), 2)+'.'+pad(dd.getMonth()+1, 2)+'.'+pad(dd.getFullYear(), 4);
		}
		
        if(target.id === 'cmd1')
		{
			close = 1;
			f_expire(id, f_expire_incday(val, 7));
		}
        else if(target.id === 'cmd2')
		{
			close = 1;
			f_expire(id, f_expire_incmonth(val, 1));
		}
        else if(target.id === 'cmd3')
		{
			close = 1;
			f_expire(id, f_expire_incmonth(val, 3));
		}
        else if(target.id === 'cmd4')
		{
			close = 1;
			f_expire(id, f_expire_incday(pad(dd.getDate(), 2)+'.'+pad(dd.getMonth()+1, 2)+'.'+pad(dd.getFullYear(), 4), 7));
		}
        else if(target.id === 'cmd5')
		{
			close = 1;
			f_expire(id, f_expire_incmonth(pad(dd.getDate(), 2)+'.'+pad(dd.getMonth()+1, 2)+'.'+pad(dd.getFullYear(), 4), 1));
		}
        else if(target.id === 'cmd6')
		{
			close = 1;
			f_expire(id, '');
		}
        else if(target.id === 'cmd_next')
		{
			wrapperElement.calendar_data.month++;
			if(wrapperElement.calendar_data.month > 12)
			{
				wrapperElement.calendar_data.month = 1;
				wrapperElement.calendar_data.year++;
			}
			gi('cal_month').textContent = str_month[wrapperElement.calendar_data.month] + ' ' + wrapperElement.calendar_data.year;
			var cal = gi('calendar_select');
			var div = cal.parentNode;
			div.removeChild(cal);
			f_calendar_display(div, wrapperElement.calendar_data.month, wrapperElement.calendar_data.year);
		}
        else if(target.id === 'cmd_prev')
		{
			wrapperElement.calendar_data.month--;
			if(wrapperElement.calendar_data.month < 1)
			{
				wrapperElement.calendar_data.month = 12;
				wrapperElement.calendar_data.year--;
			}
			gi('cal_month').textContent = str_month[wrapperElement.calendar_data.month] + ' ' + wrapperElement.calendar_data.year;
			var cal = gi('calendar_select');
			var div = cal.parentNode;
			div.removeChild(cal);
			f_calendar_display(div, wrapperElement.calendar_data.month, wrapperElement.calendar_data.year);
		}
		else if(target.classList.contains('cal_day'))
		{
			close = 1;
			f_expire(id, pad(parseInt(target.textContent, 10), 2)+'.'+pad(wrapperElement.calendar_data.month, 2)+'.'+pad(wrapperElement.calendar_data.year, 4));
		}
		
		if(close)
		{
			wrapperElement.style.display = 'none';
			wrapperElement.parentNode.removeChild(wrapperElement);
			document.removeEventListener('click', documentClick, false);
			wrapperElement = null;
		}
    };

function f_expire_cal(el, id)
{
	if(wrapperElement) return false;
	parentElement = el;
	wrapperElement = document.createElement('div');
	wrapperElement.my_id =  id;
	wrapperElement.calendar_data =  {id: id, month: 0, year: 0};
	var div = document.createElement('div');
	wrapperElement.className = 'datepickr-wrapper';
	wrapperElement.style.display = 'block';
	div.className = 'datepickr-calendar';

	//el.parentNode.insertBefore(wrapperElement, el);
	//wrapperElement.appendChild(el);
	//wrapperElement.appendChild(div);

	el.appendChild(wrapperElement);
	wrapperElement.appendChild(div);

	var val;
	if(el.textContent.length == 0)
	{
		var currentDate = new Date();
		wrapperElement.calendar_data.month = currentDate.getMonth();
		wrapperElement.calendar_data.year = currentDate.getFullYear();
	}
	else
	{
		val = el.textContent.split('.');
		wrapperElement.calendar_data.month = parseInt(val[1], 10);
		wrapperElement.calendar_data.year = parseInt(val[2], 10);
	}

	var table, row, col;

	list = document.createElement('div');
	list.id = 'cmd1';
	list.className = 'list-item';
	list.textContent = '+1 week';
	div.appendChild(list);

	list = document.createElement('div');
	list.id = 'cmd2';
	list.className = 'list-item';
	list.textContent = '+1 month';
	div.appendChild(list);

	list = document.createElement('div');
	list.id = 'cmd3';
	list.className = 'list-item';
	list.textContent = '+3 months';
	div.appendChild(list);

	list = document.createElement('div');
	list.id = 'cmd4';
	list.className = 'list-item';
	list.textContent = 'a week later';
	div.appendChild(list);

	list = document.createElement('div');
	list.id = 'cmd5';
	list.className = 'list-item';
	list.textContent = 'a month later';
	div.appendChild(list);

	list = document.createElement('div');
	list.id = 'cmd6';
	list.className = 'list-item';
	list.textContent = 'in perpetuity';
	div.appendChild(list);

	row = document.createElement('div');
	row.className = 'list-row';
	div.appendChild(row);

	list = document.createElement('span');
	list.id = 'cmd_prev';
	list.className = 'month-select f-left';
	list.textContent = '<';
	row.appendChild(list);

	list = document.createElement('span');
	list.id = 'cal_month';
	list.textContent = str_month[wrapperElement.calendar_data.month] + ' ' + wrapperElement.calendar_data.year;
	row.appendChild(list);

	list = document.createElement('span');
	list.id = 'cmd_next';
	list.className = 'month-select f-right';
	list.textContent = '>';
	row.appendChild(list);

	f_calendar_display(div, wrapperElement.calendar_data.month, wrapperElement.calendar_data.year);
	
	div.style.display = 'block';
	
	document.addEventListener('click', documentClick, false);
	div.addEventListener('mousedown', calendarClick, false);

	return false;
}

function f_calendar_display(div, month, year)
{
	var table, tr, td, tbody, list;
	var dim = dpm(month, year);
	
	var firstDay = new Date(year, month -1, 1).getDay();
	if(firstDay == 0) firstDay = 7;
	
	table = document.createElement('table');
	table.id = 'calendar_select';
	tbody = document.createElement('thead');
	table.appendChild(tbody);

	tr = document.createElement('tr');
	tbody.appendChild(tr);
	var i, n;
	for(i = 1, n = 7; i <= n; i++)
	{
		td = document.createElement('th');
		td.textContent = str_weekd[i];
		tr.appendChild(td);
	}

	tbody = document.createElement('tbody');
	tr = document.createElement('tr');
	table.appendChild(tbody);
	tbody.appendChild(tr);
	var k = 1;
	for(i = 1; i < firstDay; i++, k++)
	{
		td = document.createElement('td');
		tr.appendChild(td);
	}
	for(i = 1; i <= dim; i++, k++)
	{
		if(k > 7)
		{
			k=1;
			tr = document.createElement('tr');
			tbody.appendChild(tr);
		}
		td = document.createElement('td');
		td.className = 'cal_day' + ((k >= 6)?' cal_weekend':'');
		td.textContent = i;
		tr.appendChild(td);
	}

	for(; k <= 7; k++)
	{
		td = document.createElement('td');
		tr.appendChild(td);
	}
	
	div.appendChild(table);
}

function DragLeave(e)
{
	gi('dropzone').className = "";
}

function DragOver(e)
{
	e.stopPropagation();
	e.preventDefault();
	gi('dropzone').className = (e.type == "dragover" ? "hover" : "");
}

function FileDrop(e, uid, id)
{
	DragOver(e);

	var files = e.target.files || e.dataTransfer.files;

	if (typeof files === 'undefined')
		return;
		
	e.stopPropagation();
	e.preventDefault();
	
	for(var i=0, n=files.length;i<n;i++)
	{
		f_upload(uid, id, files[i], k++, 0, 0);
	}
}

function zxs_init(uid, id)
{
	gi("upload").onchange = function(uidd, idd) {
		return function(event) {
			var files = event.target.files;
			for(var i=0, n=files.length;i<n;i++)
			{
				f_upload(uidd, idd, files[i], k++, 0, 0);
			}
			return false;
		}
	} (uid, id);

	window.onbeforeunload = function (e) {
		for(var i=0, n=xhttp.length;i<n;i++)
		{
			if(xhttp[i])
			{
				var message = "Your have active uploads. Are you sure want to leave the page and terminate all uploads?";
				f_popup('Confirm exit', message);

				if (typeof e == "undefined")
				{
					e = window.event;
				}
				if (e)
				{
					e.returnValue = message;
				}
				return message;
			}
		}
	};

	var filedrag = document.getElementsByTagName('body')[0];

	filedrag.addEventListener("dragover", DragOver, false);
	filedrag.addEventListener("dragleave", DragLeave, false);
	filedrag.addEventListener("drop", function(event) { FileDrop(event, uid, id); }, false);
}