/* eslint-disable */

import {registry} from '#/main/app/plugins/registry'


registry.add('SidptBinderBundle', {
  resources: {
    'sidpt_document': () => { return import(/* webpackChunkName: "plugin-sidpt-resource-clarodoc" */ '~/sidpt/binder-bundle/plugin/binder/resources/clarodoc') },
    'sidpt_binder': () => { return import(/* webpackChunkName: "plugin-sidpt-resource-binder" */ '~/sidpt/binder-bundle/plugin/binder/resources/binder') }
  }
})
