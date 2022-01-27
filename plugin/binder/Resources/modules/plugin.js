/* eslint-disable */

import {registry} from '#/main/app/plugins/registry'


registry.add('SidptBinderBundle', {
  resources: {
    'sidpt_document': () => { return import(/* webpackChunkName: "plugin-sidpt-resource-clarodoc" */ '~/sidpt/ipip-binder-bundle/plugin/binder/resources/clarodoc') },
    'sidpt_binder': () => { return import(/* webpackChunkName: "plugin-sidpt-resource-binder" */ '~/sidpt/ipip-binder-bundle/plugin/binder/resources/binder') }
  },
  widgets:{
    'resources_search': () => { return import(/* webpackChunkName: "plugin-sidpt-widget-resources-search" */ '~/sidpt/ipip-binder-bundle/plugin/binder/widgets/resources_search') }
  }
})
