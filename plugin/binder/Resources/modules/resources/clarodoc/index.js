
import {DocumentResource} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/clarodoc/containers/resource'
import {DocumentMenu} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/clarodoc/containers/menu'

import {reducer} from '~/sidpt/ipip-binder-bundle/plugin/binder/resources/clarodoc/store'

/**
 * HomeTool application.
 */
export default {
  component: DocumentResource,
  menu: DocumentMenu,
  store: reducer,
  styles: ['sidpt-ipip-binder-plugin-binder-document-resource']
}
