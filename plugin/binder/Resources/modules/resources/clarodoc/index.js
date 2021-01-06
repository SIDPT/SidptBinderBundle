
import {DocumentResource} from '~/sidpt/claroline-binder-bundle/plugin/binder/resources/clarodoc/containers/resource'
import {DocumentMenu} from '~/sidpt/claroline-binder-bundle/plugin/binder/resources/clarodoc/containers/menu'

import {reducer} from '~/sidpt/claroline-binder-bundle/plugin/binder/resources/clarodoc/store'

/**
 * HomeTool application.
 */
export default {
  component: DocumentResource,
  menu: DocumentMenu,
  store: reducer,
  styles: ['claroline-distribution-plugin-binder-clarodoc-resource']
}
