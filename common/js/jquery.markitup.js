// ----------------------------------------------------------------------------
// markItUp! Universal MarkUp Engine, JQuery plugin
// v 1.1.x
// Dual licensed under the MIT and GPL licenses.
// ----------------------------------------------------------------------------
// Copyright (C) 2007-2012 Jay Salvat
// http://markitup.jaysalvat.com/
// ----------------------------------------------------------------------------
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
// 
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.
// ----------------------------------------------------------------------------
(function(c){c.fn.markItUp=function(s,q){var d,u,C,I,b,l,r,w;l=r=w=!1;"string"==typeof s&&(C=s,I=q);b={id:"",nameSpace:"",root:"",previewHandler:!1,previewInWindow:"",previewInElement:"",previewAutoRefresh:!0,previewPosition:"after",previewTemplatePath:"~/templates/preview.html",previewParser:!1,previewParserPath:"",previewParserVar:"data",resizeHandle:!0,beforeInsert:"",afterInsert:"",onEnter:{},onShiftEnter:{},onCtrlEnter:{},onTab:{},markupSet:[{}]};c.extend(b,s,q);b.root||c("script").each(function(l,
g){miuScript=c(g).get(0).src.match(/(.*)jquery\.markitup(\.pack)?\.js$/);null!==miuScript&&(b.root=miuScript[1])});d=navigator.userAgent;d=d.toLowerCase();u=/(chrome)[ \/]([\w.]+)/.exec(d)||/(webkit)[ \/]([\w.]+)/.exec(d)||/(opera)(?:.*version|)[ \/]([\w.]+)/.exec(d)||/(msie) ([\w.]+)/.exec(d)||0>d.indexOf("compatible")&&/(mozilla)(?:.*? rv:([\w.]+)|)/.exec(d)||[];d=u[1]||"";u=u[2]||"0";var g={};d&&(g[d]=!0,g.version=u);g.chrome?g.webkit=!0:g.webkit&&(g.safari=!0);return this.each(function(){function d(a,
c){return c?a.replace(/("|')~\//g,"$1"+b.root):a.replace(/^~\//,b.root)}function q(a){var k=c("<ul></ul>"),f=0;c("li:hover > ul",k).css("display","block");c.each(a,function(){var a=this,d="",j,g;j=a.key?(a.name||"")+" [Ctrl+"+a.key+"]":a.name||"";key=a.key?'accesskey="'+a.key+'"':"";if(a.separator)d=c('<li class="markItUpSeparator">'+(a.separator||"")+"</li>").appendTo(k);else{f++;for(g=z.length-1;0<=g;g--)d+=z[g]+"-";d=c('<li class="markItUpButton markItUpButton'+d+f+" "+(a.className||"")+'"><a href="" '+
key+' title="'+j+'">'+(a.name||"")+"</a></li>").bind("contextmenu.markItUp",function(){return!1}).bind("click.markItUp",function(a){a.preventDefault()}).bind("focusin.markItUp",function(){e.focus()}).bind("mouseup",function(){"preview"==a.call&&("function"===typeof b.previewHandler?h=!0:b.previewInElement?h=c(b.previewInElement):!h||h.closed?b.previewInWindow?(h=window.open("","preview",b.previewInWindow),c(window).unload(function(){h.close()})):(t=c('<iframe class="markItUpPreviewFrame"></iframe>'),
"after"==b.previewPosition?t.insertAfter(F):t.insertBefore(A),h=t[t.length-1].contentWindow||frame[t.length-1]):!0===w&&(t?t.remove():h.close(),h=t=!1),b.previewAutoRefresh||J(),b.previewInWindow&&h.focus());setTimeout(function(){x(a)},1);return!1}).bind("mouseenter.markItUp",function(){c("> ul",this).show();c(document).one("click",function(){c("ul ul",A).hide()})}).bind("mouseleave.markItUp",function(){c("> ul",this).hide()}).appendTo(k);a.dropMenu&&(z.push(f),c(d).addClass("markItUpDropMenu").append(q(a.dropMenu)))}});
z.pop();return k}function n(a){c.isFunction(a)&&(a=a(y));a?(a=a.toString(),a=a.replace(/\(\!\(([\s\S]*?)\)\!\)/g,function(a,c){var b=c.split("|!|");return!0===w?void 0!==b[1]?b[1]:b[0]:void 0===b[1]?"":b[0]}),a=a.replace(/\[\!\[([\s\S]*?)\]\!\]/g,function(a,b){var c=b.split(":!:");if(!0===B)return!1;value=prompt(c[0],c[1]?c[1]:"");null===value&&(B=!0);return value})):a="";return a}function s(a){var b=n(p.openWith),c=n(p.placeHolder),e=n(p.replaceWith),d=n(p.closeWith),f=n(p.openBlockWith),g=n(p.closeBlockWith),
j=p.multiline;if(""!==e)block=b+e+d;else if(""===selection&&""!==c)block=b+c+d;else{a=a||selection;var h=[a],l=[];!0===j&&(h=a.split(/\r?\n/));for(a=0;a<h.length;a++){line=h[a];var m;(m=line.match(/ *$/))?l.push(b+line.replace(/ *$/g,"")+d+m):l.push(b+line+d)}block=l.join("\n")}block=f+block+g;return{block:block,openBlockWith:f,openWith:b,replaceWith:e,placeHolder:c,closeWith:d,closeBlockWith:g}}function x(a){var k,d,v;y=p=a;D();c.extend(y,{line:"",root:b.root,textarea:f,selection:selection||"",caretPosition:j,
ctrlKey:l,shiftKey:r,altKey:w});n(b.beforeInsert);n(p.beforeInsert);(!0===l&&!0===r||!0===a.multiline)&&n(p.beforeMultiInsert);c.extend(y,{line:1});if(!0===l&&!0===r){lines=selection.split(/\r?\n/);k=0;d=lines.length;for(v=0;v<d;v++)""!==c.trim(lines[v])?(c.extend(y,{line:++k,selection:lines[v]}),lines[v]=s(lines[v]).block):lines[v]="";string={block:lines.join("\n")};start=j;k=string.block.length+(g.opera?d-1:0)}else!0===l?(string=s(selection),start=j+string.openWith.length,k=string.block.length-
string.openWith.length-string.closeWith.length,k-=string.block.match(/ $/)?1:0,k-=G(string.block)):!0===r?(string=s(selection),start=j,k=string.block.length,k-=G(string.block)):(string=s(selection),start=j+string.block.length,k=0,start-=G(string.block));""===selection&&""===string.replaceWith&&(m+=u(string.block),start=j+string.openBlockWith.length+string.openWith.length,k=string.block.length-string.openBlockWith.length-string.openWith.length-string.closeWith.length-string.closeBlockWith.length,m=
e.val().substring(j,e.val().length).length,m-=u(e.val().substring(0,j)));c.extend(y,{caretPosition:j,scrollPosition:E});string.block!==selection&&!1===B?(d=string.block,document.selection?document.selection.createRange().text=d:f.value=f.value.substring(0,j)+d+f.value.substring(j+selection.length,f.value.length),K(start,k)):m=-1;D();c.extend(y,{line:"",selection:selection});(!0===l&&!0===r||!0===a.multiline)&&n(p.afterMultiInsert);n(p.afterInsert);n(b.afterInsert);h&&b.previewAutoRefresh&&J();r=w=
l=B=!1}function u(a){return g.opera?a.length-a.replace(/\n*/g,"").length:0}function G(a){return g.msie?a.length-a.replace(/\r*/g,"").length:0}function K(a,b){if(f.createTextRange){if(g.opera&&9.5<=g.version&&0==b)return!1;range=f.createTextRange();range.collapse(!0);range.moveStart("character",a);range.moveEnd("character",b);range.select()}else f.setSelectionRange&&f.setSelectionRange(a,a+b);f.scrollTop=E;f.focus()}function D(){f.focus();E=f.scrollTop;if(document.selection)if(selection=document.selection.createRange().text,
g.msie){var a=document.selection.createRange(),b=a.duplicate();b.moveToElementText(f);for(j=-1;b.inRange(a);)b.moveStart("character"),j++}else j=f.selectionStart;else j=f.selectionStart,selection=f.value.substring(j,f.selectionEnd);return selection}function J(){if(b.previewHandler&&"function"===typeof b.previewHandler)b.previewHandler(e.val());else if(b.previewParser&&"function"===typeof b.previewParser){var a=b.previewParser(e.val());H(d(a,1))}else""!==b.previewParserPath?c.ajax({type:"POST",dataType:"text",
global:!1,url:b.previewParserPath,data:b.previewParserVar+"="+encodeURIComponent(e.val()),success:function(a){H(d(a,1))}}):M||c.ajax({url:b.previewTemplatePath,dataType:"text",global:!1,success:function(a){H(d(a,1).replace(/\x3c!-- content --\x3e/g,e.val()))}});return!1}function H(a){if(b.previewInElement)c(b.previewInElement).show(),c(b.previewInElement).html(a),"function"===typeof prettyPrint&&prettyPrint();else if(h&&h.document){try{sp=h.document.documentElement.scrollTop}catch(d){sp=0}h.document.open();
h.document.write(a);h.document.close();h.document.documentElement.scrollTop=sp}}function L(a){r=a.shiftKey;w=a.altKey;l=!a.altKey||!a.ctrlKey?a.ctrlKey||a.metaKey:!1;if("keydown"===a.type){if(!0===l&&(li=c('a[accesskey="'+(13==a.keyCode?"\\n":String.fromCharCode(a.keyCode))+'"]',A).parent("li"),0!==li.length))return l=!1,setTimeout(function(){li.triggerHandler("mouseup")},1),!1;if(13===a.keyCode||10===a.keyCode){if(!0===l)return l=!1,x(b.onCtrlEnter),b.onCtrlEnter.keepDefault;if(!0===r)return r=!1,
x(b.onShiftEnter),b.onShiftEnter.keepDefault;x(b.onEnter);return b.onEnter.keepDefault}if(9===a.keyCode){if(!0==r||!0==l||!0==w)return!1;if(-1!==m)return D(),m=e.val().length-m,K(m,0),m=-1,!1;x(b.onTab);return b.onTab.keepDefault}}}var e,f,z,E,j,m,p,y,A,F,h,M,t,B;e=c(this);f=this;z=[];B=!1;E=j=0;m=-1;b.previewParserPath=d(b.previewParserPath);b.previewTemplatePath=d(b.previewTemplatePath);if(C)switch(C){case "remove":e.unbind(".markItUp").removeClass("markItUpEditor");e.parent("div").parent("div.markItUp").parent("div").replaceWith(e);
e.data("markItUp",null);break;case "insert":x(I);break;default:c.error("Method "+C+" does not exist on jQuery.markItUp")}else nameSpace=id="",b.id?id='id="'+b.id+'"':e.attr("id")&&(id='id="markItUp'+e.attr("id").substr(0,1).toUpperCase()+e.attr("id").substr(1)+'"'),b.nameSpace&&(nameSpace='class="'+b.nameSpace+'"'),e.wrap("<div "+nameSpace+"></div>"),e.wrap("<div "+id+' class="markItUp"></div>'),e.wrap('<div class="markItUpContainer"></div>'),e.addClass("markItUpEditor"),A=c('<div class="markItUpHeader"></div>').insertBefore(e),
c(q(b.markupSet)).appendTo(A),F=c('<div class="markItUpFooter"></div>').insertAfter(e),!0===b.resizeHandle&&!0!==g.safari&&(resizeHandle=c('<div class="markItUpResizeHandle"></div>').insertAfter(e).bind("mousedown.markItUp",function(a){var b=e.height(),d=a.clientY,f,g;f=function(a){e.css("height",Math.max(20,a.clientY+b-d)+"px");return!1};g=function(){c("html").unbind("mousemove.markItUp",f).unbind("mouseup.markItUp",g);return!1};c("html").bind("mousemove.markItUp",f).bind("mouseup.markItUp",g)}),
F.append(resizeHandle)),e.bind("keydown.markItUp",L).bind("keyup",L),e.bind("insertion.markItUp",function(a,b){!1!==b.target&&D();f===c.markItUp.focused&&x(b)}),e.bind("focus.markItUp",function(){c.markItUp.focused=this})})};c.fn.markItUpRemove=function(){return this.each(function(){c(this).markItUp("remove")})};c.markItUp=function(s){var q={target:!1};c.extend(q,s);if(q.target)return c(q.target).each(function(){c(this).focus();c(this).trigger("insertion",[q])});c("textarea").trigger("insertion",
[q])}})(jQuery);
