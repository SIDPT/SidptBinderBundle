/* eslint-disable */

import {registry} from '#/main/app/plugins/registry'

// Note : we use clarodoc instead of document on the javascript side as resource type name, as document is reserved

registry.add('SidptBinderBundle', {
  resources: {
    'sidpt_document': () => { return import(/* webpackChunkName: "plugin-sidpt-clarodoc-resource" */ '#/plugin/binder/resources/clarodoc') }
  }
})
