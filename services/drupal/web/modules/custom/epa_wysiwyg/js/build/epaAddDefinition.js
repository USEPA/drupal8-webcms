!function(e,t){"object"==typeof exports&&"object"==typeof module?module.exports=t():"function"==typeof define&&define.amd?define([],t):"object"==typeof exports?exports.CKEditor5=t():(e.CKEditor5=e.CKEditor5||{},e.CKEditor5.epaAddDefinition=t())}(self,(()=>(()=>{var e={"./node_modules/css-loader/dist/cjs.js!./js/ckeditor5_plugins/epaAddDefinition/src/dialog.css":(e,t,n)=>{"use strict";n.d(t,{Z:()=>c});var s=n("./node_modules/css-loader/dist/runtime/noSourceMaps.js"),i=n.n(s),r=n("./node_modules/css-loader/dist/runtime/api.js"),o=n.n(r)()(i());o.push([e.id,".epa-add-def {\n  max-width: 90vw;\n\n  form {\n    max-width: 100%;\n\n    > div select {\n      max-width: 100%;\n    }\n  }\n}\n\n@media screen and (min-width: 800px) {\n  .epa-add-def {\n    max-width: 800px;\n  }\n}\n",""]);const c=o},"./node_modules/css-loader/dist/runtime/api.js":e=>{"use strict";e.exports=function(e){var t=[];return t.toString=function(){return this.map((function(t){var n="",s=void 0!==t[5];return t[4]&&(n+="@supports (".concat(t[4],") {")),t[2]&&(n+="@media ".concat(t[2]," {")),s&&(n+="@layer".concat(t[5].length>0?" ".concat(t[5]):""," {")),n+=e(t),s&&(n+="}"),t[2]&&(n+="}"),t[4]&&(n+="}"),n})).join("")},t.i=function(e,n,s,i,r){"string"==typeof e&&(e=[[null,e,void 0]]);var o={};if(s)for(var c=0;c<this.length;c++){var a=this[c][0];null!=a&&(o[a]=!0)}for(var d=0;d<e.length;d++){var l=[].concat(e[d]);s&&o[l[0]]||(void 0!==r&&(void 0===l[5]||(l[1]="@layer".concat(l[5].length>0?" ".concat(l[5]):""," {").concat(l[1],"}")),l[5]=r),n&&(l[2]?(l[1]="@media ".concat(l[2]," {").concat(l[1],"}"),l[2]=n):l[2]=n),i&&(l[4]?(l[1]="@supports (".concat(l[4],") {").concat(l[1],"}"),l[4]=i):l[4]="".concat(i)),t.push(l))}},t}},"./node_modules/css-loader/dist/runtime/noSourceMaps.js":e=>{"use strict";e.exports=function(e){return e[1]}},"./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js":e=>{"use strict";var t=[];function n(e){for(var n=-1,s=0;s<t.length;s++)if(t[s].identifier===e){n=s;break}return n}function s(e,s){for(var r={},o=[],c=0;c<e.length;c++){var a=e[c],d=s.base?a[0]+s.base:a[0],l=r[d]||0,u="".concat(d," ").concat(l);r[d]=l+1;var m=n(u),f={css:a[1],media:a[2],sourceMap:a[3],supports:a[4],layer:a[5]};if(-1!==m)t[m].references++,t[m].updater(f);else{var h=i(f,s);s.byIndex=c,t.splice(c,0,{identifier:u,updater:h,references:1})}o.push(u)}return o}function i(e,t){var n=t.domAPI(t);n.update(e);return function(t){if(t){if(t.css===e.css&&t.media===e.media&&t.sourceMap===e.sourceMap&&t.supports===e.supports&&t.layer===e.layer)return;n.update(e=t)}else n.remove()}}e.exports=function(e,i){var r=s(e=e||[],i=i||{});return function(e){e=e||[];for(var o=0;o<r.length;o++){var c=n(r[o]);t[c].references--}for(var a=s(e,i),d=0;d<r.length;d++){var l=n(r[d]);0===t[l].references&&(t[l].updater(),t.splice(l,1))}r=a}}},"./node_modules/style-loader/dist/runtime/insertBySelector.js":e=>{"use strict";var t={};e.exports=function(e,n){var s=function(e){if(void 0===t[e]){var n=document.querySelector(e);if(window.HTMLIFrameElement&&n instanceof window.HTMLIFrameElement)try{n=n.contentDocument.head}catch(e){n=null}t[e]=n}return t[e]}(e);if(!s)throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");s.appendChild(n)}},"./node_modules/style-loader/dist/runtime/insertStyleElement.js":e=>{"use strict";e.exports=function(e){var t=document.createElement("style");return e.setAttributes(t,e.attributes),e.insert(t,e.options),t}},"./node_modules/style-loader/dist/runtime/setAttributesWithAttributesAndNonce.js":e=>{"use strict";e.exports=function(e,t){Object.keys(t).forEach((function(n){e.setAttribute(n,t[n])}))}},"./node_modules/style-loader/dist/runtime/singletonStyleDomAPI.js":e=>{"use strict";var t,n=(t=[],function(e,n){return t[e]=n,t.filter(Boolean).join("\n")});function s(e,t,s,i){var r;if(s)r="";else{r="",i.supports&&(r+="@supports (".concat(i.supports,") {")),i.media&&(r+="@media ".concat(i.media," {"));var o=void 0!==i.layer;o&&(r+="@layer".concat(i.layer.length>0?" ".concat(i.layer):""," {")),r+=i.css,o&&(r+="}"),i.media&&(r+="}"),i.supports&&(r+="}")}if(e.styleSheet)e.styleSheet.cssText=n(t,r);else{var c=document.createTextNode(r),a=e.childNodes;a[t]&&e.removeChild(a[t]),a.length?e.insertBefore(c,a[t]):e.appendChild(c)}}var i={singleton:null,singletonCounter:0};e.exports=function(e){if("undefined"==typeof document)return{update:function(){},remove:function(){}};var t=i.singletonCounter++,n=i.singleton||(i.singleton=e.insertStyleElement(e));return{update:function(e){s(n,t,!1,e)},remove:function(e){s(n,t,!0,e)}}}},"ckeditor5/src/core.js":(e,t,n)=>{e.exports=n("dll-reference CKEditor5.dll")("./src/core.js")},"ckeditor5/src/ui.js":(e,t,n)=>{e.exports=n("dll-reference CKEditor5.dll")("./src/ui.js")},"ckeditor5/src/widget.js":(e,t,n)=>{e.exports=n("dll-reference CKEditor5.dll")("./src/widget.js")},"dll-reference CKEditor5.dll":e=>{"use strict";e.exports=CKEditor5.dll}},t={};function n(s){var i=t[s];if(void 0!==i)return i.exports;var r=t[s]={id:s,exports:{}};return e[s](r,r.exports,n),r.exports}n.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return n.d(t,{a:t}),t},n.d=(e,t)=>{for(var s in t)n.o(t,s)&&!n.o(e,s)&&Object.defineProperty(e,s,{enumerable:!0,get:t[s]})},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t);var s={};return(()=>{"use strict";n.d(s,{default:()=>N});var e=n("ckeditor5/src/core.js"),t=n("ckeditor5/src/widget.js");const i="definition js-definition",r="definition__trigger js-definition__trigger",o="definition__tooltip js-definition__tooltip",c="definition__term",a="epaDefinition",d="term",l="definition";class u extends e.Plugin{static get requires(){return[t.Widget]}init(){this._defineSchema(),this._defineConverters()}_defineSchema(){this.editor.model.schema.register(a,{inheritAllFrom:"$inlineObject",allowAttributes:[d,l]})}_defineConverters(){const e=this.editor.conversion;e.for("editingDowncast").elementToElement({model:a,view:(e,n)=>{const{writer:s}=n,i=e.getAttribute(d)||"",r=e.getAttribute(l)||"",o=s.createContainerElement("dfn",{title:r},[s.createText(i)]);return(0,t.toWidget)(o,s,{label:"term definition display"})}}),e.for("dataDowncast").elementToStructure({model:a,view:(e,{writer:t})=>{const n=e.getAttribute(d)||"",s=e.getAttribute(l)||"",a=t.createContainerElement.bind(t);return a("span",{class:i},[a("button",{class:r},[t.createText(n)]),a("span",{class:o,role:"tooltip"},[a("dfn",{class:c},[t.createText(n)]),t.createText(s)])])}}),e.for("upcast").add((e=>{e.on("element:span",((e,t,n)=>{const{viewItem:s}=t,{consumable:u,writer:m,safeInsert:f,updateConversionResult:h}=n,p={name:!0,classes:i.split(" ")};if(!u.test(s,p))return;if(2!==s.childCount)return;const g=s.getChild(0),v={name:!0,classes:r.split(" ")};if(!(g.is("element","button")&&u.test(g,v)))return;const w=s.getChild(1),y={name:!0,classes:o.split(" "),attributes:["role"]};if(!(w.is("element","span")&&u.test(w,y)))return;if(2!==w.childCount)return;const x=w.getChild(0),b={name:!0,classes:c.split(" ")};if(!(x.is("element","dfn")&&u.test(x,b)))return;if(1!==x.childCount)return;const j=x.getChild(0);if(!j.is("$text"))return;const _=w.getChild(1);if(!_.is("$text"))return;const C=m.createElement(a,{[d]:j.data,[l]:_.data});f(C,t.modelCursor)&&(u.consume(s,p),u.consume(g,v),u.consume(w,y),u.consume(x,b),h(C,t))}))}))}}var m=n("ckeditor5/src/ui.js");class f extends Error{constructor(e,...t){super("Unexpected non-text node",...t),this._userError="Cannot add definitions across paragraphs"}}class h extends Error{constructor(...e){super(...e),this._userError="No term definitions were found that exactly match your selected word or phrase"}}class p extends Error{constructor(...e){super(...e),this._userError="Network error while looking up definitions"}}const g=async function(e){const t=new URLSearchParams;t.set("text",e);const n=await fetch("https://termlookup.epa.gov/termlookup/v1/terms",{method:"POST",body:t,headers:{"Content-Type":"application/x-www-form-urlencoded"}});if(!n.ok){const e=new p(`Failed to look up terms: ${n.status} ${n.statusText}`);try{const t=await n.text();Object.assign(e,{responseText:t})}catch(e){}throw e}return n.json()};let v=0;const w=function(){return"epa-add-def-"+v++};function y(e,t,...n){const s=document.createElement(e);for(const[e,n]of Object.entries(t))s.setAttribute(e,n);for(const e of n){const t="string"==typeof e?document.createTextNode(e):e;s.appendChild(t)}return s}class x extends m.View{constructor(e){super(e),this.set("term",""),this.set("definitions",[]),this.set("selected","");const t=this.bindTemplate;this.selectId=w(),this.select=y("select",{id:this.selectId}),this.setTemplate({tag:"div",attributes:{class:["epa-add-def__match"]},children:[{tag:"label",attributes:{for:this.selectId},children:[{text:"Term: "},{text:t.to("term")}]},this.select],on:{[`change@select#${this.selectId}`]:t.to((()=>{const e=this.select.selectedIndex,t=0===e?"":this.definitions[e-1].definition;console.log("setting selection to",t),this.selected=t}))}}),this.on("change:definitions",((e,t,n)=>{const s=this.select.options;s.length=0,s.add(y("option",{value:""},""));for(const e of n){const t=`${e.definition} (${e.dictionary})`;s.add(y("option",{value:e.definition},t))}}))}}const b=function(e,t,n,s){const i=e.length,r=t.length,o=Math.min(i,r);for(let n=0;n<o;n++)s(t.get(n),e[n]);if(i>r){const i=e.slice(o).map((e=>{const t=n();return s(t,e),t}));t.addMany(i)}else if(i<r){const e=t.filter(((e,t)=>t>=o)).reverse();for(const n of e)t.remove(n)}};class j extends m.View{constructor(e){super(e),this.set("matches",[]),this.views=this.createCollection(),this.setTemplate({tag:"div",children:this.views}),this.on("change:matches",((e,t,n)=>{b(n,this.views,(()=>new x(this.locale)),((e,t)=>{e.term=t.term,e.definitions=t.definitions}))}))}}var _=n("./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js"),C=n.n(_),E=n("./node_modules/style-loader/dist/runtime/singletonStyleDomAPI.js"),A=n.n(E),S=n("./node_modules/style-loader/dist/runtime/insertBySelector.js"),T=n.n(S),k=n("./node_modules/style-loader/dist/runtime/setAttributesWithAttributesAndNonce.js"),I=n.n(k),B=n("./node_modules/style-loader/dist/runtime/insertStyleElement.js"),D=n.n(B),M=n("./node_modules/css-loader/dist/cjs.js!./js/ckeditor5_plugins/epaAddDefinition/src/dialog.css"),P={attributes:{"data-cke":!0}};P.setAttributes=I(),P.insert=T().bind(null,"head"),P.domAPI=A(),P.insertStyleElement=D();C()(M.Z,P);M.Z&&M.Z.locals&&M.Z.locals;class O extends m.View{constructor(t){super(t),this.set("data",[]),this.listView=new j(t),this.listView.bind("matches").to(this,"data"),this.submitButton=this._createButton("Save",e.icons.check,"ck-button-save"),this.submitButton.type="submit",this.cancelButton=this._createButton("Cancel",e.icons.cancel,"ck-button-cancel"),this.cancelButton.delegate("execute").to(this,"cancel");const n=this.bindTemplate;this.setTemplate({tag:"dialog",on:{close:n.to("cancel")},attributes:{class:"epa-add-def"},children:[{tag:"form",children:[this.listView,this.submitButton,this.cancelButton]}]})}render(){super.render(),(0,m.submitHandler)({view:this})}show(){this.element.showModal()}hide(){this.element.close()}_createButton(e,t,n){const s=new m.ButtonView(this.locale);return s.set({label:e,icon:t,tooltip:!0,class:n}),s}}const $="epaAddDefinitions";class R extends e.Plugin{static get requires(){return[m.Notification]}static get pluginName(){return"EpaAddDefinitionUI"}init(){const e=this.editor;this.modalView=new O(this.editor.locale),e.ui.componentFactory.add("epaAddDefinition",(t=>{const n=new m.ButtonView(t);return n.set({label:e.t("Add Definition"),icon:'<svg viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="m96 0c-53 0-96 43-96 96v320c0 53 43 96 96 96h288 32c17.7 0 32-14.3 32-32s-14.3-32-32-32v-64c17.7 0 32-14.3 32-32v-320c0-17.7-14.3-32-32-32h-32zm0 384h256v64h-256c-17.7 0-32-14.3-32-32s14.3-32 32-32zm32-240c0-8.8 7.2-16 16-16h192c8.8 0 16 7.2 16 16s-7.2 16-16 16h-192c-8.8 0-16-7.2-16-16zm16 48h192c8.8 0 16 7.2 16 16s-7.2 16-16 16h-192c-8.8 0-16-7.2-16-16s7.2-16 16-16z"/></svg>',tooltip:!0}),n.bind("isEnabled").to(e,"isReadOnly",(e=>!e)),this.listenTo(n,"execute",(()=>{this._execute().catch((e=>{const t=this.editor.plugins.get(m.Notification),n=e._userError||"An unexpected error occurred";t.showWarning(n,{namespace:"epa:addDefinition"}),console.error(e)}))})),n}))}async _execute(){const e=this.modalView;e&&!e.isRendered&&(e.render(),document.body.appendChild(e.element));const t=this.editor.model,n=t.document.selection,s=n.getFirstRange();if(s&&s.isCollapsed)return;const i=s&&Array.from(s.getItems()).reduce(((e,t)=>{if(t.is("$text")||t.is("$textProxy"))return e+t.data;throw new f}),"");if(i&&""===i.trim())return;const r=i.split(" "),o=[];t.change((e=>{r.forEach((s=>{const r=i.indexOf(s),c=t.createPositionAt(n.getFirstRange().start.parent,r),a=t.createPositionAt(n.getFirstRange().start.parent,r+s.length),d=t.createRange(c,a),l=e.addMarker(`${r}: ${s}`,{range:d,usingOperation:!0});o.push({term:s.replaceAll(/[?!.]+/g,""),range:d,marker:l})}))}));const c=o.map((e=>e.term)).join(", ");let a=null,d=null;try{if(!e)throw new Error("Modal not initialized");if(this.editor.enableReadOnlyMode($),c&&(d=await g(c),null===d))return;const t=d?d.matches:null;if(!t)throw new h(`Could not find a term that matches '${i}'`);e.data=t,e.show(),a=await new Promise((t=>{let n;function s(e){return()=>{n||(t(e),n=!0)}}e.on("submit",s(!0)),e.on("cancel",s(!1))}))}finally{if(!e)throw new Error("Modal not initialized");this.editor.disableReadOnlyMode($),e.hide()}if(!a)return;const l=e.listView.views._items;console.log("SelectedArray: ",l),l&&t.change((e=>{for(const t in l)if(l[t].selected){const n=o.find((e=>e.term===l[t].term)).marker.getRange();e.remove(n),e.insertElement("epaDefinition",{term:l[t].term,definition:l[t].selected},n.start)}}))}}class V extends e.Plugin{static get requires(){return[R,u]}}const N={EpaAddDefinition:V}})(),s=s.default})()));