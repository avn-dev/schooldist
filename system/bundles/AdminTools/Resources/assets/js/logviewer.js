/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/vue-loader-v16/dist/exportHelper.js":
/*!**********************************************************!*\
  !*** ./node_modules/vue-loader-v16/dist/exportHelper.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, exports) => {

eval("{\nObject.defineProperty(exports, \"__esModule\", ({ value: true }));\n// runtime helper for setting properties on components\n// in a tree-shakable way\nexports[\"default\"] = (sfc, props) => {\n    const target = sfc.__vccOpts || sfc;\n    for (const [key, val] of props) {\n        target[key] = val;\n    }\n    return target;\n};\n\n\n//# sourceURL=webpack://fidelo-framework/./node_modules/vue-loader-v16/dist/exportHelper.js?\n}");

/***/ }),

/***/ "./node_modules/vue-loader-v16/dist/index.js??ruleSet[1].rules[6].use[0]!./system/bundles/AdminTools/Resources/js/components/LogViewer.vue?vue&type=script&lang=js":
/*!*************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader-v16/dist/index.js??ruleSet[1].rules[6].use[0]!./system/bundles/AdminTools/Resources/js/components/LogViewer.vue?vue&type=script&lang=js ***!
  \*************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _LogViewerRow_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LogViewerRow.vue */ \"./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue\");\n\n\n\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({\n\tcomponents: { LogViewerRow: _LogViewerRow_vue__WEBPACK_IMPORTED_MODULE_0__[\"default\"] },\n\tprops: { fileOptions: { type: Object, required: true }},\n\tdata() {\n\t\treturn {\n\t\t\tloading: false,\n\t\t\tfile: null,\n\t\t\tlines: [],\n\t\t\t// levels: Object.keys(LEVEL_COLORS),\n\t\t\tfrom: null,\n\t\t\tuntil: null,\n\t\t\t// level: null\n\t\t}\n\t},\n\tmethods: {\n\t\tload(limit) {\n\t\t\tthis.loading = true\n\t\t\tif (!limit) {\n\t\t\t\tthis.lines = []\n\t\t\t}\n\n\t\t\tconst body = {\n\t\t\t\tfile: this.file,\n\t\t\t\toffset: this.lines.length,\n\t\t\t\tlimit: limit ?? 0,\n\t\t\t\tfrom: this.from,\n\t\t\t\tuntil: this.until,\n\t\t\t\t// level: this.level\n\t\t\t}\n\n\t\t\tfetch('/admin/tools/log-viewer/load-log', {\n\t\t\t\tmethod: 'POST',\n\t\t\t\theaders: { 'Content-Type': 'application/json' },\n\t\t\t\tbody: JSON.stringify(body)\n\t\t\t}).then(async resp => {\n\t\t\t\tconst json = await resp.json()\n\t\t\t\tthis.lines = this.lines.concat(json.lines)\n\t\t\t}).finally(() => {\n\t\t\t\tthis.loading = false\n\t\t\t})\n\t\t}\n\t}\n});\n\n\n//# sourceURL=webpack://fidelo-framework/./system/bundles/AdminTools/Resources/js/components/LogViewer.vue?./node_modules/vue-loader-v16/dist/index.js??ruleSet%5B1%5D.rules%5B6%5D.use%5B0%5D\n}");

/***/ }),

/***/ "./node_modules/vue-loader-v16/dist/index.js??ruleSet[1].rules[6].use[0]!./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue?vue&type=script&lang=js":
/*!****************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader-v16/dist/index.js??ruleSet[1].rules[6].use[0]!./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue?vue&type=script&lang=js ***!
  \****************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var vue3__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue3 */ \"./node_modules/vue3/dist/vue.runtime.esm-bundler.js\");\n\n\n\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({\n\tprops: { line: { type: Object, required: true } },\n\tdata() {\n\t\tconst levelColors = (0,vue3__WEBPACK_IMPORTED_MODULE_0__.inject)('level_colors')\n\t\treturn {\n\t\t\tcontext: null,\n\t\t\tshowDetails: false,\n\t\t\tlevelColor: levelColors[this.line.level]\n\t\t}\n\t},\n\tcomputed: {\n\t\tfaCaret() {\n\t\t\treturn 'fa-caret-' + (!this.showDetails ? 'down' : 'up')\n\t\t}\n\t},\n\tmethods: {\n\t\ttoggleDetails() {\n\t\t\tthis.showDetails = !this.showDetails\n\t\t},\n\t\tformatContext() {\n\t\t\tthis.context = JSON.stringify(JSON.parse(this.line.context), null, 4)\n\t\t}\n\t}\n});\n\n\n//# sourceURL=webpack://fidelo-framework/./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue?./node_modules/vue-loader-v16/dist/index.js??ruleSet%5B1%5D.rules%5B6%5D.use%5B0%5D\n}");

/***/ }),

/***/ "./node_modules/vue-loader-v16/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader-v16/dist/index.js??ruleSet[1].rules[6].use[0]!./system/bundles/AdminTools/Resources/js/components/LogViewer.vue?vue&type=template&id=40a1f1d4":
/*!*********************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader-v16/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader-v16/dist/index.js??ruleSet[1].rules[6].use[0]!./system/bundles/AdminTools/Resources/js/components/LogViewer.vue?vue&type=template&id=40a1f1d4 ***!
  \*********************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   render: () => (/* binding */ render)\n/* harmony export */ });\n/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ \"./node_modules/vue3/dist/vue.runtime.esm-bundler.js\");\n\n\nconst _hoisted_1 = { class: \"box-body log-viewer\" }\nconst _hoisted_2 = { class: \"input-group\" }\nconst _hoisted_3 = [\"value\"]\nconst _hoisted_4 = { class: \"input-group-btn\" }\nconst _hoisted_5 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"i\", { class: \"fa fa-refresh\" }, null, -1 /* HOISTED */)\nconst _hoisted_6 = [\n  _hoisted_5\n]\nconst _hoisted_7 = { class: \"table-responsive\" }\nconst _hoisted_8 = { class: \"table table-striped table-bordered table-hover\" }\nconst _hoisted_9 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"tr\", null, [\n  /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"th\", { style: {\"width\":\"250px\"} }, \" Date \"),\n  /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"th\", { style: {\"width\":\"100px\"} }, \" Channel \"),\n  /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"th\", { style: {\"width\":\"75px\"} }, \" Level \"),\n  /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"th\", { style: {\"width\":\"auto\"} }, \" Message \"),\n  /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"th\", {\n    title: \"Context\",\n    style: {\"width\":\"50px\"}\n  }, \" Ctx \")\n], -1 /* HOISTED */)\nconst _hoisted_10 = { style: {\"display\":\"flex\"} }\nconst _hoisted_11 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"td\", null, null, -1 /* HOISTED */)\nconst _hoisted_12 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"td\", null, [\n  /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(\"<select v-model=\\\"level\\\" class=\\\"form-control input-sm\\\">\\n\\t\\t\\t\\t\\t\\t\\t\\t<option></option>\\n\\t\\t\\t\\t\\t\\t\\t\\t<option v-for=\\\"level in levels\\\" :value=\\\"level\\\">{{ level }}</option>\\n\\t\\t\\t\\t\\t\\t\\t</select>\")\n], -1 /* HOISTED */)\nconst _hoisted_13 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"td\", null, null, -1 /* HOISTED */)\nconst _hoisted_14 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"td\", null, null, -1 /* HOISTED */)\nconst _hoisted_15 = { key: 0 }\nconst _hoisted_16 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"td\", {\n  colspan: \"5\",\n  class: \"text-center\"\n}, \" No data \", -1 /* HOISTED */)\nconst _hoisted_17 = [\n  _hoisted_16\n]\nconst _hoisted_18 = { key: 0 }\nconst _hoisted_19 = {\n  colspan: \"5\",\n  class: \"text-center\"\n}\nconst _hoisted_20 = {\n  key: 0,\n  class: \"overlay\"\n}\nconst _hoisted_21 = /*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"i\", { class: \"fa fa-refresh fa-spin\" }, null, -1 /* HOISTED */)\nconst _hoisted_22 = [\n  _hoisted_21\n]\n\nfunction render(_ctx, _cache, $props, $setup, $data, $options) {\n  const _component_log_viewer_row = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)(\"log-viewer-row\")\n\n  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, [\n    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"div\", _hoisted_1, [\n      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"div\", _hoisted_2, [\n        (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"select\", {\n          \"onUpdate:modelValue\": _cache[0] || (_cache[0] = $event => (($data.file) = $event)),\n          class: \"form-control\",\n          onChange: _cache[1] || (_cache[1] = $event => ($options.load()))\n        }, [\n          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.fileOptions, ([value, label]) => {\n            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(\"option\", {\n              key: value,\n              value: value\n            }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(label), 9 /* TEXT, PROPS */, _hoisted_3))\n          }), 128 /* KEYED_FRAGMENT */))\n        ], 544 /* NEED_HYDRATION, NEED_PATCH */), [\n          [vue__WEBPACK_IMPORTED_MODULE_0__.vModelSelect, $data.file]\n        ]),\n        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"span\", _hoisted_4, [\n          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"button\", {\n            type: \"button\",\n            class: \"btn btn-default\",\n            onClick: _cache[2] || (_cache[2] = $event => ($options.load()))\n          }, [..._hoisted_6])\n        ])\n      ]),\n      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"div\", _hoisted_7, [\n        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"table\", _hoisted_8, [\n          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"thead\", null, [\n            _hoisted_9,\n            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"tr\", null, [\n              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"td\", _hoisted_10, [\n                (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"input\", {\n                  \"onUpdate:modelValue\": _cache[3] || (_cache[3] = $event => (($data.from) = $event)),\n                  type: \"date\",\n                  class: \"form-control input-sm\",\n                  onChange: _cache[4] || (_cache[4] = $event => ($options.load()))\n                }, null, 544 /* NEED_HYDRATION, NEED_PATCH */), [\n                  [vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $data.from]\n                ]),\n                (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"input\", {\n                  \"onUpdate:modelValue\": _cache[5] || (_cache[5] = $event => (($data.until) = $event)),\n                  type: \"date\",\n                  class: \"form-control input-sm\",\n                  onChange: _cache[6] || (_cache[6] = $event => ($options.load()))\n                }, null, 544 /* NEED_HYDRATION, NEED_PATCH */), [\n                  [vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $data.until]\n                ])\n              ]),\n              _hoisted_11,\n              _hoisted_12,\n              _hoisted_13,\n              _hoisted_14\n            ])\n          ]),\n          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"tbody\", null, [\n            (!$data.lines.length)\n              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(\"tr\", _hoisted_15, [..._hoisted_17]))\n              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(\"v-if\", true),\n            ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($data.lines, (line) => {\n              return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_log_viewer_row, {\n                key: line.key,\n                line: line\n              }, null, 8 /* PROPS */, [\"line\"]))\n            }), 128 /* KEYED_FRAGMENT */))\n          ]),\n          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"tfoot\", null, [\n            ($data.lines.length)\n              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(\"tr\", _hoisted_18, [\n                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"td\", _hoisted_19, [\n                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"button\", {\n                      class: \"btn btn-default\",\n                      onClick: _cache[7] || (_cache[7] = $event => ($options.load(100)))\n                    }, \" Load 100 more … \"),\n                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(\"  \"),\n                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"button\", {\n                      class: \"btn btn-default\",\n                      onClick: _cache[8] || (_cache[8] = $event => ($options.load(1000)))\n                    }, \" Load 1000 more … \")\n                  ])\n                ]))\n              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(\"v-if\", true)\n          ])\n        ])\n      ])\n    ]),\n    ($data.loading)\n      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(\"div\", _hoisted_20, [..._hoisted_22]))\n      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(\"v-if\", true)\n  ], 64 /* STABLE_FRAGMENT */))\n}\n\n//# sourceURL=webpack://fidelo-framework/./system/bundles/AdminTools/Resources/js/components/LogViewer.vue?./node_modules/vue-loader-v16/dist/templateLoader.js??ruleSet%5B1%5D.rules%5B2%5D!./node_modules/vue-loader-v16/dist/index.js??ruleSet%5B1%5D.rules%5B6%5D.use%5B0%5D\n}");

/***/ }),

/***/ "./node_modules/vue-loader-v16/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader-v16/dist/index.js??ruleSet[1].rules[6].use[0]!./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue?vue&type=template&id=94c60bd8":
/*!************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader-v16/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader-v16/dist/index.js??ruleSet[1].rules[6].use[0]!./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue?vue&type=template&id=94c60bd8 ***!
  \************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   render: () => (/* binding */ render)\n/* harmony export */ });\n/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ \"./node_modules/vue3/dist/vue.runtime.esm-bundler.js\");\n\n\nconst _hoisted_1 = { class: \"context-actions\" }\nconst _hoisted_2 = {\n  key: 0,\n  class: \"context-row\"\n}\nconst _hoisted_3 = { colspan: \"5\" }\n\nfunction render(_ctx, _cache, $props, $setup, $data, $options) {\n  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, [\n    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"tr\", null, [\n      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"td\", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.line.date), 1 /* TEXT */),\n      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"td\", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.line.logger), 1 /* TEXT */),\n      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"td\", {\n        class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)($data.levelColor)\n      }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.line.level), 3 /* TEXT, CLASS */),\n      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"td\", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.line.message), 1 /* TEXT */),\n      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"td\", _hoisted_1, [\n        ($props.line.context)\n          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(\"a\", {\n              key: 0,\n              class: \"btn btn-default btn-sm\",\n              onClick: _cache[0] || (_cache[0] = (...args) => ($options.toggleDetails && $options.toggleDetails(...args)))\n            }, [\n              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"i\", {\n                class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(['fa', $options.faCaret])\n              }, null, 2 /* CLASS */)\n            ]))\n          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(\"v-if\", true)\n      ])\n    ]),\n    ($data.showDetails)\n      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(\"tr\", _hoisted_2, [\n          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"td\", _hoisted_3, [\n            (!$data.context)\n              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(\"button\", {\n                  key: 0,\n                  type: \"button\",\n                  class: \"btn btn-info btn-xs pull-right\",\n                  onClick: _cache[1] || (_cache[1] = (...args) => ($options.formatContext && $options.formatContext(...args)))\n                }, \" Format \"))\n              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(\"v-if\", true),\n            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)(\"div\", {\n              class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)({'pre': $data.context})\n            }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.context || $props.line.context), 3 /* TEXT, CLASS */)\n          ])\n        ]))\n      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(\"v-if\", true)\n  ], 64 /* STABLE_FRAGMENT */))\n}\n\n//# sourceURL=webpack://fidelo-framework/./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue?./node_modules/vue-loader-v16/dist/templateLoader.js??ruleSet%5B1%5D.rules%5B2%5D!./node_modules/vue-loader-v16/dist/index.js??ruleSet%5B1%5D.rules%5B6%5D.use%5B0%5D\n}");

/***/ }),

/***/ "./node_modules/vue3/dist/vue.runtime.esm-bundler.js":
/*!***********************************************************!*\
  !*** ./node_modules/vue3/dist/vue.runtime.esm-bundler.js ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   compile: () => (/* binding */ compile)\n/* harmony export */ });\n/* harmony import */ var _vue_runtime_dom__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @vue/runtime-dom */ \"@vue/runtime-dom\");\n/* harmony import */ var _vue_runtime_dom__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_vue_runtime_dom__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony reexport (unknown) */ var __WEBPACK_REEXPORT_OBJECT__ = {};\n/* harmony reexport (unknown) */ for(const __WEBPACK_IMPORT_KEY__ in _vue_runtime_dom__WEBPACK_IMPORTED_MODULE_0__) if([\"default\",\"compile\"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) __WEBPACK_REEXPORT_OBJECT__[__WEBPACK_IMPORT_KEY__] = () => _vue_runtime_dom__WEBPACK_IMPORTED_MODULE_0__[__WEBPACK_IMPORT_KEY__]\n/* harmony reexport (unknown) */ __webpack_require__.d(__webpack_exports__, __WEBPACK_REEXPORT_OBJECT__);\n/**\n* vue v3.4.38\n* (c) 2018-present Yuxi (Evan) You and Vue contributors\n* @license MIT\n**/\n\n\n\nfunction initDev() {\n  {\n    (0,_vue_runtime_dom__WEBPACK_IMPORTED_MODULE_0__.initCustomFormatter)();\n  }\n}\n\nif (true) {\n  initDev();\n}\nconst compile = () => {\n  if (true) {\n    (0,_vue_runtime_dom__WEBPACK_IMPORTED_MODULE_0__.warn)(\n      `Runtime compilation is not supported in this build of Vue.` + (` Configure your bundler to alias \"vue\" to \"vue/dist/vue.esm-bundler.js\".` )\n    );\n  }\n};\n\n\n\n\n//# sourceURL=webpack://fidelo-framework/./node_modules/vue3/dist/vue.runtime.esm-bundler.js?\n}");

/***/ }),

/***/ "./system/bundles/AdminTools/Resources/js/components/LogViewer.vue":
/*!*************************************************************************!*\
  !*** ./system/bundles/AdminTools/Resources/js/components/LogViewer.vue ***!
  \*************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _LogViewer_vue_vue_type_template_id_40a1f1d4__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LogViewer.vue?vue&type=template&id=40a1f1d4 */ \"./system/bundles/AdminTools/Resources/js/components/LogViewer.vue?vue&type=template&id=40a1f1d4\");\n/* harmony import */ var _LogViewer_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LogViewer.vue?vue&type=script&lang=js */ \"./system/bundles/AdminTools/Resources/js/components/LogViewer.vue?vue&type=script&lang=js\");\n/* harmony import */ var _node_modules_vue_loader_v16_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../../../../node_modules/vue-loader-v16/dist/exportHelper.js */ \"./node_modules/vue-loader-v16/dist/exportHelper.js\");\n\n\n\n\n;\nconst __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_v16_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(_LogViewer_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__[\"default\"], [['render',_LogViewer_vue_vue_type_template_id_40a1f1d4__WEBPACK_IMPORTED_MODULE_0__.render],['__file',\"system/bundles/AdminTools/Resources/js/components/LogViewer.vue\"]])\n/* hot reload */\nif (false) // removed by dead control flow\n{}\n\n\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);\n\n//# sourceURL=webpack://fidelo-framework/./system/bundles/AdminTools/Resources/js/components/LogViewer.vue?\n}");

/***/ }),

/***/ "./system/bundles/AdminTools/Resources/js/components/LogViewer.vue?vue&type=script&lang=js":
/*!*************************************************************************************************!*\
  !*** ./system/bundles/AdminTools/Resources/js/components/LogViewer.vue?vue&type=script&lang=js ***!
  \*************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* reexport safe */ _node_modules_vue_loader_v16_dist_index_js_ruleSet_1_rules_6_use_0_LogViewer_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_v16_dist_index_js_ruleSet_1_rules_6_use_0_LogViewer_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/vue-loader-v16/dist/index.js??ruleSet[1].rules[6].use[0]!./LogViewer.vue?vue&type=script&lang=js */ \"./node_modules/vue-loader-v16/dist/index.js??ruleSet[1].rules[6].use[0]!./system/bundles/AdminTools/Resources/js/components/LogViewer.vue?vue&type=script&lang=js\");\n \n\n//# sourceURL=webpack://fidelo-framework/./system/bundles/AdminTools/Resources/js/components/LogViewer.vue?\n}");

/***/ }),

/***/ "./system/bundles/AdminTools/Resources/js/components/LogViewer.vue?vue&type=template&id=40a1f1d4":
/*!*******************************************************************************************************!*\
  !*** ./system/bundles/AdminTools/Resources/js/components/LogViewer.vue?vue&type=template&id=40a1f1d4 ***!
  \*******************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_v16_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_v16_dist_index_js_ruleSet_1_rules_6_use_0_LogViewer_vue_vue_type_template_id_40a1f1d4__WEBPACK_IMPORTED_MODULE_0__.render)\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_v16_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_v16_dist_index_js_ruleSet_1_rules_6_use_0_LogViewer_vue_vue_type_template_id_40a1f1d4__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/vue-loader-v16/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../../../node_modules/vue-loader-v16/dist/index.js??ruleSet[1].rules[6].use[0]!./LogViewer.vue?vue&type=template&id=40a1f1d4 */ \"./node_modules/vue-loader-v16/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader-v16/dist/index.js??ruleSet[1].rules[6].use[0]!./system/bundles/AdminTools/Resources/js/components/LogViewer.vue?vue&type=template&id=40a1f1d4\");\n\n\n//# sourceURL=webpack://fidelo-framework/./system/bundles/AdminTools/Resources/js/components/LogViewer.vue?\n}");

/***/ }),

/***/ "./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue":
/*!****************************************************************************!*\
  !*** ./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue ***!
  \****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _LogViewerRow_vue_vue_type_template_id_94c60bd8__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LogViewerRow.vue?vue&type=template&id=94c60bd8 */ \"./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue?vue&type=template&id=94c60bd8\");\n/* harmony import */ var _LogViewerRow_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LogViewerRow.vue?vue&type=script&lang=js */ \"./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue?vue&type=script&lang=js\");\n/* harmony import */ var _node_modules_vue_loader_v16_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../../../../node_modules/vue-loader-v16/dist/exportHelper.js */ \"./node_modules/vue-loader-v16/dist/exportHelper.js\");\n\n\n\n\n;\nconst __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_v16_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_2__[\"default\"])(_LogViewerRow_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__[\"default\"], [['render',_LogViewerRow_vue_vue_type_template_id_94c60bd8__WEBPACK_IMPORTED_MODULE_0__.render],['__file',\"system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue\"]])\n/* hot reload */\nif (false) // removed by dead control flow\n{}\n\n\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);\n\n//# sourceURL=webpack://fidelo-framework/./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue?\n}");

/***/ }),

/***/ "./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue?vue&type=script&lang=js":
/*!****************************************************************************************************!*\
  !*** ./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue?vue&type=script&lang=js ***!
  \****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* reexport safe */ _node_modules_vue_loader_v16_dist_index_js_ruleSet_1_rules_6_use_0_LogViewerRow_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__[\"default\"])\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_v16_dist_index_js_ruleSet_1_rules_6_use_0_LogViewerRow_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/vue-loader-v16/dist/index.js??ruleSet[1].rules[6].use[0]!./LogViewerRow.vue?vue&type=script&lang=js */ \"./node_modules/vue-loader-v16/dist/index.js??ruleSet[1].rules[6].use[0]!./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue?vue&type=script&lang=js\");\n \n\n//# sourceURL=webpack://fidelo-framework/./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue?\n}");

/***/ }),

/***/ "./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue?vue&type=template&id=94c60bd8":
/*!**********************************************************************************************************!*\
  !*** ./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue?vue&type=template&id=94c60bd8 ***!
  \**********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_v16_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_v16_dist_index_js_ruleSet_1_rules_6_use_0_LogViewerRow_vue_vue_type_template_id_94c60bd8__WEBPACK_IMPORTED_MODULE_0__.render)\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_v16_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_v16_dist_index_js_ruleSet_1_rules_6_use_0_LogViewerRow_vue_vue_type_template_id_94c60bd8__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/vue-loader-v16/dist/templateLoader.js??ruleSet[1].rules[2]!../../../../../../node_modules/vue-loader-v16/dist/index.js??ruleSet[1].rules[6].use[0]!./LogViewerRow.vue?vue&type=template&id=94c60bd8 */ \"./node_modules/vue-loader-v16/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader-v16/dist/index.js??ruleSet[1].rules[6].use[0]!./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue?vue&type=template&id=94c60bd8\");\n\n\n//# sourceURL=webpack://fidelo-framework/./system/bundles/AdminTools/Resources/js/components/LogViewerRow.vue?\n}");

/***/ }),

/***/ "./system/bundles/AdminTools/Resources/js/logviewer.js":
/*!*************************************************************!*\
  !*** ./system/bundles/AdminTools/Resources/js/logviewer.js ***!
  \*************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var vue3__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue3 */ \"./node_modules/vue3/dist/vue.runtime.esm-bundler.js\");\n/* harmony import */ var _components_LogViewer_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/LogViewer.vue */ \"./system/bundles/AdminTools/Resources/js/components/LogViewer.vue\");\n\n\n\nconst app = (0,vue3__WEBPACK_IMPORTED_MODULE_0__.createApp)(_components_LogViewer_vue__WEBPACK_IMPORTED_MODULE_1__[\"default\"], { fileOptions: window.__FILE_OPTIONS__ })\n\napp.provide('level_colors', {\n\t'DEBUG': 'text-info',\n\t'INFO': 'text-info',\n\t'NOTICE': 'text-info',\n\t'WARNING': 'text-warning',\n\t'ERROR': 'text-danger',\n\t'CRITICAL': 'text-danger',\n\t'ALERT': 'text-danger',\n\t'EMERGENCY': 'text-danger'\n})\n\napp.mount('#app')\n\n\n//# sourceURL=webpack://fidelo-framework/./system/bundles/AdminTools/Resources/js/logviewer.js?\n}");

/***/ }),

/***/ "@vue/runtime-dom":
/*!**********************!*\
  !*** external "Vue" ***!
  \**********************/
/***/ ((module) => {

module.exports = Vue;

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./system/bundles/AdminTools/Resources/js/logviewer.js");
/******/ 	
/******/ })()
;