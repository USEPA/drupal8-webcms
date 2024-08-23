!function(e,n){"object"==typeof exports&&"object"==typeof module?module.exports=n():"function"==typeof define&&define.amd?define([],n):"object"==typeof exports?exports.CKEditor5=n():(e.CKEditor5=e.CKEditor5||{},e.CKEditor5.webAreaLinkit=n())}(self,(function(){return function(){var __webpack_modules__={"../../contrib/linkit/js/ckeditor5_plugins/linkit/src/autocomplete.js":function(__unused_webpack_module,__webpack_exports__,__webpack_require__){"use strict";eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": function() { return /* binding */ initializeAutocomplete; }\n/* harmony export */ });\nconst $ = jQuery;\n\n\n/**\n * Override jQuery UI _renderItem function to output HTML by default.\n *\n * @param {object} ul\n *   The <ul> element that the newly created <li> element must be appended to.\n * @param {object} item\n *  The list item to append.\n *\n * @return {object}\n *   jQuery collection of the ul element.\n */\nfunction renderItem(ul, item) {\n  var $line = $('<li>').addClass('linkit-result-line');\n  var $wrapper = $('<div>').addClass('linkit-result-line-wrapper');\n  $wrapper.append($('<span>').html(item.label).addClass('linkit-result-line--title'));\n\n  if (item.hasOwnProperty('description')) {\n    $wrapper.append($('<span>').html(item.description).addClass('linkit-result-line--description'));\n  }\n  return $line.append($wrapper).appendTo(ul);\n}\n\n/**\n * Override jQuery UI _renderMenu function to handle groups.\n *\n * @param {object} ul\n *   An empty <ul> element to use as the widget's menu.\n * @param {array} items\n *   An Array of items that match the user typed term.\n */\nfunction renderMenu(ul, items) {\n  var self = this.element.autocomplete('instance');\n\n  var grouped_items = {};\n  items.forEach(function (item) {\n    const group = item.hasOwnProperty('group') ? item.group : '';\n    if (!grouped_items.hasOwnProperty(group)) {\n      grouped_items[group] = [];\n    }\n    grouped_items[group].push(item);\n  });\n\n  $.each(grouped_items, function (group, items) {\n    if (group.length) {\n      ul.append('<li class=\"linkit-result-line--group ui-menu-divider\">' + group + '</li>');\n    }\n\n    $.each(items, function (index, item) {\n      self._renderItemData(ul, item);\n    });\n  });\n}\n\nfunction initializeAutocomplete(element, settings) {\n  const { autocompleteUrl, selectHandler, closeHandler, openHandler } = settings;\n  const autocomplete = {\n    cache: {},\n    ajax: {\n      dataType: 'json',\n      jsonp: false,\n    },\n  };\n\n  /**\n   * JQuery UI autocomplete source callback.\n   *\n   * @param {object} request\n   *   The request object.\n   * @param {function} response\n   *   The function to call with the response.\n   */\n  function sourceData(request, response) {\n    const { cache } = autocomplete;\n    /**\n     * Transforms the data object into an array and update autocomplete results.\n     *\n     * @param {object} data\n     *   The data sent back from the server.\n     */\n    function sourceCallbackHandler(data) {\n      cache[term] = data.suggestions;\n      response(data.suggestions);\n    }\n\n    // Get the desired term and construct the autocomplete URL for it.\n    var term = request.term;\n\n    // Check if the term is already cached.\n    if (cache.hasOwnProperty(term)) {\n      response(cache[term]);\n    }\n    else {\n      $.ajax(autocompleteUrl, {\n        success: sourceCallbackHandler,\n        data: {q: term},\n        ...autocomplete.ajax,\n      });\n    }\n  }\n\n  const options = {\n    appendTo: element.closest('.ck-labeled-field-view'),\n    source: sourceData,\n    select: selectHandler,\n    focus: () => false,\n    search: () => !options.isComposing,\n    close: closeHandler,\n    open: openHandler,\n    minLength: 1,\n    isComposing: false,\n  }\n  const $auto = $(element).autocomplete(options);\n\n  // Override a few things.\n  const instance = $auto.data('ui-autocomplete');\n  instance.widget().menu('option', 'items', '> :not(.linkit-result-line--group)');\n  instance._renderMenu = renderMenu;\n  instance._renderItem = renderItem;\n\n\n  $auto.autocomplete('widget').addClass('linkit-ui-autocomplete ck-reset_all-excluded');\n\n  $auto.on('click', function () {\n    $auto.autocomplete('search', $auto.val());\n  });\n\n  $auto.on('compositionstart.autocomplete', function () {\n    options.isComposing = true;\n  });\n  $auto.on('compositionend.autocomplete', function () {\n    options.isComposing = false;\n  });\n\n  return $auto;\n}\n\n\n//# sourceURL=webpack://CKEditor5.webAreaLinkit/../../contrib/linkit/js/ckeditor5_plugins/linkit/src/autocomplete.js?")},"./js/ckeditor5_plugins/webAreaLinkit/src/index.js":function(__unused_webpack_module,__webpack_exports__,__webpack_require__){"use strict";eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var ckeditor5_src_ui__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ckeditor5/src/ui */ \"ckeditor5/src/ui.js\");\n/* harmony import */ var ckeditor5_src_core__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ckeditor5/src/core */ \"ckeditor5/src/core.js\");\n/* harmony import */ var ckeditor5_src_utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ckeditor5/src/utils */ \"ckeditor5/src/utils.js\");\n/* harmony import */ var _contrib_linkit_js_ckeditor5_plugins_linkit_src_autocomplete__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../../../../contrib/linkit/js/ckeditor5_plugins/linkit/src/autocomplete */ \"../../contrib/linkit/js/ckeditor5_plugins/linkit/src/autocomplete.js\");\n\n\n\n\n\n/**\n * This plugin provides a dropdown that lets users select between different\n * linkit matchers. We have two linkit matchers currently; the default which\n * allows filtering to all content and  the second one filtering results to only\n * nodes in Web Areas the user belongs to.\n *\n * To achieve this we're following a similar pattern to what Linkit takes.\n *\n * We create an autocomplete textfield that is connected to the second\n * \"web area only\" matcher and the dropdown toggles between showing the default\n * autocomplete or our custom one.\n */\nclass WebAreaLinkit extends ckeditor5_src_core__WEBPACK_IMPORTED_MODULE_1__.Plugin {\n\n  /**\n   * @inheritdoc\n   */\n  static get pluginName() {\n    return 'WebAreaLinkit';\n  }\n\n  init() {\n    this.locale = this.editor.locale;\n    this.options = {...this.editor.config.get('linkit'), autocompleteUrl: \"/linkit/autocomplete/web_area_content\" };\n    // TRICKY: Work-around until the CKEditor team offers a better solution:\n    // force the ContextualBalloon to get instantiated early thanks to\n    // DrupalImage not yet being optimized like\n    // https://github.com/ckeditor/ckeditor5/commit/c276c45a934e4ad7c2a8ccd0bd9a01f6442d4cd3#diff-1753317a1a0b947ca8b66581b533616a5309f6d4236a527b9d21ba03e13a78d8.\n    if (this.editor.plugins.get('LinkUI')._createViews) {\n      this.editor.plugins.get('LinkUI')._createViews();\n    }\n\n    // this._addLinkitProfileSelector();\n    this._extendLinkUITemplate();\n    this._handleExtraFormFieldSubmit();\n    this._handleDataLoadingIntoExtraFormField();\n  }\n\n  _extendLinkUITemplate() {\n    const { editor } = this;\n\n    // Create a new dropdown element we'll use for switching linkit profiles.\n    const dropdownView = (0,ckeditor5_src_ui__WEBPACK_IMPORTED_MODULE_0__.createDropdown)( this.locale, ckeditor5_src_ui__WEBPACK_IMPORTED_MODULE_0__.DropdownButtonView );\n    const itemList = this._buildLinkitProfileList();\n    (0,ckeditor5_src_ui__WEBPACK_IMPORTED_MODULE_0__.addListToDropdown)(dropdownView, itemList);\n    dropdownView.buttonView.set( itemList.get(1).model );\n\n    dropdownView.extendTemplate( {\n      attributes: {\n        class: [\n          'ck-epa-web-area-linkit-dropdown'\n        ]\n      }\n    } );\n\n    // Create a new input field that we'll turn into a linkit autocomplete field.\n    const newUrlField = this._createUrlInput();\n\n    // Brought all this over from the Linkit plugin.\n    let wasAutocompleteAdded = false;\n\n    // Copy the same solution from LinkUI as pointed out on\n    // https://www.drupal.org/project/drupal/issues/3317769#comment-14985648 and\n    // https://git.drupalcode.org/project/drupal/-/merge_requests/2909/diffs?commit_id=cc2cece3be1a9513b02a53d8a6862a6841ef4d5a.\n    editor.plugins\n      .get('ContextualBalloon')\n      .on('set:visibleView', (evt, propertyName, newValue, oldValue) => {\n        const linkFormView = editor.plugins.get('LinkUI').formView;\n        if (newValue === oldValue || newValue !== linkFormView) {\n          return;\n        }\n\n        // Manual check to see if the dropdownView is already in the collection\n        let dropdownExists = false;\n        let urlFieldExists = false;\n\n        for (let i = 0; i < linkFormView.children.length; i++) {\n          if (linkFormView.children.get(i) === dropdownView) {\n            dropdownExists = true;\n          }\n          if (linkFormView.children.get(i) === newUrlField) {\n            urlFieldExists = true;\n          }\n        }\n\n        if (!dropdownExists) {\n          linkFormView.children.add(dropdownView, 0);\n        }\n        if (!urlFieldExists) {\n          linkFormView.children.add(newUrlField, 2);\n        }\n\n        linkFormView.on('render', () => {\n          if (!linkFormView._focusables.has(dropdownView)) {\n            linkFormView._focusables.add(dropdownView, 1);\n            linkFormView.focusTracker.add(dropdownView.element);\n          }\n          if (!linkFormView._focusables.has(newUrlField)) {\n            linkFormView._focusables.add(newUrlField, 1);\n            linkFormView.focusTracker.add(newUrlField.element);\n          }\n        });\n\n      /**\n       * Used to know if a selection was made from the autocomplete results.\n       *\n       * @type {boolean}\n       */\n      let selected;\n\n      // Stolen directly from linkit.\n      (0,_contrib_linkit_js_ckeditor5_plugins_linkit_src_autocomplete__WEBPACK_IMPORTED_MODULE_3__[\"default\"])(\n        newUrlField.fieldView.element,\n        {\n          ...this.options,\n          selectHandler: (event, { item }) => {\n            if (!item.path) {\n              throw 'Missing path param.' + JSON.stringify(item);\n            }\n\n            if (item.entity_type_id || item.entity_uuid || item.substitution_id) {\n              if (!item.entity_type_id || !item.entity_uuid || !item.substitution_id) {\n                throw 'Missing path param.' + JSON.stringify(item);\n              }\n\n              this.set('entityType', item.entity_type_id);\n              this.set('entityUuid', item.entity_uuid);\n              this.set('entitySubstitution', item.substitution_id);\n            }\n            else {\n              this.set('entityType', null);\n              this.set('entityUuid', null);\n              this.set('entitySubstitution', null);\n            }\n\n            event.target.value = item.path;\n            // Also set the value of the other link field.\n            linkFormView.urlInputView.fieldView.element.value = item.path;\n            selected = true;\n            return false;\n          },\n          openHandler: (event) => {\n            selected = false;\n          },\n          closeHandler: (event) => {\n            if (!selected) {\n              this.set('entityType', null);\n              this.set('entityUuid', null);\n              this.set('entitySubstitution', null);\n            }\n            selected = false;\n          },\n        },\n      );\n\n      wasAutocompleteAdded = true;\n      newUrlField.fieldView.template.attributes.class.push('form-linkit-autocomplete');\n      // Initially hide our new field.\n      newUrlField.element.style.display = 'none';\n\n      // Listen for changes to the dropdown.\n      // Show/hide one of the URL fields based on chosen element.\n      this.listenTo( dropdownView, 'execute', (evt) => {\n        // evt.source is everything that's in our model and additional items.\n        if (evt.source.linkitAll === true) {\n          // Show the \"No filter: all WebCMS content\".\n          linkFormView.urlInputView.element.style.display = 'block';\n          newUrlField.element.style.display = 'none';\n        }\n        else {\n          // Show the \"Your internal links\".\n          linkFormView.urlInputView.element.style.display = 'none';\n          newUrlField.element.style.display = 'block';\n        }\n\n        // Set the shown option as the dropdown's label.\n        dropdownView.buttonView.label = evt.source.label;\n      } );\n    });\n  }\n\n  /**\n   * Creates a dropdown element that will let the user choose the linkit profile the autocomplete\n   * will run agianst.\n   * @private\n   */\n  _buildLinkitProfileList() {\n    const items = new ckeditor5_src_utils__WEBPACK_IMPORTED_MODULE_2__.Collection();\n\n    items.add( {\n      type: 'button',\n      model: new ckeditor5_src_ui__WEBPACK_IMPORTED_MODULE_0__.Model( {\n        withText: true,\n        label: this.locale.t('Your internal links'),\n        tooltip: this.locale.t('Search within your web areas only.'),\n        linkitAll: false,\n      } )\n    } );\n\n    items.add( {\n      type: 'button',\n      model: new ckeditor5_src_ui__WEBPACK_IMPORTED_MODULE_0__.Model( {\n        withText: true,\n        label: this.locale.t('No filter: all WebCMS content'),\n        tooltip: this.locale.t('Search within your web areas and www.epa.gov.'),\n        linkitAll: true,\n      } )\n    } );\n\n    return items;\n\n  }\n\n  /**\n   * Creates a labeled input view.\n   *\n   * @private\n   * @returns {module:core/ui/labeledfield/labeledfieldview~LabeledFieldView} Labeled field view instance.\n   */\n  _createUrlInput() {\n    const t = this.locale.t;\n    const labeledInput = new ckeditor5_src_ui__WEBPACK_IMPORTED_MODULE_0__.LabeledFieldView( this.locale, ckeditor5_src_ui__WEBPACK_IMPORTED_MODULE_0__.createLabeledInputText );\n\n    labeledInput.label = t( 'CARSON - Link URL' );\n\n    return labeledInput;\n  }\n\n  _handleExtraFormFieldSubmit() {\n    const editor = this.editor;\n    const linkFormView = editor.plugins.get('LinkUI').formView;\n    const linkCommand = editor.commands.get('link');\n\n    this.listenTo(linkFormView, 'submit', () => {\n      const values = {\n        'data-entity-type': this.entityType,\n        'data-entity-uuid': this.entityUuid,\n        'data-entity-substitution': this.entitySubstitution,\n      };\n      // Stop the execution of the link command caused by closing the form.\n      // Inject the extra attribute value. The highest priority listener here\n      // injects the argument (here below 👇).\n      // - The high priority listener in\n      //   _addExtraAttributeOnLinkCommandExecute() gets that argument and sets\n      //   the extra attribute.\n      // - The normal (default) priority listener in ckeditor5-link sets\n      //   (creates) the actual link.\n      linkCommand.once('execute', (evt, args) => {\n        if (args.length < 3) {\n          args.push(values);\n        } else if (args.length === 3) {\n          Object.assign(args[2], values);\n        } else {\n          throw Error('The link command has more than 3 arguments.')\n        }\n      }, { priority: 'highest' });\n    }, { priority: 'high' });\n  }\n\n  _handleDataLoadingIntoExtraFormField() {\n    const editor = this.editor;\n    const linkCommand = editor.commands.get('link');\n\n    this.bind('entityType').to(linkCommand, 'data-entity-type');\n    this.bind('entityUuid').to(linkCommand, 'data-entity-uuid');\n    this.bind('entitySubstitution').to(linkCommand, 'data-entity-substitution');\n  }\n}\n\n/* harmony default export */ __webpack_exports__[\"default\"] = ({\n  WebAreaLinkit\n});\n\n\n//# sourceURL=webpack://CKEditor5.webAreaLinkit/./js/ckeditor5_plugins/webAreaLinkit/src/index.js?")},"ckeditor5/src/core.js":function(module,__unused_webpack_exports,__webpack_require__){eval('module.exports = (__webpack_require__(/*! dll-reference CKEditor5.dll */ "dll-reference CKEditor5.dll"))("./src/core.js");\n\n//# sourceURL=webpack://CKEditor5.webAreaLinkit/delegated_./core.js_from_dll-reference_CKEditor5.dll?')},"ckeditor5/src/ui.js":function(module,__unused_webpack_exports,__webpack_require__){eval('module.exports = (__webpack_require__(/*! dll-reference CKEditor5.dll */ "dll-reference CKEditor5.dll"))("./src/ui.js");\n\n//# sourceURL=webpack://CKEditor5.webAreaLinkit/delegated_./ui.js_from_dll-reference_CKEditor5.dll?')},"ckeditor5/src/utils.js":function(module,__unused_webpack_exports,__webpack_require__){eval('module.exports = (__webpack_require__(/*! dll-reference CKEditor5.dll */ "dll-reference CKEditor5.dll"))("./src/utils.js");\n\n//# sourceURL=webpack://CKEditor5.webAreaLinkit/delegated_./utils.js_from_dll-reference_CKEditor5.dll?')},"dll-reference CKEditor5.dll":function(e){"use strict";e.exports=CKEditor5.dll}},__webpack_module_cache__={};function __webpack_require__(e){var n=__webpack_module_cache__[e];if(void 0!==n)return n.exports;var t=__webpack_module_cache__[e]={exports:{}};return __webpack_modules__[e](t,t.exports,__webpack_require__),t.exports}__webpack_require__.d=function(e,n){for(var t in n)__webpack_require__.o(n,t)&&!__webpack_require__.o(e,t)&&Object.defineProperty(e,t,{enumerable:!0,get:n[t]})},__webpack_require__.o=function(e,n){return Object.prototype.hasOwnProperty.call(e,n)},__webpack_require__.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})};var __webpack_exports__=__webpack_require__("./js/ckeditor5_plugins/webAreaLinkit/src/index.js");return __webpack_exports__=__webpack_exports__.default,__webpack_exports__}()}));